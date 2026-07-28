<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Datos de la página principal (index.php): gráficos, actividad reciente y recorte por cursos del preceptor.
 */
class ServicioDashboardHome extends BaseService
{
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * @param array{success?: bool, data?: array<string, mixed>} $resultadoEstadisticas
     * @param list<int|string> $preceptorCursoIds
     * @return array{
     *   estadisticas: array<string, mixed>,
     *   labels_anio: list<string>,
     *   datos_anio: list<int>,
     *   estudiantes_con_curso: int,
     *   estudiantes_sin_curso: int,
     *   total_estudiantes_asignacion: int,
     *   cursos_card_activos: int,
     *   cursos_card_subtitle_mode: 'bajas'|'info',
     *   cursos_card_subtitle_text: string
     * }
     */
    public function construirVistaIndex(array $resultadoEstadisticas, array $preceptorCursoIds): array
    {
        $cursoIds = $this->normalizarIdsCursos($preceptorCursoIds);

        $estadisticas = $this->mapearEstadisticasDesdeResultado($resultadoEstadisticas);

        if ($cursoIds !== []) {
            $estadisticas = $this->aplicarRecortePreceptor($estadisticas, $cursoIds);
        }

        $distribucionPorAnio = $this->distribucionEstudiantesPorAnio($cursoIds);
        $labelsAnio = [];
        $datosAnio = [];
        foreach ($distribucionPorAnio as $row) {
            $labelsAnio[] = $row['anio'] . '° Año';
            $datosAnio[] = (int) $row['total'];
        }

        $estado = $this->conteosEstudiantesConSinCurso($cursoIds);
        $estadisticas['actividad_reciente'] = $this->actividadRecienteFormateada(10);

        $tarjetaCursos = $this->construirTarjetaCursosResumen($estadisticas);

        return [
            'estadisticas' => $estadisticas,
            'labels_anio' => $labelsAnio,
            'datos_anio' => $datosAnio,
            'estudiantes_con_curso' => $estado['con_curso'],
            'estudiantes_sin_curso' => $estado['sin_curso'],
            'total_estudiantes_asignacion' => $estado['total_asignacion'],
            'cursos_card_activos' => $tarjetaCursos['activos'],
            'cursos_card_subtitle_mode' => $tarjetaCursos['subtitle_mode'],
            'cursos_card_subtitle_text' => $tarjetaCursos['subtitle_text'],
        ];
    }

    /**
     * Texto y modo del pie de la tarjeta "Cursos activos" (evita lógica en la vista).
     *
     * @param array<string, mixed> $estadisticas
     * @return array{activos: int, subtitle_mode: 'bajas'|'info', subtitle_text: string}
     */
    private function construirTarjetaCursosResumen(array $estadisticas): array
    {
        $cursosActivos = (int) ($estadisticas['cursos_activos'] ?? 0);
        $cursosTotales = (int) ($estadisticas['total_cursos'] ?? 0);

        if ($cursosTotales > $cursosActivos) {
            $bajas = $cursosTotales - $cursosActivos;

            return [
                'activos' => $cursosActivos,
                'subtitle_mode' => 'bajas',
                'subtitle_text' => sprintf(
                    '%s dados de baja (siguen en la base)',
                    number_format($bajas, 0, ',', '.')
                ),
            ];
        }

        $reg = $cursosTotales === 1 ? 'registro' : 'registros';

        return [
            'activos' => $cursosActivos,
            'subtitle_mode' => 'info',
            'subtitle_text' => sprintf(
                '%s %s en cursos',
                number_format($cursosTotales, 0, ',', '.'),
                $reg
            ),
        ];
    }

    /**
     * @param array{success?: bool, data?: array<string, mixed>} $resultado
     * @return array<string, mixed>
     */
    private function mapearEstadisticasDesdeResultado(array $resultado): array
    {
        if (($resultado['success'] ?? false) && isset($resultado['data'])) {
            $data = $resultado['data'];

            return [
                'total_estudiantes' => $data['estudiantes']['total'] ?? 0,
                'estudiantes_activos' => $data['estudiantes']['activos'] ?? 0,
                'total_profesores' => $data['profesores']['total'] ?? 0,
                'profesores_activos' => $data['profesores']['activos'] ?? 0,
                'total_cursos' => (int) ($data['adicionales']['total_cursos'] ?? 0),
                'cursos_activos' => (int) ($data['adicionales']['cursos_activos'] ?? ($data['adicionales']['total_cursos'] ?? 0)),
                'data' => $data,
                'actividad_reciente' => [],
            ];
        }

        return [
            'total_estudiantes' => 0,
            'estudiantes_activos' => 0,
            'total_profesores' => 0,
            'profesores_activos' => 0,
            'total_cursos' => 0,
            'cursos_activos' => 0,
            'data' => [],
            'actividad_reciente' => [],
        ];
    }

    /**
     * @param array<string, mixed> $estadisticas
     * @param list<int> $cursoIds
     * @return array<string, mixed>
     */
    private function aplicarRecortePreceptor(array $estadisticas, array $cursoIds): array
    {
        $ph = implode(',', array_fill(0, count($cursoIds), '?'));
        $cntAlumnos = (int) ($this->database->fetch(
            "SELECT COUNT(*) AS t FROM estudiantes WHERE activo = 1 AND curso_id IN ($ph)",
            $cursoIds
        )['t'] ?? 0);
        $estadisticas['total_estudiantes'] = $cntAlumnos;
        $estadisticas['estudiantes_activos'] = $cntAlumnos;
        $nCursos = count($cursoIds);
        $estadisticas['total_cursos'] = $nCursos;
        $estadisticas['cursos_activos'] = $nCursos;

        return $estadisticas;
    }

    /**
     * @param list<int> $cursoIds vacío = todos los cursos activos
     * @return list<array{anio: mixed, total: mixed}>
     */
    private function distribucionEstudiantesPorAnio(array $cursoIds): array
    {
        if ($cursoIds !== []) {
            $ph = implode(',', array_fill(0, count($cursoIds), '?'));

            return $this->database->fetchAll(
                "SELECT c.anio, COUNT(e.id) as total
                FROM cursos c
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                WHERE c.activo = 1 AND c.id IN ($ph)
                GROUP BY c.anio
                ORDER BY c.anio",
                $cursoIds
            );
        }

        return $this->database->fetchAll(<<<'SQL'
            SELECT c.anio, COUNT(e.id) as total
            FROM cursos c
            LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
            WHERE c.activo = 1
            GROUP BY c.anio
            ORDER BY c.anio
            SQL,
            []
        );
    }

    /**
     * @param list<int> $cursoIds vacío = todos los estudiantes activos
     * @return array{con_curso: int, sin_curso: int, total_asignacion: int}
     */
    private function conteosEstudiantesConSinCurso(array $cursoIds): array
    {
        if ($cursoIds !== []) {
            $ph = implode(',', array_fill(0, count($cursoIds), '?'));
            $rows = $this->database->fetchAll(
                "SELECT 
                    CASE WHEN curso_id IS NULL THEN 'sin_curso' ELSE 'con_curso' END AS estado,
                    COUNT(*) AS total
                FROM estudiantes
                WHERE activo = 1 AND curso_id IN ($ph)
                GROUP BY estado",
                $cursoIds
            );
        } else {
            $rows = $this->database->fetchAll(
                <<<'SQL'
                SELECT 
                    CASE 
                        WHEN curso_id IS NULL THEN 'sin_curso'
                        ELSE 'con_curso'
                    END AS estado,
                    COUNT(*) AS total
                FROM estudiantes
                WHERE activo = 1
                GROUP BY estado
                SQL,
                []
            );
        }

        $conCurso = 0;
        $sinCurso = 0;
        foreach ($rows as $row) {
            if (($row['estado'] ?? '') === 'con_curso') {
                $conCurso = (int) $row['total'];
            } else {
                $sinCurso = (int) $row['total'];
            }
        }

        return [
            'con_curso' => $conCurso,
            'sin_curso' => $sinCurso,
            'total_asignacion' => $conCurso + $sinCurso,
        ];
    }

    /**
     * @return list<array{icono: string, descripcion: string, fecha: string}>
     */
    private function actividadRecienteFormateada(int $limite): array
    {
        $actividadReciente = [];
        try {
            $actividadesRaw = $this->database->fetchAll(
                <<<'SQL'
                SELECT 
                    eventos.fecha,
                    eventos.origen,
                    eventos.evento,
                    eventos.detalle,
                    eventos.usuario_id,
                    eventos.datos,
                    u.nombre,
                    u.apellido,
                    u.rol
                FROM (
                    SELECT 
                        la.timestamp AS fecha,
                        'auditoria' AS origen,
                        la.accion AS evento,
                        CONCAT(la.entidad, IF(la.entidad_id IS NOT NULL, CONCAT(' #', la.entidad_id), '')) AS detalle,
                        la.usuario_id,
                        la.datos
                    FROM logs_auditoria la
                    UNION ALL
                    SELECT 
                        ls.timestamp AS fecha,
                        'seguridad' AS origen,
                        ls.tipo AS evento,
                        ls.descripcion AS detalle,
                        ls.usuario_id,
                        ls.datos
                    FROM logs_seguridad ls
                ) eventos
                LEFT JOIN usuarios u ON eventos.usuario_id = u.id
                WHERE eventos.fecha IS NOT NULL
                ORDER BY eventos.fecha DESC
                LIMIT ?
                SQL,
                [$limite]
            );

            foreach ($actividadesRaw as $actividad) {
                $actividadReciente[] = $this->formatearFilaActividad($actividad);
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $actividadReciente;
    }

    /** Campos técnicos del contexto HTTP que nunca se muestran al usuario. */
    private const DATOS_CAMPOS_TECNICOS = ['user_agent', 'request_uri', 'request_method', 'ip', 'session_id', 'referrer', 'host'];

    /** Traducción de acciones de auditoría a frases verbales en tercera persona. */
    private const ACCION_FRASE = [
        'CREAR'             => 'registró',
        'ACTUALIZAR'        => 'modificó',
        'ELIMINAR'          => 'eliminó',
        'GUARDAR_ASISTENCIA'=> 'guardó asistencia de',
        'LOGIN_SUCCESS'     => 'inició sesión',
        'LOGIN_FAILED'      => 'falló al iniciar sesión',
    ];

    /** Traducción de entidades a sustantivos legibles con artículo. */
    private const ENTIDAD_NOMBRE = [
        'nota'              => 'una nota',
        'estudiante'        => 'un estudiante',
        'llamado_atencion'  => 'un llamado de atención',
        'materia_previa'    => 'una materia previa',
        'asistencia_virtual'=> 'asistencia virtual',
        'curso'             => 'un curso',
        'profesor'          => 'un profesor',
        'usuario'           => 'un usuario',
        'materia'           => 'una materia',
    ];

    /** Roles a etiquetas legibles. */
    private const ROL_ETIQUETA = [
        'admin'     => 'Administrador',
        'preceptor' => 'Preceptor',
        'directivo' => 'Directivo',
        'profesor'  => 'Profesor',
    ];

    /**
     * @param array<string, mixed> $actividad
     * @return array{icono: string, icono_clase: string, actor: string, rol: string, descripcion: string, fecha: string}
     */
    private function formatearFilaActividad(array $actividad): array
    {
        $fecha = $actividad['fecha'] ?? null;
        $fechaFormateada = $fecha ? date('d/m/Y H:i', strtotime((string) $fecha)) : 'Fecha no disponible';

        $apellido = trim((string) ($actividad['apellido'] ?? ''));
        $nombre   = trim((string) ($actividad['nombre'] ?? ''));
        if ($apellido !== '' && $nombre !== '') {
            $actor = "{$apellido}, {$nombre}";
        } elseif ($apellido !== '' || $nombre !== '') {
            $actor = $apellido !== '' ? $apellido : $nombre;
        } elseif (!empty($actividad['usuario_id'])) {
            $actor = 'Usuario #' . $actividad['usuario_id'];
        } else {
            $actor = 'Sistema';
        }

        $rolRaw    = strtolower(trim((string) ($actividad['rol'] ?? '')));
        $rolLabel  = self::ROL_ETIQUETA[$rolRaw] ?? (ucfirst($rolRaw) ?: 'Usuario');

        $eventoOriginal = strtoupper(trim((string) ($actividad['evento'] ?? '')));
        $origen         = (string) ($actividad['origen'] ?? 'seguridad');

        // Extraer entidad e id del detalle (formato "entidad #id" o sólo "entidad")
        $detalleRaw = trim((string) ($actividad['detalle'] ?? ''));
        $entidadNombre = '';
        $entidadId     = '';
        if (preg_match('/^(.+?)\s*#(\d+)$/', $detalleRaw, $m)) {
            $entidadNombre = trim($m[1]);
            $entidadId     = $m[2];
        } else {
            $entidadNombre = $detalleRaw;
        }
        $entidadLegible = self::ENTIDAD_NOMBRE[strtolower($entidadNombre)] ?? $entidadNombre;

        // Construir mensaje principal
        $mensaje = '';
        if ($origen === 'auditoria') {
            $verbo = self::ACCION_FRASE[$eventoOriginal] ?? strtolower(str_replace(['_', '-'], ' ', $eventoOriginal));
            $mensaje = ucfirst($verbo);
            if ($entidadLegible !== '') {
                $mensaje .= ' ' . $entidadLegible;
                if ($entidadId !== '') {
                    $mensaje .= " (#{$entidadId})";
                }
            }
        } elseif ($eventoOriginal === 'LOGIN_SUCCESS') {
            $mensaje = 'Inició sesión correctamente';
        } elseif ($eventoOriginal === 'LOGIN_FAILED') {
            $mensaje = 'Intento de acceso fallido';
        } else {
            $fallback = trim($detalleRaw);
            $mensaje  = $fallback !== '' ? ucfirst(mb_strtolower($fallback)) : ucwords(strtolower(str_replace(['_', '-'], ' ', $eventoOriginal)));
        }

        // Ícono y clase de color según tipo de acción
        [$icono, $iconoClase] = $this->resolverIconoActividad($eventoOriginal, $origen);

        return [
            'icono'       => $icono,
            'icono_clase' => $iconoClase,
            'actor'       => $actor,
            'rol'         => $rolLabel,
            'descripcion' => $mensaje,
            'fecha'       => $fechaFormateada,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolverIconoActividad(string $evento, string $origen): array
    {
        if ($origen === 'auditoria') {
            if (str_contains($evento, 'ELIMIN')) {
                return ['trash-alt', 'danger'];
            }
            if (str_contains($evento, 'CREA') || str_contains($evento, 'GUARDAR')) {
                return ['plus-circle', 'success'];
            }
            if (str_contains($evento, 'ACTUALIZ') || str_contains($evento, 'MODIF')) {
                return ['edit', 'warning'];
            }

            return ['clipboard-check', 'info'];
        }

        if ($evento === 'LOGIN_SUCCESS') {
            return ['sign-in-alt', 'success'];
        }
        if (str_contains($evento, 'LOGIN')) {
            return ['exclamation-triangle', 'warning'];
        }
        if (str_contains($evento, 'ERROR') || str_contains($evento, 'FAIL')) {
            return ['shield-alt', 'danger'];
        }

        return ['shield-alt', 'info'];
    }

    /**
     * @param list<int|string> $ids
     * @return list<int>
     */
    private function normalizarIdsCursos(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }
}
