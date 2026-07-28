<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioReportes;
use SistemaAdmin\Services\ServicioConsultasAvanzadas;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Controller para el Dashboard de Análisis Estadístico
 * 
 * Proporciona endpoints para el dashboard avanzado con métricas,
 * gráficos y análisis en tiempo real
 */
class DashboardAnalyticsController extends BaseService
{
    private ServicioReportes $servicioReportes;
    private ServicioConsultasAvanzadas $servicioConsultas;

    public function __construct(
        DatabaseInterface $database, 
        ServicioReportes $servicioReportes,
        ServicioConsultasAvanzadas $servicioConsultas
    ) {
        parent::__construct($database);
        $this->servicioReportes = $servicioReportes;
        $this->servicioConsultas = $servicioConsultas;
    }

    /**
     * Obtener dashboard completo con todas las métricas
     */
    public function obtenerDashboardCompleto(array $filtros = []): array
    {
        try {
            $dashboard = [
                'success' => true,
                'data' => [],
                'generado_en' => date('Y-m-d H:i:s')
            ];

            // Ejecutar todas las consultas en paralelo (simulado)
            $dashboard['data']['metricas_generales'] = $this->obtenerMetricasGenerales();
            $dashboard['data']['tendencias_rendimiento'] = $this->obtenerTendenciasRendimiento($filtros);
            $dashboard['data']['tendencias_disciplina'] = $this->obtenerTendenciasDisciplina($filtros);
            $dashboard['data']['analisis_comparativo'] = $this->obtenerAnalisisComparativo($filtros);
            $dashboard['data']['top_performers'] = $this->obtenerTopPerformers($filtros);
            $dashboard['data']['alertas'] = $this->obtenerAlertas($filtros);
            $dashboard['data']['graficos'] = $this->obtenerDatosGraficos($filtros);

            return $dashboard;

        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_dashboard_completo',
                'filtros' => $filtros
            ]);
        }
    }

    /**
     * Obtener métricas generales del sistema
     */
    public function obtenerMetricasGenerales(): array
    {
        try {
            $estadisticas = $this->servicioReportes->generarEstadisticasGenerales();
            
            if (!$estadisticas['success']) {
                throw new \Exception('Error obteniendo estadísticas generales');
            }

            $data = $estadisticas['data'];

            // Calcular métricas derivadas
            $metricas = [
                'estudiantes' => [
                    'total' => $data['estudiantes']['total'] ?? 0,
                    'activos' => $data['estudiantes']['activos'] ?? 0,
                    'inactivos' => $data['estudiantes']['inactivos'] ?? 0,
                    'porcentaje_activos' => $data['estudiantes']['total'] > 0 ? 
                        round((($data['estudiantes']['activos'] ?? 0) * 100) / $data['estudiantes']['total'], 2) : 0,
                    'ingresos_ultimo_ano' => $data['estudiantes']['ingresos_ultimo_ano'] ?? 0
                ],
                'profesores' => [
                    'total' => $data['profesores']['total'] ?? 0,
                    'activos' => $data['profesores']['activos'] ?? 0,
                    'inactivos' => $data['profesores']['inactivos'] ?? 0,
                    'porcentaje_activos' => $data['profesores']['total'] > 0 ? 
                        round((($data['profesores']['activos'] ?? 0) * 100) / $data['profesores']['total'], 2) : 0
                ],
                'academico' => [
                    'total_notas' => $data['notas']['total_notas'] ?? 0,
                    'promedio_general' => round($data['notas']['promedio_general'] ?? 0, 2),
                    'porcentaje_aprobacion' => $data['notas']['total_notas'] > 0 ? 
                        round((($data['notas']['aprobadas'] ?? 0) * 100) / $data['notas']['total_notas'], 2) : 0,
                    'nota_minima' => $data['notas']['nota_minima'] ?? 0,
                    'nota_maxima' => $data['notas']['nota_maxima'] ?? 0
                ],
                'discipline' => [
                    'total_llamados' => $data['llamados']['total_llamados'] ?? 0,
                    'estudiantes_con_llamados' => $data['llamados']['estudiantes_con_llamados'] ?? 0,
                    'porcentaje_con_llamados' => $data['estudiantes']['total'] > 0 ? 
                        round((($data['llamados']['estudiantes_con_llamados'] ?? 0) * 100) / $data['estudiantes']['total'], 2) : 0,
                    'con_sancion' => $data['llamados']['con_sancion'] ?? 0,
                    'porcentaje_sanciones' => $data['llamados']['total_llamados'] > 0 ? 
                        round((($data['llamados']['con_sancion'] ?? 0) * 100) / $data['llamados']['total_llamados'], 2) : 0
                ]
            ];

            return [
                'success' => true,
                'data' => $metricas,
                'generado_en' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_metricas_generales']);
        }
    }

    /**
     * Obtener tendencias de rendimiento
     */
    public function obtenerTendenciasRendimiento(array $filtros = []): array
    {
        try {
            $tendencias = $this->servicioConsultas->analizarTendenciasPorPeriodo('rendimiento', $filtros);
            
            if (!$tendencias['success']) {
                throw new \Exception('Error obteniendo tendencias de rendimiento');
            }

            // Calcular tendencia general
            $datos = $tendencias['data'] ?? [];
            $tendenciaGeneral = $this->calcularTendenciaGeneral($datos);

            return [
                'success' => true,
                'data' => [
                    'tendencias_mensuales' => $datos,
                    'tendencia_general' => $tendenciaGeneral,
                    'resumen' => $this->generarResumenTendencias($datos)
                ]
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_tendencias_rendimiento']);
        }
    }

    /**
     * Obtener tendencias de disciplina
     */
    public function obtenerTendenciasDisciplina(array $filtros = []): array
    {
        try {
            $tendencias = $this->servicioConsultas->analizarTendenciasPorPeriodo('discipline', $filtros);
            
            if (!$tendencias['success']) {
                throw new \Exception('Error obteniendo tendencias de disciplina');
            }

            $datos = $tendencias['data'] ?? [];
            $tendenciaGeneral = $this->calcularTendenciaGeneral($datos, 'total_llamados');

            return [
                'success' => true,
                'data' => [
                    'tendencias_mensuales' => $datos,
                    'tendencia_general' => $tendenciaGeneral,
                    'resumen' => $this->generarResumenTendencias($datos, 'total_llamados')
                ]
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_tendencias_disciplina']);
        }
    }

    /**
     * Obtener análisis comparativo
     */
    public function obtenerAnalisisComparativo(array $filtros = []): array
    {
        try {
            // Comparar último año vs año anterior
            $configuracion = [
                'periodo1' => [
                    'fecha_inicio' => date('Y-01-01', strtotime('-1 year')),
                    'fecha_fin' => date('Y-12-31', strtotime('-1 year'))
                ],
                'periodo2' => [
                    'fecha_inicio' => date('Y-01-01'),
                    'fecha_fin' => date('Y-m-d')
                ],
                'metrica' => 'rendimiento'
            ];

            $comparacion = $this->servicioConsultas->analisisComparativo($configuracion);
            
            if (!$comparacion['success']) {
                throw new \Exception('Error obteniendo análisis comparativo');
            }

            return $comparacion;

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_analisis_comparativo']);
        }
    }

    /**
     * Obtener top performers (mejores estudiantes/cursos)
     */
    public function obtenerTopPerformers(array $filtros = []): array
    {
        try {
            $topPerformers = [];

            // Top estudiantes por promedio
            $sqlTopEstudiantes = "
                SELECT 
                    e.id,
                    e.apellido,
                    e.nombre,
                    c.nombre as curso,
                    AVG(n.calificacion) as promedio,
                    COUNT(n.id) as total_notas
                FROM estudiantes e
                LEFT JOIN notas n ON e.id = n.estudiante_id
                LEFT JOIN cursos c ON e.curso_id = c.id
                WHERE e.activo = 1
                GROUP BY e.id, e.apellido, e.nombre, c.nombre
                HAVING total_notas >= 3
                ORDER BY promedio DESC
                LIMIT 10
            ";

            $topEstudiantes = $this->database->fetchAll($sqlTopEstudiantes);

            // Top cursos por rendimiento
            $sqlTopCursos = "
                SELECT 
                    c.id,
                    c.nombre as curso,
                    esp.nombre as especialidad,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    AVG(n.calificacion) as promedio_general,
                    COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) as aprobados,
                    COUNT(n.id) as total_notas
                FROM cursos c
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                LEFT JOIN notas n ON e.id = n.estudiante_id
                GROUP BY c.id, c.nombre, esp.nombre
                HAVING total_notas > 0
                ORDER BY promedio_general DESC
                LIMIT 10
            ";

            $topCursos = $this->database->fetchAll($sqlTopCursos);

            // Top profesores por calificaciones promedio
            $sqlTopProfesores = "
                SELECT 
                    p.id,
                    p.apellido,
                    p.nombre,
                    COUNT(DISTINCT n.materia_id) as materias,
                    AVG(n.calificacion) as promedio_calificaciones,
                    COUNT(n.id) as total_notas
                FROM profesores p
                LEFT JOIN notas n ON p.id = n.profesor_id
                WHERE p.activo = 1
                GROUP BY p.id, p.apellido, p.nombre
                HAVING total_notas > 0
                ORDER BY promedio_calificaciones DESC
                LIMIT 10
            ";

            $topProfesores = $this->database->fetchAll($sqlTopProfesores);

            return [
                'success' => true,
                'data' => [
                    'top_estudiantes' => $topEstudiantes,
                    'top_cursos' => $topCursos,
                    'top_profesores' => $topProfesores
                ]
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_top_performers']);
        }
    }

    /**
     * Obtener alertas del sistema
     */
    public function obtenerAlertas(array $filtros = []): array
    {
        try {
            $alertas = [];

            // Alertas de rendimiento bajo
            $sqlRendimientoBajo = "
                SELECT 
                    c.id,
                    c.nombre as curso,
                    AVG(n.calificacion) as promedio,
                    COUNT(n.id) as total_notas
                FROM cursos c
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                LEFT JOIN notas n ON e.id = n.estudiante_id
                GROUP BY c.id, c.nombre
                HAVING total_notas >0 AND promedio< 6
            ";

            $rendimientoBajo = $this->database->fetchAll($sqlRendimientoBajo);
            
            foreach ($rendimientoBajo as $curso) {
                $alertas[] = [
                    'tipo' => 'rendimiento_bajo',
                    'severidad' => 'alta',
                    'titulo' => 'Rendimiento bajo detectado',
                    'mensaje' => "El curso {$curso['curso']} tiene un promedio de {$curso['promedio']}",
                    'datos' => $curso,
                    'fecha' => date('Y-m-d H:i:s')
                ];
            }

            // Alertas de disciplina
            $sqlDisciplinaAlta = "
                SELECT 
                    c.id,
                    c.nombre as curso,
                    COUNT(l.id) as total_llamados,
                    COUNT(DISTINCT l.estudiante_id) as estudiantes_afectados
                FROM cursos c
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
                WHERE l.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY c.id, c.nombre
                HAVING total_llamados > 10
            ";

            $disciplinaAlta = $this->database->fetchAll($sqlDisciplinaAlta);
            
            foreach ($disciplinaAlta as $curso) {
                $alertas[] = [
                    'tipo' => 'disciplina_alta',
                    'severidad' => 'media',
                    'titulo' => 'Alto índice de llamados de atención',
                    'mensaje' => "El curso {$curso['curso']} tiene {$curso['total_llamados']} llamados en los últimos 30 días",
                    'datos' => $curso,
                    'fecha' => date('Y-m-d H:i:s')
                ];
            }

            // Alertas de estudiantes en riesgo
            $sqlEstudiantesRiesgo = "
                SELECT 
                    e.id,
                    e.apellido,
                    e.nombre,
                    c.nombre as curso,
                    AVG(n.calificacion) as promedio,
                    COUNT(l.id) as llamados
                FROM estudiantes e
                LEFT JOIN notas n ON e.id = n.estudiante_id
                LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
                LEFT JOIN cursos c ON e.curso_id = c.id
                WHERE e.activo = 1
                GROUP BY e.id, e.apellido, e.nombre, c.nombre
                HAVING promedio < 5 OR llamados > 5
                ORDER BY promedio ASC, llamados DESC
                LIMIT 20
            ";

            $estudiantesRiesgo = $this->database->fetchAll($sqlEstudiantesRiesgo);
            
            foreach ($estudiantesRiesgo as $estudiante) {
                $alertas[] = [
                    'tipo' => 'estudiante_riesgo',
                    'severidad' => 'alta',
                    'titulo' => 'Estudiante en riesgo académico',
                    'mensaje' => "{$estudiante['apellido']}, {$estudiante['nombre']} - Promedio: {$estudiante['promedio']}, Llamados: {$estudiante['llamados']}",
                    'datos' => $estudiante,
                    'fecha' => date('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'alertas' => $alertas,
                    'total_alertas' => count($alertas),
                    'por_severidad' => [
                        'alta' => count(array_filter($alertas, fn($a) => $a['severidad'] === 'alta')),
                        'media' => count(array_filter($alertas, fn($a) => $a['severidad'] === 'media')),
                        'baja' => count(array_filter($alertas, fn($a) => $a['severidad'] === 'baja'))
                    ]
                ]
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_alertas']);
        }
    }

    /**
     * Obtener datos para gráficos
     */
    public function obtenerDatosGraficos(array $filtros = []): array
    {
        try {
            $graficos = [];

            // Gráfico de distribución de notas
            $sqlDistribucionNotas = "
                SELECT 
                    CASE 
                        WHEN calificacion >= 9 THEN '9-10'
                        WHEN calificacion >= 8 THEN '8-8.9'
                        WHEN calificacion >= 7 THEN '7-7.9'
                        WHEN calificacion >= 6 THEN '6-6.9'
                        WHEN calificacion >= 4 THEN '4-5.9'
                        ELSE '1-3.9'
                    END as rango,
                    COUNT(*) as cantidad
                FROM notas
                GROUP BY 
                    CASE 
                        WHEN calificacion >= 9 THEN '9-10'
                        WHEN calificacion >= 8 THEN '8-8.9'
                        WHEN calificacion >= 7 THEN '7-7.9'
                        WHEN calificacion >= 6 THEN '6-6.9'
                        WHEN calificacion >= 4 THEN '4-5.9'
                        ELSE '1-3.9'
                    END
                ORDER BY 
                    CASE 
                        WHEN calificacion >= 9 THEN 1
                        WHEN calificacion >= 8 THEN 2
                        WHEN calificacion >= 7 THEN 3
                        WHEN calificacion >= 6 THEN 4
                        WHEN calificacion >= 4 THEN 5
                        ELSE 6
                    END
            ";

            $graficos['distribucion_notas'] = $this->database->fetchAll($sqlDistribucionNotas);

            // Gráfico de estudiantes por especialidad
            $sqlEstudiantesEspecialidad = "
                SELECT 
                    esp.nombre as especialidad,
                    COUNT(e.id) as cantidad_estudiantes
                FROM especialidades esp
                LEFT JOIN cursos c ON esp.id = c.especialidad_id
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                GROUP BY esp.id, esp.nombre
                ORDER BY cantidad_estudiantes DESC
            ";

            $graficos['estudiantes_por_especialidad'] = $this->database->fetchAll($sqlEstudiantesEspecialidad);

            // Gráfico de llamados por motivo
            $sqlLlamadosMotivo = "
                SELECT 
                    motivo,
                    COUNT(*) as cantidad
                FROM llamados_atencion
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY motivo
                ORDER BY cantidad DESC
                LIMIT 10
            ";

            $graficos['llamados_por_motivo'] = $this->database->fetchAll($sqlLlamadosMotivo);

            // Gráfico de tendencia mensual de notas
            $sqlTendenciaMensual = "
                SELECT 
                    DATE_FORMAT(fecha, '%Y-%m') as mes,
                    AVG(calificacion) as promedio_mensual,
                    COUNT(*) as total_notas
                FROM notas
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ORDER BY mes
            ";

            $graficos['tendencia_mensual_notas'] = $this->database->fetchAll($sqlTendenciaMensual);

            return [
                'success' => true,
                'data' => $graficos
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_datos_graficos']);
        }
    }

    /**
     * Calcular tendencia general
     */
    private function calcularTendenciaGeneral(array $datos, string $campo = 'promedio_mensual'): string
    {
        if (count($datos) < 2) {
            return 'insuficientes_datos';
        }

        $valores = array_column($datos, $campo);
        $primeros = array_slice($valores, 0, count($valores) / 2);
        $ultimos = array_slice($valores, count($valores) / 2);

        $promedioPrimeros = array_sum($primeros) / count($primeros);
        $promedioUltimos = array_sum($ultimos) / count($ultimos);

        $diferencia = $promedioUltimos - $promedioPrimeros;
        $porcentaje = ($diferencia / $promedioPrimeros) * 100;

        if ($porcentaje > 5) return 'creciente';
        if ($porcentaje < -5) return 'decreciente';
        return 'estable';
    }

    /**
     * Generar resumen de tendencias
     */
    private function generarResumenTendencias(array $datos, string $campo = 'promedio_mensual'): array
    {
        if (empty($datos)) {
            return [];
        }

        $valores = array_column($datos, $campo);
        $valores = array_filter($valores, fn($v) => $v !== null);

        return [
            'promedio_general' => round(array_sum($valores) / count($valores), 2),
            'valor_maximo' => max($valores),
            'valor_minimo' => min($valores),
            'total_periodos' => count($datos),
            'tendencia' => $this->calcularTendenciaGeneral($datos, $campo)
        ];
    }

    /**
     * Obtener métricas en tiempo real
     */
    public function obtenerMetricasTiempoReal(): array
    {
        try {
            $metricas = [];

            // Usuarios activos en la última hora
            $sqlUsuariosActivos = "
                SELECT COUNT(DISTINCT usuario_id) as usuarios_activos
                FROM sesiones_usuarios
                WHERE ultima_actividad >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ";

            try {
                $usuariosActivos = $this->database->fetch($sqlUsuariosActivos);
                $metricas['usuarios_activos'] = $usuariosActivos['usuarios_activos'] ?? 0;
            } catch (\Exception $e) {
                $metricas['usuarios_activos'] = 0;
            }

            // Notas cargadas hoy
            $sqlNotasHoy = "
                SELECT COUNT(*) as notas_hoy
                FROM notas
                WHERE DATE(fecha) = CURDATE()
            ";

            $notasHoy = $this->database->fetch($sqlNotasHoy);
            $metricas['notas_cargadas_hoy'] = $notasHoy['notas_hoy'] ?? 0;

            // Llamados registrados hoy
            $sqlLlamadosHoy = "
                SELECT COUNT(*) as llamados_hoy
                FROM llamados_atencion
                WHERE DATE(fecha) = CURDATE()
            ";

            $llamadosHoy = $this->database->fetch($sqlLlamadosHoy);
            $metricas['llamados_registrados_hoy'] = $llamadosHoy['llamados_hoy'] ?? 0;

            return [
                'success' => true,
                'data' => $metricas,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_metricas_tiempo_real']);
        }
    }
}
