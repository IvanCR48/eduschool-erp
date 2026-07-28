<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Jobs\ExportReportJob;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Mappers\LlamadoMapper;

/**
 * Servicio de Reportes y Análisis Avanzados
 * 
 * Proporciona funcionalidades para generar reportes estadísticos,
 * análisis de rendimiento y exportación de datos en múltiples formatos
 */
class ServicioReportes extends BaseService
{
    private array $reportTypes = [
        'estudiantes' => 'Reportes de Estudiantes',
        'profesores' => 'Reportes de Profesores', 
        'academicos' => 'Reportes Académicos',
        'discipline' => 'Reportes de Disciplina',
        'estadisticas' => 'Estadísticas Generales',
        'rendimiento' => 'Análisis de Rendimiento',
        'tendencias' => 'Análisis de Tendencias',
        'comparativas' => 'Análisis Comparativos',
        'llamados' => 'Reportes de Llamados de Atención'
    ];

    private array $exportFormats = ['pdf', 'excel', 'csv', 'json'];

    private ServicioLlamados $servicioLlamados;

    public function __construct(
        DatabaseInterface $database,
        ?ErrorHandlerService $errorHandler = null,
        ?ServicioLogging $logger = null,
        ?ServicioLlamados $servicioLlamados = null
    ) {
        parent::__construct($database, $errorHandler, $logger);
        $this->servicioLlamados = $servicioLlamados ?? new ServicioLlamados(
            $database,
            new LlamadoMapper($database),
            new EstudianteMapper($database)
        );
    }

    /**
     * Registra una exportación y la encola. Solo llamados + excel en esta fase.
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<string, mixed>
     */
    public function solicitarExportacionEncolada(int $usuarioId, string $tipoReporte, string $formato, array $filtros): array
    {
        if ($tipoReporte !== 'llamados' || $formato !== 'excel') {
            return ['success' => false, 'error' => 'Tipo o formato no admitido para cola de exportación.'];
        }

        $this->ensureBackgroundExportsTable();

        try {
            $json = json_encode($filtros, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ['success' => false, 'error' => 'No se pudieron serializar los filtros.'];
        }

        $exportId = $this->database->insert(
            'INSERT INTO system_background_exports (usuario_id, tipo_reporte, formato, filtros_json, estado, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$usuarioId, $tipoReporte, $formato, $json, 'pending']
        );

        $queue = new QueueService($this->database);
        $queue->dispatch(ExportReportJob::class, ['export_id' => $exportId], 'default', 0, 5);

        return [
            'success' => true,
            'export_id' => $exportId,
            'mensaje' => 'La exportación se está generando en segundo plano. Podrá descargar el archivo cuando esté lista.',
        ];
    }

    /**
     * Estado de una exportación (solo el dueño).
     *
     * @return array<string, mixed>
     */
    public function obtenerEstadoExportacion(int $exportId, int $usuarioId): array
    {
        $this->ensureBackgroundExportsTable();
        $row = $this->database->fetch(
            'SELECT id, estado, archivo_nombre, error, created_at, completed_at
             FROM system_background_exports WHERE id = ? AND usuario_id = ?',
            [$exportId, $usuarioId]
        );

        if ($row === null) {
            return ['success' => false, 'error' => 'Exportación no encontrada.'];
        }

        $estado = (string) $row['estado'];

        return [
            'success' => true,
            'export_id' => $exportId,
            'estado' => $estado,
            'listo_para_descargar' => $estado === 'ready',
            'archivo_nombre' => $row['archivo_nombre'] !== null ? (string) $row['archivo_nombre'] : null,
            'error' => $row['error'] !== null ? (string) $row['error'] : null,
            'created_at' => (string) $row['created_at'],
            'completed_at' => $row['completed_at'] !== null ? (string) $row['completed_at'] : null,
        ];
    }

    /**
     * Ruta absoluta del archivo listo para el dueño, o null si no aplica.
     */
    public function resolverRutaDescargaExportacion(int $exportId, int $usuarioId): ?array
    {
        $this->ensureBackgroundExportsTable();
        $row = $this->database->fetch(
            'SELECT estado, archivo_nombre FROM system_background_exports WHERE id = ? AND usuario_id = ?',
            [$exportId, $usuarioId]
        );

        if ($row === null || ($row['estado'] ?? '') !== 'ready' || empty($row['archivo_nombre'])) {
            return null;
        }

        $nombre = basename((string) $row['archivo_nombre']);
        $dir = realpath(__DIR__ . '/../../reports');
        if ($dir === false) {
            return null;
        }

        $ruta = realpath($dir . DIRECTORY_SEPARATOR . $nombre);
        if ($ruta === false || !str_starts_with($ruta, $dir)) {
            return null;
        }

        return ['ruta' => $ruta, 'nombre_descarga' => $nombre];
    }

    /**
     * Tras descargar y borrar el archivo en disco, evita reutilizar el mismo enlace.
     */
    public function marcarExportacionDescargadaOArchivoEliminado(int $exportId): void
    {
        try {
            $this->database->query(
                'UPDATE system_background_exports SET estado = ?, archivo_nombre = NULL WHERE id = ?',
                ['downloaded', $exportId]
            );
        } catch (\Throwable $e) {
            $this->logEvent('WARNING', 'No se pudo marcar exportación como descargada', ['export_id' => $exportId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Ejecutado por {@see ExportReportJob} en el worker (no exponer por HTTP).
     */
    public function procesarExportacionEnColaPorId(int $exportId): void
    {
        $this->ensureBackgroundExportsTable();

        $row = null;
        $this->database->transaction(function (...$__) use ($exportId, &$row): void {
            $r = $this->database->fetch(
                'SELECT * FROM system_background_exports WHERE id = ? AND estado = ? FOR UPDATE',
                [$exportId, 'pending']
            );
            if ($r === null) {
                return;
            }
            $this->database->query(
                'UPDATE system_background_exports SET estado = ? WHERE id = ? AND estado = ?',
                ['processing', $exportId, 'pending']
            );
            $row = $r;
        });

        if ($row === null) {
            return;
        }

        try {
            $filtros = json_decode((string) $row['filtros_json'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($filtros)) {
                throw new \RuntimeException('Filtros inválidos en exportación.');
            }

            $tipo = (string) $row['tipo_reporte'];
            $formato = (string) $row['formato'];
            $res = $this->exportarReporte($tipo, $filtros, $formato);

            if (empty($res['success'])) {
                $this->marcarExportacionFallida($exportId, (string) ($res['error'] ?? 'Error al generar el archivo.'));

                return;
            }

            $ruta = (string) ($res['ruta'] ?? '');
            if ($ruta === '' || !is_file($ruta)) {
                $this->marcarExportacionFallida($exportId, 'Archivo generado no encontrado en disco.');

                return;
            }

            $guardado = basename($ruta);
            $this->database->query(
                'UPDATE system_background_exports SET estado = ?, archivo_nombre = ?, completed_at = NOW(), error = NULL WHERE id = ?',
                ['ready', $guardado, $exportId]
            );
        } catch (\Throwable $e) {
            $this->marcarExportacionFallida($exportId, mb_substr($e->getMessage(), 0, 2000));
        }
    }

    private function marcarExportacionFallida(int $exportId, string $mensaje): void
    {
        $this->database->query(
            'UPDATE system_background_exports SET estado = ?, error = ?, completed_at = NOW() WHERE id = ?',
            ['failed', mb_substr($mensaje, 0, 65000), $exportId]
        );
    }

    private function ensureBackgroundExportsTable(): void
    {
        try {
            $this->database->query(
                'CREATE TABLE IF NOT EXISTS system_background_exports (
                  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                  usuario_id INT UNSIGNED NOT NULL,
                  tipo_reporte VARCHAR(64) NOT NULL,
                  formato VARCHAR(16) NOT NULL,
                  filtros_json TEXT NOT NULL,
                  estado VARCHAR(20) NOT NULL DEFAULT \'pending\',
                  archivo_nombre VARCHAR(255) NULL DEFAULT NULL,
                  error TEXT NULL,
                  created_at DATETIME NOT NULL,
                  completed_at DATETIME NULL DEFAULT NULL,
                  PRIMARY KEY (id),
                  KEY idx_user_created (usuario_id, created_at),
                  KEY idx_estado (estado, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (\Throwable $e) {
            $this->logEvent('WARNING', 'No se pudo asegurar tabla system_background_exports', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener tipos de reportes disponibles
     */
    public function obtenerTiposReportes(): array
    {
        return [
            'success' => true,
            'data' => $this->reportTypes,
            'formats' => $this->exportFormats
        ];
    }

    /**
     * Generar reporte de estudiantes
     */
    public function generarReporteEstudiantes(array $filtros = []): array
    {
        try {
            $whereConditions = [];
            $params = [];

            // Construir filtros dinámicos
            if (!empty($filtros['curso_id'])) {
                $whereConditions[] = "e.curso_id = ?";
                $params[] = $filtros['curso_id'];
            }

            if (!empty($filtros['especialidad_id'])) {
                $whereConditions[] = "c.especialidad_id = ?";
                $params[] = $filtros['especialidad_id'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $whereConditions[] = "e.fecha_ingreso >= ?";
                $params[] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $whereConditions[] = "e.fecha_ingreso <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            if (!empty($filtros['activo'])) {
                $whereConditions[] = "e.activo = ?";
                $params[] = $filtros['activo'] ? 1 : 0;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $sql = "
                SELECT 
                    e.id,
                    e.dni,
                    e.apellido,
                    e.nombre,
                    e.fecha_nacimiento,
                    e.fecha_ingreso,
                    e.activo,
                    c.nombre as curso_nombre,
                    esp.nombre as especialidad_nombre,
                    COUNT(n.id) as total_notas,
                    AVG(n.calificacion) as promedio_general,
                    COUNT(l.id) as total_llamados
                FROM estudiantes e
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN notas n ON e.id = n.estudiante_id
                LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
                {$whereClause}
                GROUP BY e.id, e.dni, e.apellido, e.nombre, e.fecha_nacimiento, e.fecha_ingreso, e.activo, c.nombre, esp.nombre
                ORDER BY e.apellido, e.nombre
            ";

            $estudiantes = $this->database->fetchAll($sql, $params);

            // Calcular estadísticas adicionales
            $estadisticas = $this->calcularEstadisticasEstudiantes($estudiantes);

            return [
                'success' => true,
                'data' => $estudiantes,
                'estadisticas' => $estadisticas,
                'total_registros' => count($estudiantes),
                'filtros_aplicados' => $filtros
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando reporte de estudiantes', [
                'error' => $e->getMessage(),
                'filtros' => $filtros
            ]);
            return $this->handleError($e, ['action' => 'generar_reporte_estudiantes']);
        }
    }

    /**
     * Generar reporte de profesores
     */
    public function generarReporteProfesores(array $filtros = []): array
    {
        try {
            $whereConditions = [];
            $params = [];

            if (!empty($filtros['especialidad_id'])) {
                $whereConditions[] = "p.especialidad_id = ?";
                $params[] = $filtros['especialidad_id'];
            }

            if (!empty($filtros['activo'])) {
                $whereConditions[] = "p.activo = ?";
                $params[] = $filtros['activo'] ? 1 : 0;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $sql = "
                SELECT 
                    p.id,
                    p.dni,
                    p.apellido,
                    p.nombre,
                    p.email,
                    p.telefono,
                    p.fecha_ingreso,
                    p.activo,
                    esp.nombre as especialidad_nombre,
                    COUNT(DISTINCT n.materia_id) as materias_asignadas,
                    COUNT(DISTINCT l.id) as llamados_realizados,
                    AVG(n.calificacion) as promedio_calificaciones
                FROM profesores p
                LEFT JOIN especialidades esp ON p.especialidad_id = esp.id
                LEFT JOIN notas n ON p.id = n.profesor_id
                LEFT JOIN llamados_atencion l ON p.id = l.usuario_id
                {$whereClause}
                GROUP BY p.id, p.dni, p.apellido, p.nombre, p.email, p.telefono, p.fecha_ingreso, p.activo, esp.nombre
                ORDER BY p.apellido, p.nombre
            ";

            $profesores = $this->database->fetchAll($sql, $params);
            $estadisticas = $this->calcularEstadisticasProfesores($profesores);

            return [
                'success' => true,
                'data' => $profesores,
                'estadisticas' => $estadisticas,
                'total_registros' => count($profesores),
                'filtros_aplicados' => $filtros
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando reporte de profesores', [
                'error' => $e->getMessage(),
                'filtros' => $filtros
            ]);
            return $this->handleError($e, ['action' => 'generar_reporte_profesores']);
        }
    }

    /**
     * Generar reporte de llamados de atención (misma consulta que discipline.php vía ServicioLlamados).
     */
    public function generarReporteLlamados(array $filtros = []): array
    {
        try {
            $f = [
                'fecha_desde' => isset($filtros['fecha_desde']) ? (string) $filtros['fecha_desde'] : '',
                'fecha_hasta' => isset($filtros['fecha_hasta']) ? (string) $filtros['fecha_hasta'] : '',
                'motivo' => isset($filtros['motivo']) ? trim((string) $filtros['motivo']) : '',
                'curso_id' => isset($filtros['curso_id']) && $filtros['curso_id'] !== '' && is_numeric($filtros['curso_id'])
                    ? (string) (int) $filtros['curso_id'] : '',
                'estudiante_id' => isset($filtros['estudiante_id']) && $filtros['estudiante_id'] !== '' && is_numeric($filtros['estudiante_id'])
                    ? (string) (int) $filtros['estudiante_id'] : '',
                'preceptor_curso_ids' => $filtros['preceptor_curso_ids'] ?? [],
            ];

            if ($f['fecha_desde'] === '' || !$this->esFechaValida($f['fecha_desde'])) {
                $f['fecha_desde'] = date('Y-m-01');
            }
            if ($f['fecha_hasta'] === '' || !$this->esFechaValida($f['fecha_hasta'])) {
                $f['fecha_hasta'] = date('Y-m-d');
            }

            $f = $this->servicioLlamados->aplicarAlcancePreceptorAFiltrosListado($f);

            if (array_key_exists('tiene_sancion', $filtros) && is_bool($filtros['tiene_sancion'])) {
                $f['tiene_sancion'] = $filtros['tiene_sancion'];
            }

            $llamadosDetalle = $this->servicioLlamados->buscarDetalladosPorFiltros($f);

            $data = array_map(fn (array $row) => $this->mapearFilaLlamadoDetalleAFilaReporte($row), $llamadosDetalle);

            $total = count($data);
            $conSancion = count(array_filter($data, fn($item) => !empty($item['sancion'])));
            $sinSancion = $total - $conSancion;
            $llamadosHoy = count(array_filter($data, fn($item) => $item['fecha'] === date('Y-m-d')));
            $estudiantesInvolucrados = count(array_unique(array_column($data, 'estudiante_id')));

            $motivosConteo = [];
            $sancionesConteo = [];
            foreach ($data as $item) {
                $motivo = $item['motivo'] ?? 'Sin motivo';
                $motivosConteo[$motivo] = ($motivosConteo[$motivo] ?? 0) + 1;

                $sancion = $item['sancion'] ?? 'Sin sanción';
                $sancionesConteo[$sancion] = ($sancionesConteo[$sancion] ?? 0) + 1;
            }

            arsort($motivosConteo);
            arsort($sancionesConteo);

            $motivosTop = array_slice(
                array_map(
                    fn($motivo, $cantidad) => ['motivo' => $motivo, 'cantidad' => $cantidad],
                    array_keys($motivosConteo),
                    array_values($motivosConteo)
                ),
                0,
                5
            );

            $sancionesTop = array_slice(
                array_map(
                    fn($sancion, $cantidad) => ['sancion' => $sancion, 'cantidad' => $cantidad],
                    array_keys($sancionesConteo),
                    array_values($sancionesConteo)
                ),
                0,
                5
            );

            return [
                'success' => true,
                'data' => $data,
                'estadisticas' => [
                    'total_llamados' => $total,
                    'llamados_hoy' => $llamadosHoy,
                    'con_sancion' => $conSancion,
                    'sin_sancion' => $sinSancion,
                    'estudiantes_involucrados' => $estudiantesInvolucrados,
                    'motivo_principal' => array_key_first($motivosConteo) ?? 'N/A',
                    'sancion_principal' => array_key_first($sancionesConteo) ?? 'Sin sanción',
                    'motivos_top' => $motivosTop,
                    'sanciones_top' => $sancionesTop
                ],
                'total_registros' => $total,
                'filtros_aplicados' => $filtros
            ];
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando reporte de llamados', [
                'error' => $e->getMessage(),
                'filtros' => $filtros
            ]);
            return $this->handleError($e, ['action' => 'generar_reporte_llamados']);
        }
    }

    /**
     * Convierte una fila del listado detallado (ServicioLlamados) al formato de filas del Excel de reporte.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapearFilaLlamadoDetalleAFilaReporte(array $row): array
    {
        $anio = $row['anio'] ?? null;
        $division = $row['division'] ?? null;
        $curso = null;
        if ($anio !== null && $anio !== '' && $division !== null && $division !== '') {
            $curso = $anio . '° ' . $division;
            if (!empty($row['especialidad'])) {
                $curso .= ' - ' . $row['especialidad'];
            }
        }

        $responsable = trim(($row['usuario_apellido'] ?? '') . ', ' . ($row['usuario_nombre'] ?? ''));
        if ($responsable === ',') {
            $responsable = 'Sin responsable registrado';
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'fecha' => $row['fecha'] ?? '',
            'estudiante_id' => (int) ($row['estudiante_id'] ?? 0),
            'estudiante' => trim(($row['apellido'] ?? '') . ', ' . ($row['nombre'] ?? '')),
            'curso' => $curso ?? 'Sin curso',
            'motivo' => $row['motivo'] ?? '',
            'descripcion' => $row['descripcion'] ?? '',
            'responsable' => $responsable,
            'responsable_rol' => strtoupper((string) ($row['usuario_rol'] ?? '')),
            'sancion' => $row['sancion'] ?? '',
            'estado' => !empty($row['sancion']) ? 'Con sanción' : 'Sin sanción',
        ];
    }

    /**
     * Generar análisis de rendimiento académico
     */
    public function generarAnalisisRendimiento(array $filtros = []): array
    {
        try {
            $whereConditions = [];
            $params = [];

            if (!empty($filtros['curso_id'])) {
                $whereConditions[] = "e.curso_id = ?";
                $params[] = $filtros['curso_id'];
            }

            if (!empty($filtros['materia_id'])) {
                $whereConditions[] = "n.materia_id = ?";
                $params[] = $filtros['materia_id'];
            }

            if (!empty($filtros['bimestre'])) {
                $whereConditions[] = "n.bimestre = ?";
                $params[] = $filtros['bimestre'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $whereConditions[] = "n.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $whereConditions[] = "n.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Análisis por curso
            $sqlCursos = "
                SELECT 
                    c.id,
                    c.nombre as curso_nombre,
                    esp.nombre as especialidad_nombre,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    COUNT(n.id) as total_notas,
                    AVG(n.calificacion) as promedio_general,
                    MIN(n.calificacion) as nota_minima,
                    MAX(n.calificacion) as nota_maxima,
                    COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) as aprobados,
                COUNT(CASE WHEN n.calificacion< 7 THEN 1 END) as desaprobados,
                    ROUND((COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) * 100.0 / COUNT(n.id)), 2) as porcentaje_aprobacion
                FROM cursos c
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                LEFT JOIN notas n ON e.id = n.estudiante_id
                {$whereClause}
                GROUP BY c.id, c.nombre, esp.nombre
                ORDER BY promedio_general DESC
            ";

            $analisisCursos = $this->database->fetchAll($sqlCursos, $params);

            // Análisis por materia
            $sqlMaterias = "
                SELECT 
                    m.id,
                    m.nombre as materia_nombre,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    COUNT(n.id) as total_notas,
                    AVG(n.calificacion) as promedio_general,
                    MIN(n.calificacion) as nota_minima,
                    MAX(n.calificacion) as nota_maxima,
                    COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) as aprobados,
                    COUNT(CASE WHEN n.calificacion < 7 THEN 1 END) as desaprobados,
                    ROUND((COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) * 100.0 / COUNT(n.id)), 2) as porcentaje_aprobacion
                FROM materias m
                LEFT JOIN notas n ON m.id = n.materia_id
                LEFT JOIN estudiantes e ON n.estudiante_id = e.id AND e.activo = 1
                {$whereClause}
                GROUP BY m.id, m.nombre
                HAVING total_notas > 0
                ORDER BY promedio_general DESC
            ";

            $analisisMaterias = $this->database->fetchAll($sqlMaterias, $params);

            // Tendencias por bimestre
            $sqlTendencias = "
                SELECT 
                    n.bimestre,
                    COUNT(n.id) as total_notas,
                    AVG(n.calificacion) as promedio_bimestre,
                    COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) as aprobados,
                    COUNT(CASE WHEN n.calificacion < 7 THEN 1 END) as desaprobados,
                    ROUND((COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) * 100.0 / COUNT(n.id)), 2) as porcentaje_aprobacion
                FROM notas n
                LEFT JOIN estudiantes e ON n.estudiante_id = e.id AND e.activo = 1
                {$whereClause}
                GROUP BY n.bimestre
                ORDER BY n.bimestre
            ";

            $tendencias = $this->database->fetchAll($sqlTendencias, $params);

            return [
                'success' => true,
                'data' => [
                    'analisis_cursos' => $analisisCursos,
                    'analisis_materias' => $analisisMaterias,
                    'tendencias_bimestrales' => $tendencias
                ],
                'resumen' => $this->generarResumenRendimiento($analisisCursos, $analisisMaterias),
                'filtros_aplicados' => $filtros
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando análisis de rendimiento', [
                'error' => $e->getMessage(),
                'filtros' => $filtros
            ]);
            return $this->handleError($e, ['action' => 'generar_analisis_rendimiento']);
        }
    }

    /**
     * Generar análisis de disciplina
     */
    public function generarAnalisisDisciplina(array $filtros = []): array
    {
        try {
            $whereConditions = [];
            $params = [];

            if (!empty($filtros['curso_id'])) {
                $whereConditions[] = "e.curso_id = ?";
                $params[] = $filtros['curso_id'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $whereConditions[] = "l.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $whereConditions[] = "l.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            if (!empty($filtros['motivo'])) {
                $whereConditions[] = "l.motivo LIKE ?";
                $params[] = '%' . $filtros['motivo'] . '%';
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Análisis por curso
            $sqlCursos = "
                SELECT 
                    c.id,
                    c.nombre as curso_nombre,
                    esp.nombre as especialidad_nombre,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    COUNT(l.id) as total_llamados,
                    COUNT(DISTINCT l.estudiante_id) as estudiantes_con_llamados,
                    ROUND((COUNT(DISTINCT l.estudiante_id) * 100.0 / COUNT(DISTINCT e.id)), 2) as porcentaje_problemas,
                    AVG(CASE WHEN l.sancion IS NOT NULL THEN 1 ELSE 0 END) as porcentaje_sanciones
                FROM cursos c
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
                {$whereClause}
                GROUP BY c.id, c.nombre, esp.nombre
                ORDER BY total_llamados DESC
            ";

            $analisisCursos = $this->database->fetchAll($sqlCursos, $params);

            // Análisis por motivo
            $sqlMotivos = "
                SELECT 
                    l.motivo,
                    COUNT(l.id) as cantidad,
                    COUNT(DISTINCT l.estudiante_id) as estudiantes_unicos,
                    COUNT(CASE WHEN l.sancion IS NOT NULL THEN 1 END) as con_sancion
                FROM llamados_atencion l
                LEFT JOIN estudiantes e ON l.estudiante_id = e.id AND e.activo = 1
                {$whereClause}
                GROUP BY l.motivo
                ORDER BY cantidad DESC
            ";

            $analisisMotivos = $this->database->fetchAll($sqlMotivos, $params);

            // Tendencias mensuales
            $sqlTendencias = "
                SELECT 
                    DATE_FORMAT(l.fecha, '%Y-%m') as mes,
                    COUNT(l.id) as total_llamados,
                    COUNT(DISTINCT l.estudiante_id) as estudiantes_unicos,
                    COUNT(CASE WHEN l.sancion IS NOT NULL THEN 1 END) as con_sancion
                FROM llamados_atencion l
                LEFT JOIN estudiantes e ON l.estudiante_id = e.id AND e.activo = 1
                {$whereClause}
                GROUP BY DATE_FORMAT(l.fecha, '%Y-%m')
                ORDER BY mes DESC
                LIMIT 12
            ";

            $tendencias = $this->database->fetchAll($sqlTendencias, $params);

            return [
                'success' => true,
                'data' => [
                    'analisis_cursos' => $analisisCursos,
                    'analisis_motivos' => $analisisMotivos,
                    'tendencias_mensuales' => $tendencias
                ],
                'resumen' => $this->generarResumenDisciplina($analisisCursos, $analisisMotivos),
                'filtros_aplicados' => $filtros
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando análisis de disciplina', [
                'error' => $e->getMessage(),
                'filtros' => $filtros
            ]);
            return $this->handleError($e, ['action' => 'generar_analisis_disciplina']);
        }
    }

    /**
     * Generar estadísticas generales del sistema
     */
    public function generarEstadisticasGenerales(): array
    {
        try {
            $estadisticas = [];

            // Estadísticas de estudiantes
            $sqlEstudiantes = "
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
                    COUNT(CASE WHEN activo = 0 THEN 1 END) as inactivos,
                    COUNT(CASE WHEN fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 END) as ingresos_ultimo_ano
                FROM estudiantes
            ";
            $estadisticas['estudiantes'] = $this->database->fetch($sqlEstudiantes);

            // Estadísticas de profesores
            $sqlProfesores = "
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
                    COUNT(CASE WHEN activo = 0 THEN 1 END) as inactivos
                FROM profesores
            ";
            $estadisticas['profesores'] = $this->database->fetch($sqlProfesores);

            // Estadísticas de cursos
            $sqlCursos = "
                SELECT 
                    COUNT(*) as total_cursos,
                    COUNT(DISTINCT especialidad_id) as total_especialidades
                FROM cursos
            ";
            $estadisticas['cursos'] = $this->database->fetch($sqlCursos);

            // Estadísticas de notas
            $sqlNotas = "
                SELECT 
                    COUNT(*) as total_notas,
                    AVG(calificacion) as promedio_general,
                    MIN(calificacion) as nota_minima,
                    MAX(calificacion) as nota_maxima,
                    COUNT(CASE WHEN calificacion >= 7 THEN 1 END) as aprobadas,
                    COUNT(CASE WHEN calificacion< 7 THEN 1 END) as desaprobadas
                FROM notas
            ";
            $estadisticas['notas'] = $this->database->fetch($sqlNotas);

            // Estadísticas de llamados
            $sqlLlamados = "
                SELECT 
                    COUNT(*) as total_llamados,
                    COUNT(DISTINCT estudiante_id) as estudiantes_con_llamados,
                    COUNT(CASE WHEN sancion IS NOT NULL THEN 1 END) as con_sancion
                FROM llamados_atencion
            ";
            $estadisticas['llamados'] = $this->database->fetch($sqlLlamados);

            // Estadísticas por especialidad
            $sqlEspecialidades = "
                SELECT 
                    esp.nombre as especialidad,
                    COUNT(e.id) as total_estudiantes,
                    COUNT(CASE WHEN e.activo = 1 THEN 1 END) as estudiantes_activos,
                    AVG(n.calificacion) as promedio_general
                FROM especialidades esp
                LEFT JOIN cursos c ON esp.id = c.especialidad_id
                LEFT JOIN estudiantes e ON c.id = e.curso_id
                LEFT JOIN notas n ON e.id = n.estudiante_id
                GROUP BY esp.id, esp.nombre
                ORDER BY total_estudiantes DESC
            ";
            $estadisticas['por_especialidad'] = $this->database->fetchAll($sqlEspecialidades);

            return [
                'success' => true,
                'data' => $estadisticas,
                'generado_en' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando estadísticas generales', [
                'error' => $e->getMessage()
            ]);
            return $this->handleError($e, ['action' => 'generar_estadisticas_generales']);
        }
    }

    /**
     * Calcular estadísticas de estudiantes
     */
    private function calcularEstadisticasEstudiantes(array $estudiantes): array
    {
        if (empty($estudiantes)) {
            return [];
        }

        $total = count($estudiantes);
        $activos = count(array_filter($estudiantes, fn($e) => $e['activo'] == 1));
        $promedios = array_filter(array_column($estudiantes, 'promedio_general'), fn($p) => $p !== null);
        $llamados = array_filter(array_column($estudiantes, 'total_llamados'), fn($l) => $l > 0);

        return [
            'total_estudiantes' => $total,
            'estudiantes_activos' => $activos,
            'estudiantes_inactivos' => $total - $activos,
            'promedio_general_sistema' => !empty($promedios) ? round(array_sum($promedios) / count($promedios), 2) : 0,
            'estudiantes_con_llamados' => count($llamados),
            'porcentaje_con_llamados' => $total > 0 ? round((count($llamados) * 100) / $total, 2) : 0
        ];
    }

    /**
     * Calcular estadísticas de profesores
     */
    private function calcularEstadisticasProfesores(array $profesores): array
    {
        if (empty($profesores)) {
            return [];
        }

        $total = count($profesores);
        $activos = count(array_filter($profesores, fn($p) => $p['activo'] == 1));
        $materias = array_sum(array_column($profesores, 'materias_asignadas'));

        return [
            'total_profesores' => $total,
            'profesores_activos' => $activos,
            'profesores_inactivos' => $total - $activos,
            'total_materias_asignadas' => $materias,
            'promedio_materias_por_profesor' => $total > 0 ? round($materias / $total, 2) : 0
        ];
    }

    /**
     * Generar resumen de rendimiento
     */
    private function generarResumenRendimiento(array $cursos, array $materias): array
    {
        $promedioGeneralCursos = !empty($cursos) ? round(array_sum(array_column($cursos, 'promedio_general')) / count($cursos), 2) : 0;
        $promedioGeneralMaterias = !empty($materias) ? round(array_sum(array_column($materias, 'promedio_general')) / count($materias), 2) : 0;
        
        $totalAprobados = array_sum(array_column($cursos, 'aprobados')) + array_sum(array_column($materias, 'aprobados'));
        $totalNotas = array_sum(array_column($cursos, 'total_notas')) + array_sum(array_column($materias, 'total_notas'));
        
        return [
            'promedio_general_cursos' => $promedioGeneralCursos,
            'promedio_general_materias' => $promedioGeneralMaterias,
            'total_aprobados' => $totalAprobados,
            'total_notas' => $totalNotas,
            'porcentaje_aprobacion_general' => $totalNotas > 0 ? round(($totalAprobados * 100) / $totalNotas, 2) : 0
        ];
    }

    /**
     * Generar resumen de disciplina
     */
    private function generarResumenDisciplina(array $cursos, array $motivos): array
    {
        $totalLlamados = array_sum(array_column($cursos, 'total_llamados'));
        $totalEstudiantes = array_sum(array_column($cursos, 'total_estudiantes'));
        $totalConSancion = array_sum(array_column($cursos, 'con_sancion'));

        return [
            'total_llamados' => $totalLlamados,
            'total_estudiantes' => $totalEstudiantes,
            'total_con_sancion' => $totalConSancion,
            'promedio_llamados_por_estudiante' => $totalEstudiantes > 0 ? round($totalLlamados / $totalEstudiantes, 2) : 0,
            'porcentaje_con_sancion' => $totalLlamados > 0 ? round(($totalConSancion * 100) / $totalLlamados, 2) : 0,
            'motivo_mas_comun' => !empty($motivos) ? $motivos[0]['motivo'] : 'N/A'
        ];
    }

    /**
     * Exportar reporte a formato específico
     */
    public function exportarReporte(string $tipoReporte, array $filtros, string $formato = 'pdf'): array
    {
        try {
            if (!in_array($formato, $this->exportFormats)) {
                throw new \InvalidArgumentException("Formato no soportado: {$formato}");
            }

            // Generar datos del reporte
            $datos = $this->generarDatosReporte($tipoReporte, $filtros);
            
            if (!$datos['success']) {
                return $datos;
            }

            // Generar archivo según formato
            $nombreArchivo = $this->generarNombreArchivo($tipoReporte, $formato);
            $rutaArchivo = $this->generarArchivo($datos, $formato, $nombreArchivo);

            return [
                'success' => true,
                'archivo' => $nombreArchivo,
                'ruta' => $rutaArchivo,
                'tamaño' => filesize($rutaArchivo),
                'formato' => $formato,
                'generado_en' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error exportando reporte', [
                'error' => $e->getMessage(),
                'tipo' => $tipoReporte,
                'formato' => $formato
            ]);
            return $this->handleError($e, ['action' => 'exportar_reporte']);
        }
    }

    /**
     * Generar datos del reporte según tipo
     */
    private function generarDatosReporte(string $tipo, array $filtros): array
    {
        switch ($tipo) {
            case 'estudiantes':
                return $this->generarReporteEstudiantes($filtros);
            case 'profesores':
                return $this->generarReporteProfesores($filtros);
            case 'rendimiento':
                return $this->generarAnalisisRendimiento($filtros);
            case 'discipline':
                return $this->generarAnalisisDisciplina($filtros);
            case 'estadisticas':
                return $this->generarEstadisticasGenerales();
            case 'llamados':
                return $this->generarReporteLlamados($filtros);
            default:
                throw new \InvalidArgumentException("Tipo de reporte no válido: {$tipo}");
        }
    }

    /**
     * Generar nombre de archivo único
     */
    private function generarNombreArchivo(string $tipo, string $formato): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        return "reporte_{$tipo}_{$timestamp}.{$formato}";
    }

    /**
     * Generar archivo según formato
     */
    private function generarArchivo(array $datos, string $formato, string $nombreArchivo): string
    {
        $directorio = __DIR__ . '/../../reports/';
        
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $rutaCompleta = $directorio . $nombreArchivo;

        switch ($formato) {
            case 'json':
                file_put_contents($rutaCompleta, json_encode($datos, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->generarCSV($datos, $rutaCompleta);
                break;
            case 'pdf':
                return $this->generarPDF($datos, $nombreArchivo);
            case 'excel':
                return $this->generarExcel($datos, $nombreArchivo);
            default:
                throw new \InvalidArgumentException("Formato no soportado: {$formato}");
        }

        return $rutaCompleta;
    }

    /**
     * Generar archivo CSV
     */
    private function generarCSV(array $datos, string $rutaArchivo): void
    {
        $archivo = fopen($rutaArchivo, 'w');
        
        if (isset($datos['data']) && is_array($datos['data']) && !empty($datos['data'])) {
            // Escribir headers
            fputcsv($archivo, array_keys($datos['data'][0]));
            
            // Escribir datos
            foreach ($datos['data'] as $fila) {
                fputcsv($archivo, $fila);
            }
        }
        
        fclose($archivo);
    }

    /**
     * Generar PDF usando PDFGeneratorService
     */
    private function generarPDF(array $datos, string $nombreArchivo): string
    {
        try {
            $pdfGenerator = new PDFGeneratorService($this->database);
            
            // Determinar tipo de reporte basado en los datos
            $tipo = $this->determinarTipoReporte($datos);
            
            $opciones = [
                'titulo' => $this->obtenerTituloReporte($tipo),
                'formato_papel' => 'A4',
                'orientacion' => 'portrait'
            ];
            
            switch ($tipo) {
                case 'estudiantes':
                    return $pdfGenerator->generarReporteEstudiantes($datos, $opciones);
                case 'profesores':
                    return $pdfGenerator->generarReporteProfesores($datos, $opciones);
                case 'rendimiento':
                    return $pdfGenerator->generarAnalisisRendimiento($datos, $opciones);
                case 'discipline':
                    return $pdfGenerator->generarAnalisisDisciplina($datos, $opciones);
                case 'estadisticas':
                    return $pdfGenerator->generarEstadisticasGenerales($datos, $opciones);
                default:
                    throw new \Exception("Tipo de reporte no soportado para PDF: {$tipo}");
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF', [
                'error' => $e->getMessage(),
                'archivo' => $nombreArchivo
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel usando ExcelGeneratorService
     */
    private function generarExcel(array $datos, string $nombreArchivo): string
    {
        try {
            $excelGenerator = new ExcelGeneratorService($this->database);
            
            // Determinar tipo de reporte basado en los datos
            $tipo = $this->determinarTipoReporte($datos);
            
            $opciones = [
                'titulo' => $this->obtenerTituloReporte($tipo),
                'formato_papel' => 'A4',
                'orientacion' => 'landscape'
            ];
            
            switch ($tipo) {
                case 'estudiantes':
                    return $excelGenerator->generarReporteEstudiantes($datos, $opciones);
                case 'profesores':
                    return $excelGenerator->generarReporteProfesores($datos, $opciones);
                case 'rendimiento':
                    return $excelGenerator->generarAnalisisRendimiento($datos, $opciones);
                case 'discipline':
                    return $excelGenerator->generarAnalisisDisciplina($datos, $opciones);
                case 'estadisticas':
                    return $excelGenerator->generarEstadisticasGenerales($datos, $opciones);
                case 'llamados':
                    return $excelGenerator->generarReporteLlamados($datos, $opciones);
                default:
                    throw new \Exception("Tipo de reporte no soportado para Excel: {$tipo}");
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel', [
                'error' => $e->getMessage(),
                'archivo' => $nombreArchivo
            ]);
            throw $e;
        }
    }

    /**
     * Determinar tipo de reporte basado en los datos
     */
    private function determinarTipoReporte(array $datos): string
    {
        // Verificar si contiene análisis de rendimiento
        if (isset($datos['data']['analisis_cursos']) || isset($datos['data']['analisis_materias'])) {
            return 'rendimiento';
        }
        
        // Verificar si contiene análisis de disciplina
        if (isset($datos['data']['analisis_motivos']) || isset($datos['data']['tendencias_mensuales'])) {
            return 'discipline';
        }
        
        // Verificar si son datos de estudiantes
        if (isset($datos['data']) && is_array($datos['data']) && !empty($datos['data'])) {
            $primerElemento = reset($datos['data']);

            if (isset($primerElemento['motivo']) && isset($primerElemento['estudiante'])) {
                return 'llamados';
            }

            if (isset($primerElemento['dni']) && isset($primerElemento['apellido'])) {
                if (isset($primerElemento['email'])) {
                    return 'profesores';
                } else {
                    return 'estudiantes';
                }
            }
        }
        
        // Verificar si son estadísticas generales
        if (isset($datos['data']['estudiantes']) || isset($datos['data']['profesores'])) {
            return 'estadisticas';
        }
        
        return 'default';
    }

    /**
     * Obtener título del reporte
     */
    private function obtenerTituloReporte(string $tipo): string
    {
        $titulos = [
            'estudiantes' => 'Reporte de Estudiantes',
            'profesores' => 'Reporte de Profesores',
            'rendimiento' => 'Análisis de Rendimiento Académico',
            'discipline' => 'Análisis de Disciplina',
            'estadisticas' => 'Estadísticas Generales del Sistema',
            'llamados' => 'Reporte de Llamados de Atención',
            'default' => 'Reporte del Sistema'
        ];
        
        return $titulos[$tipo] ?? 'Reporte del Sistema';
    }

    /**
     * Validar formato de fecha Y-m-d
     */
    private function esFechaValida(string $fecha): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $dt && $dt->format('Y-m-d') === $fecha;
    }
}
