<?php
/**
 * Security Dashboard - Panel de Métricas de Seguridad en Tiempo Real
 */

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';

use SistemaAdmin\Services\SecurityLoggingService;

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit;
}

$database = sistema_admin_db_adapter();
$securityLogger = new SecurityLoggingService($database);

// Obtener métricas de seguridad
$securityStats = $securityLogger->getSecurityStats();

// Placeholders: paneles de amenazas / sesiones / incidentes (servicios demo eliminados).
$sessionStats = [
    'secure_sessions' => 0,
    'total_sessions' => 0,
    'healthy_sessions' => 0,
    'medium_risk_sessions' => 0,
    'high_risk_sessions' => 0,
];
$threatStats = [
    'active_threats' => 0,
    'indicators_checked' => 0,
    'recent_threats' => [],
];
$incidentStats = [
    'total_incidents' => 0,
    'auto_resolved' => 0,
    'recent_actions' => [],
];

// Paginación y búsqueda de bitácora de auditoría
$level_filter = trim((string) (filter_input(INPUT_GET, 'level', FILTER_DEFAULT) ?? ''));
$search_filter = trim((string) (filter_input(INPUT_GET, 'search', FILTER_DEFAULT) ?? ''));

$pageListado = max(1, (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$perPageListado = 20;

$levelParam = $level_filter !== '' ? $level_filter : null;
$ipParam = null;
$userIdParam = null;
if ($search_filter !== '') {
    if (filter_var($search_filter, FILTER_VALIDATE_IP)) {
        $ipParam = $search_filter;
    } else {
        $userIdParam = $search_filter;
    }
}

$totalLogsFiltrado = $securityLogger->countSecurityLogs($levelParam, $ipParam, $userIdParam);

$paginationSvc = new \SistemaAdmin\Services\PaginationService($database);
$pagination = $paginationSvc->calculatePagination($totalLogsFiltrado, $pageListado, $perPageListado);
$pageNumbers = $paginationSvc->getPageNumbers((int) $pagination['total_pages'], (int) $pagination['current_page'], 7);
$pagination = array_merge($pagination, ['page_numbers' => $pageNumbers]);

$securityLogs = $securityLogger->getSecurityLogs(
    $perPageListado,
    $levelParam,
    $ipParam,
    $userIdParam,
    null,
    null,
    (int) $pagination['offset']
);

$dashboardUrlParams = [];
if ($level_filter !== '') {
    $dashboardUrlParams['level'] = $level_filter;
}
if ($search_filter !== '') {
    $dashboardUrlParams['search'] = $search_filter;
}

sistema_admin_send_html_security_headers();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('auto.security_dashboard', 'Security Dashboard'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .dashboard-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dashboard-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .security-status {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-5px);
        }

        .status-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-healthy { background: #27ae60; }
        .status-warning { background: #f39c12; }
        .status-critical { background: #e74c3c; }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .metric-card h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }

        .metric-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .alert-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .alert-item {
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .alert-item:hover {
            background: #e9ecef;
        }

        .alert-item.warning {
            border-left-color: #f39c12;
        }

        .alert-item.info {
            border-left-color: #3498db;
        }

        .alert-time {
            font-size: 0.8rem;
            color: #7f8c8d;
            float: right;
        }

        .refresh-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
            margin-top: 20px;
        }

        .refresh-btn:hover {
            background: #2980b9;
        }

        .real-time-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #27ae60;
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 8px;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .threat-level {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .threat-clean { background: #d5f4e6; color: #27ae60; }
        .threat-low { background: #fef9e7; color: #f39c12; }
        .threat-medium { background: #fdebd0; color: #e67e22; }
        .threat-high { background: #fadbd8; color: #e74c3c; }
        .threat-critical { background: #f8d7da; color: #c0392b; }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .security-status {
                flex-direction: column;
            }
            
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1>
                <i class="fas fa-shield-alt"></i><?php echo htmlspecialchars(__('auto.security_dashboard'), ENT_QUOTES, 'UTF-8'); ?><span class="real-time-indicator"></span>
            </h1>
            <p><?php echo htmlspecialchars(__('auto.monitoreo_de_seguridad_en_tiempo_real_siste'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <!-- Status Cards -->
        <div class="security-status">
            <div class="status-card">
                <h3>
                    <span class="status-indicator status-healthy"></span>
                    <i class="fas fa-heartbeat"></i><?php echo htmlspecialchars(__('auto.estado_general'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="metric-value"><?= $securityStats['overall_status'] ?? 'HEALTHY' ?></div>
                <div class="metric-label"><?php echo htmlspecialchars(__('auto.sistema_operativo'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="status-card">
                <h3>
                    <span class="status-indicator status-warning"></span>
                    <i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('auto.amenazas_activas'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="metric-value"><?= $threatStats['active_threats'] ?? 0 ?></div>
                <div class="metric-label"><?php echo htmlspecialchars(__('auto.ltimas_24_horas'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="status-card">
                <h3>
                    <span class="status-indicator status-healthy"></span>
                    <i class="fas fa-user-shield"></i><?php echo htmlspecialchars(__('auto.sesiones_seguras'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="metric-value"><?= $sessionStats['secure_sessions'] ?? 0 ?></div>
                <div class="metric-label"><?php echo htmlspecialchars(__('auto.sesiones_monitoreadas'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="status-card">
                <h3>
                    <span class="status-indicator status-critical"></span>
                    <i class="fas fa-bug"></i><?php echo htmlspecialchars(__('auto.incidentes'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="metric-value"><?= $incidentStats['total_incidents'] ?? 0 ?></div>
                <div class="metric-label">Últimas 24 horas</div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="metric-grid">
            <!-- Security Events Chart -->
            <div class="metric-card">
                <h4><i class="fas fa-chart-line"></i><?php echo htmlspecialchars(__('auto.eventos_de_seguridad'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="chart-container">
                    <canvas id="securityEventsChart"></canvas>
                </div>
            </div>

            <!-- Threat Intelligence -->
            <div class="metric-card">
                <h4><i class="fas fa-search"></i><?php echo htmlspecialchars(__('auto.threat_intelligence'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="metric-value"><?= $threatStats['indicators_checked'] ?? 0 ?></div>
                <div class="metric-label"><?php echo htmlspecialchars(__('auto.indicadores_verificados_hoy'), ENT_QUOTES, 'UTF-8'); ?></div>
                
                <div style="margin-top: 20px;">
                    <h5><?php echo htmlspecialchars(__('auto.ltimas_amenazas'), ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div id="threatList">
                        <?php if (!empty($threatStats['recent_threats'])): ?>
                            <?php foreach ($threatStats['recent_threats'] as $threat): ?>
                                <div class="alert-item">
                                    <strong><?= htmlspecialchars($threat['indicator']) ?></strong>
                                    <span class="threat-level threat-<?= strtolower($threat['level']) ?>">
                                        <?= $threat['level'] ?>
                                    </span>
                                    <div class="alert-time"><?= $threat['timestamp'] ?></div>
                                    <div style="clear: both; margin-top: 5px;">
                                        <?= htmlspecialchars($threat['description']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #27ae60; text-align: center; padding: 20px;">
                                <i class="fas fa-check-circle"></i><?php echo htmlspecialchars(__('auto.no_se_detectaron_amenazas_recientes'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Session Analytics -->
            <div class="metric-card">
                <h4><i class="fas fa-users"></i><?php echo htmlspecialchars(__('auto.an_lisis_de_sesiones'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="metric-value"><?= $sessionStats['total_sessions'] ?? 0 ?></div>
                <div class="metric-label"><?php echo htmlspecialchars(__('auto.sesiones_analizadas_hoy'), ENT_QUOTES, 'UTF-8'); ?></div>
                
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span><?php echo htmlspecialchars(__('auto.saludables'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span style="color: #27ae60; font-weight: bold;">
                            <?= $sessionStats['healthy_sessions'] ?? 0 ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span><?php echo htmlspecialchars(__('auto.riesgo_medio'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span style="color: #f39c12; font-weight: bold;">
                            <?= $sessionStats['medium_risk_sessions'] ?? 0 ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span><?php echo htmlspecialchars(__('auto.alto_riesgo'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span style="color: #e74c3c; font-weight: bold;">
                            <?= $sessionStats['high_risk_sessions'] ?? 0 ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Incident Response -->
            <div class="metric-card">
                <h4><i class="fas fa-robot"></i><?php echo htmlspecialchars(__('auto.respuesta_autom_tica'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="metric-value"><?= $incidentStats['auto_resolved'] ?? 0 ?></div>
                <div class="metric-label"><?php echo htmlspecialchars(__('auto.incidentes_resueltos_autom_ticamente'), ENT_QUOTES, 'UTF-8'); ?></div>
                
                <div style="margin-top: 20px;">
                    <h5><?php echo htmlspecialchars(__('auto.acciones_recientes'), ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div id="incidentActions">
                        <?php if (!empty($incidentStats['recent_actions'])): ?>
                            <?php foreach ($incidentStats['recent_actions'] as $action): ?>
                                <div class="alert-item <?= strtolower($action['severity']) ?>">
                                    <strong><?= htmlspecialchars($action['action']) ?></strong>
                                    <div class="alert-time"><?= $action['timestamp'] ?></div>
                                    <div style="clear: both; margin-top: 5px;">
                                        <?= htmlspecialchars($action['description']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #27ae60; text-align: center; padding: 20px;">
                                <i class="fas fa-check-circle"></i><?php echo htmlspecialchars(__('auto.no_hay_acciones_recientes'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Alerts -->
        <div class="metric-card">
            <h4><i class="fas fa-bell"></i><?php echo htmlspecialchars(__('auto.alertas_en_tiempo_real'), ENT_QUOTES, 'UTF-8'); ?></h4>
            <div class="alert-list" id="realTimeAlerts">
                <!-- Las alertas se cargarán dinámicamente -->
            </div>
        </div>

        <button type="button" class="refresh-btn" data-csp-reload="1">
            <i class="fas fa-sync-alt"></i><?php echo htmlspecialchars(__('auto.actualizar_dashboard'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>

    <!-- Bitácora de Eventos de Seguridad (Auditoría) -->
    <div class="card" style="margin-top: 30px; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.5rem; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-history" style="color: #3b82f6;"></i><?php echo htmlspecialchars(__('auto.bit_cora_de_eventos_de_seguridad'), ENT_QUOTES, 'UTF-8'); ?></h3>

        <!-- Formulario de Filtros -->
        <form method="GET" action="security_dashboard.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 100px; gap: 15px; margin-bottom: 20px; align-items: end;">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label for="filter-level" style="font-weight: 600; font-size: 0.875rem; color: #475569;"><?php echo htmlspecialchars(__('auto.nivel_de_severidad'), ENT_QUOTES, 'UTF-8'); ?></label>
                <select name="level" id="filter-level" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: white;">
                    <option value=""><?php echo htmlspecialchars(__('auto.todos_los_niveles'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="INFO" <?= $level_filter === 'INFO' ? 'selected' : '' ?><?php echo htmlspecialchars(__('auto.info'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="WARNING" <?= $level_filter === 'WARNING' ? 'selected' : '' ?><?php echo htmlspecialchars(__('auto.warning'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="CRITICAL" <?= $level_filter === 'CRITICAL' ? 'selected' : '' ?><?php echo htmlspecialchars(__('auto.critical'), ENT_QUOTES, 'UTF-8'); ?></option>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label for="filter-search" style="font-weight: 600; font-size: 0.875rem; color: #475569;"><?php echo htmlspecialchars(__('auto.ip_o_id_de_usuario'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="search" id="filter-search" value="<?= htmlspecialchars($search_filter) ?>" placeholder="Ej: 192.168.1.1 o 45" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 10px; border-radius: 6px; font-weight: 600; border: none; background: #3b82f6; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; height: 38px;">
                <i class="fas fa-filter"></i><?php echo htmlspecialchars(__('auto.filtrar'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>

        <!-- Tabla de Eventos -->
        <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600;">
                        <th style="padding: 12px 15px;"><?php echo htmlspecialchars(__('auto.fecha_hora'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th style="padding: 12px 15px;"><?php echo htmlspecialchars(__('auto.nivel'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th style="padding: 12px 15px;">IP</th>
                        <th style="padding: 12px 15px;"><?php echo htmlspecialchars(__('auto.usuario_id'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th style="padding: 12px 15px;"><?php echo htmlspecialchars(__('auto.descripci_n'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($securityLogs)): ?>
                        <?php foreach ($securityLogs as $log): ?>
                            <?php
                            $levelColor = '#64748b';
                            $levelBg = '#f1f5f9';
                            if ($log['level'] === 'CRITICAL') {
                                $levelColor = '#dc2626';
                                $levelBg = '#fee2e2';
                            } elseif ($log['level'] === 'WARNING') {
                                $levelColor = '#d97706';
                                $levelBg = '#fef3c7';
                            }
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 15px; white-space: nowrap;"><?= htmlspecialchars($log['timestamp']) ?></td>
                                <td style="padding: 12px 15px;">
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; color: <?= $levelColor ?>; background: <?= $levelBg ?>;">
                                        <?= htmlspecialchars($log['level']) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 15px; font-family: monospace;"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                                <td style="padding: 12px 15px;"><?= $log['user_id'] ? htmlspecialchars((string) $log['user_id']) : '<span style="color: #94a3b8;">' . htmlspecialchars(__('auto.anon'), ENT_QUOTES, 'UTF-8') . '</span>' ?></td>
                                <td style="padding: 12px 15px; color: #334155;"><?= htmlspecialchars($log['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">
                                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <br><?php echo htmlspecialchars(__('auto.no_se_encontraron_registros_de_seguridad_con'), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalLogsFiltrado > 0 && $pagination !== null && (int) $pagination['total_pages'] > 1): ?>
        <nav class="pagination-nav" aria-label="Paginación de logs de seguridad" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 0.35rem; padding-top: 20px; margin-top: 15px; border-top: 1px solid #e2e8f0;">
            <?php
            $pn = (int) $pagination['current_page'];
            $tp = (int) $pagination['total_pages'];
            $linkBase = $dashboardUrlParams;
            $mk = static function (array $base, int $p): string {
                $base['page'] = $p;
                return 'security_dashboard.php?' . http_build_query($base);
            };
            ?>
            <?php if (!empty($pagination['has_previous'])): ?>
            <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($mk($linkBase, $pn - 1), ENT_QUOTES, 'UTF-8'); ?>" rel="prev" style="text-decoration: none; padding: 6px 12px; background: #e2e8f0; color: #475569; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">« Anterior</a>
            <?php else: ?>
            <span class="btn btn-sm btn-secondary" style="opacity: 0.5; cursor: not-allowed; pointer-events: none; padding: 6px 12px; background: #e2e8f0; color: #475569; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">« Anterior</span>
            <?php endif; ?>

            <?php foreach ($pagination['page_numbers'] as $num): ?>
                <?php $num = (int) $num; ?>
                <?php if ($num === $pn): ?>
            <span class="btn btn-sm btn-primary" aria-current="page" style="padding: 6px 12px; background: #3b82f6; color: white; border-radius: 4px; font-size: 0.85rem; font-weight: 600;"><?php echo $num; ?></span>
                <?php else: ?>
            <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($mk($linkBase, $num), ENT_QUOTES, 'UTF-8'); ?>" style="text-decoration: none; padding: 6px 12px; background: #e2e8f0; color: #475569; border-radius: 4px; font-size: 0.85rem; font-weight: 600;"><?php echo $num; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($pagination['has_next'])): ?>
            <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($mk($linkBase, $pn + 1), ENT_QUOTES, 'UTF-8'); ?>" rel="next" style="text-decoration: none; padding: 6px 12px; background: #e2e8f0; color: #475569; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">Siguiente »</a>
            <?php else: ?>
            <span class="btn btn-sm btn-secondary" style="opacity: 0.5; cursor: not-allowed; pointer-events: none; padding: 6px 12px; background: #e2e8f0; color: #475569; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">Siguiente »</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>

    <script src="../js/csp-safe-handlers.js" nonce="<?= htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script nonce="<?= htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        // Configuración de Chart.js
        const ctx = document.getElementById('securityEventsChart').getContext('2d');
        const securityEventsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($securityStats['chart_labels'] ?? []) ?>,
                datasets: [{
                    label: 'Eventos Críticos',
                    data: <?= json_encode($securityStats['critical_events'] ?? []) ?>,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Eventos de Advertencia',
                    data: <?= json_encode($securityStats['warning_events'] ?? []) ?>,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Eventos Informativos',
                    data: <?= json_encode($securityStats['info_events'] ?? []) ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Actualización automática cada 30 segundos
        setInterval(function() {
            fetch('../api/security_dashboard_api.php')
                .then(response => response.json())
                .then(data => {
                    updateDashboard(data);
                })
                .catch(error => {
                    console.error('Error updating dashboard:', error);
                });
        }, 30000);

        // Función para actualizar el dashboard con nuevos datos
        function updateDashboard(data) {
            // Actualizar métricas
            if (data.threatStats) {
                document.querySelector('.metric-value').textContent = data.threatStats.active_threats || 0;
            }

            // Actualizar alertas en tiempo real
            if (data.realTimeAlerts) {
                const alertsContainer = document.getElementById('realTimeAlerts');
                alertsContainer.innerHTML = '';
                
                data.realTimeAlerts.forEach(alert => {
                    const alertElement = document.createElement('div');
                    alertElement.className = `alert-item ${alert.severity.toLowerCase()}`;
                    alertElement.innerHTML = `
                        <strong>${alert.title}</strong>
                        <div class="alert-time">${alert.timestamp}</div>
                        <div style="clear: both; margin-top: 5px;">
                            ${alert.description}
                        </div>
                    `;
                    alertsContainer.appendChild(alertElement);
                });
            }
        }

        // WebSocket para alertas en tiempo real (pendiente de implementación).
        // Para habilitarlo en producción, configure WS_URL en .env y
        // descomente el bloque siguiente apuntando al servidor WebSocket real.
        //
        // if (typeof WebSocket !== 'undefined' && typeof WS_ALERTS_URL !== 'undefined') {
        //     try {
        //         const ws = new WebSocket(WS_ALERTS_URL);
        //         ws.onmessage = (event) => showRealTimeAlert(JSON.parse(event.data));
        //         ws.onerror  = (err)   => console.log('WebSocket error:', err);
        //     } catch (err) {
        //         console.log('WebSocket not available:', err);
        //     }
        // }

        // Mostrar alerta en tiempo real
        function showRealTimeAlert(alert) {
            const alertsContainer = document.getElementById('realTimeAlerts');
            const alertElement = document.createElement('div');
            alertElement.className = `alert-item ${alert.severity.toLowerCase()}`;
            alertElement.innerHTML = `
                <strong>${alert.title}</strong>
                <div class="alert-time">${new Date().toLocaleTimeString()}</div>
                <div style="clear: both; margin-top: 5px;">
                    ${alert.description}
                </div>
            `;
            
            alertsContainer.insertBefore(alertElement, alertsContainer.firstChild);
            
            // Mantener solo las últimas 10 alertas
            while (alertsContainer.children.length > 10) {
                alertsContainer.removeChild(alertsContainer.lastChild);
            }
        }
    </script>
</body>
</html>
