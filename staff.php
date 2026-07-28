<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Controllers\EquipoDirectivoController;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioEquipoDirectivo;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$servicioEquipoDirectivo = new ServicioEquipoDirectivo($databaseAdapter);
$equipoDirectivoController = new EquipoDirectivoController($databaseAdapter, $servicioEquipoDirectivo);

$usuario = $servicioAutenticacion->verificarSesion();
if ($usuario === null) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

if (!hasRole(['admin', 'directivo'])) {
    header('Location: index.php?error=unauthorized');
    exit();
}

$pageTitle = 'Equipo Directivo - Sistema Administrativo E.E.S.T N°2';
$error_message = '';
$csrfToken = getCSRFToken();

$auditoriaUsuarioId = isset($usuario['id']) && (int) $usuario['id'] > 0 ? (int) $usuario['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['guardar_miembro']) || isset($_POST['eliminar_miembro']))) {
    if (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $postOutcome = $equipoDirectivoController->procesarPost(
            $_POST,
            hasRole('admin'),
            $auditoriaUsuarioId,
            $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        );
        if ($postOutcome['redirect'] !== null) {
            header('Location: ' . $postOutcome['redirect']);
            exit();
        }
        $error_message = $postOutcome['error'];
    }
}

$action = trim((string) (filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?? ''));

extract($equipoDirectivoController->datosVista(hasRole('admin')), EXTR_SKIP);

$success_message = '';

$successKey = trim((string) (filter_input(INPUT_GET, 'success', FILTER_DEFAULT) ?? ''));
if ($successKey !== '') {
    switch ($successKey) {
        case 'credenciales':
            $usernameEsc = htmlspecialchars((string) (filter_input(INPUT_GET, 'username', FILTER_DEFAULT) ?? ''), ENT_QUOTES, 'UTF-8');
            $passEsc = htmlspecialchars((string) (filter_input(INPUT_GET, 'temp_password', FILTER_DEFAULT) ?? ''), ENT_QUOTES, 'UTF-8');
            $success_message = 'Miembro registrado correctamente. Información de acceso: Usuario: '
                . $usernameEsc . ', Contraseña: ' . $passEsc;
            break;
        case 'miembro':
            $success_message = 'Miembro del equipo directivo registrado correctamente';
            break;
        case 'eliminar':
            $nombreEsc = htmlspecialchars((string) (filter_input(INPUT_GET, 'nombre', FILTER_DEFAULT) ?? ''), ENT_QUOTES, 'UTF-8');
            $success_message = 'Miembro ' . $nombreEsc . ' eliminado correctamente';
            break;
    }
}

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/equipo.css">' . "\n";

sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="equipo-section">
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('equipo.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <a href="staff.php?action=nuevo" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('equipo.add'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </div>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_miembros, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('equipo.total'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($cargos_diferentes_count, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('equipo.roles'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('equipo.add'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>

        <div class="alert alert-info alert-info-equipo">
            <i class="fas fa-info-circle"></i>
            <?php echo htmlspecialchars(__('equipo.temp_pass_notice'), ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <form method="POST" class="form-container">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="apellido"><?php echo htmlspecialchars(__('students.last_name'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <input type="text" name="apellido" id="apellido" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="nombre"><?php echo htmlspecialchars(__('students.first_name'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <input type="text" name="nombre" id="nombre" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="cargo"><?php echo htmlspecialchars(__('equipo.th_role'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="cargo" id="cargo" required>
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cargos_formulario as $valor_cargo => $label_cargo): ?>
                        <option value="<?php echo htmlspecialchars((string) $valor_cargo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $label_cargo, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefono"><?php echo htmlspecialchars(__('equipo.th_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="tel" name="telefono" id="telefono" maxlength="20" placeholder="Ej: 223-1234567">
                </div>

                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(__('equipo.th_email'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="email" name="email" id="email" maxlength="100" placeholder="Ej: director@escuela.edu">
                </div>

                <div class="form-group">
                    <label for="foto"><?php echo htmlspecialchars(__('preceptors.photo_url'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="url" name="foto" id="foto" maxlength="255" placeholder="https://ejemplo.com/foto.jpg">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="guardar_miembro" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="staff.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (!empty($equipo)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('auto.miembros_del_equipo_directivo'), ENT_QUOTES, 'UTF-8'); ?><?php echo number_format($total_miembros, 0, ',', '.'); ?>)</h3>
        </div>
        <div class="card-body">
            <div class="equipo-grid">
                <?php foreach ($equipo as $m): ?>
                    <?php include __DIR__ . '/includes/equipo/partials/miembro_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('auto.vista_de_lista'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(__('equipo.th_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('equipo.th_role'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('equipo.th_phone'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('equipo.th_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('equipo.th_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('equipo.th_actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipo as $m): ?>
                        <?php include __DIR__ . '/includes/equipo/partials/miembro_tabla_fila.php'; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <div class="card">
        <div class="card-body text-center equipo-vacio">
            <i class="fas fa-users equipo-vacio__icono"></i>
            <h3 class="equipo-vacio__titulo"><?php echo htmlspecialchars(__('equipo.empty_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="equipo-vacio__texto">
                <?php echo htmlspecialchars(__('equipo.empty_desc'), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <a href="staff.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('equipo.add_first'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
