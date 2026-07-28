<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Monitoreo y Métricas
 * 
 * Recopila métricas del sistema para monitoreo y análisis de rendimiento
 */
class MonitoringService extends BaseService
{
    private array $metrics = [];
    private string $metricsTable = 'metricas_sistema';

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
    }

    /**
     * Registrar métrica
     */
    public function registrarMetrica(string $metrica, float $valor, array $detalles = []): void
    {
        try {
            $sql = "INSERT INTO {$this->metricsTable} (fecha, metrica, valor, detalles) 
                    VALUES (CURDATE(), ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    valor = valor + VALUES(valor),
                    detalles = JSON_MERGE_PATCH(COALESCE(detalles, '{}'), VALUES(detalles))";
            
            $detallesJson = !empty($detalles) ? json_encode($detalles) : null;
            $this->database->query($sql, [$metrica, $valor, $detallesJson]);
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error registrando métrica: {$metrica}", [
                'error' => $e->getMessage(),
                'valor' => $valor,
                'detalles' => $detalles
            ]);
        }
    }

    /**
     * Incrementar contador
     */
    public function incrementarContador(string $metrica, int $incremento = 1, array $detalles = []): void
    {
        $this->registrarMetrica($metrica, $incremento, $detalles);
    }

    /**
     * Registrar tiempo de ejecución
     */
    public function registrarTiempoEjecucion(string $operacion, float $tiempoSegundos, array $detalles = []): void
    {
        $detalles['tiempo_segundos'] = $tiempoSegundos;
        $this->registrarMetrica("tiempo_{$operacion}", $tiempoSegundos, $detalles);
    }

    /**
     * Registrar uso de memoria
     */
    public function registrarUsoMemoria(string $contexto, array $detalles = []): void
    {
        $memoriaMB = memory_get_usage(true) / 1024 / 1024;
        $detalles['memoria_mb'] = $memoriaMB;
        $detalles['memoria_peak_mb'] = memory_get_peak_usage(true) / 1024 / 1024;
        
        $this->registrarMetrica("memoria_{$contexto}", $memoriaMB, $detalles);
    }

    /**
     * Registrar evento de usuario
     */
    public function registrarEventoUsuario(string $accion, int $usuarioId, array $detalles = []): void
    {
        $detalles['usuario_id'] = $usuarioId;
        $detalles['ip'] = $this->getClientIp();
        $detalles['user_agent'] = $this->getClientUserAgent();
        
        $this->incrementarContador("evento_usuario_{$accion}", 1, $detalles);
    }

    /**
     * Registrar error del sistema
     */
    public function registrarErrorSistema(string $tipoError, string $mensaje, array $detalles = []): void
    {
        $detalles['mensaje'] = $mensaje;
        $detalles['ip'] = $this->getClientIp();
        $detalles['timestamp'] = $this->getCurrentTimestamp();
        
        $this->incrementarContador("error_{$tipoError}", 1, $detalles);
    }

    /**
     * Registrar rendimiento de consulta
     */
    public function registrarRendimientoConsulta(string $tabla, float $tiempoMs, int $filasAfectadas = 0): void
    {
        $detalles = [
            'tabla' => $tabla,
            'tiempo_ms' => $tiempoMs,
            'filas_afectadas' => $filasAfectadas
        ];
        
        $this->registrarMetrica("consulta_{$tabla}", $tiempoMs, $detalles);
    }

    /**
     * Obtener métricas por fecha
     */
    public function obtenerMetricasPorFecha(string $fecha, array $metricas = []): array
    {
        try {
            $whereClause = "WHERE fecha = ?";
            $params = [$fecha];
            
            if (!empty($metricas)) {
                $placeholders = str_repeat('?,', count($metricas) - 1) . '?';
                $whereClause .= " AND metrica IN ({$placeholders})";
                $params = array_merge($params, $metricas);
            }
            
            $sql = "SELECT metrica, valor, detalles FROM {$this->metricsTable} {$whereClause} ORDER BY metrica";
            return $this->database->fetchAll($sql, $params);
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error obteniendo métricas", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener estadísticas del sistema
     */
    public function obtenerEstadisticasSistema(int $dias = 7): array
    {
        try {
            $sql = "
                SELECT 
                    metrica,
                    AVG(valor) as promedio,
                    MAX(valor) as maximo,
                    MIN(valor) as minimo,
                    SUM(valor) as total,
                    COUNT(*) as registros
                FROM {$this->metricsTable}
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY metrica
                ORDER BY metrica
            ";
            
            $resultados = $this->database->fetchAll($sql, [$dias]);
            
            $estadisticas = [];
            foreach ($resultados as $row) {
                $estadisticas[$row['metrica']] = [
                    'promedio' => round($row['promedio'], 2),
                    'maximo' => $row['maximo'],
                    'minimo' => $row['minimo'],
                    'total' => $row['total'],
                    'registros' => $row['registros']
                ];
            }
            
            return $estadisticas;
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error obteniendo estadísticas", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener métricas de rendimiento
     */
    public function obtenerMetricasRendimiento(int $dias = 7): array
    {
        $metricas = [
            'tiempo_login',
            'tiempo_consulta_estudiantes',
            'tiempo_consulta_profesores',
            'tiempo_consulta_llamados',
            'memoria_operacion',
            'consulta_estudiantes',
            'consulta_profesores',
            'consulta_llamados'
        ];
        
        return $this->obtenerEstadisticasSistema($dias);
    }

    /**
     * Obtener métricas de uso
     */
    public function obtenerMetricasUso(int $dias = 7): array
    {
        $metricas = [
            'evento_usuario_login',
            'evento_usuario_logout',
            'evento_usuario_consulta_estudiante',
            'evento_usuario_consulta_profesor',
            'evento_usuario_crear_llamado',
            'evento_usuario_crear_nota'
        ];
        
        return $this->obtenerEstadisticasSistema($dias);
    }

    /**
     * Obtener métricas de errores
     */
    public function obtenerMetricasErrores(int $dias = 7): array
    {
        $metricas = [
            'error_database',
            'error_validation',
            'error_authentication',
            'error_authorization',
            'error_system'
        ];
        
        return $this->obtenerEstadisticasSistema($dias);
    }

    /**
     * Limpiar métricas antiguas
     */
    public function limpiarMetricasAntiguas(int $dias = 90): int
    {
        try {
            $sql = "DELETE FROM {$this->metricsTable} WHERE fecha < DATE_SUB(CURDATE(), INTERVAL ? DAY)";
            $stmt = $this->database->query($sql, [$dias]);
            return $stmt->rowCount();
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error limpiando métricas antiguas", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Crear tabla de métricas si no existe
     */
    public function crearTablaMetricas(): void
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->metricsTable} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fecha DATE NOT NULL,
                    metrica VARCHAR(50) NOT NULL,
                    valor DECIMAL(15,2) NOT NULL,
                    detalles JSON,
                    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_fecha_metrica (fecha, metrica),
                    INDEX idx_fecha (fecha),
                    INDEX idx_metrica (metrica)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->database->query($sql);
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error creando tabla de métricas", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener resumen de salud del sistema
     */
    public function obtenerResumenSaludSistema(): array
    {
        $resumen = [
            'timestamp' => $this->getCurrentTimestamp(),
            'memoria_actual_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memoria_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'tiempo_ejecucion_segundos' => round((float)(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 3),
            'conexiones_activas' => $this->obtenerConexionesActivas(),
            'sesiones_activas' => $this->obtenerSesionesActivas(),
            'errores_recientes' => $this->obtenerErroresRecientes()
        ];
        
        return $resumen;
    }

    /**
     * Obtener conexiones activas
     */
    private function obtenerConexionesActivas(): int
    {
        try {
            $sql = "SHOW STATUS LIKE 'Threads_connected'";
            $result = $this->database->fetch($sql);
            return $result ? (int)$result['Value'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener sesiones activas
     */
    private function obtenerSesionesActivas(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM sesiones_activas WHERE activa = 1 AND expira_en > NOW()";
            $result = $this->database->fetch($sql);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener errores recientes
     */
    private function obtenerErroresRecientes(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM logs_errores WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $result = $this->database->fetch($sql);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Generar reporte de rendimiento
     */
    public function generarReporteRendimiento(int $dias = 7): array
    {
        return [
            'periodo' => "Últimos {$dias} días",
            'fecha_generacion' => $this->getCurrentTimestamp(),
            'metricas_rendimiento' => $this->obtenerMetricasRendimiento($dias),
            'metricas_uso' => $this->obtenerMetricasUso($dias),
            'metricas_errores' => $this->obtenerMetricasErrores($dias),
            'resumen_salud' => $this->obtenerResumenSaludSistema()
        ];
    }

    /**
     * Registrar métrica personalizada
     */
    public function registrarMetricaPersonalizada(string $nombre, mixed $valor, array $tags = []): void
    {
        try {
            // Validar tamaño de datos para prevenir memory exhaustion
            $valorJson = json_encode($valor);
            $tagsJson = json_encode($tags);
            
            if (strlen($valorJson) > 1048576 || strlen($tagsJson) > 524288) { // 1MB y 512KB
                $this->logEvent('WARNING', 'Métrica personalizada demasiado grande, truncando', [
                    'nombre' => $nombre,
                    'valor_size' => strlen($valorJson),
                    'tags_size' => strlen($tagsJson)
                ]);
                
                if (strlen($valorJson) > 1048576) {
                    $valor = substr($valorJson, 0, 1048576);
                }
                if (strlen($tagsJson) > 524288) {
                    $tags = [];
                }
            }
            
            $sql = "INSERT INTO metricas_sistema (nombre, valor, tags, creado_en) VALUES (?, ?, ?, NOW())";
            $this->database->query($sql, [$nombre, $valorJson, $tagsJson]);
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error registrando métrica personalizada', [
                'nombre' => $nombre,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Configurar alertas personalizadas
     */
    public function configurarAlerta(string $nombre, string $condicion, string $umbral, string $mensaje): bool
    {
        try {
            $sql = "INSERT INTO alertas_configuradas (nombre, condicion, umbral, mensaje, activa, creado_en) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE condicion = VALUES(condicion), umbral = VALUES(umbral), mensaje = VALUES(mensaje)";
            
            $this->database->query($sql, [$nombre, $condicion, $umbral, $mensaje]);
            return true;
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error configurando alerta', [
                'nombre' => $nombre,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
