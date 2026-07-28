<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Exceptions\CursoNoEncontradoException;
use SistemaAdmin\Mappers\CursoMapper;
use SistemaAdmin\Models\Curso;

/**
 * Lógica de negocio y acceso a datos para cursos (gestión y listados de página).
 */
class ServicioCursos extends BaseService
{
    private CursoMapper $cursoMapper;

    public function __construct(DatabaseInterface $database, ?CursoMapper $cursoMapper = null)
    {
        parent::__construct($database);
        $this->cursoMapper = $cursoMapper ?? new CursoMapper($database);
    }

    public function buscarPorId(int $id): ?Curso
    {
        $curso = $this->cursoMapper->findById($id);
        if ($curso === null) {
            throw new CursoNoEncontradoException($id);
        }

        return $curso;
    }

    public function obtenerTodos(): array
    {
        return $this->cursoMapper->findActive();
    }

    public function obtenerPorAnio(int $anio): array
    {
        return $this->cursoMapper->findByAnio($anio);
    }

    public function obtenerPorEspecialidad(int $especialidadId): array
    {
        return $this->cursoMapper->findByEspecialidad($especialidadId);
    }

    public function obtenerPorTurno(int $turnoId): array
    {
        return $this->cursoMapper->findByTurno($turnoId);
    }

    public function crear(Curso $curso): Curso
    {
        if ($curso->getAnio() >= 4 && $curso->getEspecialidadId() === null) {
            throw new \InvalidArgumentException('Los cursos de ciclo superior requieren una especialidad.');
        }
        if ($this->existeCurso($curso->getAnio(), $curso->getDivision(), null)) {
            throw new \InvalidArgumentException('Ya existe un curso activo con ese año y división.');
        }

        return $this->cursoMapper->save($curso);
    }

    /**
     * Valida POST del formulario "nuevo curso" y persiste (misma regla que la vista histórica).
     *
     * @param array<string, mixed> $post
     */
    public function crearDesdeDatosFormulario(array $post): Curso
    {
        $anio = isset($post['anio']) ? (int) $post['anio'] : 0;
        $division = isset($post['division']) ? trim((string) $post['division']) : '';
        $turnoId = !empty($post['turno_id']) ? (int) $post['turno_id'] : null;
        $especialidadId = null;

        if ($anio >= 4) {
            if (empty($post['especialidad_id'])) {
                throw new \InvalidArgumentException('Los cursos de ciclo superior requieren una especialidad.');
            }
            $especialidadId = (int) $post['especialidad_id'];
        }

        if ($anio < 1 || $anio > 7) {
            throw new \InvalidArgumentException('Seleccione un año válido.');
        }

        $divisionesValidas = ['1', '2', '3', '4', '5', '6'];
        if (!in_array($division, $divisionesValidas, true)) {
            throw new \InvalidArgumentException('Seleccione una división válida (1 a 6).');
        }

        if ($turnoId !== null && !$this->existeTurnoId($turnoId)) {
            throw new \InvalidArgumentException('El turno seleccionado no existe.');
        }

        if ($anio >= 4 && !$this->existeEspecialidadActivaId((int) $especialidadId)) {
            throw new \InvalidArgumentException('La especialidad seleccionada no existe o no está activa.');
        }

        $curso = new Curso($anio, $division, $especialidadId, $turnoId, true);

        return $this->crear($curso);
    }

    private function existeTurnoId(int $id): bool
    {
        $row = $this->database->fetch('SELECT id FROM turnos WHERE id = ? LIMIT 1', [$id]);

        return $row !== null && $row !== [];
    }

    private function existeEspecialidadActivaId(int $id): bool
    {
        $row = $this->database->fetch(
            'SELECT id FROM especialidades WHERE id = ? AND activa = 1 LIMIT 1',
            [$id]
        );

        return $row !== null && $row !== [];
    }

    public function actualizar(Curso $curso): Curso
    {
        if ($curso->getId() === null) {
            throw new \InvalidArgumentException('El curso no tiene ID para actualizar.');
        }
        $existente = $this->cursoMapper->findById($curso->getId());
        if ($existente === null) {
            throw new CursoNoEncontradoException($curso->getId());
        }
        if ($curso->getAnio() >= 4 && $curso->getEspecialidadId() === null) {
            throw new \InvalidArgumentException('Los cursos de ciclo superior requieren una especialidad.');
        }
        if ($this->existeCurso($curso->getAnio(), $curso->getDivision(), $curso->getId())) {
            throw new \InvalidArgumentException('Ya existe otro curso activo con ese año y división.');
        }

        $this->cursoMapper->update($curso);

        return $curso;
    }

    public function eliminar(int $id): bool
    {
        if ($this->cursoMapper->findById($id) === null) {
            throw new CursoNoEncontradoException($id);
        }

        return $this->cursoMapper->delete($id);
    }

    /**
     * Baja de curso desde administración: sin estudiantes activos, borra horarios/notas relacionadas y soft delete.
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function bajaCursoAdministracion(int $cursoId): array
    {
        if ($cursoId <= 0) {
            return ['success' => false, 'error' => 'Curso inválido'];
        }

        $row = $this->database->fetch(
            'SELECT COUNT(*) as total FROM estudiantes WHERE curso_id = ? AND activo = 1',
            [$cursoId]
        );
        $estudiantesCount = (int) ($row['total'] ?? 0);

        if ($estudiantesCount > 0) {
            return [
                'success' => false,
                'error' => "No se puede eliminar el curso porque tiene {$estudiantesCount} estudiante(s) asignado(s). Primero debe reasignar o eliminar los estudiantes.",
            ];
        }

        try {
            $this->database->transaction(function () use ($cursoId): void {
                $this->database->query('DELETE FROM horarios WHERE curso_id = ?', [$cursoId]);
                $this->database->query(
                    'DELETE n FROM notas n JOIN estudiantes e ON n.estudiante_id = e.id WHERE e.curso_id = ?',
                    [$cursoId]
                );
                $this->database->query('UPDATE cursos SET activo = 0 WHERE id = ?', [$cursoId]);
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al eliminar curso: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Curso eliminado correctamente'];
    }

    public function obtenerEstadisticas(): array
    {
        return $this->cursoMapper->getEstadisticas();
    }

    public function obtenerCicloBasico(): array
    {
        return $this->cursoMapper->getCicloBasico();
    }

    public function obtenerCicloSuperior(): array
    {
        return $this->cursoMapper->getCicloSuperior();
    }

    public function existeCurso(int $anio, string $division, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) as c FROM cursos WHERE anio = ? AND division = ? AND activo = 1';
        $params = [$anio, $division];
        if ($excluirId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excluirId;
        }
        $result = $this->database->fetch($sql, $params);

        return (int) ($result['c'] ?? 0) > 0;
    }

    /**
     * Listado agregado para courses.php (mismas columnas que la consulta original).
     *
     * @param list<int>|null $alcanceCids null = sin restricción de curso; [] = ningún curso; lista = IN (...)
     *
     * @return list<array<string, mixed>>
     */
    public function listarParaVistaGestion(?array $alcanceCids, string $anioFilter, string $divisionFilter, string $especialidadFilter): array
    {
        $whereConditions = ['c.activo = 1'];
        $params = [];

        if ($alcanceCids !== null) {
            if ($alcanceCids === []) {
                $whereConditions[] = '1 = 0';
            } else {
                $ph = implode(',', array_fill(0, count($alcanceCids), '?'));
                $whereConditions[] = "c.id IN ($ph)";
                $params = array_merge($params, $alcanceCids);
            }
        }

        if ($anioFilter !== '') {
            $whereConditions[] = 'c.anio = ?';
            $params[] = (int) $anioFilter;
        }

        if ($divisionFilter !== '') {
            $whereConditions[] = 'c.division = ?';
            $params[] = $divisionFilter;
        }

        if ($especialidadFilter !== '') {
            if ($especialidadFilter === 'sin_especialidad') {
                $whereConditions[] = 'c.especialidad_id IS NULL';
            } else {
                $whereConditions[] = 'c.especialidad_id = ?';
                $params[] = $especialidadFilter;
            }
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "
            SELECT c.*, t.nombre as turno, esp.nombre as especialidad,
                   COUNT(DISTINCT e.id) as cantidad_estudiantes,
                   CASE
                       WHEN c.anio <= 3 THEN 'inferior'
                       ELSE 'superior'
                   END as grado
            FROM cursos c
            LEFT JOIN turnos t ON c.turno_id = t.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY c.anio, c.division
        ";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarTurnosOrdenados(): array
    {
        return $this->database->fetchAll('SELECT * FROM turnos ORDER BY id');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarEspecialidadesActivasOrdenadas(): array
    {
        return $this->database->fetchAll(
            'SELECT * FROM especialidades WHERE activa = 1 ORDER BY nombre'
        );
    }

    public function listarAniosDisponibles(): array
    {
        $rows = $this->database->fetchAll(
            'SELECT DISTINCT anio FROM cursos WHERE activo = 1 ORDER BY anio'
        );
        return array_map(fn($row) => (int)$row['anio'], $rows);
    }

    public function listarDivisionesPorAnio(int $anio): array
    {
        $rows = $this->database->fetchAll(
            'SELECT DISTINCT division FROM cursos WHERE anio = ? AND activo = 1 ORDER BY division',
            [$anio]
        );
        return array_map(fn($row) => (string)$row['division'], $rows);
    }

    public function buscarCursoActivoPorAnioDivision(int $anio, string $division): ?array
    {
        return $this->database->fetch(
            'SELECT * FROM cursos WHERE anio = ? AND division = ? AND activo = 1 LIMIT 1',
            [$anio, $division]
        ) ?: null;
    }
}
