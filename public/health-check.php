<?php
/**
 * Health Check Endpoint
 * 
 * Verifica el estado de salud del sistema para deployments y monitoreo
 */

header('Content-Type: application/json');

$projectRoot = dirname(__DIR__);

// Configurar timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '3.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'checks' => []
];

$overall_status = 'ok';

try {
    require_once __DIR__ . '/../includes/database_bootstrap.php';
    $db = sistema_admin_db_adapter();
    $result = $db->fetch("SELECT 1 as test");
    
    if ($result && $result['test'] == 1) {
        $health['checks']['database'] = [
            'status' => 'ok',
            'message' => 'Database connection successful'
        ];
    } else {
        $health['checks']['database'] = [
            'status' => 'error',
            'message' => 'Database connection failed'
        ];
        $overall_status = 'error';
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    $overall_status = 'error';
}

// Verificar servicios críticos (rutas absolutas: PSR-4 usa src/Services con mayúscula; Linux distingue mayúsculas)
$critical_services = [
    'authentication' => $projectRoot . '/src/Services/ServicioAutenticacion.php',
    'backup' => $projectRoot . '/src/Services/BackupService.php',
    'monitoring' => $projectRoot . '/src/Services/SystemMonitoringService.php'
];

foreach ($critical_services as $service => $file) {
    if (file_exists($file)) {
        $health['checks'][$service] = [
            'status' => 'ok',
            'message' => 'Service file exists'
        ];
    } else {
        $health['checks'][$service] = [
            'status' => 'error',
            'message' => 'Service file missing'
        ];
        $overall_status = 'error';
    }
}

// Verificar directorios críticos
$critical_directories = [
    'logs' => $projectRoot . '/logs/',
    'backups' => $projectRoot . '/backups/',
    'uploads' => $projectRoot . '/uploads/'
];

foreach ($critical_directories as $dir => $path) {
    if (is_dir($path) && is_writable($path)) {
        $health['checks'][$dir . '_directory'] = [
            'status' => 'ok',
            'message' => 'Directory exists and is writable'
        ];
    } else {
        $health['checks'][$dir . '_directory'] = [
            'status' => 'warning',
            'message' => 'Directory missing or not writable'
        ];
        if ($overall_status !== 'error') {
            $overall_status = 'warning';
        }
    }
}

// Verificar memoria disponible
$memory_limit = ini_get('memory_limit');
$memory_usage = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);

$health['checks']['memory'] = [
    'status' => 'ok',
    'memory_limit' => $memory_limit,
    'memory_usage' => round($memory_usage / 1024 / 1024, 2) . ' MB',
    'memory_peak' => round($memory_peak / 1024 / 1024, 2) . ' MB'
];

// Verificar espacio en disco
$disk_free = disk_free_space(__DIR__);
$disk_total = disk_total_space(__DIR__);
$disk_usage_percent = round((($disk_total - $disk_free) / $disk_total) * 100, 2);

if ($disk_usage_percent > 90) {
    $health['checks']['disk'] = [
        'status' => 'error',
        'message' => 'Disk usage critical: ' . $disk_usage_percent . '%',
        'disk_usage_percent' => $disk_usage_percent
    ];
    $overall_status = 'error';
} elseif ($disk_usage_percent > 80) {
    $health['checks']['disk'] = [
        'status' => 'warning',
        'message' => 'Disk usage high: ' . $disk_usage_percent . '%',
        'disk_usage_percent' => $disk_usage_percent
    ];
    if ($overall_status !== 'error') {
        $overall_status = 'warning';
    }
} else {
    $health['checks']['disk'] = [
        'status' => 'ok',
        'message' => 'Disk usage normal: ' . $disk_usage_percent . '%',
        'disk_usage_percent' => $disk_usage_percent
    ];
}

// Verificar extensiones PHP críticas
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'openssl'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    $health['checks']['php_extensions'] = [
        'status' => 'error',
        'message' => 'Missing PHP extensions: ' . implode(', ', $missing_extensions)
    ];
    $overall_status = 'error';
} else {
    $health['checks']['php_extensions'] = [
        'status' => 'ok',
        'message' => 'All required PHP extensions loaded'
    ];
}

// Verificar logs de error recientes
try {
    $error_log_file = $projectRoot . '/logs/error.log';
    if (file_exists($error_log_file)) {
        $log_content = file_get_contents($error_log_file);
        $recent_errors = substr_count($log_content, date('Y-m-d'));
        
        if ($recent_errors > 100) {
            $health['checks']['error_logs'] = [
                'status' => 'warning',
                'message' => 'High number of errors today: ' . $recent_errors
            ];
            if ($overall_status !== 'error') {
                $overall_status = 'warning';
            }
        } else {
            $health['checks']['error_logs'] = [
                'status' => 'ok',
                'message' => 'Error logs normal: ' . $recent_errors . ' errors today'
            ];
        }
    } else {
        $health['checks']['error_logs'] = [
            'status' => 'ok',
            'message' => 'No error log file found'
        ];
    }
} catch (Exception $e) {
    $health['checks']['error_logs'] = [
        'status' => 'warning',
        'message' => 'Could not check error logs'
    ];
}

// Actualizar estado general
$health['status'] = $overall_status;

// Establecer código de respuesta HTTP apropiado
switch ($overall_status) {
    case 'ok':
        http_response_code(200);
        break;
    case 'warning':
        http_response_code(200); // 200 para warnings
        break;
    case 'error':
        http_response_code(503); // Service Unavailable para errores
        break;
}

// Respuesta JSON
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Log del health check si hay problemas
if ($overall_status !== 'ok') {
    error_log("Health check failed: " . json_encode($health));
}
?>
