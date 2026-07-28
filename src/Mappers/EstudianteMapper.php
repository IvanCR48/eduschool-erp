<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Mappers\BaseMapper;
use DateTime;

/**
 * Implementación concreta del EstudianteMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * sin modificar la estructura de tablas actual.
 */
class EstudianteMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Estudiante
    {
        $sql = "SELECT * FROM estudiantes WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToEstudiante($row);
    }

    public function findByDni(string $dni): ?Estudiante
    {
        $sql = "SELECT * FROM estudiantes WHERE dni = ?";
        $row = $this->database->fetch($sql, [$dni]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToEstudiante($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM estudiantes ORDER BY apellido, nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM estudiantes WHERE activo = 1 ORDER BY apellido, nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    public function findBy(array $criteria): array
    {
        $whereConditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT * FROM estudiantes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY apellido, nombre";
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    public function save(Estudiante $estudiante): Estudiante
    {
        $sql = "INSERT INTO estudiantes (
                    dni, dni_responsable, apellido, nombre, fecha_nacimiento,
                    grupo_sanguineo, obra_social, domicilio, telefono, email,
                    curso_id, grupo_taller, activo, fecha_ingreso
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $estudiante->getDni(),
            $estudiante->getDniResponsable(),
            $estudiante->getApellido(),
            $estudiante->getNombre(),
            $estudiante->getFechaNacimiento()?->format('Y-m-d'),
            $estudiante->getGrupoSanguineo(),
            $estudiante->getObraSocial(),
            $estudiante->getDomicilio(),
            $estudiante->getTelefonoCelular() ?? $estudiante->getTelefonoFijo(),
            $estudiante->getEmail(),
            $estudiante->getCursoId(),
            $estudiante->getGrupoTaller(),
            $estudiante->esActivo() ? 1 : 0,
            $estudiante->getFechaIngreso()?->format('Y-m-d') ?? date('Y-m-d')
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $estudiante->setId($id);
        return $estudiante;
    }

    public function update(Estudiante $estudiante): bool
    {
        $sql = "UPDATE estudiantes SET dni = ?, dni_responsable = ?, apellido = ?, nombre = ?, fecha_nacimiento = ?, 
                grupo_sanguineo = ?, obra_social = ?, domicilio = ?, telefono = ?, email = ?, curso_id = ?, grupo_taller = ?, activo = ?, fecha_ingreso = ? WHERE id = ?";
        
        $params = [
            $estudiante->getDni(),
            $estudiante->getDniResponsable(),
            $estudiante->getApellido(),
            $estudiante->getNombre(),
            $estudiante->getFechaNacimiento()?->format('Y-m-d'),
            $estudiante->getGrupoSanguineo(),
            $estudiante->getObraSocial(),
            $estudiante->getDomicilio(),
            $estudiante->getTelefonoCelular() ?? $estudiante->getTelefonoFijo(),
            $estudiante->getEmail(),
            $estudiante->getCursoId(),
            $estudiante->getGrupoTaller(),
            $estudiante->esActivo() ? 1 : 0,
            $estudiante->getFechaIngreso()?->format('Y-m-d'),
            $estudiante->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM estudiantes WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function existsByDni(string $dni, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM estudiantes WHERE dni = ?";
        $params = [$dni];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->database->fetch($sql, $params);
        return $result['count'] > 0;
    }

    public function countBy(array $criteria): int
    {
        $whereConditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT COUNT(*) as count FROM estudiantes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function findWithPagination(int $offset, int $limit, array $criteria = []): array
    {
        $whereConditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT * FROM estudiantes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY apellido, nombre LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    /**
     * Listado de estudiantes activos con los mismos filtros que la vista (búsqueda, curso, alcance por cursos).
     *
     * @param list<int>|null $alcanceCursoIds null = sin restricción; [] = ningún curso (resultado vacío)
     * @return list<array<string, mixed>> filas en formato {@see Estudiante::toArray()}
     */
    public function findListadoVistaPaginado(string $search, ?int $cursoIdFiltro, ?array $alcanceCursoIds, int $limit, int $offset, ?string $grupoTaller = null): array
    {
        [$where, $params] = $this->buildListadoVistaWhere($search, $cursoIdFiltro, $alcanceCursoIds, $grupoTaller);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $sql = "SELECT e.* FROM estudiantes e {$where} ORDER BY e.apellido ASC, e.nombre ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->database->fetchAll($sql, $params);

        return array_map(function (array $row) {
            return $this->mapRowToEstudiante($row)->toArray();
        }, $rows);
    }

    /**
     * Total de filas que cumplen el mismo filtro que {@see findListadoVistaPaginado}.
     *
     * @param list<int>|null $alcanceCursoIds
     */
    public function countListadoVista(string $search, ?int $cursoIdFiltro, ?array $alcanceCursoIds, ?string $grupoTaller = null): int
    {
        [$where, $params] = $this->buildListadoVistaWhere($search, $cursoIdFiltro, $alcanceCursoIds, $grupoTaller);
        $row = $this->database->fetch("SELECT COUNT(*) AS c FROM estudiantes e {$where}", $params);

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @param list<int>|null $alcanceCursoIds
     *
     * @return array{0: string, 1: list<mixed>} [WHERE clause with leading WHERE, params]
     */
    private function buildListadoVistaWhere(string $search, ?int $cursoIdFiltro, ?array $alcanceCursoIds, ?string $grupoTaller = null): array
    {
        $parts = ['e.activo = 1'];
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $like = '%' . $escaped . '%';
            $parts[] = "(LOWER(e.apellido) LIKE LOWER(?) OR LOWER(e.nombre) LIKE LOWER(?) OR LOWER(CONCAT(e.apellido, ' ', e.nombre)) LIKE LOWER(?) OR e.dni LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($cursoIdFiltro !== null && $cursoIdFiltro > 0) {
            $parts[] = 'e.curso_id = ?';
            $params[] = $cursoIdFiltro;
        }

        if ($alcanceCursoIds !== null) {
            if ($alcanceCursoIds === []) {
                $parts[] = '1 = 0';
            } else {
                $placeholders = implode(',', array_fill(0, count($alcanceCursoIds), '?'));
                $parts[] = "e.curso_id IN ({$placeholders})";
                foreach ($alcanceCursoIds as $cid) {
                    $params[] = (int) $cid;
                }
            }
        }

        if ($grupoTaller !== null && $grupoTaller !== '') {
            if ($grupoTaller === 'sin_grupo') {
                $parts[] = "(e.grupo_taller IS NULL OR e.grupo_taller = '')";
            } elseif (in_array($grupoTaller, ['A', 'B', 'C', 'D', 'E'], true)) {
                $parts[] = "e.grupo_taller = ?";
                $params[] = $grupoTaller;
            }
        }

        return ['WHERE ' . implode(' AND ', $parts), $params];
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function actualizarContactoFicha(
        int $estudianteId,
        ?string $telefono,
        ?string $email,
        ?string $domicilio,
        ?string $grupoSanguineo = null,
        ?string $obraSocial = null,
        ?string $dniResponsable = null,
        ?string $grupoTaller = null,
        ?string $fechaNacimiento = null,
        ?string $fechaIngreso = null
    ): bool {
        $stmt = $this->database->query(
            "UPDATE estudiantes
             SET telefono = ?, email = ?, domicilio = ?, grupo_sanguineo = ?, obra_social = ?, dni_responsable = ?, grupo_taller = ?, fecha_nacimiento = ?, fecha_ingreso = ?
             WHERE id = ?",
            [$telefono, $email, $domicilio, $grupoSanguineo, $obraSocial, $dniResponsable, $grupoTaller, $fechaNacimiento, $fechaIngreso, $estudianteId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function obtenerAnioDivisionPorEstudiante(int $estudianteId): ?array
    {
        return $this->database->fetch(
            "SELECT c.anio, c.division
             FROM estudiantes e
             LEFT JOIN cursos c ON e.curso_id = c.id
             WHERE e.id = ?",
            [$estudianteId]
        ) ?: null;
    }

    public function buscarCursoPorAnioDivisionTurno(int $anio, string $division, int $turnoId): ?array
    {
        return $this->database->fetch(
            "SELECT id FROM cursos
             WHERE anio = ? AND division = ? AND turno_id = ? AND activo = 1
             ORDER BY id LIMIT 1",
            [$anio, $division, $turnoId]
        ) ?: null;
    }

    public function insertarResponsable(
        int $estudianteId,
        string $nombre,
        string $apellido,
        ?string $dni,
        string $telefono,
        ?string $email,
        string $parentesco,
        int $esContactoEmergencia
    ): bool {
        $stmt = $this->database->query(
            "INSERT INTO responsables (estudiante_id, nombre, apellido, dni, telefono_celular, email, parentesco, es_contacto_emergencia)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$estudianteId, $nombre, $apellido, $dni, $telefono, $email, $parentesco, $esContactoEmergencia]
        );
        return $stmt->rowCount() > 0;
    }

    public function insertarContactoEmergencia(int $estudianteId, string $nombre, string $telefono, string $parentesco): bool
    {
        $stmt = $this->database->query(
            "INSERT INTO contactos_emergencia (estudiante_id, nombre, telefono, parentesco)
             VALUES (?, ?, ?, ?)",
            [$estudianteId, $nombre, $telefono, $parentesco]
        );
        return $stmt->rowCount() > 0;
    }

    public function eliminarResponsable(int $estudianteId, int $responsableId): bool
    {
        $stmt = $this->database->query(
            "DELETE FROM responsables WHERE id = ? AND estudiante_id = ?",
            [$responsableId, $estudianteId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function eliminarContactoEmergencia(int $estudianteId, int $contactoId): bool
    {
        $stmt = $this->database->query(
            "DELETE FROM contactos_emergencia WHERE id = ? AND estudiante_id = ?",
            [$contactoId, $estudianteId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function obtenerCursoActivoPorId(int $cursoId): ?array
    {
        return $this->database->fetch(
            "SELECT c.*, esp.nombre as especialidad
             FROM cursos c
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             WHERE c.id = ? AND c.activo = 1",
            [$cursoId]
        ) ?: null;
    }

    public function obtenerCursoActualEstudiante(int $estudianteId): ?array
    {
        return $this->database->fetch(
            "SELECT c.anio, c.division, esp.nombre as especialidad
             FROM estudiantes e
             LEFT JOIN cursos c ON e.curso_id = c.id
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             WHERE e.id = ?",
            [$estudianteId]
        ) ?: null;
    }

    public function eliminarMateriasPreviasEstudiante(int $estudianteId): bool
    {
        return true;
    }

    public function actualizarCursoEstudiante(int $estudianteId, int $cursoId): bool
    {
        $stmt = $this->database->query(
            "UPDATE estudiantes SET curso_id = ? WHERE id = ?",
            [$cursoId, $estudianteId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function obtenerCursoIdEstudianteActivo(int $estudianteId): ?array
    {
        $row = $this->database->fetch(
            'SELECT curso_id FROM estudiantes WHERE id = ? AND activo = 1',
            [$estudianteId]
        );
        return $row ?: null;
    }

    /**
     * Datos de BD para armar la vista de ficha (sin agregación del boletín).
     */
    public function obtenerDatosRawVistaFichaEstudiante(int $estudianteId): ?array
    {
        $estudiante = $this->database->fetch(
            "SELECT e.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno, t.id as turno_id
             FROM estudiantes e
             LEFT JOIN cursos c ON e.curso_id = c.id
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             LEFT JOIN turnos t ON c.turno_id = t.id
             WHERE e.id = ? AND e.activo = 1",
            [$estudianteId]
        );
        if (!$estudiante) {
            return null;
        }

        $llamados = $this->database->fetchAll(
            "SELECT l.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
             FROM llamados_atencion l
             LEFT JOIN usuarios u ON l.usuario_id = u.id
             WHERE l.estudiante_id = ?
             ORDER BY l.fecha DESC
             LIMIT 20",
            [$estudianteId]
        );

        $responsables = $this->database->fetchAll(
            "SELECT * FROM responsables WHERE estudiante_id = ? ORDER BY es_contacto_emergencia DESC",
            [$estudianteId]
        );

        $contactosEmergencia = $this->database->fetchAll(
            "SELECT * FROM contactos_emergencia WHERE estudiante_id = ?",
            [$estudianteId]
        );

        $turnosLista = $this->database->fetchAll(
            "SELECT id, nombre FROM turnos WHERE activo = 1 ORDER BY nombre"
        );

        $statsLlamadosTotal = (int) ($this->database->fetch(
            "SELECT COUNT(*) as total FROM llamados_atencion WHERE estudiante_id = ?",
            [$estudianteId]
        )['total'] ?? 0);

        $statsLlamadosMes = (int) ($this->database->fetch(
            "SELECT COUNT(*) as total FROM llamados_atencion
             WHERE estudiante_id = ? AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())",
            [$estudianteId]
        )['total'] ?? 0);

        $llamadosAmonestacionVerbal = (int) ($this->database->fetch(
            "SELECT COUNT(*) as total FROM llamados_atencion WHERE estudiante_id = ? AND sancion = 'Amonestación verbal'",
            [$estudianteId]
        )['total'] ?? 0);

        $cursoIdEst = $estudiante['curso_id'] ?? null;
        if (!empty($cursoIdEst)) {
            $cursosDisponibles = $this->database->fetchAll(
                "SELECT c.*, esp.nombre as especialidad, t.nombre as turno
                 FROM cursos c
                 LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                 LEFT JOIN turnos t ON c.turno_id = t.id
                 WHERE c.id != ? AND c.activo = 1
                 ORDER BY c.anio, c.division",
                [$cursoIdEst]
            );
        } else {
            $cursosDisponibles = $this->database->fetchAll(
                "SELECT c.*, esp.nombre as especialidad, t.nombre as turno
                 FROM cursos c
                 LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                 LEFT JOIN turnos t ON c.turno_id = t.id
                 WHERE c.activo = 1
                 ORDER BY c.anio, c.division"
            );
        }

        $materias = [];
        $notasRaw = [];
        $avancesRaw = [];
        if (!empty($cursoIdEst)) {
            $materias = $this->database->fetchAll(
                "SELECT m.* FROM materias m
                 INNER JOIN materia_curso mc ON m.id = mc.materia_id
                 WHERE mc.curso_id = ? AND m.activa = 1
                 ORDER BY m.nombre",
                [$cursoIdEst]
            );

            $notasRaw = $this->database->fetchAll(
                "SELECT n.*, m.nombre as materia_nombre
                 FROM notas n
                 LEFT JOIN materias m ON n.materia_id = m.id
                 WHERE n.estudiante_id = ? AND n.bimestre IN (1, 2, 3, 4)
                 ORDER BY n.bimestre, m.nombre",
                [$estudianteId]
            );

            $avancesRaw = $this->database->fetchAll(
                "SELECT na.* FROM notas_avance na WHERE na.estudiante_id = ?",
                [$estudianteId]
            );
        }

        return [
            'estudiante' => $estudiante,
            'llamados' => $llamados,
            'responsables' => $responsables,
            'contactos_emergencia' => $contactosEmergencia,
            'turnos_lista' => $turnosLista,
            'stats_llamados_total' => $statsLlamadosTotal,
            'stats_llamados_mes' => $statsLlamadosMes,
            'llamados_amonestacion_verbal' => $llamadosAmonestacionVerbal,
            'cursos_disponibles' => $cursosDisponibles,
            'materias' => $materias,
            'notas_raw' => $notasRaw,
            'avances_raw' => $avancesRaw,
        ];
    }

    /**
     * Convierte una fila de la base de datos en un objeto Estudiante
     */
    public function mapRowToEstudiante(array $row): Estudiante
    {
        $utilityService = new \SistemaAdmin\Services\UtilityService($this->database);
        $fechaNacimiento = $utilityService->createDateTimeFromString($row['fecha_nacimiento'] ?? '', 'Y-m-d');
        $fechaIngreso = $utilityService->createDateTimeFromString($row['fecha_ingreso'] ?? '', 'Y-m-d');
        
        $estudiante = new Estudiante(
            $row['dni'],
            $row['nombre'],
            $row['apellido'],
            $fechaNacimiento,
            $row['grupo_sanguineo'] ?? null,
            $row['obra_social'] ?? null,
            $row['domicilio'] ?? null,
            null, // telefono_fijo - no existe en la BD
            $row['telefono'] ?? null, // usar telefono en lugar de telefono_celular
            $row['email'],
            $row['curso_id'],
            (bool) $row['activo'], // usar activo
            $row['grupo_taller'] ?? null,
            $fechaIngreso
        );
        
        // Establecer el ID
        $estudiante->setId((int) $row['id']);

        if (array_key_exists('dni_responsable', $row) && $row['dni_responsable'] !== null && (string) $row['dni_responsable'] !== '') {
            try {
                $estudiante->setDniResponsable((string) $row['dni_responsable']);
            } catch (\InvalidArgumentException) {
                // Fila antigua con formato no estándar
            }
        }

        if (array_key_exists('grupo_taller', $row) && $row['grupo_taller'] !== null && (string) $row['grupo_taller'] !== '') {
            try {
                $estudiante->setGrupoTaller((string) $row['grupo_taller']);
            } catch (\InvalidArgumentException) {
                // Formato no estándar
            }
        }

        return $estudiante;
    }

    public function obtenerRecursadasPorEstudiante(int $estudianteId, int $schoolYear): array
    {
        return $this->database->fetchAll(
            "SELECT r.*, m.nombre AS materia_nombre, c.anio, c.division, esp.nombre AS especialidad
             FROM estudiante_materias_recursadas r
             JOIN materias m ON r.materia_id = m.id
             JOIN cursos c ON r.curso_id = c.id
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             WHERE r.estudiante_id = ? AND r.school_year = ? AND r.activo = 1",
            [$estudianteId, $schoolYear]
        );
    }

    public function crearRecursada(int $estudianteId, int $materiaId, int $cursoId, int $schoolYear): int
    {
        $this->database->query(
            "INSERT INTO estudiante_materias_recursadas (estudiante_id, materia_id, curso_id, school_year, activo)
             VALUES (?, ?, ?, ?, 1)",
            [$estudianteId, $materiaId, $cursoId, $schoolYear]
        );
        return (int) $this->database->lastInsertId();
    }

    public function eliminarRecursada(int $recursadaId): void
    {
        $this->database->query(
            "DELETE FROM estudiante_materias_recursadas WHERE id = ?",
            [$recursadaId]
        );
    }

    public function obtenerRecursadaPorId(int $recursadaId): ?array
    {
        return $this->database->fetch(
            "SELECT * FROM estudiante_materias_recursadas WHERE id = ?",
            [$recursadaId]
        ) ?: null;
    }

    public function obtenerHorariosRecursadas(int $estudianteId, int $schoolYear): array
    {
        return $this->database->fetchAll(
            "SELECT h.*, r.materia_id, m.nombre AS materia_nombre
             FROM estudiante_materias_recursadas r
             JOIN horarios h ON r.curso_id = h.curso_id AND r.materia_id = h.materia_id
             JOIN materias m ON r.materia_id = m.id
             WHERE r.estudiante_id = ? AND r.school_year = ? AND r.activo = 1 AND h.activo = 1",
            [$estudianteId, $schoolYear]
        );
    }

    public function obtenerHorariosCurso(int $cursoId): array
    {
        return $this->database->fetchAll(
            "SELECT h.*, m.nombre AS materia_nombre
             FROM horarios h
             JOIN materias m ON h.materia_id = m.id
             WHERE h.curso_id = ? AND h.activo = 1",
            [$cursoId]
        );
    }

    public function obtenerEstudiantesRecursantes(int $materiaId, int $cursoId, int $schoolYear): array
    {
        return $this->database->fetchAll(
            "SELECT e.*, c.anio, c.division, esp.nombre AS especialidad
             FROM estudiante_materias_recursadas r
             JOIN estudiantes e ON r.estudiante_id = e.id
             LEFT JOIN cursos c ON e.curso_id = c.id
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             WHERE r.materia_id = ? AND r.curso_id = ? AND r.school_year = ? AND r.activo = 1 AND e.activo = 1",
            [$materiaId, $cursoId, $schoolYear]
        );
    }

    public function obtenerEstudiantesRecursantesDeCurso(int $cursoId, int $schoolYear): array
    {
        return $this->database->fetchAll(
            "SELECT DISTINCT e.*
             FROM estudiante_materias_recursadas r
             JOIN estudiantes e ON r.estudiante_id = e.id
             WHERE r.curso_id = ? AND r.school_year = ? AND r.activo = 1 AND e.activo = 1",
            [$cursoId, $schoolYear]
        );
    }
}
