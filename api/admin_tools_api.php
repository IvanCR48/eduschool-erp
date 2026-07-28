<?php
/**
 * API para Herramientas Administrativas
 * 
 * Proporciona endpoints para obtener métricas y ejecutar acciones
 */

require_once __DIR__ . '/../includes/database_bootstrap.php';

use SistemaAdmin\Middleware\SecurityHeadersMiddleware;
use SistemaAdmin\Services\ValidationService;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\PermissionService;
use SistemaAdmin\Services\SessionService;
use SistemaAdmin\Controllers\AdminToolsController;

// Aplicar headers de seguridad para API
SecurityHeadersMiddleware::applyAPIHeaders();

try {
    $databaseAdapter = sistema_admin_db_adapter();
    $validationService = new ValidationService($databaseAdapter);
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $action = $_GET['action'] ?? 'metricas';

    if (!$validationService->checkRateLimit("api_{$action}", $ip)) {
        http_response_code(429);
        echo json_encode([
            'success' => false, 
            'error' => 'Demasiadas solicitudes. Intenta más tarde.',
            'retry_after' => 300
        ]);
        exit;
    }
    
    $servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
    $usuario = $servicioAutenticacion->verificarSesion();

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autenticado']);
        exit;
    }

    // Verificar permisos de administrador
    $sessionService = new SessionService($databaseAdapter);
    $permissionService = new PermissionService($databaseAdapter, $sessionService);

    if (!$permissionService->tienePermiso('administrar_sistema')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sin permisos']);
        exit;
    }

    $controller = new AdminToolsController($databaseAdapter);

    switch ($action) {
        case 'metricas':
            $result = $controller->obtenerMetricas();
            break;
            
        case 'historial':
            $horas = (int)($_GET['horas'] ?? 24);
            $result = $controller->obtenerHistorialMetricas($horas);
            break;
            
        case 'alertas':
            $dashboard = $controller->obtenerDashboard();
            $result = [
                'success' => true,
                'data' => ['alertas' => $dashboard['data']['alertas'] ?? []]
            ];
            break;
            
        case 'sesiones':
            $result = $controller->obtenerSesionesActivasConDetalle();
            break;
            
        case 'logs':
            $tipo = $validationService->sanitizeInput($_GET['tipo'] ?? 'error');
            $limite = (int)($_GET['limite'] ?? 100);
            $filtro = isset($_GET['filtro']) ? $validationService->sanitizeInput($_GET['filtro']) : null;
            $result = $controller->obtenerLogs($tipo, $limite, $filtro);
            break;
            
        case 'info_sistema':
            $result = $controller->obtenerInfoSistema();
            break;
            
        case 'verificar_integridad':
            $result = $controller->verificarIntegridad();
            break;
            
        default:
            http_response_code(400);
            $result = ['success' => false, 'error' => 'Acción no válida'];
            break;
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
