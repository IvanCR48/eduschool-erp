<?php
/**
 * API para el Security Dashboard
 * 
 * Proporciona datos de métricas y alertas de seguridad en tiempo real.
 */

require_once __DIR__ . '/../includes/database_bootstrap.php';

use SistemaAdmin\Middleware\SecurityHeadersMiddleware;
use SistemaAdmin\Services\ValidationService;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\SecurityLoggingService;

// Aplicar headers de seguridad para API
SecurityHeadersMiddleware::applyAPIHeaders();

try {
    $databaseAdapter = sistema_admin_db_adapter();
    $validationService = new ValidationService($databaseAdapter);
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!$validationService->checkRateLimit("api_security_dashboard", $ip)) {
        http_response_code(429);
        echo json_encode([
            'success' => false, 
            'error' => 'Demasiadas solicitudes. Intenta más tarde.',
            'retry_after' => 30
        ]);
        exit;
    }
    
    $servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
    $usuario = $servicioAutenticacion->verificarSesion();

    if (!$usuario || ($usuario['rol'] ?? '') !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $securityLogger = new SecurityLoggingService($databaseAdapter);
    
    // Obtener los últimos 10 logs de seguridad (que actúan como alertas en tiempo real)
    $latestLogs = $securityLogger->getSecurityLogs(10);
    
    $realTimeAlerts = [];
    foreach ($latestLogs as $log) {
        $realTimeAlerts[] = [
            'severity' => strtolower($log['level'] ?? 'info'),
            'title' => 'Evento de Seguridad: ' . ($log['level'] ?? 'INFO'),
            'timestamp' => $log['timestamp'] ?? date('Y-m-d H:i:s'),
            'description' => $log['message'] ?? ''
        ];
    }
    
    // Calcular amenazas activas
    $stats = $securityLogger->getSecurityStats();
    
    $response = [
        'success' => true,
        'threatStats' => [
            'active_threats' => (int) ($stats['critical_events_24h'] ?? 0)
        ],
        'realTimeAlerts' => $realTimeAlerts
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
