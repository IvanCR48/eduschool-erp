<?php
// Redireccionar al panel unificado de Herramientas Administrativas (Pestaña Configuración)
require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';

header('Location: ' . app_base_path('/admin/admin_tools.php?tab=configuration'));
exit();

$sessionService = new SessionService($databaseAdapter);
$permissionService = new PermissionService($databaseAdapter, $sessionService);

if (!$permissionService->tienePermiso('administrar_sistema')) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

$configService = new ConfigurationService($databaseAdapter);
$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $mensajeError = 'Error de seguridad CSRF. Por favor reintente.';
    } else {
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        
        $camposConfig = [
            'sistema.nombre' => trim($_POST['school_name'] ?? 'EduSchool ERP'),
            'sistema.subtitulo' => trim($_POST['school_subtitle'] ?? 'School Management System'),
            'sistema.logo' => trim($_POST['school_logo'] ?? 'img/logo.png'),
            'sistema.email' => trim($_POST['school_email'] ?? 'admin@school.com'),
            'sistema.telefono' => trim($_POST['school_phone'] ?? '+1 (555) 019-2834'),
            'sistema.direccion' => trim($_POST['school_address'] ?? ''),
            'sistema.moneda_simbolo' => trim($_POST['currency_symbol'] ?? '$'),
            'sistema.moneda_codigo' => trim($_POST['currency_code'] ?? 'USD'),
            'sistema.timezone' => trim($_POST['timezone'] ?? 'America/New_York'),
            'seguridad.max_intentos_login' => (int) ($_POST['max_login_attempts'] ?? 5),
            'seguridad.sesion_duracion' => (int) ($_POST['session_duration'] ?? 480),
        ];

        foreach ($camposConfig as $clave => $valor) {
            $configService->establecer($clave, $valor, $usuarioId);
        }

        $mensajeExito = 'System configuration updated successfully!';
    }
}

$pageTitle = 'School & System Settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1><i class="fas fa-sliders-h" style="color: #2563eb; margin-right: 0.5rem;"></i><?php echo htmlspecialchars(__('auto.school_system_settings'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p style="color: #64748b; font-size: 0.95rem;"><?php echo htmlspecialchars(__('auto.manage_institution_branding_contact_details'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="../index.php" class="btn btn-secondary" style="padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-arrow-left"></i><?php echo htmlspecialchars(__('auto.return_to_dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>

    <?php if ($mensajeExito !== ''): ?>
        <div class="alert alert-success" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 1rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
            <span><?php echo htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($mensajeError !== ''): ?>
        <div class="alert alert-danger" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem;"></i>
            <span><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="configuracion.php">
        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            
            <!-- School Branding Card -->
            <div class="card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 1.2rem; color: #0f172a; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-school" style="color: #2563eb;"></i><?php echo htmlspecialchars(__('auto.institution_branding'), ENT_QUOTES, 'UTF-8'); ?></h3>
                
                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.school_institution_name'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="school_name" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.nombre', 'EduSchool ERP'), ENT_QUOTES, 'UTF-8'); ?>" required style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.subtitle_tagline'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="school_subtitle" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.subtitulo', 'School Management System'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.logo_image_path'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="school_logo" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.logo', 'img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <small style="color: #64748b; font-size: 0.75rem;">Path relative to public directory (e.g. <code>img/logo.png</code>)</small>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 1.2rem; color: #0f172a; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-address-card" style="color: #059669;"></i><?php echo htmlspecialchars(__('auto.contact_information'), ENT_QUOTES, 'UTF-8'); ?></h3>
                
                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.official_email'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="email" name="school_email" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.email', 'admin@school.com'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.phone_number'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="school_phone" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.telefono', '+1 (555) 019-2834'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.physical_address'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="school_address" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.direccion', '123 Education Way, Campus Suite 100'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>
            </div>

            <!-- Currency & Regional Card -->
            <div class="card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 1.2rem; color: #0f172a; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-globe" style="color: #7c3aed;"></i><?php echo htmlspecialchars(__('auto.regional_currency'), ENT_QUOTES, 'UTF-8'); ?></h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.1rem;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.currency_symbol'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.moneda_simbolo', '$'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.currency_code'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" name="currency_code" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('sistema.moneda_codigo', 'USD'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.timezone'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="timezone" class="form-control" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                        <?php
                        $tzCurrent = (string) $configService->obtener('sistema.timezone', 'America/New_York');
                        $timezones = timezone_identifiers_list();
                        foreach ($timezones as $tz) {
                            $sel = ($tz === $tzCurrent) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' . htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Security Parameters Card -->
            <div class="card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 1.2rem; color: #0f172a; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-shield-alt" style="color: #dc2626;"></i><?php echo htmlspecialchars(__('auto.security_parameters'), ENT_QUOTES, 'UTF-8'); ?></h3>

                <div class="form-group" style="margin-bottom: 1.1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.max_failed_login_attempts'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="number" name="max_login_attempts" min="1" max="20" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('seguridad.max_intentos_login', 5), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;"><?php echo htmlspecialchars(__('auto.session_duration_minutes'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="number" name="session_duration" min="15" max="1440" class="form-control" value="<?php echo htmlspecialchars((string) $configService->obtener('seguridad.sesion_duracion', 480), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>
            </div>

        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="background: #2563eb; color: #fff; border: none; padding: 0.85rem 2rem; border-radius: 999px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                <i class="fas fa-save"></i><?php echo htmlspecialchars(__('auto.save_configuration'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
