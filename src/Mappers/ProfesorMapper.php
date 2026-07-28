<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Profesor;
use SistemaAdmin\Mappers\BaseMapper;

/**
 * Implementación concreta del ProfesorMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de profesores.
 */
class ProfesorMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Profesor
    {
        $sql = "SELECT p.*, esp.nombre as especialidad_nombre 
                FROM profesores p 
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id 
                WHERE p.id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToProfesor($row);
    }

    public function findByDni(string $dni): ?Profesor
    {
        $sql = "SELECT p.*, esp.nombre as especialidad_nombre 
                FROM profesores p 
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id 
                WHERE p.dni = ? AND p.activo = 1";
        $row = $this->database->fetch($sql, [$dni]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToProfesor($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT p.*, esp.nombre as especialidad_nombre 
                FROM profesores p 
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id 
                ORDER BY p.apellido, p.nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findActive(): array
    {
        $sql = "SELECT p.*, esp.nombre as especialidad_nombre 
                FROM profesores p 
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id 
                WHERE p.activo = 1 
                ORDER BY p.apellido, p.nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
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
        
        $sql = "SELECT * FROM profesores";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY apellido, nombre";
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function save(Profesor $profesor): Profesor
    {
        $sql = "INSERT INTO profesores (dni, apellido, nombre, fecha_nacimiento, domicilio, 
                telefono_fijo, telefono_celular, email, titulo, especialidad_id, fecha_ingreso, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $profesor->getDni(),
            $profesor->getApellido(),
            $profesor->getNombre(),
            $profesor->getFechaNacimiento()?->format('Y-m-d'),
            $profesor->getDomicilio(),
            $profesor->getTelefonoFijo(),
            $profesor->getTelefonoCelular(),
            $profesor->getEmail(),
            $profesor->getTitulo(),
            $profesor->getEspecialidadId(),
            $profesor->getFechaIngreso()?->format('Y-m-d'),
            $profesor->esActivo() ? 1 : 0
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $profesor->setId($id);
        return $profesor;
    }

    public function update(Profesor $profesor): bool
    {
        $sql = "UPDATE profesores SET dni = ?, apellido = ?, nombre = ?, fecha_nacimiento = ?, 
                domicilio = ?, telefono_fijo = ?, telefono_celular = ?, email = ?, titulo = ?, 
                especialidad_id = ?, fecha_ingreso = ?, activo = ? WHERE id = ?";
        
        $params = [
            $profesor->getDni(),
            $profesor->getApellido(),
            $profesor->getNombre(),
            $profesor->getFechaNacimiento()?->format('Y-m-d'),
            $profesor->getDomicilio(),
            $profesor->getTelefonoFijo(),
            $profesor->getTelefonoCelular(),
            $profesor->getEmail(),
            $profesor->getTitulo(),
            $profesor->getEspecialidadId(),
            $profesor->getFechaIngreso()?->format('Y-m-d'),
            $profesor->esActivo() ? 1 : 0,
            $profesor->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function updateInformacionFicha(
        int $profesorId,
        ?string $telefonoFijo,
        ?string $telefonoCelular,
        ?string $email,
        ?string $domicilio,
        ?string $titulo,
        ?int $especialidadId,
        ?string $fechaNacimiento = null,
        ?string $fechaIngreso = null
    ): bool {
        $sql = "UPDATE profesores SET 
                    telefono_fijo = ?, telefono_celular = ?, email = ?, domicilio = ?,
                    titulo = ?, especialidad_id = ?, fecha_nacimiento = ?, fecha_ingreso = ?
                WHERE id = ? AND activo = 1";

        $stmt = $this->database->query($sql, [
            $telefonoFijo,
            $telefonoCelular,
            $email,
            $domicilio,
            $titulo,
            $especialidadId,
            $fechaNacimiento,
            $fechaIngreso,
            $profesorId
        ]);

        // Consideramos éxito si la consulta ejecutó, incluso sin cambios.
        return $stmt->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        // Hard delete - eliminación permanente
        $sql = "DELETE FROM profesores WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function existsByDni(string $dni, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM profesores WHERE dni = ?";
        $params = [$dni];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->database->fetch($sql, $params);
        return $result['count'] > 0;
    }

    public function findByEmail(string $email): ?Profesor
    {
        $sql = "SELECT p.*, esp.nombre as especialidad_nombre 
                FROM profesores p 
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id 
                WHERE LOWER(TRIM(p.email)) = ? AND p.activo = 1";
        $row = $this->database->fetch($sql, [strtolower(trim($email))]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToProfesor($row);
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM profesores WHERE LOWER(TRIM(email)) = ? AND activo = 1";
        $params = [strtolower(trim($email))];
        
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
        
        $sql = "SELECT COUNT(*) as count FROM profesores";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

        public function findByNombre(string $nombre): array
    {
        $sql = "SELECT * FROM profesores WHERE activo = 1 AND 
                (nombre LIKE ? OR apellido LIKE ? OR dni LIKE ?) 
                ORDER BY apellido, nombre";
        $searchTerm = "%$nombre%";
        $rows = $this->database->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findByEspecialidad(string $especialidad): array
    {
        $sql = "SELECT p.*, esp.nombre as especialidad_nombre 
                FROM profesores p 
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id 
                WHERE p.activo = 1 AND esp.nombre LIKE ? 
                ORDER BY p.apellido, p.nombre";
        $searchTerm = "%$especialidad%";
        $rows = $this->database->fetchAll($sql, [$searchTerm]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findByMateria(int $materiaId): array
    {
        $sql = "SELECT DISTINCT p.* FROM profesores p
                JOIN profesor_materia pm ON p.id = pm.profesor_id
                WHERE p.activo = 1 AND pm.materia_id = ? AND pm.activo = 1
                ORDER BY p.apellido, p.nombre";
        $rows = $this->database->fetchAll($sql, [$materiaId]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findByMateriaYCurso(int $materiaId, int $cursoId): array
    {
        $sql = "SELECT DISTINCT p.* FROM profesores p
                JOIN profesor_materia pm ON p.id = pm.profesor_id
                JOIN profesor_curso pc ON p.id = pc.profesor_id
                WHERE p.activo = 1 AND pm.materia_id = ? AND pc.curso_id = ? 
                AND pm.activo = 1 AND pc.activo = 1
                ORDER BY p.apellido, p.nombre";
        $rows = $this->database->fetchAll($sql, [$materiaId, $cursoId]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function tieneCursosAsignados(int $profesorId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM profesor_curso 
                WHERE profesor_id = ? AND activo = 1";
        $result = $this->database->fetch($sql, [$profesorId]);
        
        return $result['count'] > 0;
    }

    public function getCursosAsignados(int $profesorId): array
    {
        $sql = "SELECT c.*, esp.nombre as especialidad_nombre 
                FROM cursos c
                JOIN profesor_curso pc ON c.id = pc.curso_id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                WHERE pc.profesor_id = ? AND pc.activo = 1
                ORDER BY c.anio, c.division";
        $rows = $this->database->fetchAll($sql, [$profesorId]);
        
        return $rows;
    }

    public function getMateriasAsignadas(int $profesorId): array
    {
        $sql = "SELECT m.* FROM materias m
                JOIN profesor_materia pm ON m.id = pm.materia_id
                WHERE pm.profesor_id = ? AND pm.activo = 1
                ORDER BY m.nombre";
        $rows = $this->database->fetchAll($sql, [$profesorId]);
        
        return $rows;
    }

    public function buscarAsignacionCursoExistente(int $profesorId, int $cursoId): ?array
    {
        return $this->database->fetch(
            "SELECT id FROM profesor_curso WHERE profesor_id = ? AND curso_id = ?",
            [$profesorId, $cursoId]
        ) ?: null;
    }

    public function reactivarAsignacionCurso(int $profesorId, int $cursoId): bool
    {
        $stmt = $this->database->query(
            "UPDATE profesor_curso SET activo = 1 WHERE profesor_id = ? AND curso_id = ?",
            [$profesorId, $cursoId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function crearAsignacionCurso(int $profesorId, int $cursoId): bool
    {
        $stmt = $this->database->query(
            "INSERT INTO profesor_curso (profesor_id, curso_id) VALUES (?, ?)",
            [$profesorId, $cursoId]
        );
        return $stmt->rowCount() > 0;
    }

    public function desasignarCurso(int $asignacionId, int $profesorId): bool
    {
        $stmt = $this->database->query(
            "UPDATE profesor_curso SET activo = 0 WHERE id = ? AND profesor_id = ?",
            [$asignacionId, $profesorId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function buscarMateriaAsignada(int $profesorId, int $materiaId, int $cursoId, ?string $grupoTaller = null): ?array
    {
        $sql = "SELECT id FROM profesor_materia WHERE profesor_id = ? AND materia_id = ? AND curso_id = ? AND activo = 1";
        $params = [$profesorId, $materiaId, $cursoId];
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $sql .= " AND grupo_taller = ?";
            $params[] = $grupoTaller;
        } else {
            $sql .= " AND grupo_taller IS NULL";
        }
        return $this->database->fetch($sql, $params) ?: null;
    }

    /**
     * Fila del año lectivo actual (activa o no). Sirve para reactivar tras una baja lógica sin violar el UNIQUE.
     *
     * @return array{id: int|string, activo: int|string}|null
     */
    public function buscarMateriaFilaMismoAnioLectivo(int $profesorId, int $materiaId, int $cursoId, ?string $grupoTaller = null): ?array
    {
        $sql = 'SELECT id, activo FROM profesor_materia
             WHERE profesor_id = ? AND materia_id = ? AND curso_id = ? AND anio_academico = YEAR(CURDATE())';
        $params = [$profesorId, $materiaId, $cursoId];
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $sql .= " AND grupo_taller = ?";
            $params[] = $grupoTaller;
        } else {
            $sql .= " AND grupo_taller IS NULL";
        }
        $row = $this->database->fetch($sql, $params);

        return $row ?: null;
    }

    public function reactivarMateriaProfesor(int $profesorMateriaId): bool
    {
        $stmt = $this->database->query(
            'UPDATE profesor_materia SET activo = 1 WHERE id = ?',
            [$profesorMateriaId]
        );

        return $stmt->rowCount() > 0;
    }

    public function buscarConflictoMateriaCurso(int $profesorId, int $materiaId, int $cursoId, ?string $grupoTaller = null): ?array
    {
        $sql = "SELECT p.apellido, p.nombre, c.anio, c.division
             FROM profesor_materia pm
             JOIN profesores p ON pm.profesor_id = p.id
             JOIN cursos c ON pm.curso_id = c.id
             WHERE pm.materia_id = ? 
               AND pm.curso_id = ?
               AND pm.profesor_id != ?
               AND pm.activo = 1
               AND pm.anio_academico = YEAR(CURDATE())";
        $params = [$materiaId, $cursoId, $profesorId];
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $sql .= " AND pm.grupo_taller = ?";
            $params[] = $grupoTaller;
        } else {
            $sql .= " AND pm.grupo_taller IS NULL";
        }
        return $this->database->fetch($sql, $params) ?: null;
    }

    public function asignarMateria(int $profesorId, int $materiaId, int $cursoId, ?string $grupoTaller = null): bool
    {
        $stmt = $this->database->query(
            "INSERT INTO profesor_materia (profesor_id, materia_id, curso_id, activo, anio_academico, grupo_taller)
             VALUES (?, ?, ?, 1, YEAR(CURDATE()), ?)",
            [$profesorId, $materiaId, $cursoId, ($grupoTaller !== '' ? $grupoTaller : null)]
        );
        return $stmt->rowCount() > 0;
    }

    public function desasignarMateria(int $materiaCursoId, int $profesorId): bool
    {
        $stmt = $this->database->query(
            "UPDATE profesor_materia SET activo = 0 WHERE id = ? AND profesor_id = ?",
            [$materiaCursoId, $profesorId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function buscarSuplenciaActivaPorMateria(int $profesorId, int $materiaId): ?array
    {
        return $this->database->fetch(
            "SELECT id FROM suplencias WHERE profesor_id = ? AND materia_id = ? AND estado = 'activa'",
            [$profesorId, $materiaId]
        ) ?: null;
    }

    public function crearSuplencia(
        int $profesorId,
        ?int $suplenteId,
        int $materiaId,
        string $fechaInicio,
        ?string $fechaFin,
        string $motivo,
        int $fueraServicio,
        int $usuarioId
    ): bool {
        $stmt = $this->database->query(
            "INSERT INTO suplencias (profesor_id, suplente_id, materia_id, fecha_inicio, fecha_fin, motivo, fuera_servicio, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$profesorId, $suplenteId, $materiaId, $fechaInicio, $fechaFin, $motivo, $fueraServicio, $usuarioId]
        );
        return $stmt->rowCount() > 0;
    }

    public function finalizarSuplencia(int $suplenciaId, int $profesorId): bool
    {
        $stmt = $this->database->query(
            "UPDATE suplencias SET estado = 'finalizada', fecha_fin = CURDATE() WHERE id = ? AND profesor_id = ?",
            [$suplenciaId, $profesorId]
        );
        return $stmt->rowCount() >= 0;
    }

    public function buscarSuplentePorDni(string $dni): ?array
    {
        return $this->database->fetch(
            "SELECT id, apellido, nombre, activo FROM suplentes WHERE dni = ?",
            [$dni]
        ) ?: null;
    }

    public function crearSuplente(
        string $dni,
        string $apellido,
        string $nombre,
        ?string $telefonoCelular,
        ?string $email,
        ?string $especialidad
    ): int {
        $this->database->query(
            "INSERT INTO suplentes (dni, apellido, nombre, telefono_celular, email, especialidad)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$dni, $apellido, $nombre, $telefonoCelular, $email, $especialidad]
        );
        return (int) $this->database->lastInsertId();
    }

    public function obtenerProfesorFicha(int $profesorId): ?array
    {
        $profesor = null;
        try {
            $profesor = $this->database->fetch(
                "SELECT p.*, esp.nombre AS especialidad_nombre
                 FROM profesores p
                 LEFT JOIN especialidades esp ON p.especialidad_id = esp.id
                 WHERE p.id = ?",
                [$profesorId]
            );
        } catch (\Throwable $e) {
            error_log('ProfesorMapper::obtenerProfesorFicha [profesor+join]: ' . $e->getMessage());
            try {
                $profesor = $this->database->fetch('SELECT * FROM profesores WHERE id = ?', [$profesorId]);
                if (is_array($profesor)) {
                    $profesor['especialidad_nombre'] = $profesor['especialidad_nombre'] ?? null;
                }
            } catch (\Throwable $e2) {
                error_log('ProfesorMapper::obtenerProfesorFicha [profesor]: ' . $e2->getMessage());

                return null;
            }
        }

        if (!$profesor) {
            return null;
        }

        // Cada bloque aislado: si falta una tabla (p. ej. profesor_materia corrupta), el resto de la ficha sigue visible.
        $cursosAsignados = $this->profesorFichaFetchAll(
            "SELECT pc.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno,
                    pc.creado_en as fecha_asignacion
             FROM profesor_curso pc
             JOIN cursos c ON pc.curso_id = c.id
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             LEFT JOIN turnos t ON c.turno_id = t.id
             WHERE pc.profesor_id = ? AND pc.activo = 1
             ORDER BY c.anio, c.division",
            [$profesorId],
            'cursos_asignados'
        );

        $cursosDisponibles = $this->profesorFichaFetchAll(
            "SELECT c.id, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
             FROM cursos c
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             LEFT JOIN turnos t ON c.turno_id = t.id
             WHERE c.activo = 1
             ORDER BY c.anio, c.division",
            [],
            'cursos_disponibles'
        );

        $especialidadesCatalogo = $this->profesorFichaFetchAll(
            "SELECT id, nombre
             FROM especialidades
             WHERE activa = 1
             ORDER BY nombre",
            [],
            'especialidades_catalogo'
        );

        $suplenciasActivas = $this->profesorFichaFetchAll(
            "SELECT s.*, m.nombre as materia,
                    sup.apellido as suplente_apellido, sup.nombre as suplente_nombre
             FROM suplencias s
             JOIN materias m ON s.materia_id = m.id
             LEFT JOIN suplentes sup ON s.suplente_id = sup.id
             WHERE s.profesor_id = ? AND s.estado = 'activa'
             ORDER BY s.fecha_inicio DESC",
            [$profesorId],
            'suplencias_activas'
        );

        $suplentesDisponibles = $this->profesorFichaFetchAll(
            "SELECT id, apellido, nombre, especialidad
             FROM suplentes
             WHERE activo = 1
             ORDER BY apellido, nombre",
            [],
            'suplentes_disponibles'
        );

        $materiasAsignadas = $this->profesorFichaFetchAll(
            "SELECT pm.id, pm.materia_id, pm.curso_id, pm.grupo_taller, m.nombre,
                    DATE(COALESCE(pm.creado_en, CURRENT_DATE())) AS fecha_asignacion,
                    CASE
                        WHEN pm.curso_id IS NOT NULL THEN
                            CONCAT(c.anio, '° ', c.division,
                                   CASE WHEN c.anio > 3 THEN CONCAT(' - ', esp.nombre) ELSE '' END)
                        ELSE 'Sin curso específico'
                    END as curso
             FROM profesor_materia pm
             JOIN materias m ON pm.materia_id = m.id
             LEFT JOIN cursos c ON pm.curso_id = c.id AND c.activo = 1
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             WHERE pm.profesor_id = ? AND pm.activo = 1 AND m.activa = 1
             ORDER BY COALESCE(c.anio, 0), COALESCE(c.division, ''), m.nombre",
            [$profesorId],
            'materias_asignadas'
        );

        $totalCursosActuales = $this->profesorFichaFetchCount(
            'SELECT COUNT(*) as total FROM profesor_curso WHERE profesor_id = ? AND activo = 1',
            [$profesorId],
            'total_cursos'
        );

        $totalMateriasActuales = $this->profesorFichaFetchCount(
            'SELECT COUNT(*) as total FROM profesor_materia WHERE profesor_id = ? AND activo = 1',
            [$profesorId],
            'total_materias'
        );

        return [
            'profesor' => $profesor,
            'cursos_asignados' => $cursosAsignados,
            'cursos_disponibles' => $cursosDisponibles,
            'especialidades_catalogo' => $especialidadesCatalogo,
            'suplencias_activas' => $suplenciasActivas,
            'suplentes_disponibles' => $suplentesDisponibles,
            'materias_asignadas' => $materiasAsignadas,
            'materias_profesor' => $materiasAsignadas,
            'total_cursos_actuales' => $totalCursosActuales,
            'total_materias_actuales' => $totalMateriasActuales,
        ];
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function profesorFichaFetchAll(string $sql, array $params, string $contexto): array
    {
        try {
            return $this->database->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('ProfesorMapper::obtenerProfesorFicha [' . $contexto . ']: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param list<mixed> $params
     */
    private function profesorFichaFetchCount(string $sql, array $params, string $contexto): int
    {
        try {
            $row = $this->database->fetch($sql, $params);

            return (int) ($row['total'] ?? 0);
        } catch (\Throwable $e) {
            error_log('ProfesorMapper::obtenerProfesorFicha [' . $contexto . ']: ' . $e->getMessage());

            return 0;
        }
    }

    public function obtenerInfoCurso(int $cursoId): ?array
    {
        return $this->database->fetch(
            "SELECT c.especialidad_id
             FROM cursos c
             WHERE c.id = ?",
            [$cursoId]
        ) ?: null;
    }

    public function obtenerMateriasDisponiblesPorCurso(int $cursoId, ?int $especialidadCursoId): array
    {
        $params = [$cursoId];
        $sql = "
            SELECT DISTINCT m.id, m.nombre, m.es_taller
            FROM materias m
            LEFT JOIN materia_curso mc ON m.id = mc.materia_id AND mc.curso_id = ?
            WHERE m.activa = 1
            AND (
                mc.materia_id IS NOT NULL
                OR m.especialidad_id IS NULL
        ";

        if ($especialidadCursoId !== null) {
            $sql .= " OR (m.especialidad_id = ?)";
            $params[] = $especialidadCursoId;
        }

        $sql .= ")
            ORDER BY m.nombre";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Convierte una fila de la base de datos en un objeto Profesor
     */
    private function mapRowToProfesor(array $row): Profesor
    {
        $utilityService = new \SistemaAdmin\Services\UtilityService($this->database);
        $fechaNacimiento = $utilityService->createDateTimeFromString($row['fecha_nacimiento'] ?? '', 'Y-m-d');
        $fechaIngreso = $utilityService->createDateTimeFromString($row['fecha_ingreso'] ?? '', 'Y-m-d');
        
        $profesor = new Profesor(
            $row['dni'],
            $row['apellido'],
            $row['nombre'],
            $fechaNacimiento,
            $row['domicilio'] ?? null,
            $row['telefono_fijo'] ?? null,
            $row['telefono_celular'] ?? null,
            $row['email'] ?? null,
            $row['titulo'] ?? null,
            $row['especialidad_id'] ?? null,
            $row['especialidad_nombre'] ?? null,
            $fechaIngreso,
            (bool) $row['activo']
        );
        
        // Establecer el ID
        $profesor->setId((int) $row['id']);
        
        return $profesor;
    }
}
