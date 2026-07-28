<?php

declare(strict_types=1);

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * AsistenciaMapper — acceso a datos puro para el módulo de asistencia virtual.
 *
 * Responsabilidades:
 *   - Consultas SQL sobre las tablas asistencia_virtual y estudiantes.
 *   - Sin lógica de negocio (eso queda en ServicioAsistencia).
 */
class AsistenciaMapper
{
    public function __construct(private DatabaseInterface $db) {}

    // ------------------------------------------------------------------ //
    // IDs de alumnos activos de un curso
    // ------------------------------------------------------------------ //

    public function idsActivosPorCurso(int $cursoId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT id FROM estudiantes WHERE activo = 1 AND curso_id = ? ORDER BY id',
            [$cursoId]
        );
        return array_values(array_filter(
            array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $rows),
            static fn(int $id): bool => $id > 0
        ));
    }

    public function idsActivosParaAsistenciaMateria(int $cursoId, int $materiaId, int $schoolYear, ?string $grupoTaller = null): array
    {
        $sql = "SELECT id FROM estudiantes
                WHERE activo = 1 AND (curso_id = ? OR id IN (
                    SELECT estudiante_id FROM estudiante_materias_recursadas
                    WHERE materia_id = ? AND curso_id = ? AND school_year = ? AND activo = 1
                ))";
        $params = [$cursoId, $materiaId, $cursoId, $schoolYear];
        
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $sql .= " AND grupo_taller = ?";
            $params[] = $grupoTaller;
        }
        
        $sql .= " ORDER BY id";
        
        $rows = $this->db->fetchAll($sql, $params);
        return array_values(array_filter(
            array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $rows),
            static fn(int $id): bool => $id > 0
        ));
    }

    public function tablaAsistenciaExiste(): bool
    {
        $dbActual = $this->db->fetch('SELECT DATABASE() AS db');
        $nombreDb = (string) ($dbActual['db'] ?? '');
        if ($nombreDb === '') {
            return false;
        }

        return $this->db->fetch(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1',
            [$nombreDb, 'asistencia_virtual']
        ) !== null;
    }

    /**
     * Cursos activos para selector de asistencia.
     *
     * @param list<int>|null $scopeCursos null = todos los cursos activos
     * @return list<array{id:int,anio:int,division:string,especialidad:string}>
     */
    public function cursosActivosParaAsistencia(?array $scopeCursos = null): array
    {
        $sql = "SELECT c.id, c.anio, c.division, COALESCE(esp.nombre, 'Sin especialidad') AS especialidad
                FROM cursos c
                LEFT JOIN especialidades esp ON esp.id = c.especialidad_id
                WHERE c.activo = 1";
        $params = [];
        if ($scopeCursos !== null) {
            if ($scopeCursos === []) {
                $sql .= ' AND 1 = 0';
            } else {
                $sql .= ' AND c.id IN (' . implode(',', array_fill(0, count($scopeCursos), '?')) . ')';
                $params = array_map('intval', $scopeCursos);
            }
        }
        $sql .= ' ORDER BY c.anio, c.division';

        /** @var list<array{id:int,anio:int,division:string,especialidad:string}> */
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Materias activas asociadas a un curso.
     *
     * @return list<array{id:int,nombre:string}>
     */
    public function materiasActivasPorCurso(int $cursoId): array
    {
         /** @var list<array{id:int,nombre:string,es_taller:int}> */
         return $this->db->fetchAll(
             'SELECT m.id, m.nombre, m.es_taller
              FROM materias m
              INNER JOIN materia_curso mc ON mc.materia_id = m.id AND mc.activo = 1
              WHERE m.activa = 1 AND mc.curso_id = ?
              ORDER BY m.nombre',
             [$cursoId]
         );
    }

    /**
     * Alumnos activos del curso para selector de filtros.
     *
     * @return list<array{id:int,apellido:string,nombre:string}>
     */
    public function alumnosActivosPorCurso(int $cursoId): array
    {
        /** @var list<array{id:int,apellido:string,nombre:string}> */
        return $this->db->fetchAll(
            'SELECT id, apellido, nombre
             FROM estudiantes
             WHERE activo = 1 AND curso_id = ?
             ORDER BY apellido, nombre',
            [$cursoId]
        );
    }

    public function materiaPerteneceACurso(int $materiaId, int $cursoId): bool
    {
        $row = $this->db->fetch(
            'SELECT 1
             FROM materia_curso
             WHERE materia_id = ? AND curso_id = ? AND activo = 1
             LIMIT 1',
            [$materiaId, $cursoId]
        );
        return $row !== null;
    }

    /**
     * @return list<string>
     */
    public function diasConHorarioMateria(int $cursoId, int $materiaId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT LOWER(TRIM(dia_semana)) AS dia_semana
             FROM horarios
             WHERE activo = 1 AND curso_id = ? AND materia_id = ?
             ORDER BY dia_semana',
            [$cursoId, $materiaId]
        );

        $dias = [];
        foreach ($rows as $row) {
            $dia = trim((string) ($row['dia_semana'] ?? ''));
            if ($dia !== '') {
                $dias[] = $dia;
            }
        }

        return array_values(array_unique($dias));
    }

    // ------------------------------------------------------------------ //
    // Vista de toma de asistencia (un alumno por fila con su estado del día)
    // ------------------------------------------------------------------ //

    /**
     * Devuelve los alumnos activos del curso con su estado para la fecha dada.
     *
     * @return list<array{
     *   id: int,
     *   apellido: string,
     *   nombre: string,
     *   dni: string,
     *   estado: string,
     *   observacion: string|null,
     *   adjunto: string|null
     * }>
     */
    public function filasPorCursoYFecha(int $cursoId, string $fechaYmd, int $materiaId, ?string $grupoTaller = null): array
    {
        $schoolYear = (int) substr($fechaYmd, 0, 4);
        
        $sql = "SELECT
                    e.id,
                    e.apellido,
                    e.nombre,
                    e.dni,
                    av.estado                           AS estado,
                    av.observacion                      AS observacion,
                    av.adjunto                          AS adjunto,
                    e.curso_id                          AS regular_curso_id
                 FROM estudiantes e
                 LEFT JOIN asistencia_virtual av
                    ON av.estudiante_id = e.id
                   AND av.fecha         = ?
                   AND av.materia_id    = ?
                   " . (($grupoTaller !== null && $grupoTaller !== '') ? "AND av.grupo_taller = ?" : "") . "
                 WHERE e.activo    = 1
                   AND (e.curso_id  = ? OR e.id IN (
                       SELECT r.estudiante_id
                       FROM estudiante_materias_recursadas r
                       WHERE r.materia_id = ? AND r.curso_id = ? AND r.school_year = ? AND r.activo = 1
                   ))";
        
        $params = [$fechaYmd, $materiaId];
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $params[] = $grupoTaller;
        }
        $params = array_merge($params, [$cursoId, $materiaId, $cursoId, $schoolYear]);
        
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $sql .= " AND e.grupo_taller = ?";
            $params[] = $grupoTaller;
        }
        
        $sql .= " ORDER BY e.apellido, e.nombre";
        
        return $this->db->fetchAll($sql, $params);
    }

    // ------------------------------------------------------------------ //
    // Persistencia (UPSERT por transacción)
    // ------------------------------------------------------------------ //

    /**
     * Guarda/actualiza todos los registros de asistencia de un curso/fecha.
     *
     * @param list<array{
     *   estudiante_id: int,
     *   estado: string,
     *   observacion: string|null,
     *   adjunto: string|null
     * }> $registros
     */
    public function upsertRegistros(
        array  $registros,
        int    $cursoId,
        int    $materiaId,
        string $fechaYmd,
        int    $usuarioId,
        ?string $grupoTaller = null
    ): void {
        if ($registros === []) {
            return;
        }

        $db = $this->db;
        $db->transaction(static function () use ($db, $registros, $cursoId, $materiaId, $fechaYmd, $usuarioId, $grupoTaller): void {
            foreach ($registros as $r) {
                $existentes = $db->fetchAll(
                    'SELECT id
                     FROM asistencia_virtual
                     WHERE estudiante_id = ? AND fecha = ? AND materia_id = ?
                     ORDER BY id DESC',
                    [
                        $r['estudiante_id'],
                        $fechaYmd,
                        $materiaId,
                    ]
                );

                if ($existentes !== []) {
                    $idPrincipal = (int) ($existentes[0]['id'] ?? 0);
                    $db->query(
                        'UPDATE asistencia_virtual
                         SET curso_id = ?, materia_id = ?, grupo_taller = ?, estado = ?, observacion = ?,
                             adjunto = IF(? IS NOT NULL, ?, adjunto), registrado_por = ?
                         WHERE id = ?',
                        [
                            $cursoId,
                            $materiaId,
                            ($grupoTaller !== '' ? $grupoTaller : null),
                            $r['estado'],
                            $r['observacion'] ?? null,
                            $r['adjunto'] ?? null,
                            $r['adjunto'] ?? null,
                            $usuarioId,
                            $idPrincipal,
                        ]
                    );

                    // Estricto: si había duplicados históricos para la misma clave, se eliminan.
                    if (count($existentes) > 1) {
                        for ($i = 1, $len = count($existentes); $i < $len; $i++) {
                            $idDuplicado = (int) ($existentes[$i]['id'] ?? 0);
                            if ($idDuplicado > 0) {
                                $db->query('DELETE FROM asistencia_virtual WHERE id = ?', [$idDuplicado]);
                            }
                        }
                    }
                } else {
                    $db->query(
                        'INSERT INTO asistencia_virtual
                            (estudiante_id, curso_id, materia_id, grupo_taller, fecha, estado, observacion, adjunto, registrado_por)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $r['estudiante_id'],
                            $cursoId,
                            $materiaId,
                            ($grupoTaller !== '' ? $grupoTaller : null),
                            $fechaYmd,
                            $r['estado'],
                            $r['observacion'] ?? null,
                            $r['adjunto'] ?? null,
                            $usuarioId,
                        ]
                    );
                }
            }
        });
    }

    // ------------------------------------------------------------------ //
    // Resumen de asistencia por alumno (para el % acumulado)
    // ------------------------------------------------------------------ //

    /**
     * Cuenta registros por estado para un alumno en un rango de fechas.
     *
     * @return array{presente: int, tardanza: int, media_falta: int, ausente_justificado: int, ausente: int, total: int}
     */
    public function resumenPorEstudiante(
        int     $estudianteId,
        ?string $desde = null,
        ?string $hasta = null,
        ?int    $materiaId = null
    ): array {
        $params = [$estudianteId];
        $condFecha = '';

        if ($desde !== null) {
            $condFecha .= ' AND av.fecha >= ?';
            $params[] = $desde;
        }
        if ($hasta !== null) {
            $condFecha .= ' AND av.fecha <= ?';
            $params[] = $hasta;
        }
        if ($materiaId !== null && $materiaId > 0) {
            $condFecha .= ' AND av.materia_id = ?';
            $params[] = $materiaId;
        }

        $rows = $this->db->fetchAll(
            "SELECT estado, COUNT(*) AS cant
             FROM asistencia_virtual av
             WHERE av.estudiante_id = ?{$condFecha}
             GROUP BY av.estado",
            $params
        );

        $mapa = [
            'Presente'           => 0,
            'Tardanza'           => 0,
            'Media falta'        => 0,
            'Ausente justificado'=> 0,
            'Ausente'            => 0,
        ];

        foreach ($rows as $row) {
            $estado = (string) ($row['estado'] ?? '');
            if (array_key_exists($estado, $mapa)) {
                $mapa[$estado] = (int) $row['cant'];
            }
        }

        $total = array_sum($mapa);

        return [
            'presente'           => $mapa['Presente'],
            'tardanza'           => $mapa['Tardanza'],
            'media_falta'        => $mapa['Media falta'],
            'ausente_justificado'=> $mapa['Ausente justificado'],
            'ausente'            => $mapa['Ausente'],
            'total'              => $total,
        ];
    }

    /**
     * Resumen de todos los alumnos de un curso para el reporte.
     * Devuelve una fila por alumno activo con sus contadores.
     *
     * @return list<array{
     *   id: int,
     *   apellido: string,
     *   nombre: string,
     *   presente: int,
     *   tardanza: int,
     *   media_falta: int,
     *   ausente_justificado: int,
     *   ausente: int,
     *   total: int
     * }>
     */
    public function resumenPorCurso(
        int $cursoId,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $materiaId = null,
        ?string $grupoTaller = null
    ): array {
        $params = [$cursoId];
        $condFecha = '';

        if ($desde !== null) {
            $condFecha .= ' AND av.fecha >= ?';
            $params[] = $desde;
        }
        if ($hasta !== null) {
            $condFecha .= ' AND av.fecha <= ?';
            $params[] = $hasta;
        }
        if ($materiaId !== null && $materiaId > 0) {
            $condFecha .= ' AND av.materia_id = ?';
            $params[] = $materiaId;
        }
        
        $condEstudiantes = ' AND e.curso_id = ?';
        $estudiantesParams = [$cursoId];
        if ($grupoTaller !== null && $grupoTaller !== '') {
            $condEstudiantes .= ' AND e.grupo_taller = ?';
            $estudiantesParams[] = $grupoTaller;
            
            $condFecha .= ' AND av.grupo_taller = ?';
            $params[] = $grupoTaller;
        }

        return $this->db->fetchAll(
            "SELECT
                e.id,
                e.apellido,
                e.nombre,
                SUM(av.estado = 'Presente')            AS presente,
                SUM(av.estado = 'Tardanza')            AS tardanza,
                SUM(av.estado = 'Media falta')         AS media_falta,
                SUM(av.estado = 'Ausente justificado') AS ausente_justificado,
                SUM(av.estado = 'Ausente')             AS ausente,
                COUNT(av.id)                           AS total
             FROM estudiantes e
             LEFT JOIN asistencia_virtual av
                ON av.estudiante_id = e.id
               AND av.curso_id      = ?{$condFecha}
             WHERE e.activo   = 1{$condEstudiantes}
             GROUP BY e.id, e.apellido, e.nombre
             ORDER BY e.apellido, e.nombre",
            array_merge($params, $estudiantesParams)
        );
    }

    // ------------------------------------------------------------------ //
    // Historial reciente de un alumno (para la ficha)
    // ------------------------------------------------------------------ //

    /**
     * Últimos N registros de asistencia de un estudiante.
     *
     * @return list<array<string, mixed>>
     */
    public function historialRecienteEstudiante(
        int $estudianteId,
        int $limite = 15,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $materiaId = null
    ): array
    {
        $params = [$estudianteId];
        $condFecha = '';
        if ($desde !== null) {
            $condFecha .= ' AND av.fecha >= ?';
            $params[] = $desde;
        }
        if ($hasta !== null) {
            $condFecha .= ' AND av.fecha <= ?';
            $params[] = $hasta;
        }
        if ($materiaId !== null && $materiaId > 0) {
            $condFecha .= ' AND av.materia_id = ?';
            $params[] = $materiaId;
        }

        return $this->db->fetchAll(
            'SELECT av.fecha, av.estado, av.observacion, av.adjunto, m.nombre AS materia_nombre
             FROM asistencia_virtual av
             LEFT JOIN materias m ON m.id = av.materia_id
             WHERE av.estudiante_id = ?' . $condFecha . '
             ORDER BY av.fecha DESC, av.id DESC
             LIMIT ' . max(1, $limite),
            $params
        );
    }

    // ------------------------------------------------------------------ //
    // Alumnos en riesgo
    // ------------------------------------------------------------------ //

    /**
     * Alumnos de un curso con porcentaje de asistencia menor al umbral.
     *
     * @return list<array<string, mixed>>
     */
    public function alumnosEnRiesgo(int $cursoId, float $umbral, string $desde, string $hasta): array
    {
        $rows = $this->db->fetchAll(
            "SELECT
                e.id, e.apellido, e.nombre,
                SUM(av.estado = 'Presente')    AS presente,
                SUM(av.estado = 'Tardanza')    AS tardanza,
                SUM(av.estado = 'Media falta') AS media_falta,
                COUNT(av.id)                   AS total
             FROM estudiantes e
             INNER JOIN asistencia_virtual av
                ON av.estudiante_id = e.id
               AND av.curso_id = ?
               AND av.fecha BETWEEN ? AND ?
             WHERE e.activo = 1 AND e.curso_id = ?
             GROUP BY e.id, e.apellido, e.nombre
             HAVING total > 0
             ORDER BY (presente + (tardanza * 0.75) + (media_falta * 0.5)) / total ASC, e.apellido",
            [$cursoId, $desde, $hasta, $cursoId]
        );
        $resultado = [];
        foreach ($rows as $r) {
            $total   = (int) ($r['total'] ?? 0);
            $asistio = (float) ($r['presente'] ?? 0) + ((float) ($r['tardanza'] ?? 0) * 0.75) + ((float) ($r['media_falta'] ?? 0) * 0.5);
            $pct     = $total > 0 ? round($asistio / $total * 100, 1) : 0.0;
            if ($pct < $umbral) {
                $resultado[] = array_merge($r, ['porcentaje' => $pct]);
            }
        }
        return $resultado;
    }

    /**
     * Alumnos en riesgo de múltiples cursos (dashboard global).
     *
     * @param  list<int>|null $cursosIds
     * @return list<array<string, mixed>>
     */
    public function alumnosEnRiesgoGlobal(?array $cursosIds, float $umbral, string $desde, string $hasta): array
    {
        if ($cursosIds !== null && $cursosIds === []) {
            return [];
        }
        
        $cond = '';
        $params = [$desde, $hasta];
        
        if ($cursosIds !== null) {
            $ph = implode(',', array_fill(0, count($cursosIds), '?'));
            $cond = " AND av.curso_id IN ({$ph}) AND e.curso_id IN ({$ph})";
            $params = array_merge($cursosIds, [$desde, $hasta], $cursosIds);
        }

        $rows = $this->db->fetchAll(
            "SELECT
                e.id, e.apellido, e.nombre, e.curso_id,
                CONCAT(c.anio, '° ', c.division) AS curso_label,
                SUM(av.estado = 'Presente')    AS presente,
                SUM(av.estado = 'Tardanza')    AS tardanza,
                SUM(av.estado = 'Media falta') AS media_falta,
                COUNT(av.id)                   AS total
             FROM estudiantes e
             INNER JOIN cursos c ON c.id = e.curso_id
             INNER JOIN asistencia_virtual av
                ON av.estudiante_id = e.id
               AND av.fecha BETWEEN ? AND ?" . ($cursosIds !== null ? " AND av.curso_id IN ({$ph})" : "") . "
             WHERE e.activo = 1" . ($cursosIds !== null ? " AND e.curso_id IN ({$ph})" : "") . "
             GROUP BY e.id, e.apellido, e.nombre, e.curso_id, c.anio, c.division
             HAVING total > 0
             ORDER BY (presente + (tardanza * 0.75) + (media_falta * 0.5)) / total ASC, e.apellido",
            $params
        );
        $resultado = [];
        foreach ($rows as $r) {
            $total   = (int) ($r['total'] ?? 0);
            $asistio = (float) ($r['presente'] ?? 0) + ((float) ($r['tardanza'] ?? 0) * 0.75) + ((float) ($r['media_falta'] ?? 0) * 0.5);
            $pct     = $total > 0 ? round($asistio / $total * 100, 1) : 0.0;
            if ($pct < $umbral) {
                $resultado[] = array_merge($r, ['porcentaje' => $pct]);
            }
        }
        return $resultado;
    }

    // ------------------------------------------------------------------ //
    // Resumen del día por divisiones (dashboard)
    // ------------------------------------------------------------------ //

    /**
     * @param  list<int>|null $cursosIds  null = todos los cursos activos
     * @return list<array<string, mixed>>
     */
    public function resumenDivisionesHoy(string $fecha, ?array $cursosIds = null): array
    {
        $cond   = '';
        $params = [$fecha];
        if ($cursosIds !== null && $cursosIds !== []) {
            $ph     = implode(',', array_fill(0, count($cursosIds), '?'));
            $cond   = " AND c.id IN ({$ph})";
            $params = array_merge($params, $cursosIds);
        }
        return $this->db->fetchAll(
            "SELECT
                c.id                             AS curso_id,
                CONCAT(c.anio,'° ',c.division)   AS curso_label,
                COUNT(e.id)                      AS total_alumnos,
                SUM(av.estado = 'Presente')      AS presentes,
                SUM(av.estado = 'Tardanza')      AS tardanzas,
                SUM(av.estado IN ('Ausente','Media falta','Ausente justificado')) AS ausentes
             FROM cursos c
             INNER JOIN estudiantes e ON e.curso_id = c.id AND e.activo = 1
             LEFT JOIN asistencia_virtual av ON av.estudiante_id = e.id AND av.fecha = ?
             WHERE c.activo = 1{$cond}
             GROUP BY c.id, c.anio, c.division
             ORDER BY c.anio, c.division",
            $params
        );
    }

    /**
     * Devuelve los alumnos con Ausente justificado en un día específico.
     * @param list<int>|null $cursosIds
     * @return list<array<string, mixed>>
     */
    public function justificadosDelDia(string $fecha, ?array $cursosIds = null): array
    {
        $cond   = '';
        $params = [$fecha, 'Ausente justificado'];
        if ($cursosIds !== null && $cursosIds !== []) {
            $ph     = implode(',', array_fill(0, count($cursosIds), '?'));
            $cond   = " AND e.curso_id IN ({$ph})";
            $params = array_merge($params, $cursosIds);
        }
        
        // Return empty array if user has scope but no courses.
        if ($cursosIds !== null && $cursosIds === []) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT 
                e.id AS estudiante_id,
                e.apellido,
                e.nombre,
                c.id AS curso_id,
                CONCAT(c.anio, '° ', c.division) AS curso_label,
                m.id AS materia_id,
                m.nombre AS materia_nombre,
                av.adjunto,
                av.observacion
             FROM asistencia_virtual av
             INNER JOIN estudiantes e ON e.id = av.estudiante_id AND e.activo = 1
             INNER JOIN cursos c ON c.id = e.curso_id
             LEFT JOIN materias m ON m.id = av.materia_id
             WHERE av.fecha = ? AND av.estado = ? {$cond}
             ORDER BY c.anio, c.division, e.apellido, e.nombre",
            $params
        );
    }

    // ------------------------------------------------------------------ //
    // Métricas para Directivos
    // ------------------------------------------------------------------ //

    private function construirFiltrosSql(array $filtros, string $aliasC = 'c'): array
    {
        $conds = [];
        $params = [];

        if (!empty($filtros['anio'])) {
            $conds[] = "{$aliasC}.anio = ?";
            $params[] = (int) $filtros['anio'];
        }
        if (!empty($filtros['division'])) {
            $conds[] = "{$aliasC}.division = ?";
            $params[] = $filtros['division'];
        }
        if (!empty($filtros['turno_id'])) {
            $conds[] = "{$aliasC}.turno_id = ?";
            $params[] = (int) $filtros['turno_id'];
        }
        if (!empty($filtros['especialidad_id'])) {
            $conds[] = "{$aliasC}.especialidad_id = ?";
            $params[] = (int) $filtros['especialidad_id'];
        }

        return [$conds, $params];
    }

    public function obtenerAsistenciaDiariaGeneral(string $fecha, array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        $condSql = $conds !== [] ? ' AND ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                       COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id
                WHERE av.fecha = ?" . $condSql;
        
        array_unshift($params, $fecha);
        return $this->db->fetch($sql, $params) ?: ['presentes' => 0, 'total' => 0];
    }

    public function obtenerAsistenciaDiariaPorGrado(string $fecha, array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        $condSql = $conds !== [] ? ' AND ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT c.anio AS label,
                       SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                       COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id
                WHERE av.fecha = ?" . $condSql . "
                GROUP BY c.anio
                ORDER BY c.anio";
        
        array_unshift($params, $fecha);
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerAsistenciaDiariaPorDivision(string $fecha, array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        $condSql = $conds !== [] ? ' AND ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT c.division AS label,
                       SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                       COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id
                WHERE av.fecha = ?" . $condSql . "
                GROUP BY c.division
                ORDER BY c.division";
        
        array_unshift($params, $fecha);
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerAsistenciaDiariaPorTurno(string $fecha, array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        $condSql = $conds !== [] ? ' AND ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT t.nombre AS label,
                       SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                       COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id
                INNER JOIN turnos t ON t.id = c.turno_id
                WHERE av.fecha = ?" . $condSql . "
                GROUP BY t.id, t.nombre
                ORDER BY t.id";
        
        array_unshift($params, $fecha);
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerAsistenciaHistoricaSemanal(array $filtros, int $limite = 8): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        
        if (!empty($filtros['desde'])) {
            $conds[] = "av.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = "av.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        
        $condSql = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT 
                    YEAR(av.fecha) AS anio,
                    WEEK(av.fecha, 1) AS semana,
                    MIN(av.fecha) AS fecha_inicio,
                    MAX(av.fecha) AS fecha_fin,
                    SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                    COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id"
                . $condSql . "
                GROUP BY YEAR(av.fecha), WEEK(av.fecha, 1)
                ORDER BY anio DESC, semana DESC
                LIMIT " . $limite;
                
        return array_reverse($this->db->fetchAll($sql, $params));
    }

    public function obtenerAsistenciaHistoricaMensual(array $filtros, int $limite = 12): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        
        if (!empty($filtros['desde'])) {
            $conds[] = "av.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = "av.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        
        $condSql = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT 
                    YEAR(av.fecha) AS anio,
                    MONTH(av.fecha) AS mes,
                    SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                    COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id"
                . $condSql . "
                GROUP BY YEAR(av.fecha), MONTH(av.fecha)
                ORDER BY anio DESC, mes DESC
                LIMIT " . $limite;
                
        return array_reverse($this->db->fetchAll($sql, $params));
    }

    public function obtenerAsistenciaHistoricaAnual(array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        
        if (!empty($filtros['desde'])) {
            $conds[] = "av.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = "av.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        
        $condSql = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT 
                    YEAR(av.fecha) AS anio,
                    SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                    COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id"
                . $condSql . "
                GROUP BY YEAR(av.fecha)
                ORDER BY anio ASC";
                
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerTendenciaInasistenciasDiaria(array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        
        if (!empty($filtros['desde'])) {
            $conds[] = "av.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = "av.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        
        $condSql = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT 
                    av.fecha,
                    SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                    COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id"
                . $condSql . "
                GROUP BY av.fecha
                ORDER BY av.fecha ASC";
                
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerAsistenciaPorTurno(array $filtros): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        
        if (!empty($filtros['desde'])) {
            $conds[] = "av.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = "av.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        
        $condSql = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT 
                    t.nombre AS turno,
                    SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                    COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id
                INNER JOIN turnos t ON t.id = c.turno_id"
                . $condSql . "
                GROUP BY t.id, t.nombre
                ORDER BY t.id";
                
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerRankingAusentismoCursos(array $filtros, int $limite = 10): array
    {
        [$conds, $params] = $this->construirFiltrosSql($filtros);
        
        if (!empty($filtros['desde'])) {
            $conds[] = "av.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $conds[] = "av.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        
        $condSql = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "SELECT 
                    c.id AS curso_id,
                    CONCAT(c.anio, '° ', c.division) AS curso_label,
                    COALESCE(esp.nombre, 'Sin especialidad') AS especialidad,
                    SUM(av.estado IN ('Presente', 'Tardanza')) AS presentes,
                    COUNT(av.id) AS total
                FROM asistencia_virtual av
                INNER JOIN cursos c ON c.id = av.curso_id
                LEFT JOIN especialidades esp ON esp.id = c.especialidad_id"
                . $condSql . "
                GROUP BY c.id, c.anio, c.division, esp.nombre
                HAVING COUNT(av.id) > 0
                ORDER BY (COUNT(av.id) - SUM(av.estado IN ('Presente', 'Tardanza'))) / COUNT(av.id) DESC
                LIMIT " . (int)$limite;
                
        return $this->db->fetchAll($sql, $params);
    }

    public function obtenerTurnosActivos(): array
    {
        return $this->db->fetchAll("SELECT id, nombre FROM turnos WHERE activo = 1 ORDER BY nombre");
    }

    public function obtenerEspecialidadesActivas(): array
    {
        return $this->db->fetchAll("SELECT id, nombre FROM especialidades WHERE activa = 1 ORDER BY nombre");
    }

}

