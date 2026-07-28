<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;

/**
 * Servicio de Consultas Avanzadas
 * 
 * Proporciona funcionalidades para consultas complejas,
 * análisis estadísticos avanzados y agregaciones de datos
 */
class ServicioConsultasAvanzadas extends BaseService
{
    private array $cacheQueries = [];
    private int $cacheTimeout = 300; // 5 minutos

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
    }

    /**
     * Análisis de tendencias por período
     */
    public function analizarTendenciasPorPeriodo(string $tipo, array $filtros = []): array
    {
        try {
            switch ($tipo) {
                case 'rendimiento':
                    return $this->analizarTendenciasRendimiento($filtros);
                case 'discipline':
                    return $this->analizarTendenciasDisciplina($filtros);
                case 'matricula':
                    return $this->analizarTendenciasMatricula($filtros);
                default:
                    throw new \InvalidArgumentException("Tipo de tendencia no válido: {$tipo}");
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error analizando tendencias', [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'filtros' => $filtros
            ]);
            return $this->handleError($e, ['action' => 'analizar_tendencias']);
        }
    }

    /**
     * Análisis comparativo entre períodos
     */
    public function analisisComparativo(array $configuracion): array
    {
        try {
            $periodo1 = $configuracion['periodo1'] ?? [];
            $periodo2 = $configuracion['periodo2'] ?? [];
            $metrica = $configuracion['metrica'] ?? 'rendimiento';

            if (empty($periodo1) || empty($periodo2)) {
                throw new \InvalidArgumentException('Se requieren ambos períodos para el análisis comparativo');
            }

            $datos1 = $this->obtenerDatosPeriodo($metrica, $periodo1);
            $datos2 = $this->obtenerDatosPeriodo($metrica, $periodo2);

            $comparacion = $this->generarComparacion($datos1, $datos2, $metrica);

            return [
                'success' => true,
                'data' => [
                    'periodo1' => $datos1,
                    'periodo2' => $datos2,
                    'comparacion' => $comparacion
                ],
                'configuracion' => $configuracion
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error en análisis comparativo', [
                'error' => $e->getMessage(),
                'configuracion' => $configuracion
            ]);
            return $this->handleError($e, ['action' => 'analisis_comparativo']);
        }
    }

    /**
     * Análisis de correlaciones
     */
    public function analizarCorrelaciones(array $variables): array
    {
        try {
            if (count($variables) < 2) {
                throw new \InvalidArgumentException('Se requieren al menos 2 variables para análisis de correlación');
            }

            $datos = $this->obtenerDatosCorrelacion($variables);
            $correlaciones = $this->calcularCorrelaciones($datos, $variables);

            return [
                'success' => true,
                'data' => [
                    'variables' => $variables,
                    'correlaciones' => $correlaciones,
                    'interpretacion' => $this->interpretarCorrelaciones($correlaciones)
                ]
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error analizando correlaciones', [
                'error' => $e->getMessage(),
                'variables' => $variables
            ]);
            return $this->handleError($e, ['action' => 'analizar_correlaciones']);
        }
    }

    /**
     * Análisis predictivo básico
     */
    public function analisisPredictivo(string $metrica, array $configuracion): array
    {
        try {
            $periodos = $configuracion['periodos'] ?? 12; // meses
            $tipoPrediccion = $configuracion['tipo'] ?? 'lineal';

            $datosHistoricos = $this->obtenerDatosHistoricos($metrica, $periodos);
            
            if (count($datosHistoricos) < 3) {
                throw new \Exception('Se requieren al menos 3 puntos de datos históricos para predicción');
            }

            $prediccion = $this->generarPrediccion($datosHistoricos, $tipoPrediccion, $configuracion['futuro'] ?? 6);

            return [
                'success' => true,
                'data' => [
                    'datos_historicos' => $datosHistoricos,
                    'prediccion' => $prediccion,
                    'confianza' => $this->calcularConfianzaPrediccion($datosHistoricos),
                    'tipo' => $tipoPrediccion
                ],
                'configuracion' => $configuracion
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error en análisis predictivo', [
                'error' => $e->getMessage(),
                'metrica' => $metrica,
                'configuracion' => $configuracion
            ]);
            return $this->handleError($e, ['action' => 'analisis_predictivo']);
        }
    }

    /**
     * Análisis de segmentación
     */
    public function analizarSegmentacion(string $entidad, array $criterios): array
    {
        try {
            switch ($entidad) {
                case 'estudiantes':
                    return $this->segmentarEstudiantes($criterios);
                case 'profesores':
                    return $this->segmentarProfesores($criterios);
                case 'cursos':
                    return $this->segmentarCursos($criterios);
                default:
                    throw new \InvalidArgumentException("Entidad no válida para segmentación: {$entidad}");
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error en análisis de segmentación', [
                'error' => $e->getMessage(),
                'entidad' => $entidad,
                'criterios' => $criterios
            ]);
            return $this->handleError($e, ['action' => 'analizar_segmentacion']);
        }
    }

    /**
     * Consulta personalizada con SQL
     */
    public function consultaPersonalizada(string $sql, array $parametros = []): array
    {
        try {
            // Validar que la consulta sea de solo lectura
            if (!$this->validarConsultaSoloLectura($sql)) {
                throw new \SecurityException('Solo se permiten consultas de lectura');
            }

            // Validar que no contenga palabras peligrosas
            if (!$this->validarConsultaSegura($sql)) {
                throw new \SecurityException('La consulta contiene elementos no permitidos');
            }

            // Ejecutar consulta
            $resultados = $this->database->fetchAll($sql, $parametros);

            return [
                'success' => true,
                'data' => $resultados,
                'total_registros' => count($resultados),
                'sql' => $sql,
                'parametros' => $parametros
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error en consulta personalizada', [
                'error' => $e->getMessage(),
                'sql' => $sql,
                'parametros' => $parametros
            ]);
            return $this->handleError($e, ['action' => 'consulta_personalizada']);
        }
    }

    /**
     * Análisis de rendimiento del sistema
     */
    public function analizarRendimientoSistema(): array
    {
        try {
            $metricas = [];

            // Consultas más lentas
            $sqlConsultasLentas = "
                SELECT 
                    query,
                    avg_timer/1000000000 as tiempo_promedio_segundos,
                    count_star as ejecuciones,
                    sum_timer/1000000000 as tiempo_total_segundos
                FROM performance_schema.events_statements_summary_by_digest
                WHERE schema_name = DATABASE()
                ORDER BY avg_timer DESC
                LIMIT 10
            ";

            try {
                $consultasLentas = $this->database->fetchAll($sqlConsultasLentas);
                $metricas['consultas_lentas'] = $consultasLentas;
            } catch (\Exception $e) {
                $metricas['consultas_lentas'] = [];
            }

            // Estadísticas de tablas
            $sqlEstadisticasTablas = "
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                    ROUND((index_length / 1024 / 1024), 2) AS index_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ";

            $estadisticasTablas = $this->database->fetchAll($sqlEstadisticasTablas);
            $metricas['estadisticas_tablas'] = $estadisticasTablas;

            // Métricas de conexiones
            $sqlConexiones = "
                SELECT 
                    COUNT(*) as conexiones_activas,
                    MAX(connections) as max_conexiones,
                    VARIABLE_VALUE as conexiones_maximas
                FROM information_schema.processlist,
                     (SELECT VARIABLE_VALUE FROM information_schema.global_variables WHERE VARIABLE_NAME = 'max_connections') as max_conn
                WHERE db = DATABASE()
            ";

            $conexiones = $this->database->fetch($sqlConexiones);
            $metricas['conexiones'] = $conexiones;

            return [
                'success' => true,
                'data' => $metricas,
                'generado_en' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error analizando rendimiento del sistema', [
                'error' => $e->getMessage()
            ]);
            return $this->handleError($e, ['action' => 'analizar_rendimiento_sistema']);
        }
    }

    /**
     * Análisis de tendencias de rendimiento
     */
    private function analizarTendenciasRendimiento(array $filtros): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(n.fecha, '%Y-%m') as mes,
                AVG(n.calificacion) as promedio_mensual,
                COUNT(n.id) as total_notas,
                COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) as aprobados,
                COUNT(CASE WHEN n.calificacion< 7 THEN 1 END) as desaprobados,
                ROUND((COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) * 100.0 / COUNT(n.id)), 2) as porcentaje_aprobacion
            FROM notas n
            LEFT JOIN estudiantes e ON n.estudiante_id = e.id
            WHERE n.fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        ";

        $params = [];
        if (!empty($filtros['curso_id'])) {
            $sql .= " AND e.curso_id = ?";
            $params[] = $filtros['curso_id'];
        }

        $sql .= " GROUP BY DATE_FORMAT(n.fecha, '%Y-%m') ORDER BY mes";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Análisis de tendencias de disciplina
     */
    private function analizarTendenciasDisciplina(array $filtros): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(l.fecha, '%Y-%m') as mes,
                COUNT(l.id) as total_llamados,
                COUNT(DISTINCT l.estudiante_id) as estudiantes_unicos,
                COUNT(CASE WHEN l.sancion IS NOT NULL THEN 1 END) as con_sancion,
                AVG(CASE WHEN l.sancion IS NOT NULL THEN 1 ELSE 0 END) as porcentaje_sanciones
            FROM llamados_atencion l
            LEFT JOIN estudiantes e ON l.estudiante_id = e.id
            WHERE l.fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        ";

        $params = [];
        if (!empty($filtros['curso_id'])) {
            $sql .= " AND e.curso_id = ?";
            $params[] = $filtros['curso_id'];
        }

        $sql .= " GROUP BY DATE_FORMAT(l.fecha, '%Y-%m') ORDER BY mes";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Análisis de tendencias de matrícula
     */
    private function analizarTendenciasMatricula(array $filtros): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(e.fecha_ingreso, '%Y-%m') as mes,
                COUNT(e.id) as nuevos_estudiantes,
                COUNT(CASE WHEN e.activo = 1 THEN 1 END) as estudiantes_activos,
                COUNT(CASE WHEN e.activo = 0 THEN 1 END) as estudiantes_inactivos
            FROM estudiantes e
            WHERE e.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
        ";

        $params = [];
        if (!empty($filtros['especialidad_id'])) {
            $sql .= " AND e.curso_id IN (SELECT id FROM cursos WHERE especialidad_id = ?)";
            $params[] = $filtros['especialidad_id'];
        }

        $sql .= " GROUP BY DATE_FORMAT(e.fecha_ingreso, '%Y-%m') ORDER BY mes";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Obtener datos para un período específico
     */
    private function obtenerDatosPeriodo(string $metrica, array $periodo): array
    {
        $fechaInicio = $periodo['fecha_inicio'] ?? date('Y-m-d', strtotime('-1 year'));
        $fechaFin = $periodo['fecha_fin'] ?? date('Y-m-d');

        switch ($metrica) {
            case 'rendimiento':
                return $this->obtenerDatosRendimientoPeriodo($fechaInicio, $fechaFin);
            case 'discipline':
                return $this->obtenerDatosDisciplinaPeriodo($fechaInicio, $fechaFin);
            default:
                return [];
        }
    }

    /**
     * Obtener datos de rendimiento para un período
     */
    private function obtenerDatosRendimientoPeriodo(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT 
                AVG(n.calificacion) as promedio_general,
                COUNT(n.id) as total_notas,
                COUNT(CASE WHEN n.calificacion >= 7 THEN 1 END) as aprobados,
                COUNT(CASE WHEN n.calificacion < 7 THEN 1 END) as desaprobados,
                MIN(n.calificacion) as nota_minima,
                MAX(n.calificacion) as nota_maxima
            FROM notas n
            WHERE n.fecha BETWEEN ? AND ?
        ";

        return $this->database->fetch($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Obtener datos de disciplina para un período
     */
    private function obtenerDatosDisciplinaPeriodo(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT 
                COUNT(l.id) as total_llamados,
                COUNT(DISTINCT l.estudiante_id) as estudiantes_unicos,
                COUNT(CASE WHEN l.sancion IS NOT NULL THEN 1 END) as con_sancion
            FROM llamados_atencion l
            WHERE l.fecha BETWEEN ? AND ?
        ";

        return $this->database->fetch($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Generar comparación entre dos períodos
     */
    private function generarComparacion(array $datos1, array $datos2, string $metrica): array
    {
        $comparacion = [];

        foreach ($datos1 as $clave => $valor1) {
            $valor2 = $datos2[$clave] ?? 0;
            
            if (is_numeric($valor1) && is_numeric($valor2) && $valor2 != 0) {
                $diferencia = $valor1 - $valor2;
                $porcentaje = ($diferencia / $valor2) * 100;
                
                $comparacion[$clave] = [
                    'periodo1' => $valor1,
                    'periodo2' => $valor2,
                    'diferencia' => $diferencia,
                    'porcentaje_cambio' => round($porcentaje, 2),
                    'tendencia' => $diferencia > 0 ? 'aumento' : ($diferencia < 0 ? 'disminucion' : 'sin_cambio')
                ];
            }
        }

        return $comparacion;
    }

    /**
     * Obtener datos para análisis de correlación
     */
    private function obtenerDatosCorrelacion(array $variables): array
    {
        // Implementación básica - se puede expandir según necesidades
        $sql = "
            SELECT 
                e.id,
                AVG(n.calificacion) as promedio_estudiante,
                COUNT(l.id) as total_llamados,
                DATEDIFF(CURDATE(), e.fecha_ingreso) as dias_en_instituto,
                c.id as curso_id
            FROM estudiantes e
            LEFT JOIN notas n ON e.id = n.estudiante_id
            LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
            LEFT JOIN cursos c ON e.curso_id = c.id
            WHERE e.activo = 1
            GROUP BY e.id, e.fecha_ingreso, c.id
        ";

        return $this->database->fetchAll($sql);
    }

    /**
     * Calcular correlaciones entre variables
     */
    private function calcularCorrelaciones(array $datos, array $variables): array
    {
        $correlaciones = [];

        for ($i = 0; $i < count($variables); $i++) {
            for ($j = $i + 1; $j < count($variables); $j++) {
                $var1 = $variables[$i];
                $var2 = $variables[$j];
                
                $correlacion = $this->calcularCorrelacionPearson($datos, $var1, $var2);
                
                $correlaciones[] = [
                    'variable1' => $var1,
                    'variable2' => $var2,
                    'correlacion' => round($correlacion, 4),
                    'fuerza' => $this->interpretarFuerzaCorrelacion($correlacion)
                ];
            }
        }

        return $correlaciones;
    }

    /**
     * Calcular correlación de Pearson
     */
    private function calcularCorrelacionPearson(array $datos, string $var1, string $var2): float
    {
        $n = count($datos);
        if ($n < 2) return 0;

        $sum1 = $sum2 = $sum1Sq = $sum2Sq = $pSum = 0;

        foreach ($datos as $fila) {
            $val1 = $fila[$var1] ?? 0;
            $val2 = $fila[$var2] ?? 0;
            
            $sum1 += $val1;
            $sum2 += $val2;
            $sum1Sq += $val1 * $val1;
            $sum2Sq += $val2 * $val2;
            $pSum += $val1 * $val2;
        }

        $num = $pSum - ($sum1 * $sum2 / $n);
        $den = sqrt(($sum1Sq - $sum1 * $sum1 / $n) * ($sum2Sq - $sum2 * $sum2 / $n));

        return $den == 0 ? 0 : $num / $den;
    }

    /**
     * Interpretar fuerza de correlación
     */
    private function interpretarFuerzaCorrelacion(float $correlacion): string
    {
        $abs = abs($correlacion);
        
        if ($abs >= 0.9) return 'muy_fuerte';
        if ($abs >= 0.7) return 'fuerte';
        if ($abs >= 0.5) return 'moderada';
        if ($abs >= 0.3) return 'debil';
        return 'muy_debil';
    }

    /**
     * Interpretar correlaciones
     */
    private function interpretarCorrelaciones(array $correlaciones): array
    {
        $interpretaciones = [];
        
        foreach ($correlaciones as $corr) {
            $interpretacion = "Correlación {$corr['fuerza']} ";
            
            if ($corr['correlacion'] > 0) {
                $interpretacion .= "positiva";
            } else {
                $interpretacion .= "negativa";
            }
            
            $interpretacion .= " entre {$corr['variable1']} y {$corr['variable2']}";
            
            $interpretaciones[] = $interpretacion;
        }
        
        return $interpretaciones;
    }

    /**
     * Obtener datos históricos para predicción
     */
    private function obtenerDatosHistoricos(string $metrica, int $periodos): array
    {
        switch ($metrica) {
            case 'rendimiento':
                $sql = "
                    SELECT 
                        DATE_FORMAT(n.fecha, '%Y-%m') as periodo,
                        AVG(n.calificacion) as valor
                    FROM notas n
                    WHERE n.fecha >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    GROUP BY DATE_FORMAT(n.fecha, '%Y-%m')
                    ORDER BY periodo
                ";
                break;
            case 'matricula':
                $sql = "
                    SELECT 
                        DATE_FORMAT(fecha_ingreso, '%Y-%m') as periodo,
                        COUNT(*) as valor
                    FROM estudiantes
                    WHERE fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    GROUP BY DATE_FORMAT(fecha_ingreso, '%Y-%m')
                    ORDER BY periodo
                ";
                break;
            default:
                return [];
        }

        return $this->database->fetchAll($sql, [$periodos]);
    }

    /**
     * Generar predicción simple
     */
    private function generarPrediccion(array $datosHistoricos, string $tipo, int $periodosFuturo): array
    {
        if (count($datosHistoricos) < 2) {
            return [];
        }

        $valores = array_column($datosHistoricos, 'valor');
        $predicciones = [];

        // Predicción lineal simple
        if ($tipo === 'lineal') {
            $tendencia = $this->calcularTendenciaLineal($valores);
            $ultimoValor = end($valores);
            
            for ($i = 1; $i <= $periodosFuturo; $i++) {
                $prediccion = $ultimoValor + ($tendencia * $i);
                $predicciones[] = [
                    'periodo' => $i,
                    'valor_predicho' => round($prediccion, 2)
                ];
            }
        }

        return $predicciones;
    }

    /**
     * Calcular tendencia lineal simple
     */
    private function calcularTendenciaLineal(array $valores): float
    {
        $n = count($valores);
        if ($n < 2) return 0;

        $suma = 0;
        for ($i = 1; $i < $n; $i++) {
            $suma += $valores[$i] - $valores[$i - 1];
        }

        return $suma / ($n - 1);
    }

    /**
     * Calcular confianza de predicción
     */
    private function calcularConfianzaPrediccion(array $datosHistoricos): float
    {
        $valores = array_column($datosHistoricos, 'valor');
        $n = count($valores);
        
        if ($n < 3) return 0.5;
        
        $promedio = array_sum($valores) / $n;
        $varianza = 0;
        
        foreach ($valores as $valor) {
            $varianza += pow($valor - $promedio, 2);
        }
        
        $varianza /= $n;
        $desviacion = sqrt($varianza);
        $coeficienteVariacion = $desviacion / $promedio;
        
        // Confianza basada en coeficiente de variación
        return max(0, min(1, 1 - $coeficienteVariacion));
    }

    /**
     * Segmentar estudiantes
     */
    private function segmentarEstudiantes(array $criterios): array
    {
        $sql = "
            SELECT 
                e.id,
                e.apellido,
                e.nombre,
                AVG(n.calificacion) as promedio,
                COUNT(l.id) as llamados,
                c.nombre as curso
            FROM estudiantes e
            LEFT JOIN notas n ON e.id = n.estudiante_id
            LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
            LEFT JOIN cursos c ON e.curso_id = c.id
            WHERE e.activo = 1
            GROUP BY e.id, e.apellido, e.nombre, c.nombre
        ";

        $estudiantes = $this->database->fetchAll($sql);
        
        return $this->aplicarSegmentacion($estudiantes, $criterios);
    }

    /**
     * Segmentar profesores
     */
    private function segmentarProfesores(array $criterios): array
    {
        $sql = "
            SELECT 
                p.id,
                p.apellido,
                p.nombre,
                COUNT(DISTINCT n.materia_id) as materias,
                COUNT(l.id) as llamados_realizados,
                AVG(n.calificacion) as promedio_calificaciones
            FROM profesores p
            LEFT JOIN notas n ON p.id = n.profesor_id
            LEFT JOIN llamados_atencion l ON p.id = l.usuario_id
            WHERE p.activo = 1
            GROUP BY p.id, p.apellido, p.nombre
        ";

        $profesores = $this->database->fetchAll($sql);
        
        return $this->aplicarSegmentacion($profesores, $criterios);
    }

    /**
     * Segmentar cursos
     */
    private function segmentarCursos(array $criterios): array
    {
        $sql = "
            SELECT 
                c.id,
                c.nombre,
                esp.nombre as especialidad,
                COUNT(e.id) as estudiantes,
                AVG(n.calificacion) as promedio_general,
                COUNT(l.id) as total_llamados
            FROM cursos c
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN estudiantes e ON c.id = e.curso_id
            LEFT JOIN notas n ON e.id = n.estudiante_id
            LEFT JOIN llamados_atencion l ON e.id = l.estudiante_id
            GROUP BY c.id, c.nombre, esp.nombre
        ";

        $cursos = $this->database->fetchAll($sql);
        
        return $this->aplicarSegmentacion($cursos, $criterios);
    }

    /**
     * Aplicar criterios de segmentación
     */
    private function aplicarSegmentacion(array $datos, array $criterios): array
    {
        $segmentos = [];

        foreach ($criterios as $nombreSegmento => $criterio) {
            $segmentos[$nombreSegmento] = [];
            
            foreach ($datos as $item) {
                if ($this->cumpleCriterio($item, $criterio)) {
                    $segmentos[$nombreSegmento][] = $item;
                }
            }
        }

        return $segmentos;
    }

    /**
     * Verificar si un item cumple un criterio
     */
    private function cumpleCriterio(array $item, array $criterio): bool
    {
        foreach ($criterio as $campo => $condicion) {
            $valor = $item[$campo] ?? null;
            
            if (isset($condicion['min']) && $valor < $condicion['min']) {
                return false;
            }
            
            if (isset($condicion['max']) && $valor > $condicion['max']) {
                return false;
            }
            
            if (isset($condicion['equals']) && $valor != $condicion['equals']) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validar que la consulta sea de solo lectura
     */
    private function validarConsultaSoloLectura(string $sql): bool
    {
        $sql = strtoupper(trim($sql));
        $palabrasProhibidas = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE'];
        
        foreach ($palabrasProhibidas as $palabra) {
            if (strpos($sql, $palabra) !== false) {
                return false;
            }
        }
        
        return strpos($sql, 'SELECT') === 0;
    }

    /**
     * Validar que la consulta sea segura
     */
    private function validarConsultaSegura(string $sql): bool
    {
        $sql = strtoupper($sql);
        $palabrasPeligrosas = ['INFORMATION_SCHEMA', 'MYSQL', 'PERFORMANCE_SCHEMA', 'SYS'];
        
        foreach ($palabrasPeligrosas as $palabra) {
            if (strpos($sql, $palabra) !== false) {
                return false;
            }
        }
        
        return true;
    }
}
