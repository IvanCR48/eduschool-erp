<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Lógica de negocio y acceso a datos de horarios (listados, formularios, validaciones).
 */
class ServicioHorarios extends BaseService
{
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * Listado de horarios activos con joins (curso, materia, profesor, suplencias).
     *
     * @param array<string, mixed> $filtros curso|string, cursos|int[], profesor|int
     * @return list<array<string, mixed>>
     */
    public function listarHorarios(array $filtros = []): array
    {
        $where_conditions = ['1=1'];
        $params = [];

        if (!empty($filtros['forzar_sin_filas'])) {
            $where_conditions[] = '1 = 0';
        }

        if (!empty($filtros['curso'])) {
            $where_conditions[] = 'h.curso_id = ?';
            $params[] = $filtros['curso'];
        }

        if (!empty($filtros['cursos']) && is_array($filtros['cursos'])) {
            $cursosIds = array_values(array_filter(array_map('intval', $filtros['cursos']), static function ($id) {
                return $id > 0;
            }));
            if ($cursosIds !== []) {
                $placeholders = implode(',', array_fill(0, count($cursosIds), '?'));
                $where_conditions[] = "h.curso_id IN ($placeholders)";
                $params = array_merge($params, $cursosIds);
            }
        }

        if (!empty($filtros['profesor'])) {
            $where_conditions[] = 'h.profesor_id = ?';
            $params[] = $filtros['profesor'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        return $this->database->fetchAll("
            SELECT h.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno,
                   m.nombre as materia, m.es_taller,
                   p.apellido as profesor_apellido, p.nombre as profesor_nombre,
                   s.estado as suplencia_estado, s.fuera_servicio,
                   sup.apellido as suplente_apellido, sup.nombre as suplente_nombre
            FROM horarios h
            JOIN cursos c ON h.curso_id = c.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            LEFT JOIN materias m ON h.materia_id = m.id
            LEFT JOIN profesores p ON h.profesor_id = p.id
            LEFT JOIN suplencias s ON h.materia_id = s.materia_id AND s.estado = 'activa'
            LEFT JOIN suplentes sup ON s.suplente_id = sup.id
            WHERE $where_clause AND c.activo = 1 AND h.activo = 1
            ORDER BY
                c.anio,
                CASE
                    WHEN c.division REGEXP '^[0-9]+\$' THEN CAST(c.division AS UNSIGNED)
                    ELSE 999
                END,
                c.division,
                FIELD(h.dia_semana, 'lunes','martes','miercoles','jueves','viernes','sabado'),
                h.hora_inicio
        ", $params);
    }

    /**
     * Materias vinculadas al curso en materia_curso (activas) para selects / AJAX.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function listarMateriasActivasPorCurso(int $cursoId): array
    {
        if ($cursoId <= 0) {
            throw new \InvalidArgumentException('ID de curso inválido');
        }

        return $this->database->fetchAll('
            SELECT m.id, m.nombre, m.es_taller
            FROM materia_curso mc
            JOIN materias m ON mc.materia_id = m.id
            WHERE mc.curso_id = ?
              AND (mc.activo = 1 OR mc.activo IS NULL)
              AND m.activa = 1
            ORDER BY m.nombre
        ', [$cursoId]);
    }

    /**
     * Conteos de catálogo y nombre de BD (panel de diagnóstico en horarios).
     *
     * @return array{db_nombre: string, cursos_total: int, cursos_activos: int, materias_activas: int, profesores_activos: int}
     */
    public function obtenerDiagnosticoCatalogosHorarios(): array
    {
        try {
            $dbInfo = $this->database->fetch('SELECT DATABASE() AS db');
            $dbNombre = (string) ($dbInfo['db'] ?? '');

            return [
                'db_nombre' => $dbNombre,
                'cursos_total' => (int) ($this->database->fetch('SELECT COUNT(*) AS n FROM cursos')['n'] ?? 0),
                'cursos_activos' => (int) ($this->database->fetch('SELECT COUNT(*) AS n FROM cursos WHERE activo = 1')['n'] ?? 0),
                'materias_activas' => (int) ($this->database->fetch('SELECT COUNT(*) AS n FROM materias WHERE activa = 1')['n'] ?? 0),
                'profesores_activos' => (int) ($this->database->fetch('SELECT COUNT(*) AS n FROM profesores WHERE activo = 1')['n'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return [
                'db_nombre' => '',
                'cursos_total' => 0,
                'cursos_activos' => 0,
                'materias_activas' => 0,
                'profesores_activos' => 0,
            ];
        }
    }

    /**
     * Docentes que ya figuran en horarios activos de un curso (filtro dinámico).
     *
     * @return list<array{id: int, apellido: string, nombre: string}>
     */
    public function listarProfesoresDocentesEnHorariosPorCurso(int $cursoId): array
    {
        if ($cursoId <= 0) {
            return [];
        }

        return $this->database->fetchAll('
            SELECT DISTINCT p.id, p.apellido, p.nombre
            FROM horarios h
            JOIN profesores p ON h.profesor_id = p.id
            WHERE h.curso_id = ? AND h.activo = 1 AND p.activo = 1
            ORDER BY p.apellido, p.nombre
        ', [$cursoId]);
    }

    /**
     * @param list<array<string, mixed>> $cursos filas con al menos id
     * @return array<int, list<array<string, mixed>>>
     */
    public function construirMapaProfesoresPorCursoDesdeHorarios(array $cursos): array
    {
        $map = [];
        foreach ($cursos as $curso) {
            $cid = (int) ($curso['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $map[$cid] = $this->listarProfesoresDocentesEnHorariosPorCurso($cid);
        }

        return $map;
    }

    /**
     * @param list<array<string, mixed>> $filas filas de listarHorarios
     * @return list<array<string, mixed>>
     */
    public function enriquecerFilasListadoHorariosParaVista(array $filas): array
    {
        $out = [];
        foreach ($filas as $horario) {
            $docente = '';
            if (!empty($horario['profesor_apellido']) || !empty($horario['profesor_nombre'])) {
                $docente = trim(($horario['profesor_apellido'] ?? '') . ' ' . ($horario['profesor_nombre'] ?? ''));
            }
            $horario['docente'] = $docente;
            $horario['es_contraturno'] = $horario['es_contraturno'] ?? false;
            $out[] = $horario;
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $cursos
     * @param list<int>|null             $alcanceCids null = sin filtrar; [] = ningún curso; lista = IN
     *
     * @return list<array<string, mixed>>
     */
    public function filtrarCursosPorAlcancePreceptor(array $cursos, ?array $alcanceCids): array
    {
        if ($alcanceCids === null) {
            return $cursos;
        }
        if ($alcanceCids === []) {
            return [];
        }

        return array_values(array_filter($cursos, static function ($c) use ($alcanceCids): bool {
            return in_array((int) ($c['id'] ?? 0), $alcanceCids, true);
        }));
    }

    /**
     * Un horario por id con datos de curso, materia y profesor.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerHorarioPorId(int $horarioId): ?array
    {
        $horario = $this->database->fetch("
            SELECT h.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno,
                   m.nombre as materia, m.es_taller,
                   p.apellido as profesor_apellido, p.nombre as profesor_nombre
            FROM horarios h
            JOIN cursos c ON h.curso_id = c.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            LEFT JOIN materias m ON h.materia_id = m.id
            LEFT JOIN profesores p ON h.profesor_id = p.id
            WHERE h.id = ?
        ", [$horarioId]);

        return $horario ?: null;
    }

    /**
     * Cursos, materias y docentes (en horarios) para armar formularios de la pantalla.
     *
     * @return array{cursos: list<array<string, mixed>>, materias: list<array<string, mixed>>, profesores: list<array<string, mixed>>}
     */
    public function obtenerDatosFormularios(): array
    {
        try {
            $cursos = $this->database->fetchAll("
                SELECT c.id, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
                FROM cursos c
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN turnos t ON c.turno_id = t.id
                WHERE c.activo = 1
                ORDER BY c.anio, c.division
            ");
        } catch (\Exception $e) {
            $cursos = $this->database->fetchAll("
                SELECT c.id, c.anio, c.division, '' AS especialidad, '' AS turno
                FROM cursos c
                WHERE c.activo = 1
                ORDER BY c.anio, c.division
            ");
        }

        $materias = $this->database->fetchAll('
            SELECT * FROM materias WHERE activa = 1 ORDER BY nombre
        ');

        $profesores = $this->database->fetchAll("
            SELECT DISTINCT p.id, p.apellido, p.nombre
            FROM horarios h
            JOIN profesores p ON h.profesor_id = p.id
            WHERE h.activo = 1 AND p.activo = 1
            ORDER BY p.apellido, p.nombre
        ");

        return [
            'cursos' => $cursos,
            'materias' => $materias,
            'profesores' => $profesores,
        ];
    }

    /**
     * Docentes sugeridos al cargar un horario (misma cadena de prioridad en todo el sistema).
     *
     * @return list<array{id: int, apellido: string, nombre: string}>
     */
    public function listarDocentesParaHorario(int $materiaId, int $cursoId): array
    {
        $anioPm = 'pm.anio_academico BETWEEN YEAR(CURDATE()) - 3 AND YEAR(CURDATE()) + 1';
        $anioPc = 'pc.anio_academico BETWEEN YEAR(CURDATE()) - 3 AND YEAR(CURDATE()) + 1';

        $sql = "
            SELECT DISTINCT p.id, p.apellido, p.nombre, pm.grupo_taller
            FROM profesores p
            INNER JOIN profesor_materia pm ON p.id = pm.profesor_id
                AND pm.materia_id = ?
                AND pm.curso_id = ?
                AND pm.activo = 1
                AND $anioPm
            INNER JOIN profesor_curso pc ON p.id = pc.profesor_id
                AND pc.curso_id = ?
                AND pc.activo = 1
                AND $anioPc
            WHERE p.activo = 1
            ORDER BY p.apellido, p.nombre
        ";
        $profesores = $this->database->fetchAll($sql, [$materiaId, $cursoId, $cursoId]);
        if ($profesores === []) {
            $profesores = $this->database->fetchAll(
                "SELECT DISTINCT p.id, p.apellido, p.nombre, pm.grupo_taller
                 FROM profesores p
                 INNER JOIN profesor_materia pm ON p.id = pm.profesor_id
                 WHERE pm.materia_id = ?
                   AND pm.curso_id = ?
                   AND pm.activo = 1
                   AND $anioPm
                   AND p.activo = 1
                 ORDER BY p.apellido, p.nombre",
                [$materiaId, $cursoId]
            );
        }
        if ($profesores === []) {
            $profesores = $this->database->fetchAll(
                "SELECT DISTINCT p.id, p.apellido, p.nombre, NULL AS grupo_taller
                 FROM profesores p
                 INNER JOIN profesor_curso pc ON p.id = pc.profesor_id
                 WHERE pc.curso_id = ?
                   AND pc.activo = 1
                   AND $anioPc
                   AND p.activo = 1
                 ORDER BY p.apellido, p.nombre",
                [$cursoId]
            );
        }

        return $profesores;
    }

    /**
     * Validación completa antes de crear/actualizar (campos, rango horario, solapamiento).
     *
     * @return list<string>
     */
    public function validarHorario(array $datos, ?int $excluirHorarioId = null): array
    {
        $errores = [];

        if (empty($datos['curso_id'])) {
            $errores[] = 'El curso es requerido';
        }

        if (empty($datos['materia_id'])) {
            $errores[] = 'La materia es requerida';
        }

        if (empty($datos['dia_semana'])) {
            $errores[] = 'El día de la semana es requerido';
        }

        if (empty($datos['hora_inicio'])) {
            $errores[] = 'La hora de inicio es requerida';
        }

        if (empty($datos['hora_fin'])) {
            $errores[] = 'La hora de fin es requerida';
        }

        $diasValidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        if (!empty($datos['dia_semana']) && !in_array(strtolower((string) $datos['dia_semana']), $diasValidos, true)) {
            $errores[] = 'El día de la semana debe ser uno de: lunes, martes, miercoles, jueves, viernes, sabado';
        }

        if (!empty($datos['hora_inicio']) && !empty($datos['hora_fin'])) {
            $minInicio = $this->horaAMinutosDesdeMedianoche((string) $datos['hora_inicio']);
            $minFin = $this->horaAMinutosDesdeMedianoche((string) $datos['hora_fin']);
            if ($minInicio === null || $minFin === null) {
                $errores[] = 'Formato de hora inválido. Use HH:MM (ej. 07:30).';
            } elseif ($minFin <= $minInicio) {
                $errores[] = 'En cada módulo, la hora de fin debe ser posterior a la de inicio. '
                    . 'Si el siguiente módulo arranca cuando termina este (p. ej. de 07:30 a 09:30 y el otro de 09:30 a …), '
                    . 'cargue dos horarios distintos: el fin del primero y el inicio del segundo pueden ser la misma hora.';
            }
        }

        if ($errores !== []) {
            return $errores;
        }

        $esTaller = 0;
        if (!empty($datos['materia_id'])) {
            $m = $this->database->fetch('SELECT es_taller FROM materias WHERE id = ?', [$datos['materia_id']]);
            $esTaller = $m ? (int) $m['es_taller'] : 0;
        }

        if ($esTaller === 1) {
            if (empty($datos['grupo_taller']) || !in_array($datos['grupo_taller'], ['A', 'B', 'C', 'D', 'E'], true)) {
                $errores[] = 'El grupo de taller es requerido y debe ser uno de: A, B, C, D, E';
            }
        }

        if ($errores !== []) {
            return $errores;
        }

        if ($this->existeSolapamientoHorario(
            (int) $datos['curso_id'],
            strtolower((string) $datos['dia_semana']),
            (string) $datos['hora_inicio'],
            (string) $datos['hora_fin'],
            $excluirHorarioId,
            (!empty($datos['grupo_taller']) && $esTaller ? $datos['grupo_taller'] : null)
        )) {
            $errores[] = 'En ese curso, día (y grupo de taller, si aplica) ya hay una materia cuyo horario se superpone con el rango elegido (no se permiten cruces).';
        }

        return $errores;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{ok: true, id: int}|array{ok: false, errors: list<string>}
     */
    public function crearHorario(array $datos): array
    {
        $errores = $this->validarHorario($datos, null);
        if ($errores !== []) {
            return ['ok' => false, 'errors' => $errores];
        }

        $m = $this->database->fetch('SELECT es_taller FROM materias WHERE id = ?', [$datos['materia_id']]);
        $esTaller = $m ? (int) $m['es_taller'] : 0;
        $grupoTaller = ($esTaller === 1 && !empty($datos['grupo_taller'])) ? strtoupper($datos['grupo_taller']) : null;

        $this->database->query(
            'INSERT INTO horarios (curso_id, materia_id, profesor_id, grupo_taller, dia_semana, hora_inicio, hora_fin, aula, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $datos['curso_id'],
                $datos['materia_id'],
                $datos['profesor_id'] ?? null,
                $grupoTaller,
                $datos['dia_semana'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['aula'] ?? null,
            ]
        );

        return ['ok' => true, 'id' => (int) $this->database->lastInsertId()];
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{ok: true}|array{ok: false, errors: list<string>}|array{ok: false, not_found: true}
     */
    public function actualizarHorario(int $horarioId, array $datos): array
    {
        $errores = $this->validarHorario($datos, $horarioId);
        if ($errores !== []) {
            return ['ok' => false, 'errors' => $errores];
        }

        $m = $this->database->fetch('SELECT es_taller FROM materias WHERE id = ?', [$datos['materia_id']]);
        $esTaller = $m ? (int) $m['es_taller'] : 0;
        $grupoTaller = ($esTaller === 1 && !empty($datos['grupo_taller'])) ? strtoupper($datos['grupo_taller']) : null;

        $stmt = $this->database->query(
            'UPDATE horarios SET
                curso_id = ?, materia_id = ?, profesor_id = ?, grupo_taller = ?, dia_semana = ?, hora_inicio = ?,
                hora_fin = ?, aula = ?
             WHERE id = ?',
            [
                $datos['curso_id'],
                $datos['materia_id'],
                $datos['profesor_id'] ?? null,
                $grupoTaller,
                $datos['dia_semana'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['aula'] ?? null,
                $horarioId,
            ]
        );

        if ($stmt->rowCount() > 0) {
            return ['ok' => true];
        }

        return ['ok' => false, 'not_found' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, not_found: true}
     */
    public function eliminarHorario(int $horarioId): array
    {
        $existe = $this->database->fetch('SELECT id FROM horarios WHERE id = ?', [$horarioId]);
        if (!$existe) {
            return ['ok' => false, 'not_found' => true];
        }

        $this->database->query('DELETE FROM horarios WHERE id = ?', [$horarioId]);

        return ['ok' => true];
    }

    /**
     * Dos intervalos [inicio, fin] se superponen si se cruzan; coincidir solo en el límite no cuenta.
     */
    public function existeSolapamientoHorario(
        int $cursoId,
        string $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $excluirHorarioId,
        ?string $grupoTaller = null
    ): bool {
        $sql = 'SELECT id FROM horarios
                WHERE curso_id = ? AND dia_semana = ? AND activo = 1
                AND (
                    ? IS NULL
                    OR grupo_taller IS NULL
                    OR grupo_taller = ?
                )
                AND hora_inicio < ? AND ? < hora_fin';
        $params = [$cursoId, $diaSemana, $grupoTaller, $grupoTaller, $horaFin, $horaInicio];
        if ($excluirHorarioId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excluirHorarioId;
        }
        $sql .= ' LIMIT 1';

        return $this->database->fetch($sql, $params) !== null;
    }

    private function horaAMinutosDesdeMedianoche(string $hora): ?int
    {
        $hora = trim($hora);
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $hora, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h < 0 || $h >23 || $i< 0 || $i > 59) {
            return null;
        }

        return $h * 60 + $i;
    }
}
