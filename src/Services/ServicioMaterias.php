<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Mappers\MateriaMapper;
use SistemaAdmin\Models\Materia;

/**
 * Catálogo (IServicioMaterias + MateriaMapper) y reglas de administración en pantalla (subjects.php).
 */
class ServicioMaterias extends BaseService
{
    public const ANIO_MIN_ESPECIALIDAD = 4;

    private MateriaMapper $materiaMapper;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->materiaMapper = new MateriaMapper($database);
    }

    public function buscarPorId(int $id): ?Materia
    {
        return $this->materiaMapper->findById($id);
    }

    public function buscarPorCodigo(string $codigo): ?Materia
    {
        return $this->materiaMapper->findByCodigo($codigo);
    }

    /**
     * @return array<int, Materia>
     */
    public function obtenerTodas(): array
    {
        return $this->materiaMapper->findActive();
    }

    /**
     * @return array<int, Materia>
     */
    public function obtenerPorEspecialidad(int $especialidadId): array
    {
        return $this->materiaMapper->findByEspecialidad($especialidadId);
    }

    /**
     * @return array<int, Materia>
     */
    public function obtenerComunes(): array
    {
        return $this->materiaMapper->findComunes();
    }

    /**
     * @return array<int, Materia>
     */
    public function buscarPorNombre(string $termino): array
    {
        return $this->materiaMapper->findByNombre($termino);
    }

    public function crear(Materia $materia): Materia
    {
        return $this->materiaMapper->save($materia);
    }

    public function actualizar(Materia $materia): Materia
    {
        if ($materia->getId() === null) {
            throw new \InvalidArgumentException('La materia no tiene ID');
        }
        if (!$this->materiaMapper->update($materia)) {
            throw new \InvalidArgumentException('No se pudo actualizar la materia');
        }

        return $materia;
    }

    public function eliminar(int $id): bool
    {
        return $this->materiaMapper->delete($id);
    }

    /**
     * @return array<string, int|float>
     */
    public function obtenerEstadisticas(): array
    {
        return $this->materiaMapper->getEstadisticas();
    }

    public function codigoExiste(string $codigo, ?int $excluirId = null): bool
    {
        return $this->materiaMapper->codigoExiste($codigo, $excluirId);
    }

    /**
     * @return array<int, Materia>
     */
    public function obtenerPorCargaHoraria(int $horasMinimas, int $horasMaximas): array
    {
        return $this->materiaMapper->findByCargaHoraria($horasMinimas, $horasMaximas);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarEspecialidadesActivas(): array
    {
        return $this->database->fetchAll(
            'SELECT * FROM especialidades WHERE activa = 1 ORDER BY nombre',
            []
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarCursosActivosParaFormularioAlta(): array
    {
        $sql = <<<'SQL'
            SELECT c.id, c.anio, c.division, c.especialidad_id, e.nombre AS especialidad, t.nombre AS turno
            FROM cursos c
            LEFT JOIN especialidades e ON c.especialidad_id = e.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            WHERE c.activo = 1
            ORDER BY c.anio, c.division
            SQL;

        return $this->database->fetchAll($sql, []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarMateriasParaAdministracion(string $filtroEspecialidad): array
    {
        $where = ['m.activa = 1'];
        $params = [];

        if ($filtroEspecialidad !== '') {
            if ($filtroEspecialidad === 'sin_especialidad') {
                $where[] = 'm.especialidad_id IS NULL';
            } else {
                $where[] = 'm.especialidad_id = ?';
                $params[] = $filtroEspecialidad;
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT m.*, e.nombre AS especialidad,
                   GROUP_CONCAT(CONCAT(c.anio, '°', c.division) ORDER BY c.anio, c.division SEPARATOR ', ') AS cursos_asignados,
                   COUNT(c.id) AS total_cursos
            FROM materias m
            LEFT JOIN especialidades e ON e.id = m.especialidad_id
            LEFT JOIN materia_curso mc ON m.id = mc.materia_id AND mc.activo = 1
            LEFT JOIN cursos c ON mc.curso_id = c.id AND c.activo = 1
            WHERE {$whereClause}
            GROUP BY m.id
            ORDER BY m.nombre
        ";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerMateriaParaGestion(int $materiaId): ?array
    {
        if ($materiaId < 1) {
            return null;
        }

        $row = $this->database->fetch(<<<'SQL'
            SELECT m.*, e.nombre AS especialidad
            FROM materias m
            LEFT JOIN especialidades e ON e.id = m.especialidad_id
            WHERE m.id = ? AND m.activa = 1
            SQL,
            [$materiaId]
        );

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarCursosDisponiblesParaGestion(int $materiaId, ?int $especialidadIdMateria): array
    {
        $where = ['c.activo = 1'];
        $params = [];

        if ($especialidadIdMateria !== null && $especialidadIdMateria > 0) {
            $where[] = '((c.especialidad_id = ? AND c.anio >= ?) OR c.id IN (
                SELECT curso_id FROM materia_curso WHERE materia_id = ?
            ))';
            $params[] = $especialidadIdMateria;
            $params[] = self::ANIO_MIN_ESPECIALIDAD;
            $params[] = $materiaId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT c.*, esp.nombre AS especialidad, t.nombre AS turno
            FROM cursos c
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            WHERE {$whereClause}
            ORDER BY c.anio, c.division
        ";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * @return list<int>
     */
    public function listarIdsCursosAsignadosAMateria(int $materiaId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT mc.curso_id 
             FROM materia_curso mc
             INNER JOIN cursos c ON mc.curso_id = c.id AND c.activo = 1
             WHERE mc.materia_id = ? AND mc.activo = 1',
            [$materiaId]
        );

        return array_map(static fn (array $r): int => (int) $r['curso_id'], $rows);
    }

    /**
     * @param list<int> $cursosIds
     */
    public function crearMateriaConCursos(string $nombre, ?int $especialidadId, array $cursosIds, int $esTaller): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre de la materia es obligatorio');
        }

        $cursosIds = array_values(array_unique(array_filter(array_map('intval', $cursosIds), static fn (int $id): bool => $id > 0)));
        if ($cursosIds === []) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un curso');
        }

        $primerCurso = $this->database->fetch('SELECT anio FROM cursos WHERE id = ?', [$cursosIds[0]]);
        $anioMateria = (int) ($primerCurso['anio'] ?? 1);

        if ($especialidadId !== null && $especialidadId > 0) {
            $this->assertCursosCoincidenEspecialidadYAnio(
                $cursosIds,
                $especialidadId,
                'Para una materia con especialidad, solo puede asignar cursos de esa especialidad de 4° año en adelante.'
            );
        }

        $esTaller = $esTaller !== 0 ? 1 : 0;

        $this->database->transaction(function () use ($nombre, $especialidadId, $anioMateria, $esTaller, $cursosIds): void {
            $this->database->query(
                'INSERT INTO materias (nombre, especialidad_id, anio_materia, es_taller, activa) VALUES (?, ?, ?, ?, 1)',
                [$nombre, $especialidadId, $anioMateria, $esTaller]
            );
            $materiaId = (int) $this->database->lastInsertId();
            $sqlCurso = 'INSERT INTO materia_curso (materia_id, curso_id) VALUES (?, ?)';
            foreach ($cursosIds as $idc) {
                $this->database->query($sqlCurso, [$materiaId, $idc]);
            }
        });
    }

    public function desactivarMateria(int $materiaId): void
    {
        if ($materiaId < 1) {
            throw new \InvalidArgumentException('Materia inválida');
        }

        $this->database->query('UPDATE materias SET activa = 0 WHERE id = ?', [$materiaId]);
    }

    /**
     * @param list<int> $cursosSeleccionados
     */
    public function sincronizarCursosDeMateria(int $materiaId, array $cursosSeleccionados): void
    {
        if ($materiaId < 1) {
            throw new \InvalidArgumentException('Materia inválida');
        }

        $cursosSeleccionados = array_values(array_unique(array_filter(
            array_map('intval', $cursosSeleccionados),
            static fn (int $id): bool => $id > 0
        )));

        $materiaRow = $this->database->fetch(
            'SELECT especialidad_id FROM materias WHERE id = ? AND activa = 1',
            [$materiaId]
        );
        if ($materiaRow === null) {
            throw new \InvalidArgumentException('Materia no válida');
        }

        $matEsp = !empty($materiaRow['especialidad_id']) ? (int) $materiaRow['especialidad_id'] : null;
        if ($matEsp !== null && $matEsp > 0 && $cursosSeleccionados !== []) {
            $this->assertCursosCoincidenEspecialidadYAnio(
                $cursosSeleccionados,
                $matEsp,
                'Solo puede asignar cursos de la especialidad de la materia, de 4° año en adelante.'
            );
        }

        $this->database->transaction(function () use ($materiaId, $cursosSeleccionados): void {
            $this->database->query('DELETE FROM materia_curso WHERE materia_id = ?', [$materiaId]);
            if ($cursosSeleccionados !== []) {
                $sql = 'INSERT INTO materia_curso (materia_id, curso_id) VALUES (?, ?)';
                foreach ($cursosSeleccionados as $cursoId) {
                    $this->database->query($sql, [$materiaId, $cursoId]);
                }
            }
        });
    }

    /**
     * @param list<int> $cursosIds
     */
    private function assertCursosCoincidenEspecialidadYAnio(array $cursosIds, int $especialidadId, string $mensajeError): void
    {
        $placeholders = implode(',', array_fill(0, count($cursosIds), '?'));
        $params = array_merge($cursosIds, [$especialidadId, self::ANIO_MIN_ESPECIALIDAD]);
        $validos = $this->database->fetchAll(
            "SELECT id FROM cursos WHERE id IN ($placeholders) AND activo = 1 AND especialidad_id = ? AND anio >= ?",
            $params
        );
        if (count($validos) !== count($cursosIds)) {
            throw new \InvalidArgumentException($mensajeError);
        }
    }
}
