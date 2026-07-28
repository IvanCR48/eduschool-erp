<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';

use SistemaAdmin\Controllers\EquipoDirectivoController;
use SistemaAdmin\Controllers\PreceptorCursosController;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioEquipoDirectivo;
use SistemaAdmin\Services\ServicioPreceptorCursos;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$servicioPreceptorCursos = new ServicioPreceptorCursos($databaseAdapter);
$preceptorCursosController = new PreceptorCursosController($databaseAdapter, $servicioPreceptorCursos);
$servicioEquipoDirectivo = new ServicioEquipoDirectivo($databaseAdapter);
$equipoDirectivoController = new EquipoDirectivoController($databaseAdapter, $servicioEquipoDirectivo);

$usuario = $servicioAutenticacion->verificarSesion();
if (!$usuario) {
    header('Location: ' . app_base_path('public/login.php'));
    exit();
}

require_once __DIR__ . '/includes/preceptor_scope.php';
if (!puedeAccesoPestanaPreceptor()) {
    header('Location: ' . app_base_path('index.php?error=unauthorized'));
    exit();
}

require_once __DIR__ . '/includes/auth_helpers.php';

$pageTitle = 'Preceptores - Sistema Administrativo E.E.S.T N°2';

require_once __DIR__ . '/includes/csrf_functions.php';
$csrfToken = getCSRFToken();

$preceptorPageUrl = function_exists('app_base_path') ? app_base_path('advisors.php') : 'advisors.php';
$preceptorNuevoUrl = function_exists('app_base_path') ? app_base_path('advisors.php?action=nuevo') : 'advisors.php?action=nuevo';
$actionGet = trim((string) (filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?? ''));

$esPost = strtoupper((string) (filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_DEFAULT) ?? '')) === 'POST';

$error_message = '';
$volverFormAltaPreceptor = false;

if ($esPost && filter_input(INPUT_POST, 'preceptor_curso_action', FILTER_DEFAULT) !== null) {
    $csrfPost = (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? '');
    if (!verifyCSRFToken($csrfPost)) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $miembroPost = filter_input(INPUT_POST, 'miembro_id', FILTER_VALIDATE_INT);
        $cursoPost = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT);
        $postSanitizado = [
            'preceptor_curso_action' => trim((string) (filter_input(INPUT_POST, 'preceptor_curso_action', FILTER_DEFAULT) ?? '')),
            'miembro_id' => ($miembroPost !== false && $miembroPost > 0) ? $miembroPost : 0,
            'curso_id' => ($cursoPost !== false && $cursoPost > 0) ? $cursoPost : 0,
        ];
        $postOutcome = $preceptorCursosController->procesarPost($postSanitizado);
        if ($postOutcome['redirect'] !== null) {
            header('Location: ' . $postOutcome['redirect']);
            exit();
        }
        $error_message = $postOutcome['error'];
    }
} elseif ($esPost && (isset($_POST['guardar_preceptor']) || isset($_POST['eliminar_miembro']))) {
    $auditoriaUsuarioId = isset($usuario['id']) && (int) $usuario['id'] > 0 ? (int) $usuario['id'] : null;
    if (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
        if (isset($_POST['guardar_preceptor'])) {
            $volverFormAltaPreceptor = true;
        }
    } else {
        $postOutcomeEquipo = $equipoDirectivoController->procesarPost(
            $_POST,
            hasRole('admin'),
            $auditoriaUsuarioId,
            (string) ($_SERVER['REMOTE_ADDR'] ?? 'CLI'),
            ['redirect_pagina' => 'advisors.php']
        );
        if ($postOutcomeEquipo['redirect'] !== null) {
            header('Location: ' . $postOutcomeEquipo['redirect']);
            exit();
        }
        $error_message = $postOutcomeEquipo['error'];
        if (isset($_POST['guardar_preceptor'])) {
            $volverFormAltaPreceptor = true;
        }
    }
}

$success_message = '';
$successFlag = trim((string) (filter_input(INPUT_GET, 'success', FILTER_DEFAULT) ?? ''));
if ($successFlag === 'agregar') {
    $success_message = 'Curso agregado al preceptor. Los cambios aplican en la próxima sincronización de sesión del usuario.';
} elseif ($successFlag === 'quitar') {
    $success_message = 'Curso quitado del preceptor.';
} elseif ($successFlag === 'credenciales') {
    $usernameEsc = htmlspecialchars((string) (filter_input(INPUT_GET, 'username', FILTER_DEFAULT) ?? ''), ENT_QUOTES, 'UTF-8');
    $passEsc = htmlspecialchars((string) (filter_input(INPUT_GET, 'temp_password', FILTER_DEFAULT) ?? ''), ENT_QUOTES, 'UTF-8');
    $success_message = 'Preceptor registrado. Usuario: ' . $usernameEsc . ' · Contraseña inicial: ' . $passEsc;
} elseif ($successFlag === 'miembro') {
    $success_message = 'Preceptor registrado correctamente.';
} elseif ($successFlag === 'eliminar') {
    $nombreEsc = htmlspecialchars((string) (filter_input(INPUT_GET, 'nombre', FILTER_DEFAULT) ?? ''), ENT_QUOTES, 'UTF-8');
    $success_message = $nombreEsc !== ''
        ? ('Preceptor ' . $nombreEsc . ' dado de baja correctamente.')
        : 'Preceptor dado de baja correctamente.';
}

$mostrarFormAltaPreceptor = ($actionGet === 'nuevo') || $volverFormAltaPreceptor;
$cursosAltaPreceptor = $mostrarFormAltaPreceptor ? $servicioEquipoDirectivo->listarCursosActivosParaPreceptor() : [];

$vistaPreceptor = $preceptorCursosController->datosVista();
$tabla_preceptor_curso_ok = $vistaPreceptor['tabla_preceptor_curso_ok'];
$preceptores = $vistaPreceptor['preceptores'];
$filas_preceptor = $vistaPreceptor['filas_preceptor'];
$total_preceptores = $vistaPreceptor['total_preceptores'];
$preceptores_sin_cursos = $vistaPreceptor['preceptores_sin_cursos'];
$total_cursos_activos = $vistaPreceptor['total_cursos_activos'];

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="' . htmlspecialchars(app_base_path('css/preceptor.css'), ENT_QUOTES, 'UTF-8') . '">' . "\n";

sistema_admin_send_html_security_headers();
include __DIR__ . '/includes/header.php';
?>

<section class="preceptores-section">
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('preceptors.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="header-actions">
            <a href="<?php echo htmlspecialchars($preceptorNuevoUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> <span class="btn-text"><?php echo htmlspecialchars(__('preceptors.add'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <a href="<?php echo htmlspecialchars(app_base_path('staff.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <i class="fas fa-user-tie"></i> <span class="btn-text"><?php echo htmlspecialchars(__('equipo.title'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        </div>
    </div>

    <?php if (!$tabla_preceptor_curso_ok): ?>
        <div class="alert alert-warning preceptor-alert--spaced">
            <i class="fas fa-database"></i>
            <strong><?php echo htmlspecialchars(__('auto.configuraci_n_pendiente'), ENT_QUOTES, 'UTF-8'); ?></strong><?php echo htmlspecialchars(__('auto.falta_crear_en_la_base_de_datos_la_tabla_que'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($mostrarFormAltaPreceptor): ?>
    <div class="card preceptor-alta-card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('preceptors.add'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info preceptor-alert--spaced">
                <i class="fas fa-info-circle"></i>
                <?php echo htmlspecialchars(__('preceptors.temp_pass_notice'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($preceptorPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="form-container">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="preceptor_apellido"><?php echo htmlspecialchars(__('students.last_name'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <input type="text" name="apellido" id="preceptor_apellido" required maxlength="100" value="<?php echo htmlspecialchars((string) ($_POST['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="preceptor_nombre"><?php echo htmlspecialchars(__('students.first_name'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <input type="text" name="nombre" id="preceptor_nombre" required maxlength="100" value="<?php echo htmlspecialchars((string) ($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="preceptor_curso_id"><?php echo htmlspecialchars(__('preceptors.working_course'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <select name="curso_id" id="preceptor_curso_id" required>
                            <option value=""><?php echo htmlspecialchars(__('preceptors.select_course'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php foreach ($cursosAltaPreceptor as $c): ?>
                            <option value="<?php echo (int) $c['id']; ?>" <?php echo isset($_POST['curso_id']) && (string) $_POST['curso_id'] === (string) $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(sprintf('%d° %s', (int) $c['anio'], (string) ($c['division'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="preceptor_telefono"><?php echo htmlspecialchars(__('equipo.th_phone'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="tel" name="telefono" id="preceptor_telefono" maxlength="20" value="<?php echo htmlspecialchars((string) ($_POST['telefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="preceptor_email"><?php echo htmlspecialchars(__('equipo.th_email'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="email" name="email" id="preceptor_email" maxlength="100" value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="preceptor_foto"><?php echo htmlspecialchars(__('preceptors.photo_url'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="url" name="foto" id="preceptor_foto" maxlength="255" value="<?php echo htmlspecialchars((string) ($_POST['foto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="guardar_preceptor" value="1" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <a href="<?php echo htmlspecialchars($preceptorPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary"><?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_preceptores, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('preceptors.active'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?php echo $preceptores_sin_cursos > 0 ? 'warning' : 'success'; ?>">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($preceptores_sin_cursos, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('preceptors.no_course'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-school"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_cursos_activos, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('courses.total_courses'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($preceptores) && !$mostrarFormAltaPreceptor): ?>
        <div class="card">
            <div class="card-body preceptor-empty-card-body">
                <div class="preceptor-empty-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="preceptor-empty-title"><?php echo htmlspecialchars(__('preceptors.empty_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="text-muted preceptor-empty-lead">
                    <?php echo htmlspecialchars(__('preceptors.empty_desc'), ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <a href="<?php echo htmlspecialchars($preceptorNuevoUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('preceptors.add'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo htmlspecialchars(__('preceptors.registered_table_title'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo number_format($total_preceptores, 0, ',', '.'); ?>)</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(__('equipo.th_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('students.th_contact'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('preceptors.th_working_courses'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('students.th_actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($filas_preceptor as $row): ?>
                                <?php
                                $p = $row['p'];
                                $eid = $row['eid'];
                                $cursosQuitar = $row['cursos_quitar'];
                                $cursosParaAgregar = $row['cursos_para_agregar'];
                                $apellidoP = $row['apellido_display'];
                                $nombreP = $row['nombre_display'];
                                $nombreCompleto = trim($apellidoP . ($nombreP !== '' ? ', ' . $nombreP : ''));
                                if ($nombreCompleto === '') {
                                    $nombreCompleto = '—';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['telefono'])): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars((string) $p['telefono'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($p['email'])): ?>
                                            <br><small><i class="fas fa-envelope"></i>
                                            <a href="mailto:<?php echo htmlspecialchars((string) $p['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $p['email'], ENT_QUOTES, 'UTF-8'); ?></a></small>
                                        <?php endif; ?>
                                        <?php if (empty($p['telefono']) && empty($p['email'])): ?>
                                            <span class="status status-warning" style="font-size: 0.75rem;"><?php echo htmlspecialchars(__('auto.sin_datos'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cursosQuitar === []): ?>
                                            <span class="status status-warning" style="font-size: 0.75rem;"><?php echo htmlspecialchars(__('auto.sin_cursos_asignados'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <div class="course-tags">
                                                <?php foreach ($cursosQuitar as $cq): ?>
                                                    <span class="status status-primary" style="display: inline-block; margin-bottom: 0.25rem;">
                                                        <?php echo htmlspecialchars($cq['etiqueta'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="preceptor-acciones-cell">
                                        <div class="preceptor-acciones-stack">
                                        <?php if (hasRole('admin')): ?>
                                            <?php if (!empty($p['usuario_login'])): ?>
                                                <button type="button" class="btn btn-secondary btn-sm preceptor-creds-btn" data-csp-toggle-credenciales="<?php echo (int) $eid; ?>">
                                                    <i class="fas fa-eye"></i><?php echo htmlspecialchars(__('auto.ver_credenciales'), ENT_QUOTES, 'UTF-8'); ?></button>
                                                <div id="credenciales-<?php echo (int) $eid; ?>" class="preceptor-creds-panel credenciales-info">
                                                    <small class="preceptor-creds-line"><strong><?php echo htmlspecialchars(__('auto.usuario'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars((string) $p['usuario_login'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <small class="preceptor-creds-hint"><?php echo htmlspecialchars(__('auto.la_contrase_a_temporal_solo_se_muestra_al_mo'), ENT_QUOTES, 'UTF-8'); ?></small>
                                                </div>
                                            <?php else: ?>
                                                <small class="preceptor-creds-sin"><?php echo htmlspecialchars(__('auto.sin_usuario_de_acceso_registrado'), ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <details class="preceptor-details">
                                            <summary class="btn btn-primary preceptor-details-summary preceptor-manage-summary">
                                                <i class="fas fa-layer-group"></i><?php echo htmlspecialchars(__('auto.gestionar'), ENT_QUOTES, 'UTF-8'); ?></summary>
                                            <div class="preceptor-details-panel">
                                                <div class="preceptor-panel-block">
                                                    <h4 class="preceptor-panel-title"><i class="fas fa-minus-circle"></i><?php echo htmlspecialchars(__('auto.quitar_curso'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                    <?php if ($cursosQuitar === []): ?>
                                                        <p class="preceptor-panel-hint"><?php echo htmlspecialchars(__('auto.no_hay_cursos_asignados_para_quitar'), ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <?php else: ?>
                                                        <div class="preceptor-quitar-list">
                                                            <?php foreach ($cursosQuitar as $cq): ?>
                                                                <form method="post" action="<?php echo htmlspecialchars($preceptorPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="preceptor-quitar-form">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <input type="hidden" name="preceptor_curso_action" value="quitar">
                                                                    <input type="hidden" name="miembro_id" value="<?php echo (int) $eid; ?>">
                                                                    <input type="hidden" name="curso_id" value="<?php echo (int) $cq['curso_id']; ?>">
                                                                    <button type="submit" class="btn btn-sm preceptor-btn-quitar">
                                                                        <i class="fas fa-times"></i>
                                                                        <?php echo htmlspecialchars($cq['etiqueta'], ENT_QUOTES, 'UTF-8'); ?>
                                                                    </button>
                                                                </form>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="preceptor-panel-block preceptor-panel-block--add">
                                                    <h4 class="preceptor-panel-title"><i class="fas fa-plus-circle"></i><?php echo htmlspecialchars(__('auto.agregar_curso'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                    <?php if ($cursosParaAgregar === []): ?>
                                                        <p class="preceptor-panel-hint"><?php echo htmlspecialchars(__('auto.ya_tiene_todos_los_cursos_activos_del_sistema'), ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <?php else: ?>
                                                        <form method="post" action="<?php echo htmlspecialchars($preceptorPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="form-container preceptor-add-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="preceptor_curso_action" value="agregar">
                                                            <input type="hidden" name="miembro_id" value="<?php echo (int) $eid; ?>">
                                                            <div class="form-row preceptor-add-row">
                                                                <div class="form-group preceptor-form-group-curso">
                                                                    <label class="preceptor-sr-only" for="curso_add_<?php echo (int) $eid; ?>"><?php echo htmlspecialchars(__('auto.curso'), ENT_QUOTES, 'UTF-8'); ?></label>
                                                                    <select name="curso_id" id="curso_add_<?php echo (int) $eid; ?>" class="form-control" required>
                                                                        <?php foreach ($cursosParaAgregar as $opt): ?>
                                                                            <option value="<?php echo (int) $opt['id']; ?>"><?php echo htmlspecialchars($opt['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group preceptor-add-submit-wrap">
                                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                                        <i class="fas fa-plus"></i><?php echo htmlspecialchars(__('auto.agregar'), ENT_QUOTES, 'UTF-8'); ?></button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </details>
                                        <?php
                                        $msgElimPre = '¿Dar de baja a ' . $nombreCompleto . '? Se desactivará el usuario de acceso.';
                                        ?>
                                        <form method="post" action="<?php echo htmlspecialchars($preceptorPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="preceptor-eliminar-form js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars($msgElimPre, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="miembro_id" value="<?php echo (int) $eid; ?>">
                                            <button type="submit" name="eliminar_miembro" class="btn btn-sm btn-danger preceptor-btn-baja">
                                                <i class="fas fa-user-times"></i><?php echo htmlspecialchars(__('auto.dar_de_baja'), ENT_QUOTES, 'UTF-8'); ?></button>
                                        </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>


<?php include __DIR__ . '/includes/footer.php'; ?>
