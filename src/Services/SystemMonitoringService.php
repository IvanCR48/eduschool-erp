<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Monitoreo del Sistema
 * 
 * Proporciona métricas en tiempo real del sistema
 */
class SystemMonitoringService extends BaseService
{
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }
    
    /**
     * Obtener métricas completas del sistema
     */
    public function obtenerMetricasCompletas(): array
    {
        return [
            'sistema' => $this->obtenerMetricasSistema(),
            'base_datos' => $this->obtenerMetricasBaseDatos(),
            'aplicacion' => $this->obtenerMetricasAplicacion(),
            'seguridad' => $this->obtenerMetricasSeguridad(),
            'rendimiento' => $this->obtenerMetricasRendimiento(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Métricas del sistema operativo
     */
    private function obtenerMetricasSistema(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        
        return [
            'php_version' => PHP_VERSION,
            'sistema_operativo' => PHP_OS,
            'memoria_php' => [
                'uso_actual' => memory_get_usage(true),
                'uso_actual_formateado' => $this->formatBytes(memory_get_usage(true)),
                'pico_maximo' => memory_get_peak_usage(true),
                'pico_maximo_formateado' => $this->formatBytes(memory_get_peak_usage(true)),
                'limite' => ini_get('memory_limit')
            ],
            'carga_sistema' => [
                '1min' => round($loadAvg[0], 2),
                '5min' => round($loadAvg[1], 2),
                '15min' => round($loadAvg[2], 2)
            ],
            'espacio_disco' => $this->obtenerEspacioDisco(),
            'uptime' => $this->obtenerUptime()
        ];
    }
    
    /**
     * Métricas de la base de datos
     */
    private function obtenerMetricasBaseDatos(): array
    {
        try {
            // Tamaño de la base de datos
            $dbName = $_ENV['DB_NAME'] ?? 'school_admin';
            $sizeQuery = $this->database->fetch(
                "SELECT SUM(data_length + index_length) as size 
                 FROM information_schema.TABLES 
                 WHERE table_schema = ?",
                [$dbName]
            );
            
            $dbSize = $sizeQuery['size'] ?? 0;
            
            // Número de tablas
            $tablesCount = $this->database->fetch(
                "SELECT COUNT(*) as count 
                 FROM information_schema.TABLES 
                 WHERE table_schema = ?",
                [$dbName]
            );
            
            // Estado de las conexiones
            $connections = $this->database->fetchAll("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = $this->database->fetchAll("SHOW VARIABLES LIKE 'max_connections'");
            
            // Estadísticas de consultas
            $queries = $this->database->fetch("SHOW STATUS LIKE 'Questions'");
            
            return [
                'tamaño_db' => $dbSize,
                'tamaño_db_formateado' => $this->formatBytes($dbSize),
                'numero_tablas' => (int)$tablesCount['count'],
                'conexiones_activas' => (int)($connections[0]['Value'] ?? 0),
                'conexiones_maximas' => (int)($maxConnections[0]['Value'] ?? 0),
                'total_consultas' => (int)($queries['Value'] ?? 0),
                'estado' => 'operativo'
            ];
            
        } catch (\Exception $e) {
            return [
                'estado' => 'error',
                'mensaje' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Métricas de la aplicación
     */
    private function obtenerMetricasAplicacion(): array
    {
        try {
            // Estadísticas de usuarios
            $usuariosActivos = $this->database->fetch(
                "SELECT COUNT(*) as count FROM usuarios WHERE activo = 1"
            );
            
            $sesionesActivas = $this->database->fetch(
                "SELECT COUNT(*) as count FROM sesiones_usuarios 
                 WHERE activa = 1 AND ultima_actividad >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            // Estadísticas de datos
            $estudiantes = $this->database->fetch("SELECT COUNT(*) as count FROM estudiantes");
            $profesores = $this->database->fetch("SELECT COUNT(*) as count FROM profesores");
            $materias = $this->database->fetch("SELECT COUNT(*) as count FROM materias");
            $cursos = $this->database->fetch("SELECT COUNT(*) as count FROM cursos");
            
            // Actividad reciente
            $actividadHoy = $this->obtenerActividadHoy();
            
            return [
                'usuarios' => [
                    'activos' => (int)$usuariosActivos['count'],
                    'sesiones_activas' => (int)$sesionesActivas['count']
                ],
                'datos' => [
                    'estudiantes' => (int)$estudiantes['count'],
                    'profesores' => (int)$profesores['count'],
                    'materias' => (int)$materias['count'],
                    'cursos' => (int)$cursos['count']
                ],
                'actividad_hoy' => $actividadHoy
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Métricas de seguridad
     */
    private function obtenerMetricasSeguridad(): array
    {
        try {
            // Intentos de login fallidos en las últimas 24 horas (según logs de seguridad)
            $loginsFallidos = $this->database->fetch(
                "SELECT COUNT(*) as count 
                 FROM logs_seguridad
                 WHERE tipo IN ('LOGIN_FAILED', 'MFA_FAILED')
                   AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );

            // Intentos fallidos por IP (top 5)
            $loginsFallidosPorIp = $this->database->fetchAll(
                "SELECT ip, COUNT(*) as total
                 FROM logs_seguridad
                 WHERE tipo IN ('LOGIN_FAILED', 'MFA_FAILED')
                   AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 GROUP BY ip
                 ORDER BY total DESC
                 LIMIT 5"
            );

            // Intentos fallidos por usuario (top 5)
            $loginsFallidosPorUsuario = $this->database->fetchAll(
                "SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(datos, '$.username')) AS username,
                    COUNT(*) as total
                 FROM logs_seguridad
                 WHERE tipo IN ('LOGIN_FAILED', 'MFA_FAILED')
                   AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 GROUP BY username
                 ORDER BY total DESC
                 LIMIT 5"
            );

            // Último intento de login fallido
            $ultimoLoginFallidoRaw = $this->database->fetch(
                "SELECT timestamp, ip, descripcion, datos
                 FROM logs_seguridad
                 WHERE tipo IN ('LOGIN_FAILED', 'MFA_FAILED')
                 ORDER BY timestamp DESC
                 LIMIT 1"
            );

            $ultimoLoginFallido = null;
            if ($ultimoLoginFallidoRaw) {
                $datos = json_decode($ultimoLoginFallidoRaw['datos'] ?? '{}', true) ?? [];
                $ultimoLoginFallido = [
                    'timestamp' => $ultimoLoginFallidoRaw['timestamp'],
                    'ip' => $ultimoLoginFallidoRaw['ip'],
                    'username' => $datos['username'] ?? null,
                    'descripcion' => $ultimoLoginFallidoRaw['descripcion'],
                    'error' => $datos['error'] ?? null
                ];
            }
            
            // Usuarios bloqueados
            $usuariosBloqueados = $this->database->fetch(
                "SELECT COUNT(*) as count FROM usuarios 
                 WHERE bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()"
            );
            
            // Sesiones sospechosas (múltiples IPs)
            $sesionesSospechosas = $this->obtenerSesionesSospechosas();
            
            // Verificar logs de seguridad
            $alertasSeguridad = $this->contarAlertasSeguridad();
            
            return [
                'logins_fallidos_24h' => (int)$loginsFallidos['count'],
                'usuarios_bloqueados' => (int)$usuariosBloqueados['count'],
                'sesiones_sospechosas' => $sesionesSospechosas,
                'alertas_seguridad' => $alertasSeguridad,
                'nivel_amenaza' => $this->calcularNivelAmenaza($loginsFallidos['count'], $alertasSeguridad),
                'logins_fallidos_detalle' => [
                    'por_ip' => $this->normalizarTopListado($loginsFallidosPorIp, 'ip'),
                    'por_usuario' => $this->normalizarTopListado($loginsFallidosPorUsuario, 'username'),
                    'ultimo' => $ultimoLoginFallido
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Métricas de rendimiento
     */
    private function obtenerMetricasRendimiento(): array
    {
        try {
            // Tiempo de respuesta promedio (simulado - en producción usar herramienta APM)
            $tiempoRespuesta = $this->calcularTiempoRespuestaPromedio();
            
            // Cache hits (si está implementado)
            $cacheStats = $this->obtenerEstadisticasCache();
            
            // Consultas lentas
            $consultasLentas = $this->contarConsultasLentas();
            
            return [
                'tiempo_respuesta_promedio_ms' => $tiempoRespuesta,
                'cache' => $cacheStats,
                'consultas_lentas_24h' => $consultasLentas,
                'salud' => $this->calcularSaludSistema($tiempoRespuesta, $consultasLentas)
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener espacio en disco
     */
    private function obtenerEspacioDisco(): array
    {
        $rootPath = __DIR__ . '/../..';
        
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $free = disk_free_space($rootPath);
            $total = disk_total_space($rootPath);
            $usado = $total - $free;
            $porcentaje = ($usado / $total) * 100;
            
            return [
                'total' => $total,
                'total_formateado' => $this->formatBytes($total),
                'usado' => $usado,
                'usado_formateado' => $this->formatBytes($usado),
                'libre' => $free,
                'libre_formateado' => $this->formatBytes($free),
                'porcentaje_uso' => round($porcentaje, 2)
            ];
        }
        
        return [
            'disponible' => false,
            'mensaje' => 'Información no disponible'
        ];
    }
    
    /**
     * Obtener uptime del sistema
     */
    private function obtenerUptime(): array
    {
        // En Windows, usar información de PHP
        $uptimeSeconds = (float)(time() - $_SERVER['REQUEST_TIME_FLOAT']);
        
        return [
            'segundos' => (int)round($uptimeSeconds),
            'formateado' => $this->formatUptime($uptimeSeconds)
        ];
    }
    
    /**
     * Obtener actividad del día
     */
    private function obtenerActividadHoy(): array
    {
        try {
            $loginsHoy = $this->database->fetch(
                "SELECT COUNT(*) as count FROM usuarios 
                 WHERE DATE(ultimo_acceso) = CURDATE()"
            );
            
            $notasHoy = $this->database->fetch(
                "SELECT COUNT(*) as count FROM notas 
                 WHERE DATE(fecha) = CURDATE()"
            );
            
            $llamadosHoy = $this->database->fetch(
                "SELECT COUNT(*) as count FROM llamados_atencion 
                 WHERE DATE(fecha) = CURDATE()"
            );
            
            return [
                'logins' => (int)$loginsHoy['count'],
                'notas_cargadas' => (int)$notasHoy['count'],
                'llamados_registrados' => (int)$llamadosHoy['count']
            ];
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function obtenerSesionesSospechosas(): int
    {
        try {
            // Detectar usuarios con múltiples sesiones desde diferentes IPs
            $result = $this->database->fetch(
                "SELECT COUNT(*) as count FROM (
                     SELECT usuario_id 
                     FROM sesiones_usuarios 
                     WHERE activa = 1 AND ultima_actividad >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     GROUP BY usuario_id 
                     HAVING COUNT(DISTINCT ip_address) > 2
                 ) as sub"
            );
            
            return (int)($result['count'] ?? 0);
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Contar alertas de seguridad
     */
    private function contarAlertasSeguridad(): int
    {
        $logFile = __DIR__ . '/../../logs/security.log.php';
        
        if (!file_exists($logFile)) {
            return 0;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        
        $count = 0;
        foreach ($lines as $line) {
            if (str_contains($line, $yesterday) || str_contains($line, $today)) {
                if (str_contains($line, 'WARNING') || str_contains($line, 'ERROR')) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Calcular tiempo de respuesta promedio
     */
    private function calcularTiempoRespuestaPromedio(): float
    {
        // En un sistema real, esto vendría de métricas almacenadas
        // Por ahora, retornamos un valor estimado basado en la carga
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 1;
        
        return round(50 + ($loadAvg * 10), 2); // Base 50ms + carga
    }
    
    /**
     * Obtener estadísticas de caché
     */
    private function obtenerEstadisticasCache(): array
    {
        // Verificar si existe tabla de cache
        try {
            $stats = $this->database->fetch(
                "SELECT COUNT(*) as total FROM cache_data WHERE expiracion > NOW()"
            );
            
            return [
                'activo' => true,
                'entradas' => (int)$stats['total'],
                'hit_rate' => 85 // Valor simulado
            ];
            
        } catch (\Exception $e) {
            return [
                'activo' => false
            ];
        }
    }
    
    /**
     * Contar consultas lentas
     */
    private function contarConsultasLentas(): int
    {
        try {
            $result = $this->database->fetch("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            return (int)($result['Value'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Calcular nivel de amenaza
     */
    private function calcularNivelAmenaza(int $loginsFallidos, int $alertas): string
    {
        $score = $loginsFallidos + ($alertas * 2);
        
        if ($score >= 20) return 'crítico';
        if ($score >= 10) return 'alto';
        if ($score >= 5) return 'medio';
        return 'bajo';
    }
    
    /**
     * Calcular salud del sistema
     */
    private function calcularSaludSistema(float $tiempoRespuesta, int $consultasLentas): array
    {
        $score = 100;
        
        // Penalizar por tiempo de respuesta alto
        if ($tiempoRespuesta > 200) $score -= 20;
        else if ($tiempoRespuesta > 100) $score -= 10;
        
        // Penalizar por consultas lentas
        if ($consultasLentas > 100) $score -= 20;
        else if ($consultasLentas > 50) $score -= 10;
        
        $nivel = 'excelente';
        if ($score < 60) $nivel = 'crítico';
        else if ($score < 75) $nivel = 'precaución';
        else if ($score < 90) $nivel = 'bueno';
        
        return [
            'puntuacion' => max(0, $score),
            'nivel' => $nivel
        ];
    }

    /**
     * Normalizar listados TOP (IP, usuarios, etc.)
     */
    private function normalizarTopListado(array $items, string $claveValor): array
    {
        $resultado = [];

        foreach ($items as $item) {
            $valor = $item[$claveValor] ?? null;

            if (is_null($valor) || $valor === '' || strtolower((string)$valor) === 'null') {
                $valor = 'Desconocido';
            }

            $resultado[] = [
                'valor' => $valor,
                'total' => (int)($item['total'] ?? 0)
            ];
        }

        return $resultado;
    }
    
    /**
     * Formatear bytes
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->formatBytes($bytes, $precision);
    }
    
    /**
     * Formatear uptime
     */
    private function formatUptime(float $seconds): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->formatUptime($seconds);
    }
    
    /**
     * Obtener historial de métricas
     */
    public function obtenerHistorialMetricas(int $horas = 24): array
    {
        // En un sistema real, esto vendría de una tabla de métricas históricas
        // Por ahora, generamos datos simulados basados en tendencias
        
        $data = [];
        $now = time();
        
        for ($i = $horas; $i >= 0; $i--) {
            $timestamp = $now - ($i * 3600);
            
            $data[] = [
                'timestamp' => date('Y-m-d H:i:s', $timestamp),
                'memoria_uso' => rand(40, 80),
                'cpu_uso' => rand(10, 60),
                'consultas_por_segundo' => rand(5, 50),
                'tiempo_respuesta' => rand(30, 150)
            ];
        }
        
        return $data;
    }
    
    /**
     * Parsear límite de memoria de PHP a bytes
     */
    private function parseMemoryLimit(string $val): int
    {
        $val = trim($val);
        if ($val === '-1' || $val === '') {
            return -1;
        }
        $last = strtolower($val[strlen($val) - 1]);
        $numeric = (int)$val;
        switch ($last) {
            case 'g':
                $numeric *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $numeric *= 1024 * 1024;
                break;
            case 'k':
                $numeric *= 1024;
                break;
        }
        return $numeric;
    }

    /**
     * Obtener alertas activas
     */
    public function obtenerAlertasActivas(): array
    {
        $alertas = [];
        $metricas = $this->obtenerMetricasCompletas();
        
        $memoriaUso = $metricas['sistema']['memoria_php']['uso_actual'];
        $limiteStr = $metricas['sistema']['memoria_php']['limite'] ?? '';
        $limiteBytes = $this->parseMemoryLimit($limiteStr);
        
        if ($limiteBytes > 0 && ($memoriaUso / $limiteBytes) > 0.9) {
            $alertas[] = [
                'tipo' => 'warning',
                'categoria' => 'sistema',
                'mensaje' => 'Uso de memoria cercano al límite',
                'detalles' => "Uso actual: {$metricas['sistema']['memoria_php']['uso_actual_formateado']} de {$metricas['sistema']['memoria_php']['limite']}"
            ];
        }
        
        // Verificar espacio en disco
        if (isset($metricas['sistema']['espacio_disco']['porcentaje_uso'])) {
            $discoUso = $metricas['sistema']['espacio_disco']['porcentaje_uso'];
            
            if ($discoUso > 90) {
                $alertas[] = [
                    'tipo' => 'error',
                    'categoria' => 'sistema',
                    'mensaje' => 'Espacio en disco crítico',
                    'detalles' => "Uso: {$discoUso}%"
                ];
            } else if ($discoUso > 80) {
                $alertas[] = [
                    'tipo' => 'warning',
                    'categoria' => 'sistema',
                    'mensaje' => 'Espacio en disco bajo',
                    'detalles' => "Uso: {$discoUso}%"
                ];
            }
        }
        
        // Verificar seguridad
        if ($metricas['seguridad']['nivel_amenaza'] === 'crítico') {
            $alertas[] = [
                'tipo' => 'error',
                'categoria' => 'seguridad',
                'mensaje' => 'Nivel de amenaza crítico detectado',
                'detalles' => "Logins fallidos: {$metricas['seguridad']['logins_fallidos_24h']}"
            ];
        }
        
        return $alertas;
    }
}

