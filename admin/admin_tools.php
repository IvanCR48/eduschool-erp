<?php 
// Herramientas Administrativas Avanzadas
require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\PermissionService;
use SistemaAdmin\Services\SessionService;
use SistemaAdmin\Services\ValidationService;
use SistemaAdmin\Controllers\AdminToolsController;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$usuario = $servicioAutenticacion->verificarSesion();

if (!$usuario) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

// Verificar permisos de administrador
$sessionService = new SessionService($databaseAdapter);
$permissionService = new PermissionService($databaseAdapter, $sessionService);

if (!$permissionService->tienePermiso('administrar_sistema')) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

$controller = new AdminToolsController($databaseAdapter);
$validationService = new ValidationService($databaseAdapter);

// Manejar acciones con validación
$action = $_GET['action'] ?? 'dashboard';

// Validar acción
if (!$validationService->validateAction($action)) {
    header('Location: ../index.php?error=invalid_action');
    exit();
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $action;
    
    // Validar acción POST
    if (!$validationService->validateAction($action)) {
        header('Location: ../index.php?error=invalid_action');
        exit();
    }
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!$validationService->checkRateLimit($action, $ip)) {
        $result = ['success' => false, 'mensaje' => 'Demasiadas solicitudes. Intenta más tarde.'];
    } else {
        switch ($action) {
            case 'crear_backup':
                $result = $controller->crearBackup();
                break;
            case 'restaurar_backup':
                $archivo = $validationService->sanitizeInput($_POST['archivo'] ?? '');
                $result = $controller->restaurarBackup($archivo);
                break;
            case 'eliminar_backup':
                $archivo = $validationService->sanitizeInput($_POST['archivo'] ?? '');
                $result = $controller->eliminarBackup($archivo);
                break;
            case 'actualizar_config':
                $config = [];
                if (isset($_POST['config']) && is_array($_POST['config'])) {
                    foreach ($_POST['config'] as $key => $value) {
                        $config[$validationService->sanitizeInput($key)] = $validationService->sanitizeInput($value);
                    }
                }
                $result = $controller->actualizarConfiguracion($config);
                break;
            case 'limpiar_cache':
                $result = $controller->limpiarCache();
                break;
            case 'optimizar_db':
                $result = $controller->optimizarBaseDatos();
                break;
            case 'revocar_sesion':
                $sessId = $validationService->sanitizeInput($_POST['session_id'] ?? '');
                $result = $controller->revocarSesion($sessId);
                break;
            case 'revocar_otras_sesiones':
                $result = $controller->revocarTodasLasSesionesExcepto(session_id());
                break;
            case 'limpiar_log':
                $tipo = $validationService->sanitizeInput($_POST['tipo'] ?? '');
                $result = $controller->limpiarLog($tipo);
                break;
        }
    }
}

if ($action === 'descargar_backup') {
    if (isset($_GET['token'])) {
        $controller->descargarBackupConToken($_GET['token']);
        exit;
    }
    header('Location: ../index.php?error=missing_token');
    exit();
}

$pageTitle = 'Herramientas Administrativas - Sistema E.E.S.T N°2';
$currentPage = 'admin_tools.php';
$bodyClass = 'admin-tools-page';

// Asegurar que la ruta del CSS sea correcta desde admin/
$GLOBALS['css_path'] = '../css/style.css';

sistema_admin_send_html_security_headers();
include __DIR__ . '/../includes/header.php';

// Generar token CSRF
$csrfToken = $validationService->generateCSRFToken();
?>
<?php $nonce = $GLOBALS['csp_nonce'] ?? ''; ?>
<script src="../js/admin_tools.js" nonce="<?php echo htmlspecialchars($nonce); ?>"></script>
<?php

// Obtener datos del dashboard
$dashboard = $controller->obtenerDashboard();
$metricas = $dashboard['data']['metricas'] ?? [];
$alertas = $dashboard['data']['alertas'] ?? [];
$backups = $dashboard['data']['backups_recientes'] ?? [];
$config = $dashboard['data']['configuracion'] ?? [];
$backupCron = $dashboard['data']['backup_cron'] ?? [];
$queueCron = $dashboard['data']['queue_cron'] ?? [];
$queueStats = $dashboard['data']['queue_stats'] ?? [];
?>

<style>
.admin-tools-container {
    padding: 2rem;
    background: #f8fafc;
    min-height: 100vh;
}

.section-header {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.section-header h2 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.section-header h2 i {
    color: #3b82f6;
    font-size: 1.5rem;
}

.alert-banner {
    padding: 1rem;
    margin-bottom: 2rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-banner.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-banner.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.tabs-container {
    margin-top: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.tabs {
    display: flex;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    margin: 0;
    padding: 0;
}

.tab {
    flex: 1;
    padding: 1.25rem 1.5rem;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    color: #64748b;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    position: relative;
    border-right: 1px solid #e2e8f0;
}

.tab:last-child {
    border-right: none;
}

.tab:hover {
    color: #3b82f6;
    background: #f1f5f9;
}

.tab.active {
    color: #3b82f6;
    background: white;
    box-shadow: 0 -2px 0 0 #3b82f6 inset;
}

.tab i {
    font-size: 1.125rem;
}

.tab-content {
    display: none;
    padding: 3rem;
    min-height: 400px;
}

.tab-content h3 {
    padding: 1rem 0;
    margin-bottom: 2rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
}

.section-title {
    padding: 1.5rem 0;
    margin-bottom: 2rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
}

.card-title {
    padding: 1rem 0;
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
}

.metric-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.metric-title {
    font-size: 0.875rem;
    color: var(--secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.5rem 0;
    margin-bottom: 0.5rem;
}

.metric-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    position: relative;
    z-index: 2;
}

.metric-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.metric-icon.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.metric-icon.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.metric-icon.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 0.5rem;
    padding: 0.5rem 0;
}

.metric-label {
    font-size: 0.875rem;
    color: var(--secondary-color);
    padding: 0.25rem 0;
}

.alerts-container {
    margin-bottom: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
}

.card {
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.alert-item {
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-item.warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.alert-item.error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.btn-action {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.btn-action.primary {
    background: var(--primary-color);
    color: white;
}

.btn-action.primary:hover {
    background: var(--primary-hover, var(--primary-dark));
    color: #fff;
}

.btn-action.success {
    background: var(--success-color);
    color: white;
}

.btn-action.success:hover {
    opacity: 0.9;
}

.btn-action.danger {
    background: var(--danger-color);
    color: white;
}

.btn-action.danger:hover {
    opacity: 0.9;
}

.config-form {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.config-section {
    margin-bottom: 2rem;
}

.config-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-color);
}

.config-field {
    margin-bottom: 1.5rem;
}

.config-field label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
}

.config-field input,
.config-field select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--medium-gray);
    border-radius: 6px;
    font-size: 1rem;
}

.config-field small {
    display: block;
    margin-top: 0.25rem;
    color: var(--secondary-color);
    font-size: 0.875rem;
}

.backup-list {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.backup-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--medium-gray);
}

.backup-item:last-child {
    border-bottom: none;
}

.backup-info {
    flex: 1;
}

.backup-name {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.25rem;
}

.backup-meta {
    font-size: 0.875rem;
    color: var(--secondary-color);
}

.backup-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-small.download {
    background: var(--primary-color);
    color: white;
}

.btn-small.restore {
    background: var(--success-color);
    color: white;
}

.btn-small.delete {
    background: var(--danger-color, #dc2626);
    color: white;
}

form.backup-delete-form {
    display: inline;
    margin: 0;
}

.health-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.health-indicator.excelente {
    background: #d4edda;
    color: #155724;
}

.health-indicator.bueno {
    background: #d1ecf1;
    color: #0c5460;
}

.health-indicator.precaucion {
    background: #fff3cd;
    color: #856404;
}

.health-indicator.critico {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .tabs {
        overflow-x: auto;
    }
    
    .tab {
        white-space: nowrap;
    }
}
</style>

<div class="admin-tools-container">
    <div class="section-header">
        <h2><i class="fas fa-tools"></i> <?php echo htmlspecialchars(__('admin_tools.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p style="color: var(--secondary-color); margin-top: 0.5rem;"><?php echo htmlspecialchars(__('admin_tools.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if ($result): ?>
        <div class="alert-banner <?php echo $result['success'] ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo $result['success'] ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($result['mensaje']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($alertas)): ?>
        <div class="alerts-container card">
            <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('auto.alertas_activas'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php foreach ($alertas as $alerta): ?>
                <div class="alert-item <?php echo $alerta['tipo']; ?>">
                    <i class="fas fa-<?php echo $alerta['tipo'] === 'error' ? 'times-circle' : 'exclamation-triangle'; ?>"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($alerta['mensaje']); ?></strong>
                        <?php if (isset($alerta['detalles'])): ?>
                            <br><small><?php echo htmlspecialchars($alerta['detalles']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 1.5rem; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08);">
        <div class="card-header" style="padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;">
            <h3 class="card-title" style="margin: 0; font-size: 1.1rem;"><i class="fas fa-calendar-check"></i><?php echo htmlspecialchars(__('admin_tools.feb_mar_closure'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            <p style="margin: 0 0 1rem; color: #64748b;"><?php echo htmlspecialchars(__('admin_tools.feb_mar_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="calendario_escolar.php" class="btn btn-primary"><i class="fas fa-external-link-alt"></i><?php echo htmlspecialchars(__('auto.abrir_configuraci_n_de_calendario'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab active" data-tab="monitoring">
                <i class="fas fa-chart-line"></i> <?php echo htmlspecialchars(__('admin.monitoring'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <button class="tab" data-tab="backups">
                <i class="fas fa-database"></i> <?php echo htmlspecialchars(__('admin.backups'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <button class="tab" data-tab="configuration">
                <i class="fas fa-cog"></i> <?php echo htmlspecialchars(__('admin.configuration'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <button class="tab" data-tab="sessions">
                <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars(__('admin_tools.active_sessions_control'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <button class="tab" data-tab="logs">
                <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars(__('admin_tools.server_log_viewer'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <button class="tab" data-tab="maintenance">
                <i class="fas fa-wrench"></i> <?php echo htmlspecialchars(__('admin_tools.maintenance_tasks'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>

        <!-- Tab: Monitoreo -->
        <div class="tab-content active" id="tab-monitoring">
            <h3 class="section-title"><?php echo htmlspecialchars(__('auto.estado_del_sistema'), ENT_QUOTES, 'UTF-8'); ?></h3>
            
            <div class="metrics-grid">
                <!-- Memoria PHP -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title"><?php echo htmlspecialchars(__('auto.memoria_php'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="metric-icon primary">
                            <i class="fas fa-memory"></i>
                        </div>
                    </div>
                    <div class="metric-value">
                        <?php echo $metricas['sistema']['memoria_php']['uso_actual_formateado'] ?? 'N/A'; ?>
                    </div>
                    <div class="metric-label"><?php echo htmlspecialchars(__('auto.pico'), ENT_QUOTES, 'UTF-8'); ?><?php echo $metricas['sistema']['memoria_php']['pico_maximo_formateado'] ?? 'N/A'; ?>
                    </div>
                </div>

                <!-- Base de Datos -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title"><?php echo htmlspecialchars(__('auto.base_de_datos'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="metric-icon success">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                    <div class="metric-value">
                        <?php echo $metricas['base_datos']['tamaño_db_formateado'] ?? 'N/A'; ?>
                    </div>
                    <div class="metric-label">
                        <?php echo $metricas['base_datos']['numero_tablas'] ?? 0; ?><?php echo htmlspecialchars(__('auto.tablas'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <!-- Disco -->
                <?php if (isset($metricas['sistema']['espacio_disco']['disponible']) && $metricas['sistema']['espacio_disco']['disponible']): ?>
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title"><?php echo htmlspecialchars(__('auto.espacio_en_disco'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="metric-icon warning">
                            <i class="fas fa-hdd"></i>
                        </div>
                    </div>
                    <div class="metric-value">
                        <?php echo round($metricas['sistema']['espacio_disco']['porcentaje_uso'], 1); ?>%
                    </div>
                    <div class="metric-label">
                        <?php echo $metricas['sistema']['espacio_disco']['libre_formateado']; ?><?php echo htmlspecialchars(__('auto.libre'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <?php endif; ?>

                <!-- Usuarios Activos -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title"><?php echo htmlspecialchars(__('auto.usuarios'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="metric-icon info">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="metric-value">
                        <?php echo $metricas['aplicacion']['usuarios']['sesiones_activas'] ?? 0; ?>
                    </div>
                    <div class="metric-label"><?php echo htmlspecialchars(__('auto.sesiones_activas'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <!-- Cola de trabajos -->
            <div class="card" style="margin-top: 2rem;">
                <h3 class="card-title"><i class="fas fa-tasks"></i><?php echo htmlspecialchars(__('auto.cola_de_trabajos_as_ncronos'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p style="color: var(--secondary-color); margin: 0 0 1rem 0; font-size: 0.9rem;">
                    Trabajos pesados se guardan en la base de datos y los procesa el cron HTTP. Cola por defecto: <code>default</code>.
                </p>
                <div class="metrics-grid" style="margin-bottom: 1rem;">
                    <div>
                        <strong><?php echo htmlspecialchars(__('auto.pendientes_listos_para_ejecutar'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo (int) ($queueStats['pendientes'] ?? 0); ?></span>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars(__('auto.fallidos_ltimos_7_d_as'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="color: var(--danger-color);"><?php echo (int) ($queueStats['fallidos_7d'] ?? 0); ?></span>
                    </div>
                </div>
                <?php if (!empty($queueCron['path_con_token'])): ?>
                <div style="padding: 1rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem;"><?php echo htmlspecialchars(__('auto.cron_del_worker'), ENT_QUOTES, 'UTF-8'); ?></h4>
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: #475569;">
                        Programe cada 1–5 minutos (por ejemplo con <code>curl</code>). Parámetros opcionales: <code>queue=default</code>, <code>limit=10</code>.
                    </p>
                    <code style="display: block; word-break: break-all; font-size: 0.8rem; padding: 0.5rem; background: #fff; border-radius: 4px;">
                        <?php
                        $schemeQ = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $hostQ = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        echo htmlspecialchars($schemeQ . '://' . $hostQ . ($queueCron['path_con_token'] ?? ''), ENT_QUOTES, 'UTF-8');
                        ?>
                    </code>
                </div>
                <?php endif; ?>
            </div>

            <!-- Salud del Sistema -->
            <div class="card">
                <h3 class="card-title"><?php echo htmlspecialchars(__('auto.salud_del_sistema'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="health-indicator <?php echo $metricas['rendimiento']['salud']['nivel'] ?? 'bueno'; ?>">
                        <i class="fas fa-heartbeat"></i>
                        <?php echo strtoupper($metricas['rendimiento']['salud']['nivel'] ?? 'Desconocido'); ?>
                    </span>
                    <span style="color: var(--secondary-color);"><?php echo htmlspecialchars(__('auto.puntuaci_n'), ENT_QUOTES, 'UTF-8'); ?><?php echo $metricas['rendimiento']['salud']['puntuacion'] ?? 0; ?>/100
                    </span>
                </div>
            </div>

            <!-- Seguridad -->
            <div class="card" style="margin-top: 2rem;">
                <h3 class="card-title"><i class="fas fa-shield-alt"></i><?php echo htmlspecialchars(__('auto.estado_de_seguridad'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="metrics-grid">
                    <div>
                        <strong><?php echo htmlspecialchars(__('auto.logins_fallidos_24h'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="color: var(--danger-color);">
                            <?php echo $metricas['seguridad']['logins_fallidos_24h'] ?? 0; ?>
                        </span>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars(__('auto.usuarios_bloqueados'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo $metricas['seguridad']['usuarios_bloqueados'] ?? 0; ?></span>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars(__('auto.nivel_de_amenaza'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="text-transform: uppercase; font-weight: 600;">
                            <?php echo $metricas['seguridad']['nivel_amenaza'] ?? 'bajo'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Backups -->
        <div class="tab-content" id="tab-backups">
            <h3 class="section-title"><?php echo htmlspecialchars(__('admin_tools.manage_backups'), ENT_QUOTES, 'UTF-8'); ?></h3>
            
            <div class="action-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="crear_backup">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn-action primary">
                        <i class="fas fa-plus"></i><?php echo htmlspecialchars(__('admin_tools.create_backup_now'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </div>

            <?php if (!empty($backupCron['path_con_token'])): ?>
            <div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem;"><?php echo htmlspecialchars(__('admin_tools.automatic_cron_backup'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: #475569;">
                    <?php echo htmlspecialchars(__('admin_tools.cron_help_text'), ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <code style="display: block; word-break: break-all; font-size: 0.8rem; padding: 0.5rem; background: #fff; border-radius: 4px;">
                    <?php
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    echo htmlspecialchars($scheme . '://' . $host . ($backupCron['path_con_token'] ?? ''), ENT_QUOTES, 'UTF-8');
                    ?>
                </code>
            </div>
            <?php endif; ?>

            <div class="backup-list">
                <h4 style="margin-bottom: 1rem;"><?php echo htmlspecialchars(__('admin_tools.recent_backups'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <?php if (!empty($backups)): ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item">
                            <div class="backup-info">
                                <div class="backup-name">
                                    <i class="fas fa-file-archive"></i>
                                    <?php echo htmlspecialchars($backup['nombre']); ?>
                                </div>
                                <div class="backup-meta">
                                    <?php echo $backup['tamaño_formateado']; ?> • 
                                    <?php echo date('d/m/Y H:i', strtotime($backup['fecha'])); ?>
                                </div>
                            </div>
                            <div class="backup-actions">
                                <?php $token = $controller->getBackupDownloadToken($backup['nombre']); ?>
                                <a href="?action=descargar_backup&token=<?php echo urlencode($token); ?>" 
                                   class="btn-small download">
                                    <i class="fas fa-download"></i><?php echo htmlspecialchars(__('admin_tools.download'), ENT_QUOTES, 'UTF-8'); ?></a>
                                <form method="POST" class="backup-delete-form js-confirm-submit"
                                      data-confirm-message="<?php echo htmlspecialchars('¿Eliminar permanentemente este backup del servidor? No se puede deshacer.', ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="eliminar_backup">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($backup['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn-small delete" title="Eliminar backup">
                                        <i class="fas fa-trash-alt"></i><?php echo htmlspecialchars(__('admin_tools.delete'), ENT_QUOTES, 'UTF-8'); ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--secondary-color); text-align: center; padding: 2rem;"><?php echo htmlspecialchars(__('auto.no_hay_backups_disponibles'), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Configuración -->
        <div class="tab-content" id="tab-configuration">
            <h3 class="section-title"><?php echo htmlspecialchars(__('admin_tools.system_configuration'), ENT_QUOTES, 'UTF-8'); ?></h3>
            
            <form method="POST" class="config-form">
                <input type="hidden" name="action" value="actualizar_config">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <?php foreach ($config as $categoria => $configs): ?>
                    <div class="config-section">
                        <h4 class="config-section-title">
                            <i class="fas fa-cog"></i> <?php echo htmlspecialchars(__('admin_tools.cat_' . $categoria), ENT_QUOTES, 'UTF-8'); ?>
                        </h4>
                        
                        <?php foreach ($configs as $clave => $data): ?>
                            <?php
                                $fieldCleanKey = str_replace('.', '_', $clave);
                                $labelVal = __('admin_tools.field_' . $fieldCleanKey);
                                if ($labelVal === 'admin_tools.field_' . $fieldCleanKey) {
                                    $labelVal = ucwords(str_replace('_', ' ', explode('.', $clave)[1] ?? $clave));
                                }
                                $descVal = __('admin_tools.desc_' . $fieldCleanKey);
                                if ($descVal === 'admin_tools.desc_' . $fieldCleanKey) {
                                    $descVal = $data['descripcion'] ?? '';
                                }
                            ?>
                            <div class="config-field">
                                <label for="<?php echo htmlspecialchars($clave); ?>">
                                    <?php echo htmlspecialchars($labelVal, ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                                
                                <?php if ($clave === 'academico.perfil_escuela'): ?>
                                    <select name="config[<?php echo htmlspecialchars($clave); ?>]" id="<?php echo htmlspecialchars($clave); ?>">
                                        <option value="general" <?php echo $data['valor'] === 'general' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.profile_general'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="technical" <?php echo $data['valor'] === 'technical' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.profile_technical'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="custom" <?php echo $data['valor'] === 'custom' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.profile_custom'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    </select>
                                <?php elseif ($clave === 'academico.escala_notas'): ?>
                                    <select name="config[<?php echo htmlspecialchars($clave); ?>]" id="<?php echo htmlspecialchars($clave); ?>">
                                        <option value="numeric_10" <?php echo $data['valor'] === 'numeric_10' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.scale_numeric_10'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="numeric_100" <?php echo $data['valor'] === 'numeric_100' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.scale_numeric_100'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="letter_af" <?php echo $data['valor'] === 'letter_af' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.scale_letter_af'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="gpa" <?php echo $data['valor'] === 'gpa' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.scale_gpa'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="tea_tep_ted" <?php echo $data['valor'] === 'tea_tep_ted' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.scale_tea_tep_ted'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    </select>
                                <?php elseif ($clave === 'academico.cantidad_periodos'): ?>
                                    <select name="config[<?php echo htmlspecialchars($clave); ?>]" id="<?php echo htmlspecialchars($clave); ?>">
                                        <option value="2" <?php echo (string)$data['valor'] === '2' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.terms_2'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="3" <?php echo (string)$data['valor'] === '3' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.terms_3'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="4" <?php echo (string)$data['valor'] === '4' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('admin_tools.terms_4'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    </select>
                                <?php elseif ($data['tipo'] === 'boolean'): ?>
                                    <select name="config[<?php echo htmlspecialchars($clave); ?>]" 
                                            id="<?php echo htmlspecialchars($clave); ?>">
                                        <option value="1" <?php echo $data['valor'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.activado'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="0" <?php echo !$data['valor'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.desactivado'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    </select>
                                <?php else: ?>
                                    <input type="<?php echo $data['tipo'] === 'number' ? 'number' : 'text'; ?>" 
                                           name="config[<?php echo htmlspecialchars($clave); ?>]"
                                           id="<?php echo htmlspecialchars($clave); ?>"
                                           value="<?php echo htmlspecialchars($data['valor']); ?>">
                                <?php endif; ?>
                                
                                <?php if (!empty($descVal)): ?>
                                    <small><?php echo htmlspecialchars($descVal, ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="action-buttons">
                    <button type="submit" class="btn-action success">
                        <i class="fas fa-save"></i><?php echo htmlspecialchars(__('admin_tools.save_changes'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </form>
        </div>

        <!-- Tab: Sesiones Activas -->
        <div class="tab-content" id="tab-sessions">
            <h3 class="section-title"><?php echo htmlspecialchars(__('admin_tools.active_sessions_control'), ENT_QUOTES, 'UTF-8'); ?></h3>
            
            <div class="action-buttons" style="margin-bottom: 2rem;">
                <form method="POST" class="js-confirm-submit" style="display: inline;" data-confirm-message="<?php echo htmlspecialchars(__('admin_tools.close_other_sessions') . '?', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="revocar_otras_sesiones">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn-action danger">
                        <i class="fas fa-user-slash"></i><?php echo htmlspecialchars(__('admin_tools.close_other_sessions'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </div>

            <div class="card" style="padding: 1.5rem; overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.user_dni'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.full_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.role'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.ip_address'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.device_browser'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.session_start'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem;"><?php echo htmlspecialchars(__('admin_tools.last_activity'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="padding: 0.75rem; text-align: center;"><?php echo htmlspecialchars(__('admin_tools.action'), ENT_QUOTES, 'UTF-8'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sesResp = $controller->obtenerSesionesActivasConDetalle();
                        $sesList = $sesResp['data'] ?? [];
                        $currentSessId = session_id();
                        if (!empty($sesList)): 
                            foreach ($sesList as $s): 
                                $isCurrent = ($s['session_id'] === $currentSessId);
                                $ua = htmlspecialchars($s['user_agent'] ?? '');
                                $uaShort = strlen($ua) > 40 ? substr($ua, 0, 37) . '...' : $ua;
                        ?>
                            <tr style="border-bottom: 1px solid #e2e8f0; vertical-align: middle;">
                                <td style="padding: 0.75rem;"><strong><?php echo htmlspecialchars($s['dni'] ?? 'N/A'); ?></strong></td>
                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars(($s['nombre'] ?? '') . ' ' . ($s['apellido'] ?? '')); ?></td>
                                <td style="padding: 0.75rem;">
                                    <span class="badge" style="background: #e2e8f0; color: #475569; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase;">
                                        <?php echo htmlspecialchars($s['rol'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td style="padding: 0.75rem;"><code><?php echo htmlspecialchars($s['ip_address'] ?? 'N/A'); ?></code></td>
                                <td style="padding: 0.75rem;"><span title="<?php echo $ua; ?>" style="cursor: help; font-size: 0.85rem; color: #64748b;"><?php echo $uaShort; ?></span></td>
                                <td style="padding: 0.75rem; font-size: 0.85rem;"><?php echo date('d/m/Y H:i', strtotime($s['creado_en'])); ?></td>
                                <td style="padding: 0.75rem; font-size: 0.85rem;"><?php echo date('d/m/Y H:i', strtotime($s['ultima_actividad'])); ?></td>
                                <td style="padding: 0.75rem; text-align: center;">
                                    <?php if ($isCurrent): ?>
                                        <span style="color: #22c55e; font-weight: 600; font-size: 0.85rem;"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars(__('admin_tools.current_session'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        <form method="POST" class="js-confirm-submit" style="display: inline;" data-confirm-message="<?php echo htmlspecialchars(__('admin_tools.close') . '?', ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="revocar_sesion">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($s['session_id']); ?>">
                                            <button type="submit" class="btn-small delete">
                                                <i class="fas fa-sign-out-alt"></i><?php echo htmlspecialchars(__('admin_tools.close'), ENT_QUOTES, 'UTF-8'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="8" style="padding: 2rem; text-align: center; color: #64748b;"><?php echo htmlspecialchars(__('admin_tools.no_active_sessions'), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Visor de Logs -->
        <div class="tab-content" id="tab-logs">
            <h3 class="section-title"><?php echo htmlspecialchars(__('admin_tools.server_log_viewer'), ENT_QUOTES, 'UTF-8'); ?></h3>
            
            <div class="card" style="padding: 1.5rem; margin-bottom: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="log-type" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo htmlspecialchars(__('admin_tools.log_file'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select id="log-type" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="error"><?php echo htmlspecialchars(__('admin_tools.system_errors_log'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="security"><?php echo htmlspecialchars(__('admin_tools.security_events_log'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="audit"><?php echo htmlspecialchars(__('admin_tools.audit_logs_log'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                    </div>
                    
                    <div style="flex: 1; min-width: 200px;">
                        <label for="log-filter" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo htmlspecialchars(__('admin_tools.search_logs'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" id="log-filter" placeholder="<?php echo htmlspecialchars(__('admin_tools.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    
                    <div style="width: 120px;">
                        <label for="log-limit" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo htmlspecialchars(__('admin_tools.limit'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select id="log-limit" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="50"><?php echo htmlspecialchars(__('admin_tools.lines_50'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="100" selected><?php echo htmlspecialchars(__('admin_tools.lines_100'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="200"><?php echo htmlspecialchars(__('admin_tools.lines_200'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="500"><?php echo htmlspecialchars(__('admin_tools.lines_500'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                    </div>
                    
                    <div>
                        <button type="button" id="btn-refresh-logs" class="btn-action primary" style="height: 46px;">
                            <i class="fas fa-sync-alt"></i><?php echo htmlspecialchars(__('admin_tools.refresh'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    
                    <div>
                        <form method="POST" id="form-clear-log" class="js-confirm-submit" style="display: inline;" data-confirm-message="<?php echo htmlspecialchars(__('admin_tools.clear_log') . '?', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="limpiar_log">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="tipo" id="clear-log-type" value="error">
                            <button type="submit" id="btn-clear-log" class="btn-action danger" style="height: 46px;">
                                <i class="fas fa-trash-alt"></i><?php echo htmlspecialchars(__('admin_tools.clear_log'), ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card" style="padding: 0; background: #0f172a; border-radius: 12px; overflow: hidden; border: 1px solid #1e293b; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                <div style="padding: 0.75rem 1.5rem; background: #1e293b; color: #94a3b8; font-family: monospace; font-size: 0.85rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
                    <span><?php echo htmlspecialchars(__('admin_tools.log_content'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span id="log-stats" style="color: #38bdf8;"><?php echo htmlspecialchars(__('admin_tools.loading'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div id="log-viewer-content" style="max-height: 600px; overflow-y: auto; padding: 1.5rem; font-family: 'Courier New', Courier, monospace; font-size: 0.9rem; line-height: 1.5; color: #e2e8f0;">
                    <!-- Los logs se cargarán dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Tab: Mantenimiento -->
        <div class="tab-content" id="tab-maintenance">
            <h3 class="section-title"><?php echo htmlspecialchars(__('admin_tools.maintenance_tasks'), ENT_QUOTES, 'UTF-8'); ?></h3>
            
            <div class="action-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="limpiar_cache">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn-action primary">
                        <i class="fas fa-broom"></i><?php echo htmlspecialchars(__('admin_tools.clear_cache'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
                
                <form method="POST" style="display: inline;" class="js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars(__('admin_tools.optimize_database') . '?', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="optimizar_db">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn-action success">
                        <i class="fas fa-database"></i><?php echo htmlspecialchars(__('admin_tools.optimize_database'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </div>

            <!-- Información del Sistema -->
            <div class="card">
                <h4 style="margin-bottom: 1rem;"><?php echo htmlspecialchars(__('admin_tools.system_information'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <table class="table">
                    <tr>
                        <td><strong><?php echo htmlspecialchars(__('admin_tools.php_version'), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo $metricas['sistema']['php_version'] ?? PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(__('admin_tools.operating_system'), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo $metricas['sistema']['sistema_operativo'] ?? PHP_OS; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(__('admin_tools.memory_limit'), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo $metricas['sistema']['memoria_php']['limite'] ?? ini_get('memory_limit'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(__('admin_tools.database'), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo $metricas['base_datos']['estado'] ?? 'Operativo'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo htmlspecialchars($GLOBALS['csp_nonce'] ?? ''); ?>">
// Manejo interactivo de pestañas (Tabs)
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const target = this.getAttribute('data-tab');
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        const targetContent = document.getElementById('tab-' + target);
        if (targetContent) {
            targetContent.classList.add('active');
        }
        if (target === 'logs' && typeof cargarLogs === 'function') {
            cargarLogs();
        }
    });
});

// Activar pestaña según URL (?tab=configuration o #configuration)
(function() {
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get('tab') || window.location.hash.replace('#', '');
    if (activeTab) {
        const tabBtn = document.querySelector(`.tab[data-tab="${activeTab}"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    }
})();

// Confirmación para acciones peligrosas
document.querySelectorAll('form[method="POST"]').forEach(form => {
    const action = form.querySelector('input[name="action"]')?.value;
    if (action === 'restaurar_backup') {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que quieres restaurar este backup? Esta acción sobrescribirá los datos actuales.')) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
