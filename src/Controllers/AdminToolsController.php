<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Services\BackupService;
use SistemaAdmin\Services\BackupSchedulerService;
use SistemaAdmin\Services\SystemMonitoringService;
use SistemaAdmin\Services\ConfigurationService;
use SistemaAdmin\Services\ValidationService;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\PermissionService;
use SistemaAdmin\Services\SessionService;
use SistemaAdmin\Services\QueueService;

/**
 * Controlador de Herramientas Administrativas
 * 
 * Gestiona todas las herramientas avanzadas de administración
 */
class AdminToolsController extends BaseService
{
    private BackupService $backupService;
    private SystemMonitoringService $monitoringService;
    private ConfigurationService $configService;
    private ServicioAutenticacion $authService;
    private PermissionService $permissionService;
    private ValidationService $validationService;
    
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->backupService = new BackupService($database);
        $this->monitoringService = new SystemMonitoringService($database);
        $this->configService = new ConfigurationService($database);
        $this->authService = new ServicioAutenticacion($database);
        $this->validationService = new ValidationService($database);
        
        $sessionService = new SessionService($database);
        $this->permissionService = new PermissionService($database, $sessionService);
    }
    
    /**
     * Verificar permisos de administrador
     */
    private function verificarPermisos(): void
    {
        if (!$this->permissionService->tienePermiso('administrar_sistema')) {
            throw new \Exception('No tienes permisos para acceder a esta sección');
        }
    }
    
    /**
     * Respuesta exitosa estándar
     */
    protected function successResponse($data, string $mensaje = ''): array
    {
        $response = [
            'success' => true,
            'data' => $data
        ];
        
        if (!empty($mensaje)) {
            $response['mensaje'] = $mensaje;
        }
        
        return $response;
    }
    
    /**
     * Respuesta de error estándar
     */
    protected function errorResponse(string $mensaje, $data = null): array
    {
        $response = [
            'success' => false,
            'mensaje' => $mensaje
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
    
    /**
     * Obtener dashboard de herramientas administrativas
     */
    public function obtenerDashboard(): array
    {
        try {
            $this->verificarPermisos();

            $scheduler = new BackupSchedulerService($this->database);
            try {
                $scheduler->runIfDue();
            } catch (\Throwable $e) {
                // No bloquear el panel si el backup automático falla
            }

            $cronToken = $scheduler->ensureCronToken();
            $cronPath = '/public/cron_backup.php';
            $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
            $prefix = preg_replace('#/admin/.*$#', '', $script) ?: '';
            $backupCronUrl = $prefix . $cronPath . '?key=' . rawurlencode($cronToken);

            $queueService = new QueueService($this->database);
            $queueToken = $queueService->ensureCronToken();
            $queueCronUrl = $prefix . '/public/cron_queue.php?key=' . rawurlencode($queueToken);

            return $this->successResponse([
                'metricas' => $this->monitoringService->obtenerMetricasCompletas(),
                'alertas' => $this->monitoringService->obtenerAlertasActivas(),
                'backups_recientes' => array_slice($this->backupService->listarBackups(), 0, 5),
                'backup_cron' => [
                    'path_con_token' => $backupCronUrl,
                    'token' => $cronToken,
                ],
                'queue_cron' => [
                    'path_con_token' => $queueCronUrl,
                    'token' => $queueToken,
                ],
                'queue_stats' => [
                    'pendientes' => $queueService->pendingCount('default'),
                    'fallidos_7d' => $queueService->failedCount('default', 24 * 7),
                ],
                'configuracion' => $this->configService->obtenerTodas(),
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Obtener métricas del sistema
     */
    public function obtenerMetricas(): array
    {
        try {
            $this->verificarPermisos();
            
            return $this->successResponse([
                'metricas' => $this->monitoringService->obtenerMetricasCompletas(),
                'alertas' => $this->monitoringService->obtenerAlertasActivas()
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Obtener historial de métricas
     */
    public function obtenerHistorialMetricas(int $horas = 24): array
    {
        try {
            $this->verificarPermisos();
            
            return $this->successResponse([
                'historial' => $this->monitoringService->obtenerHistorialMetricas($horas)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Crear backup
     */
    public function crearBackup(): array
    {
        try {
            // Validar permisos
            $this->verificarPermisos();
            
            // Validar rate limiting
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->validationService->checkRateLimit('crear_backup', $ip)) {
                throw new \Exception('Demasiadas solicitudes. Intenta más tarde.');
            }
            
            // Validar CSRF token
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->validationService->validateCSRFToken($csrfToken)) {
                throw new \Exception('Token de seguridad inválido');
            }
            
            $resultado = $this->backupService->crearBackupCompleto();
            
            if ($resultado['success']) {
                return $this->successResponse($resultado, 'Backup creado exitosamente');
            } else {
                return $this->errorResponse($resultado['mensaje']);
            }
            
        } catch (\Exception $e) {
            $this->logError('Error creando backup: ' . $e->getMessage());
            return $this->errorResponse('Error creando backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Listar backups
     */
    public function listarBackups(): array
    {
        try {
            $this->verificarPermisos();
            
            return $this->successResponse([
                'backups' => $this->backupService->listarBackups()
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Descargar backup
     */
    public function descargarBackup(string $archivo): void
    {
        try {
            $this->verificarPermisos();
            
            $backupsDir = __DIR__ . '/../../backups';
            $backupFile = "{$backupsDir}/{$archivo}";
            
            if (!file_exists($backupFile)) {
                throw new \Exception('Archivo de backup no encontrado');
            }
            
            $this->backupService->descargarBackup($backupFile);
            
        } catch (\Exception $e) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    public function getBackupDownloadToken(string $archivo): string
    {
        return $this->backupService->generarTokenDescarga($archivo, 600);
    }

    public function descargarBackupConToken(string $token): void
    {
        try {
            $this->verificarPermisos();
            $valid = $this->backupService->validarTokenDescarga($token);
            if (!$valid['ok']) {
                throw new \Exception($valid['error']);
            }
            $archivo = $valid['archivo'];
            $backupsDir = __DIR__ . '/../../backups';
            $backupFile = "{$backupsDir}/{$archivo}";
            if (!file_exists($backupFile)) {
                throw new \Exception('Archivo de backup no encontrado');
            }
            $this->backupService->descargarBackup($backupFile);
        } catch (\Exception $e) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Restaurar backup
     */
    public function restaurarBackup(string $archivo): array
    {
        try {
            // Validar permisos
            $this->verificarPermisos();
            
            // Validar rate limiting
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->validationService->checkRateLimit('restaurar_backup', $ip)) {
                throw new \Exception('Demasiadas solicitudes. Intenta más tarde.');
            }
            
            // Validar CSRF token
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->validationService->validateCSRFToken($csrfToken)) {
                throw new \Exception('Token de seguridad inválido');
            }
            
            // Validar nombre de archivo
            if (!$this->validationService->validateFileName($archivo)) {
                throw new \Exception('Nombre de archivo no válido');
            }
            
            $backupsDir = __DIR__ . '/../../backups';
            $backupFile = "{$backupsDir}/{$archivo}";
            
            // Verificar que el archivo existe y está en el directorio correcto
            if (!file_exists($backupFile) || !is_file($backupFile)) {
                throw new \Exception('Archivo de backup no encontrado');
            }
            
            $resultado = $this->backupService->restaurarBackup($backupFile);
            
            if ($resultado['success']) {
                return $this->successResponse($resultado, 'Backup restaurado exitosamente');
            } else {
                return $this->errorResponse($resultado['mensaje']);
            }
            
        } catch (\Exception $e) {
            $this->logError('Error restaurando backup: ' . $e->getMessage());
            return $this->errorResponse('Error restaurando backup: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un archivo de backup del servidor
     */
    public function eliminarBackup(string $archivo): array
    {
        try {
            $this->verificarPermisos();

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->validationService->checkRateLimit('eliminar_backup', $ip)) {
                throw new \Exception('Demasiadas solicitudes. Intenta más tarde.');
            }

            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!$this->validationService->validateCSRFToken($csrfToken)) {
                throw new \Exception('Token de seguridad inválido');
            }

            if (!$this->validationService->validateFileName($archivo)) {
                throw new \Exception('Nombre de archivo no válido');
            }

            $resultado = $this->backupService->eliminarArchivoBackup($archivo);
            if (!empty($resultado['success'])) {
                return $this->successResponse(
                    ['archivo' => $archivo],
                    (string) ($resultado['mensaje'] ?? 'Backup eliminado')
                );
            }

            return $this->errorResponse($resultado['mensaje'] ?? 'No se pudo eliminar el backup');
        } catch (\Exception $e) {
            $this->logError('Error eliminando backup: ' . $e->getMessage());

            return $this->errorResponse('Error eliminando backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener configuración
     */
    public function obtenerConfiguracion(?string $categoria = null): array
    {
        try {
            $this->verificarPermisos();
            
            if ($categoria) {
                $config = $this->configService->obtenerPorCategoria($categoria);
            } else {
                $config = $this->configService->obtenerTodas();
            }
            
            return $this->successResponse(['configuracion' => $config]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Actualizar configuración
     */
    public function actualizarConfiguracion(array $configuracion): array
    {
        try {
            $this->verificarPermisos();
            
            // Obtener usuario actual
            $usuario = $this->authService->verificarSesion();
            $usuarioId = $usuario ? ($usuario['id'] ?? null) : null;
            
            $resultado = $this->configService->actualizarMultiples($configuracion, $usuarioId);
            
            return $this->successResponse($resultado, 'Configuración actualizada');
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Restaurar configuración por defecto
     */
    public function restaurarConfiguracionDefault(): array
    {
        try {
            $this->verificarPermisos();
            
            $usuario = $this->authService->verificarSesion();
            $usuarioId = $usuario ? $usuario->getId() : null;
            
            if ($this->configService->restaurarDefaults($usuarioId)) {
                return $this->successResponse([], 'Configuración restaurada a valores por defecto');
            } else {
                return $this->errorResponse('Error restaurando configuración');
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Exportar configuración
     */
    public function exportarConfiguracion(): void
    {
        try {
            $this->verificarPermisos();
            
            $config = $this->configService->exportar();
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="configuracion_' . date('Y-m-d_H-i-s') . '.json"');
            
            echo json_encode($config, JSON_PRETTY_PRINT);
            exit;
            
        } catch (\Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Importar configuración
     */
    public function importarConfiguracion(array $configuracion): array
    {
        try {
            $this->verificarPermisos();
            
            $usuario = $this->authService->verificarSesion();
            $usuarioId = $usuario ? $usuario->getId() : null;
            
            if ($this->configService->importar($configuracion, $usuarioId)) {
                return $this->successResponse([], 'Configuración importada exitosamente');
            } else {
                return $this->errorResponse('Error importando configuración');
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Obtener información del sistema
     */
    public function obtenerInfoSistema(): array
    {
        try {
            $this->verificarPermisos();
            
            return $this->successResponse([
                'php_version' => PHP_VERSION,
                'php_extensions' => get_loaded_extensions(),
                'sistema_operativo' => PHP_OS,
                'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
                'memoria_limite' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'timezone' => date_default_timezone_get()
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Verificar integridad del sistema
     */
    public function verificarIntegridad(): array
    {
        try {
            $this->verificarPermisos();
            
            $issues = [];
            
            // Verificar permisos de directorios críticos
            $directorios = [
                'logs' => __DIR__ . '/../../logs',
                'backups' => __DIR__ . '/../../backups',
                'uploads' => __DIR__ . '/../../uploads',
                'config' => __DIR__ . '/../../config'
            ];
            
            foreach ($directorios as $nombre => $ruta) {
                if (!file_exists($ruta)) {
                    $issues[] = [
                        'tipo' => 'warning',
                        'mensaje' => "Directorio '{$nombre}' no existe",
                        'solucion' => "Crear directorio: {$ruta}"
                    ];
                } else if (!is_writable($ruta)) {
                    $issues[] = [
                        'tipo' => 'error',
                        'mensaje' => "Directorio '{$nombre}' no tiene permisos de escritura",
                        'solucion' => "Dar permisos de escritura al directorio"
                    ];
                }
            }
            
            // Verificar extensiones PHP requeridas
            $extensionesRequeridas = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
            foreach ($extensionesRequeridas as $ext) {
                if (!extension_loaded($ext)) {
                    $issues[] = [
                        'tipo' => 'error',
                        'mensaje' => "Extensión PHP '{$ext}' no está instalada",
                        'solucion' => "Instalar y habilitar la extensión {$ext}"
                    ];
                }
            }
            
            // Verificar conexión a base de datos
            try {
                $this->database->query("SELECT 1");
            } catch (\Exception $e) {
                $issues[] = [
                    'tipo' => 'critical',
                    'mensaje' => 'Error de conexión a la base de datos',
                    'solucion' => 'Verificar configuración de base de datos'
                ];
            }
            
            $estadoGeneral = empty($issues) ? 'saludable' : 'requiere_atencion';
            if (!empty(array_filter($issues, fn($i) => $i['tipo'] === 'critical'))) {
                $estadoGeneral = 'critico';
            }
            
            return $this->successResponse([
                'estado' => $estadoGeneral,
                'issues' => $issues,
                'total_issues' => count($issues)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Limpiar caché del sistema
     */
    public function limpiarCache(): array
    {
        try {
            $this->verificarPermisos();
            
            // Limpiar caché de configuración
            $this->configService->limpiarCache();
            
            // Limpiar caché de archivos temporales
            $tempDir = sys_get_temp_dir();
            $archivosEliminados = 0;
            
            // Limpiar caché de base de datos si existe
            try {
                $this->database->query("DELETE FROM cache_data WHERE expiracion < NOW()");
                $archivosEliminados += $this->database->query("SELECT ROW_COUNT()")->fetchColumn();
            } catch (\Exception $e) {
                // Tabla cache no existe, continuar
            }
            
            return $this->successResponse([
                'archivos_eliminados' => $archivosEliminados
            ], 'Caché limpiado exitosamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Optimizar base de datos
     */
    public function optimizarBaseDatos(): array
    {
        try {
            $this->verificarPermisos();
            
            $dbName = $_ENV['DB_NAME'] ?? 'school_admin';
            
            // Obtener todas las tablas
            $tables = $this->database->fetchAll(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = ?",
                [$dbName]
            );
            
            $tablasOptimizadas = 0;
            
            foreach ($tables as $table) {
                $tableName = $table['table_name'] ?? $table['TABLE_NAME'];
                try {
                    $this->database->query("OPTIMIZE TABLE `{$tableName}`");
                    $tablasOptimizadas++;
                } catch (\Exception $e) {
                    // Continuar con la siguiente tabla
                }
            }
            
            return $this->successResponse([
                'tablas_optimizadas' => $tablasOptimizadas,
                'total_tablas' => count($tables)
            ], 'Base de datos optimizada');
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Obtener todas las sesiones activas en la BD con detalle de usuario
     */
    public function obtenerSesionesActivasConDetalle(): array
    {
        try {
            $this->verificarPermisos();
            
            $sql = "SELECT su.id, su.usuario_id, su.session_id, su.ip_address, su.user_agent, su.creado_en, su.ultima_actividad, su.activa,
                           u.dni, u.nombre, u.apellido, u.rol
                    FROM sesiones_usuarios su
                    LEFT JOIN usuarios u ON su.usuario_id = u.id
                    WHERE su.activa = 1 AND su.ultima_actividad >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY su.ultima_actividad DESC";
            
            $sesiones = $this->database->fetchAll($sql);
            return $this->successResponse($sesiones);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Revocar una sesión específica por session_id
     */
    public function revocarSesion(string $sessionId): array
    {
        try {
            $this->verificarPermisos();
            
            $this->database->query(
                "UPDATE sesiones_usuarios SET activa = 0 WHERE session_id = ?",
                [$sessionId]
            );
            
            return $this->successResponse([], 'Sesión revocada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Revocar todas las sesiones de usuario excepto la indicada
     */
    public function revocarTodasLasSesionesExcepto(string $sessionId): array
    {
        try {
            $this->verificarPermisos();
            
            $this->database->query(
                "UPDATE sesiones_usuarios SET activa = 0 WHERE session_id != ?",
                [$sessionId]
            );
            
            return $this->successResponse([], 'Todas las demás sesiones activas han sido cerradas');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Obtener logs formateados de la carpeta logs/
     */
    public function obtenerLogs(string $tipo, int $limite = 100, ?string $filtro = null): array
    {
        try {
            $this->verificarPermisos();
            
            $tipoMap = [
                'audit' => 'audit.log.php',
                'security' => 'security.log.php',
                'error' => 'error.log.php'
            ];
            
            if (!isset($tipoMap[$tipo])) {
                throw new \Exception('Tipo de log no válido');
            }
            
            $logDir = is_dir(__DIR__ . '/../../admin/logs') ? __DIR__ . '/../../admin/logs' : __DIR__ . '/../../logs';
            $filePath = $logDir . '/' . $tipoMap[$tipo];
            if (!file_exists($filePath)) {
                return $this->successResponse([], 'El archivo de log no existe aún.');
            }
            
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $parsedEntries = [];
            
            foreach ($lines as $line) {
                if (str_starts_with($line, '<?php')) {
                    continue;
                }
                
                $data = json_decode($line, true);
                if (!$data) {
                    continue;
                }
                
                // Aplicar filtro si existe
                if (!empty($filtro)) {
                    $match = false;
                    foreach ($data as $key => $val) {
                        if (is_array($val)) {
                            $valStr = json_encode($val);
                        } else {
                            $valStr = (string)$val;
                        }
                        if (stripos($valStr, $filtro) !== false) {
                            $match = true;
                            break;
                        }
                    }
                    if (!$match) {
                        continue;
                    }
                }
                
                $parsedEntries[] = $data;
            }
            
            // Invertir para mostrar los más recientes primero
            $parsedEntries = array_reverse($parsedEntries);
            
            // Limitar resultados
            $parsedEntries = array_slice($parsedEntries, 0, $limite);
            
            return $this->successResponse($parsedEntries);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Limpiar (vaciar) un archivo de log efímero
     */
    public function limpiarLog(string $tipo): array
    {
        try {
            $this->verificarPermisos();
            
            $tipoMap = [
                'security' => 'security.log.php',
                'error' => 'error.log.php'
            ];
            
            if ($tipo === 'audit') {
                throw new \Exception('Los logs de auditoría legal no pueden ser vaciados por motivos de cumplimiento.');
            }
            
            if (!isset($tipoMap[$tipo])) {
                throw new \Exception('Tipo de log no válido');
            }
            
            $logDir = is_dir(__DIR__ . '/../../admin/logs') ? __DIR__ . '/../../admin/logs' : __DIR__ . '/../../logs';
            $filePath = $logDir . '/' . $tipoMap[$tipo];
            
            // Escribir cabecera vacía de seguridad
            file_put_contents($filePath, "<?php exit; ?>\n", LOCK_EX);
            
            return $this->successResponse([], 'Archivo de log limpiado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
