<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Controllers\UsuarioController;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

$usuario = $servicioAutenticacion->verificarSesion();
if ($usuario === null) {
    header('Location: public/login.php');
    exit();
}

// Control de acceso basado en RBAC
if (!can('gestionar_usuarios')) {
    header('Location: index.php?error=unauthorized');
    exit();
}

$usuarioController = new UsuarioController($databaseAdapter);

$pageTitle = 'Gestión de Usuarios - Sistema Administrativo E.E.S.T N°2';
$error_message = '';
$success_message = '';
$csrfToken = getCSRFToken();

// Procesar POST acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postCsrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);

    if (!verifyCSRFToken((string)$postCsrf)) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $postResult = $usuarioController->procesarPost($_POST, $usuario);
        if ($postResult['success']) {
            if ($postResult['redirect']) {
                header("Location: " . $postResult['redirect']);
                exit();
            }
            $success_message = $postResult['success_message'];
        } else {
            $error_message = $postResult['error'];
        }
    }
}

$successGet = filter_input(INPUT_GET, 'success', FILTER_DEFAULT);
if ($successGet) {
    $success_message = $successGet;
}

// Obtener datos para la vista
$vistaDatos = $usuarioController->datosVistaGestion();
$usuariosLista = $vistaDatos['usuariosLista'];
$stats = $vistaDatos['stats'];
$cursosDisponibles = $vistaDatos['cursosDisponibles'];

// Modo Edición / Formulario
$editUser = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editPreceptorCursos = [];

if ($editId !== false && $editId > 0) {
    $editUser = $databaseAdapter->fetch("SELECT id, dni, apellido, nombre, email, rol, activo FROM usuarios WHERE id = ?", [$editId]);
    if ($editUser) {
        if (in_array($editUser['rol'], ['directivo', 'preceptor', 'secretario'])) {
            $profile = $databaseAdapter->fetch("SELECT id, cargo FROM equipo_directivo WHERE usuario_id = ?", [$editUser['id']]);
            if ($profile) {
                $editUser['cargo'] = $profile['cargo'];
                if ($editUser['rol'] === 'preceptor') {
                    $cursos = $databaseAdapter->fetchAll("SELECT curso_id FROM preceptor_curso WHERE equipo_directivo_id = ?", [$profile['id']]);
                    $editPreceptorCursos = array_column($cursos, 'curso_id');
                }
            }
        }
    }
}

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/equipo.css">' . "\n";
sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<style>
.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}
.badge-admin { background-color: #6366f1; color: white; }
.badge-directivo { background-color: #f59e0b; color: white; }
.badge-preceptor { background-color: #0ea5a3; color: white; }
.badge-secretario { background-color: #3b82f6; color: white; }
.badge-success { background-color: #10b981; color: white; }
.badge-danger { background-color: #ef4444; color: white; }

.cursos-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 5px;
}
.cursos-checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    margin: 0;
}
.cursos-checkbox-item input {
    margin: 0;
    cursor: pointer;
}
.form-container .form-group.full-width {
    flex: 1 1 100%;
}
</style>

<section class="equipo-section">
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('users.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div>
            <?php if ($editUser): ?>
                <a href="users.php" class="btn btn-secondary"><i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('users.add_new'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['total']; ?></h3>
                <p><?php echo htmlspecialchars(__('users.total'), ENT_QUOTES, 'UTF-8'); ?></p>
                <small><?php echo htmlspecialchars(__('users.active_status', ['active' => $stats['activo'], 'inactive' => $stats['inactivo']]), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-user-shield"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['admin']; ?></h3>
                <p><?php echo htmlspecialchars(__('users.admins'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-user-tie"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['directivo']; ?></h3>
                <p><?php echo htmlspecialchars(__('users.directors'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="stat-card" style="position:relative;">
            <div class="stat-icon info" style="background:#0ea5a3;"><i class="fas fa-user-graduate" style="color:white;"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['preceptor']; ?></h3>
                <p><?php echo htmlspecialchars(__('users.preceptors'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <!-- FORMULARIO DE ALTA O EDICIÓN -->
    <div class="card" style="margin-bottom: 2.5rem;" id="form-usuario-card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas <?php echo $editUser ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i> 
                <?php echo $editUser ? htmlspecialchars(__('auto.editar_usuario'), ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($editUser['nombre'] . ' ' . $editUser['apellido']) : htmlspecialchars(__('users.add_new'), ENT_QUOTES, 'UTF-8'); ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" class="form-container">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="apellido"><?php echo htmlspecialchars(__('equipo.th_lastname'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <input type="text" name="apellido" id="apellido" required maxlength="100" value="<?php echo htmlspecialchars((string)($editUser ? $editUser['apellido'] : ($_POST['apellido'] ?? ''))); ?>">
                    </div>
                    <div class="form-group">
                        <label for="nombre"><?php echo htmlspecialchars(__('equipo.th_firstname'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <input type="text" name="nombre" id="nombre" required maxlength="100" value="<?php echo htmlspecialchars((string)($editUser ? $editUser['nombre'] : ($_POST['nombre'] ?? ''))); ?>">
                    </div>
                    <div class="form-group">
                        <label for="dni"><?php echo htmlspecialchars(__('login.username'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <input type="text" name="dni" id="dni" required maxlength="60" value="<?php echo htmlspecialchars((string)($editUser ? $editUser['dni'] : ($_POST['dni'] ?? ''))); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email"><?php echo htmlspecialchars(__('auto.email_institucional_opcional'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="email" name="email" id="email" maxlength="255" placeholder="usuario@ejemplo.com" value="<?php echo htmlspecialchars((string)($editUser ? $editUser['email'] : ($_POST['email'] ?? ''))); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo htmlspecialchars(__('auto.contrase_a'), ENT_QUOTES, 'UTF-8'); ?><?php echo $editUser ? ' (' . htmlspecialchars(__('auto.dejar_vac_o_para_no_cambiar'), ENT_QUOTES, 'UTF-8') . ')' : '*'; ?></label>
                        <input type="password" name="password" id="password" <?php echo $editUser ? '' : 'required'; ?> placeholder="<?php echo $editUser ? '••••••••' : htmlspecialchars(__('auto.ingrese_contrase_a'), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rol"><?php echo htmlspecialchars(__('auto.rol_del_usuario'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select name="rol" id="rol" required>
                            <?php $rVal = $editUser ? $editUser['rol'] : ($_POST['rol'] ?? ''); ?>
                            <option value=""><?php echo htmlspecialchars(__('auto.seleccionar_rol'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="admin" <?php echo $rVal === 'admin' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.administrador'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="directivo" <?php echo $rVal === 'directivo' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.directivo_director_vicedirector'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="profesor" <?php echo $rVal === 'profesor' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.profesor'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="preceptor" <?php echo $rVal === 'preceptor' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.preceptor'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="secretario" <?php echo $rVal === 'secretario' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.secretario'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="activo"><?php echo htmlspecialchars(__('auto.estado_de_la_cuenta'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <select name="activo" id="activo" required>
                            <?php $aVal = $editUser ? (int)$editUser['activo'] : (int)($_POST['activo'] ?? 1); ?>
                            <option value="1" <?php echo $aVal === 1 ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.activo'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="0" <?php echo $aVal === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.inactivo_desactivado'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                    </div>

                    <!-- CAMPO DINÁMICO: Cargo (Para Directivos) -->
                    <div class="form-group" id="group-cargo" style="display: none;">
                        <label for="cargo"><?php echo htmlspecialchars(__('auto.cargo_directivo'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <select name="cargo" id="cargo">
                            <?php $cVal = $editUser ? ($editUser['cargo'] ?? '') : ($_POST['cargo'] ?? ''); ?>
                            <option value=""><?php echo htmlspecialchars(__('auto.seleccionar_cargo'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="Director" <?php echo $cVal === 'Director' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.director'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="Vicedirector" <?php echo $cVal === 'Vicedirector' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.vicedirector'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- CAMPO DINÁMICO: Cursos (Para Preceptores) -->
                <div class="form-row" id="group-cursos" style="display: none;">
                    <div class="form-group full-width">
                        <label><?php echo htmlspecialchars(__('auto.cursos_divisiones_asignadas'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                        <div class="cursos-checkbox-grid">
                            <?php foreach ($cursosDisponibles as $c): 
                                $checked = in_array($c['id'], $editPreceptorCursos) ? 'checked' : '';
                            ?>
                                <label class="cursos-checkbox-item">
                                    <input type="checkbox" name="cursos[]" value="<?php echo $c['id']; ?>" <?php echo $checked; ?>>
                                    <span><?php echo htmlspecialchars($c['anio'] . '° ' . $c['division'] . ' - ' . $c['especialidad']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editUser ? htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('users.add_new'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <?php if ($editUser): ?>
                        <a href="users.php" class="btn btn-secondary"><i class="fas fa-times"></i><?php echo htmlspecialchars(__('auto.cancelar'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTA DE USUARIOS -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users-cog"></i> <?php echo htmlspecialchars(__('users.system_users'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container" style="border:none; margin:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(__('equipo.th_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('login.username'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('equipo.th_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('equipo.th_role'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('dashboard.last_login'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('equipo.th_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th style="width: 150px; text-align: center;"><?php echo htmlspecialchars(__('equipo.th_actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuariosLista)): ?>
                            <tr>
                                <td colspan="8" class="text-center" style="color: #94a3b8; padding: 2rem;"><?php echo htmlspecialchars(__('auto.no_hay_usuarios_cargados'), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuariosLista as $u):
                                $badgeRol = 'badge-secretario';
                                $rolTexto = ucfirst($u['rol']);
                                if ($u['rol'] === 'admin') {
                                    $badgeRol = 'badge-admin';
                                } elseif ($u['rol'] === 'directivo') {
                                    $badgeRol = 'badge-directivo';
                                    $rolTexto = htmlspecialchars($u['cargo'] ?? 'Directivo');
                                } elseif ($u['rol'] === 'preceptor') {
                                    $badgeRol = 'badge-preceptor';
                                }
                                $ultimoAcc = $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca';
                            ?>
                            <tr>
                                <td style="font-weight: 700; color: #1e293b;">
                                    <?php echo htmlspecialchars($u['apellido'] . ', ' . $u['nombre']); ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($u['dni']); ?></code></td>
                                <td><?php echo htmlspecialchars($u['email'] ?: '—'); ?></td>
                                <td><span class="badge <?php echo $badgeRol; ?>"><?php echo $rolTexto; ?></span></td>
                                <td style="font-size: 0.8rem; color: #64748b;"><?php echo $ultimoAcc; ?></td>
                                <td>
                                    <?php if ($u['activo'] == 1): ?>
                                        <span class="badge badge-success"><?php echo htmlspecialchars(__('auto.activo'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo htmlspecialchars(__('auto.inactivo'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="display:flex; justify-content:center; gap: 6px; padding: 10px;">
                                    <!-- Editar -->
                                    <a href="users.php?edit=<?php echo $u['id']; ?>#form-usuario-card" class="btn btn-sm btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Activar / Desactivar -->
                                    <?php if ($u['id'] !== $usuario['id']): ?>
                                        <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('¿Está seguro de cambiar el estado de este usuario?');">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $u['activo'] == 1 ? 'btn-warning' : 'btn-primary'; ?>" title="<?php echo $u['activo'] == 1 ? 'Desactivar' : 'Activar'; ?>" style="font-size: 0.75rem; padding: 6px 10px;">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>

                                        <!-- Eliminar Físicamente -->
                                        <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('¿Está seguro de ELIMINAR DEFINITIVAMENTE a este usuario? Esta acción es irreversible y borrará sus perfiles relacionados.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar definitivamente" style="font-size: 0.75rem; padding: 6px 10px; background-color:#ef4444; border-color:#ef4444;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.75rem; font-style:italic; padding-top:4px;"><?php echo htmlspecialchars(__('auto.tu_cuenta'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php $nonceVal = htmlspecialchars((string)($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
<script nonce="<?php echo $nonceVal; ?>">
document.addEventListener('DOMContentLoaded', function () {
    var rolSelect = document.getElementById('rol');
    var groupCargo = document.getElementById('group-cargo');
    var selectCargo = document.getElementById('cargo');
    var groupCursos = document.getElementById('group-cursos');
    
    function toggleFields() {
        var rol = rolSelect.value;
        
        if (rol === 'directivo') {
            groupCargo.style.display = 'block';
            selectCargo.setAttribute('required', 'required');
            
            groupCursos.style.display = 'none';
        } else if (rol === 'preceptor') {
            groupCursos.style.display = 'block';
            
            groupCargo.style.display = 'none';
            selectCargo.removeAttribute('required');
        } else {
            groupCargo.style.display = 'none';
            selectCargo.removeAttribute('required');
            groupCursos.style.display = 'none';
        }
    }
    
    if (rolSelect) {
        rolSelect.addEventListener('change', toggleFields);
        toggleFields(); // Ejecutar al cargar la página
    }
});
</script>

<?php include 'includes/footer.php'; ?>
