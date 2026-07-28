<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioReportes;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Controller para manejar las peticiones HTTP relacionadas con reportes
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para reportes y análisis.
 */
class ReportesController extends BaseService
{
    private ServicioReportes $servicioReportes;

    public function __construct(DatabaseInterface $database, ServicioReportes $servicioReportes)
    {
        parent::__construct($database);
        $this->servicioReportes = $servicioReportes;
    }

    /**
     * Obtener tipos de reportes disponibles
     */
    public function obtenerTiposReportes(): array
    {
        try {
            return $this->servicioReportes->obtenerTiposReportes();
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_tipos_reportes'
            ]);
        }
    }

    /**
     * Generar reporte de estudiantes
     */
    public function reporteEstudiantes(array $filtros = []): array
    {
        try {
            // Validar filtros
            $filtros = $this->validarFiltrosEstudiantes($filtros);
            
            return $this->servicioReportes->generarReporteEstudiantes($filtros);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'reporte_estudiantes',
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Generar reporte de profesores
     */
    public function reporteProfesores(array $filtros = []): array
    {
        try {
            // Validar filtros
            $filtros = $this->validarFiltrosProfesores($filtros);
            
            return $this->servicioReportes->generarReporteProfesores($filtros);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'reporte_profesores',
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Generar análisis de rendimiento académico
     */
    public function analisisRendimiento(array $filtros = []): array
    {
        try {
            // Validar filtros
            $filtros = $this->validarFiltrosRendimiento($filtros);
            
            return $this->servicioReportes->generarAnalisisRendimiento($filtros);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'analisis_rendimiento',
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Generar análisis de disciplina
     */
    public function analisisDisciplina(array $filtros = []): array
    {
        try {
            // Validar filtros
            $filtros = $this->validarFiltrosDisciplina($filtros);
            
            return $this->servicioReportes->generarAnalisisDisciplina($filtros);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'analisis_disciplina',
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Generar reporte de llamados de atención
     */
    public function reporteLlamados(array $filtros = []): array
    {
        try {
            $filtros = $this->validarFiltrosLlamados($filtros);

            return $this->servicioReportes->generarReporteLlamados($filtros);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'reporte_llamados',
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Generar estadísticas generales del sistema
     */
    public function estadisticasGenerales(): array
    {
        try {
            return $this->servicioReportes->generarEstadisticasGenerales();
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'estadisticas_generales'
            ]);
        }
    }

    /**
     * Filtros para exportar llamados (export_discipline.php) a partir de $_GET.
     *
     * @param array<string, mixed> $get
     * @param list<int> $preceptorCids
     *
     * @return array<string, mixed>
     */
    public function construirFiltrosExportLlamadosDesdeGet(array $get, array $preceptorCids): array
    {
        return $this->construirFiltrosExportLlamadosDesdeFuente($get, $preceptorCids);
    }

    /**
     * Misma lógica que la exportación por GET, para POST (API) o arrays equivalentes.
     *
     * @param array<string, mixed> $fuente
     * @param list<int> $preceptorCids
     *
     * @return array<string, mixed>
     */
    public function construirFiltrosExportLlamadosDesdeFuente(array $fuente, array $preceptorCids): array
    {
        $filtros = [
            'preceptor_curso_ids' => $preceptorCids,
        ];

        $filtros['fecha_desde'] = !empty($fuente['fecha_desde']) ? (string) $fuente['fecha_desde'] : date('Y-m-01');
        $filtros['fecha_hasta'] = !empty($fuente['fecha_hasta']) ? (string) $fuente['fecha_hasta'] : date('Y-m-d');

        if (!empty($fuente['curso'])) {
            $filtros['curso_id'] = (int) $fuente['curso'];
        }

        if (!empty($fuente['estudiante'])) {
            $filtros['estudiante_id'] = (int) $fuente['estudiante'];
        }

        if (!empty($fuente['motivo'])) {
            $filtros['motivo'] = (string) $fuente['motivo'];
        }

        if (isset($fuente['tiene_sancion'])) {
            $ts = filter_var($fuente['tiene_sancion'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($ts !== null) {
                $filtros['tiene_sancion'] = $ts;
            }
        }

        return $filtros;
    }

    /**
     * Encola exportación de llamados a Excel (procesada por {@see ExportReportJob}).
     *
     * @param array<string, mixed> $filtros
     *
     * @return array<string, mixed>
     */
    public function solicitarExportacionAsincrona(string $tipo, string $formato, array $filtros, int $usuarioId): array
    {
        try {
            if ($tipo !== 'llamados' || $formato !== 'excel') {
                throw new \InvalidArgumentException('Solo está habilitada la exportación asíncrona de llamados a Excel.');
            }

            $this->validarParametrosExportacion($tipo, $formato);
            $filtros = $this->validarFiltrosPorTipo($tipo, $filtros);

            return $this->servicioReportes->solicitarExportacionEncolada($usuarioId, $tipo, $formato, $filtros);
        } catch (\Throwable $e) {
            return $this->handleError($e, [
                'action' => 'solicitar_exportacion_asincrona',
                'tipo' => $tipo,
                'formato' => $formato,
            ]);
        }
    }

    /**
     * Exportar reporte a formato específico
     */
    public function exportarReporte(string $tipo, string $formato = 'pdf', array $filtros = []): array
    {
        try {
            // Validar parámetros
            $this->validarParametrosExportacion($tipo, $formato);
            
            // Validar filtros según tipo
            $filtros = $this->validarFiltrosPorTipo($tipo, $filtros);
            
            return $this->servicioReportes->exportarReporte($tipo, $filtros, $formato);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'exportar_reporte',
                'tipo' => $tipo,
                'formato' => $formato,
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Obtener reportes generados recientemente
     */
    public function reportesRecientes(int $limite = 10): array
    {
        try {
            $directorio = __DIR__ . '/../../reports/';
            
            if (!is_dir($directorio)) {
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0
                ];
            }

            $archivos = glob($directorio . '*');
            $reportes = [];

            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    $info = pathinfo($archivo);
                    $reportes[] = [
                        'nombre' => $info['basename'],
                        'tamaño' => filesize($archivo),
                        'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($archivo)),
                        'formato' => $info['extension']
                    ];
                }
            }

            // Ordenar por fecha de modificación (más recientes primero)
            usort($reportes, function($a, $b) {
                return strtotime($b['fecha_modificacion']) - strtotime($a['fecha_modificacion']);
            });

            // Limitar resultados
            $reportes = array_slice($reportes, 0, $limite);

            return [
                'success' => true,
                'data' => $reportes,
                'total' => count($reportes)
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'reportes_recientes',
                'limite' => $limite
            ]);
        }
    }

    /**
     * Descargar archivo de reporte
     */
    public function descargarReporte(string $nombreArchivo): array
    {
        try {
            $directorio = __DIR__ . '/../../reports/';
            $rutaCompleta = $directorio . basename($nombreArchivo);

            if (!file_exists($rutaCompleta)) {
                return [
                    'success' => false,
                    'error' => 'Archivo no encontrado'
                ];
            }

            // Validar que el archivo esté en el directorio permitido (seguridad)
            $rutaReal = realpath($rutaCompleta);
            $directorioReal = realpath($directorio);
            
            if (!$rutaReal || strpos($rutaReal, $directorioReal) !== 0) {
                return [
                    'success' => false,
                    'error' => 'Acceso no autorizado al archivo'
                ];
            }

            return [
                'success' => true,
                'archivo' => $nombreArchivo,
                'ruta' => $rutaCompleta,
                'tamaño' => filesize($rutaCompleta),
                'tipo_mime' => $this->obtenerTipoMime($rutaCompleta)
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'descargar_reporte',
                'archivo' => $nombreArchivo
            ]);
        }
    }

    /**
     * Eliminar archivo de reporte
     */
    public function eliminarReporte(string $nombreArchivo): array
    {
        try {
            $directorio = __DIR__ . '/../../reports/';
            $rutaCompleta = $directorio . basename($nombreArchivo);

            if (!file_exists($rutaCompleta)) {
                return [
                    'success' => false,
                    'error' => 'Archivo no encontrado'
                ];
            }

            // Validar que el archivo esté en el directorio permitido (seguridad)
            $rutaReal = realpath($rutaCompleta);
            $directorioReal = realpath($directorio);
            
            if (!$rutaReal || strpos($rutaReal, $directorioReal) !== 0) {
                return [
                    'success' => false,
                    'error' => 'Acceso no autorizado al archivo'
                ];
            }

            if (unlink($rutaCompleta)) {
                $this->logEvent('INFO', 'Reporte eliminado exitosamente', [
                    'archivo' => $nombreArchivo
                ]);

                return [
                    'success' => true,
                    'message' => 'Reporte eliminado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el archivo'
                ];
            }

        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'eliminar_reporte',
                'archivo' => $nombreArchivo
            ]);
        }
    }

    /**
     * Validar filtros para reporte de estudiantes
     */
    private function validarFiltrosEstudiantes(array $filtros): array
    {
        $filtrosValidos = [];

        if (!empty($filtros['curso_id']) && is_numeric($filtros['curso_id'])) {
            $filtrosValidos['curso_id'] = (int)$filtros['curso_id'];
        }

        if (!empty($filtros['especialidad_id']) && is_numeric($filtros['especialidad_id'])) {
            $filtrosValidos['especialidad_id'] = (int)$filtros['especialidad_id'];
        }

        if (!empty($filtros['fecha_desde']) && $this->validarFecha($filtros['fecha_desde'])) {
            $filtrosValidos['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta']) && $this->validarFecha($filtros['fecha_hasta'])) {
            $filtrosValidos['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (isset($filtros['activo'])) {
            $filtrosValidos['activo'] = (bool)$filtros['activo'];
        }

        return $filtrosValidos;
    }

    /**
     * Validar filtros para reporte de profesores
     */
    private function validarFiltrosProfesores(array $filtros): array
    {
        $filtrosValidos = [];

        if (!empty($filtros['especialidad_id']) && is_numeric($filtros['especialidad_id'])) {
            $filtrosValidos['especialidad_id'] = (int)$filtros['especialidad_id'];
        }

        if (isset($filtros['activo'])) {
            $filtrosValidos['activo'] = (bool)$filtros['activo'];
        }

        return $filtrosValidos;
    }

    /**
     * Validar filtros para análisis de rendimiento
     */
    private function validarFiltrosRendimiento(array $filtros): array
    {
        $filtrosValidos = [];

        if (!empty($filtros['curso_id']) && is_numeric($filtros['curso_id'])) {
            $filtrosValidos['curso_id'] = (int)$filtros['curso_id'];
        }

        if (!empty($filtros['materia_id']) && is_numeric($filtros['materia_id'])) {
            $filtrosValidos['materia_id'] = (int)$filtros['materia_id'];
        }

        if (!empty($filtros['bimestre']) && in_array($filtros['bimestre'], ['1', '2', '3', '4'])) {
            $filtrosValidos['bimestre'] = $filtros['bimestre'];
        }

        if (!empty($filtros['fecha_desde']) && $this->validarFecha($filtros['fecha_desde'])) {
            $filtrosValidos['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta']) && $this->validarFecha($filtros['fecha_hasta'])) {
            $filtrosValidos['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        return $filtrosValidos;
    }

    /**
     * Validar filtros para análisis de disciplina
     */
    private function validarFiltrosDisciplina(array $filtros): array
    {
        $filtrosValidos = [];

        if (!empty($filtros['curso_id']) && is_numeric($filtros['curso_id'])) {
            $filtrosValidos['curso_id'] = (int)$filtros['curso_id'];
        }

        if (!empty($filtros['fecha_desde']) && $this->validarFecha($filtros['fecha_desde'])) {
            $filtrosValidos['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta']) && $this->validarFecha($filtros['fecha_hasta'])) {
            $filtrosValidos['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['motivo']) && is_string($filtros['motivo'])) {
            $filtrosValidos['motivo'] = trim($filtros['motivo']);
        }

        return $filtrosValidos;
    }

    /**
     * Validar parámetros de exportación
     */
    private function validarParametrosExportacion(string $tipo, string $formato): void
    {
        $tiposValidos = ['estudiantes', 'profesores', 'rendimiento', 'discipline', 'estadisticas', 'llamados'];
        $formatosValidos = ['pdf', 'excel', 'csv', 'json'];

        if (!in_array($tipo, $tiposValidos)) {
            throw new \InvalidArgumentException("Tipo de reporte no válido: {$tipo}");
        }

        if (!in_array($formato, $formatosValidos)) {
            throw new \InvalidArgumentException("Formato no válido: {$formato}");
        }

        if ($tipo === 'llamados' && $formato === 'pdf') {
            throw new \InvalidArgumentException("El reporte de llamados no admite exportación en PDF por el momento.");
        }
    }

    /**
     * Validar filtros según tipo de reporte
     */
    private function validarFiltrosPorTipo(string $tipo, array $filtros): array
    {
        switch ($tipo) {
            case 'estudiantes':
                return $this->validarFiltrosEstudiantes($filtros);
            case 'profesores':
                return $this->validarFiltrosProfesores($filtros);
            case 'rendimiento':
                return $this->validarFiltrosRendimiento($filtros);
            case 'discipline':
                return $this->validarFiltrosDisciplina($filtros);
            case 'estadisticas':
                return []; // No requiere filtros
            case 'llamados':
                return $this->validarFiltrosLlamados($filtros);
            default:
                return [];
        }
    }

    /**
     * Validar filtros para reporte de llamados de atención
     */
    private function validarFiltrosLlamados(array $filtros): array
    {
        $filtrosValidos = [];

        if (!empty($filtros['fecha_desde']) && $this->validarFecha($filtros['fecha_desde'])) {
            $filtrosValidos['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta']) && $this->validarFecha($filtros['fecha_hasta'])) {
            $filtrosValidos['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['curso_id']) && is_numeric($filtros['curso_id'])) {
            $filtrosValidos['curso_id'] = (int)$filtros['curso_id'];
        } elseif (!empty($filtros['curso']) && is_numeric($filtros['curso'])) {
            $filtrosValidos['curso_id'] = (int)$filtros['curso'];
        }

        if (!empty($filtros['estudiante_id']) && is_numeric($filtros['estudiante_id'])) {
            $filtrosValidos['estudiante_id'] = (int)$filtros['estudiante_id'];
        } elseif (!empty($filtros['estudiante']) && is_numeric($filtros['estudiante'])) {
            $filtrosValidos['estudiante_id'] = (int)$filtros['estudiante'];
        }

        if (!empty($filtros['motivo']) && is_string($filtros['motivo'])) {
            $filtrosValidos['motivo'] = trim($filtros['motivo']);
        }

        if (isset($filtros['tiene_sancion'])) {
            $valorSancion = filter_var($filtros['tiene_sancion'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($valorSancion !== null) {
                $filtrosValidos['tiene_sancion'] = $valorSancion;
            }
        }

        if (isset($filtros['preceptor_curso_ids']) && is_array($filtros['preceptor_curso_ids'])) {
            $filtrosValidos['preceptor_curso_ids'] = array_values(array_filter(
                array_map('intval', $filtros['preceptor_curso_ids']),
                static fn (int $id): bool => $id > 0
            ));
        }

        return $filtrosValidos;
    }

    /**
     * Validar formato de fecha
     */
    private function validarFecha(string $fecha): bool
    {
        $utilityService = new \SistemaAdmin\Services\UtilityService($this->database);
        return $utilityService->validateDate($fecha, 'Y-m-d');
    }

    /**
     * Obtener tipo MIME del archivo
     */
    private function obtenerTipoMime(string $rutaArchivo): string
    {
        $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));
        
        $tiposMime = [
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'json' => 'application/json'
        ];

        return $tiposMime[$extension] ?? 'application/octet-stream';
    }
}
