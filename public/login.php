<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';
require_once __DIR__ . '/../includes/csrf_functions.php';
require_once __DIR__ . '/../includes/i18n.php';

use SistemaAdmin\Services\ConfigurationService;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioSeguridad;
use SistemaAdmin\Services\ServicioLogging;
use SistemaAdmin\Services\RecaptchaService;
use SistemaAdmin\Controllers\LoginController;
use SistemaAdmin\Middleware\SessionSecurityMiddleware;
use SistemaAdmin\Services\GoogleOAuthService;
use SistemaAdmin\Services\I18nService;

$databaseAdapter = sistema_admin_db_adapter();
$configService = new ConfigurationService($databaseAdapter);
I18nService::init((string) $configService->obtener('sistema.idioma_defecto', 'en'));

$servicioLogging = new ServicioLogging($databaseAdapter);
$servicioSeguridad = new ServicioSeguridad($databaseAdapter);
$recaptchaService = new RecaptchaService();

$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

$configService = new ConfigurationService($databaseAdapter);
$loginController = new LoginController($databaseAdapter, $servicioAutenticacion, $servicioSeguridad, $servicioLogging, $configService);

$servicioLogging->crearTablasLogs();

$servicioSeguridad->configurarHeadersSeguridad();

if (isset($_SESSION['usuario_id']) && !SessionSecurityMiddleware::validateSessionIntegrity()) {
    SessionSecurityMiddleware::destroySession();
    header('Location: login.php?error=session_compromised');
    exit();
}

$suspiciousActivity = SessionSecurityMiddleware::detectSuspiciousActivity();
if (!empty($suspiciousActivity)) {
    error_log('Actividad sospechosa detectada: ' . implode(', ', $suspiciousActivity));
}

$recaptchaConfigured = $recaptchaService->isConfigured();
$recaptchaSiteKey = $recaptchaService->getSiteKey();

$googleOauthConfig = GoogleOAuthService::loadLocalConfig();
$googleOAuth = new GoogleOAuthService($googleOauthConfig ?? []);
$googleOauthActivo = $googleOAuth->isConfigured();

$error = '';
$errGet = trim((string) (filter_input(INPUT_GET, 'error', FILTER_DEFAULT) ?? ''));
$erroresGoogle = [
    'google_oauth_no_config' => 'El acceso con Google para docentes no está configurado. Consulte con administración.',
    'google_denegado' => 'Acceso con Google cancelado o denegado.',
    'google_estado_invalido' => 'La solicitud de acceso con Google no pudo validarse. Intente de nuevo.',
    'google_token' => 'No se pudo completar el acceso con Google. Intente nuevamente.',
    'google_email_no_verificado' => 'Su cuenta de Google no tiene el correo verificado.',
    'google_profesor_no_registrado' => 'Este correo no está cargado como docente en el sistema. Verifique el email en secretaría.',
    'google_email_en_uso' => 'Este correo ya está asociado a otro tipo de usuario del sistema.',
    'google_email_en_desuso' => 'Este correo de Google se encuentra en desuso. Use la nueva cuenta de correo registrada en su ficha.',
    'google_profesor_sin_dni' => 'El registro del docente no tiene DNI: complete la ficha antes de usar acceso con Google.',
    'google_usuario' => 'Error al preparar la sesión. Contacte a administración.',
];
if ($errGet !== '' && isset($erroresGoogle[$errGet])) {
    $error = $erroresGoogle[$errGet];
}

$username = '';
$password = '';
$esPost = strtoupper((string) (filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_DEFAULT) ?? '')) === 'POST';

if ($esPost) {
    // Reset any error carried over from a previous Google OAuth redirect in the URL.
    // Without this, a stale ?error=google_* in the URL would keep showing the Google
    // error message even when the user is now trying a normal DNI/password login.
    $error = '';
    $username = trim((string) (filter_input(INPUT_POST, 'username', FILTER_DEFAULT) ?? ''));
    $password = (string) (filter_input(INPUT_POST, 'password', FILTER_DEFAULT) ?? '');

    if ($username === '' || $password === '') {
        $error = 'Por favor complete todos los campos.';
    } else {
        $resultado = $loginController->autenticar([
            'username' => $username,
            'password' => $password,
            'csrf_token' => (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? ''),
        ]);

        if ($resultado['success']) {
            if (!empty($_SESSION['must_change_password'])) {
                header('Location: ' . app_base_path('/force_password_change.php'));
                exit();
            }
            header('Location: ' . app_base_path('/'));
            exit();
        }
        $error = (string) ($resultado['error'] ?? 'Error de autenticación');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Personal · <?php echo htmlspecialchars(\SistemaAdmin\Bootstrap\AppRequestInit::systemName(), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 16px; }
    body {
        font-family: 'Inter', system-ui, sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: stretch;
        background: #f0f9ff;
        overflow: hidden;
    }

    /* ══ PANEL IZQUIERDO (decorativo) ══ */
    .lp-side {
        flex: 0 0 46%;
        background: linear-gradient(155deg, #080f25 0%, #0f2952 35%, #1255a3 68%, #0e8dc4 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 2.5rem;
        position: relative;
        overflow: hidden;
    }
    .lp-side-shapes { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
    .lp-shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: .18;
        animation: lp-float 15s ease-in-out infinite alternate;
    }
    .lp-shape--a { width: 420px; height: 420px; background: #38bdf8; top: -140px; right: -100px; animation-duration: 18s; }
    .lp-shape--b { width: 320px; height: 320px; background: #6366f1; bottom: -90px; left: -80px; animation-duration: 12s; animation-delay: 4s; }
    .lp-shape--c { width: 220px; height: 220px; background: #06b6d4; top: 40%; left: 20%; animation-duration: 20s; animation-delay: 8s; }
    @keyframes lp-float {
        from { transform: translate(0,0) scale(1); }
        to   { transform: translate(28px, 38px) scale(1.1); }
    }
    .lp-side > * { position: relative; z-index: 1; }

    /* Logo: fondo blanco con padding para que no haya marco de color sobre el logo */
    .lp-side-logo {
        width: 90px; height: 90px;
        border-radius: 20px;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        padding: 10px;
        margin-bottom: 1.75rem;
        box-shadow: 0 12px 36px rgba(0,0,0,.25);
    }
    .lp-side-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }

    .lp-side-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #fff;
        text-align: center;
        line-height: 1.2;
        margin-bottom: .55rem;
        letter-spacing: -.02em;
    }
    .lp-side-sub {
        font-size: .88rem;
        color: rgba(255,255,255,.65);
        text-align: center;
        margin-bottom: 2.5rem;
    }
    .lp-side-features {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        width: 100%;
        max-width: 300px;
    }
    .lp-feat {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .85rem 1.1rem;
        border-radius: 12px;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.1);
        backdrop-filter: blur(6px);
    }
    .lp-feat-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        color: #bae6fd;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .lp-feat-text strong { display: block; font-size: .85rem; font-weight: 700; color: #fff; line-height: 1.3; }
    .lp-feat-text span   { font-size: .73rem; color: rgba(255,255,255,.55); }

    .lp-side-canvas {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        opacity: .45;
    }

    /* ══ PANEL DERECHO (formulario) ══ */
    .lp-main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.5rem;
        background: #fff;
        overflow-y: auto;
    }
    .lp-card {
        width: 100%;
        max-width: 400px;
        animation: lp-in .5s cubic-bezier(.4,0,.2,1) both;
    }
    @keyframes lp-in {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: none; }
    }

    .lp-form-header { margin-bottom: 2rem; }
    .lp-form-header h1 {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -.03em;
        margin-bottom: .3rem;
    }
    .lp-form-header p { font-size: .875rem; color: #64748b; }

    /* Alertas */
    .lp-alert {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        padding: .8rem 1rem;
        border-radius: 10px;
        font-size: .83rem;
        line-height: 1.55;
        margin-bottom: 1.25rem;
    }
    .lp-alert i { flex-shrink: 0; margin-top: .1rem; }
    .lp-alert--error { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
    .lp-alert--warn  { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }

    /* Formulario */
    .lp-form { display: flex; flex-direction: column; gap: 1.15rem; }
    .lp-field { display: flex; flex-direction: column; gap: .35rem; }
    .lp-field label {
        font-size: .78rem;
        font-weight: 600;
        color: #334155;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .lp-field label i { color: #3b82f6; font-size: .78rem; }
    .lp-input-wrap { position: relative; }
    .lp-input-icon {
        position: absolute;
        left: .9rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: .88rem;
        pointer-events: none;
    }
    .lp-field input {
        width: 100%;
        padding: .8rem 1rem .8rem 2.55rem;
        border: 1.5px solid rgba(148,163,184,.3);
        border-radius: 11px;
        font-size: .92rem;
        font-family: inherit;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        transition: border-color .22s, box-shadow .22s, background .22s;
    }
    .lp-field input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3.5px rgba(59,130,246,.13);
        background: #fff;
    }
    .lp-field small { font-size: .74rem; color: #64748b; line-height: 1.5; }

    .lp-recaptcha { display: flex; justify-content: center; }

    .lp-submit {
        width: 100%;
        padding: .9rem;
        border-radius: 999px;
        font-size: .93rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #2563eb 0%, #6366f1 100%);
        border: none;
        cursor: pointer;
        font-family: inherit;
        box-shadow: 0 4px 20px rgba(37,99,235,.32);
        transition: all .25s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .55rem;
        margin-top: .35rem;
    }
    .lp-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,.42); }
    .lp-submit:disabled { opacity: .45; cursor: not-allowed; }

    /* Footer */
    .lp-footer {
        margin-top: 1.85rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(148,163,184,.18);
        text-align: center;
    }
    .lp-footer p   { font-size: .76rem; color: #64748b; margin-bottom: .2rem; }
    .lp-footer small { font-size: .7rem; color: #94a3b8; display: block; margin-bottom: 1.1rem; }
    .lp-doc-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .6rem 1.35rem;
        border-radius: 999px;
        background: rgba(59,130,246,.07);
        color: #2563eb;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .22s;
        border: 1.5px solid rgba(59,130,246,.16);
    }
    .lp-doc-btn:hover { background: #2563eb; color: #fff; }
    .lp-familia-link {
        margin-top: .9rem;
        font-size: .77rem;
        color: #64748b;
        text-align: center;
    }
    .lp-familia-link a { color: #0ea5e9; font-weight: 600; text-decoration: none; }
    .lp-familia-link a:hover { text-decoration: underline; }

    /* ══ DEMO CREDENTIALS BANNER ══ */
    .demo-box {
        background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
        border: 1.5px solid #86efac;
        border-radius: 14px;
        padding: 1rem 1.15rem;
        margin-bottom: 1.35rem;
        position: relative;
        overflow: hidden;
    }
    .demo-box::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 4px; height: 100%;
        background: linear-gradient(180deg, #16a34a, #22c55e);
        border-radius: 4px 0 0 4px;
    }
    .demo-box-header {
        display: flex; align-items: center; gap: .45rem;
        font-size: .78rem; font-weight: 700; color: #15803d;
        margin-bottom: .65rem; letter-spacing: .02em; text-transform: uppercase;
    }
    .demo-box-header i { font-size: .82rem; }
    .demo-creds { display: flex; flex-direction: column; gap: .38rem; }
    .demo-cred-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: .5rem; flex-wrap: wrap;
    }
    .demo-cred-role {
        font-size: .72rem; font-weight: 600; color: #166534;
        min-width: 70px;
    }
    .demo-cred-pair {
        display: flex; align-items: center; gap: .35rem; flex: 1;
    }
    .demo-cred-val {
        font-size: .71rem; font-family: 'Courier New', monospace;
        background: rgba(255,255,255,.75); border: 1px solid #bbf7d0;
        border-radius: 6px; padding: .18rem .5rem; color: #065f46;
        cursor: pointer; transition: background .18s;
        user-select: all;
    }
    .demo-cred-val:hover { background: #dcfce7; }
    .demo-cred-sep { color: #86efac; font-size: .7rem; }
    .demo-box-note { font-size: .67rem; color: #4ade80; margin-top: .6rem; }

    /* Responsive: panel izquierdo oculto en móviles */
    @media (max-width: 800px) {
        body { overflow: auto; flex-direction: column; }
        .lp-side { display: none; }
        .lp-main { padding: 2.5rem 1.25rem; min-height: 100vh; background: linear-gradient(160deg, #080f25, #0f2952 45%, #1255a3 80%, #0e8dc4); }
        .lp-card {
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(20px);
            border-radius: 22px;
            padding: 2.25rem 1.75rem;
            box-shadow: 0 24px 70px rgba(0,0,0,.35);
        }
        .lp-form-header h1 { color: #0f172a; }
        .lp-form-header p  { color: #64748b; }
    }
    @media (max-width: 400px) {
        .lp-card { padding: 1.75rem 1.25rem; }
    }
    </style>
</head>
<body>
    <!-- Panel izquierdo (decorativo) -->
    <div class="lp-side">
        <div class="lp-side-shapes">
            <div class="lp-shape lp-shape--a"></div>
            <div class="lp-shape lp-shape--b"></div>
            <div class="lp-shape lp-shape--c"></div>
        </div>
        <canvas class="lp-side-canvas" id="lp-particles"></canvas>

        <?php 
            $sysLogo = (string) $configService->obtener('sistema.logo', 'img/logo.png');
            $sysName = (string) $configService->obtener('sistema.nombre', 'EduSchool ERP');
            $sysSub = (string) $configService->obtener('sistema.subtitulo', 'School Management System');
        ?>
        <div class="lp-side-logo">
            <img src="<?php echo htmlspecialchars(app_base_path('/' . ltrim($sysLogo, '/')), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
        </div>
        <div class="lp-side-title"><?php echo htmlspecialchars($sysName, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="lp-side-sub"><?php echo htmlspecialchars($sysSub, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="lp-side-features">
            <div class="lp-feat">
                <div class="lp-feat-icon"><i class="fas fa-users"></i></div>
                <div class="lp-feat-text">
                    <strong><?php echo htmlspecialchars(__('login.feat_students_title'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span><?php echo htmlspecialchars(__('login.feat_students_desc'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="lp-feat">
                <div class="lp-feat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="lp-feat-text">
                    <strong><?php echo htmlspecialchars(__('login.feat_teachers_title'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span><?php echo htmlspecialchars(__('login.feat_teachers_desc'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="lp-feat">
                <div class="lp-feat-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="lp-feat-text">
                    <strong><?php echo htmlspecialchars(__('login.feat_security_title'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span><?php echo htmlspecialchars(__('login.feat_security_desc'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel derecho (formulario) -->
    <div class="lp-main">
        <div class="lp-card">
            <div style="display: flex; justify-content: flex-end; margin-bottom: 0.75rem;">
                <div class="lang-switcher" style="display: flex; gap: 0.2rem; background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 20px; font-size: 0.8rem; border: 1px solid #cbd5e1;">
                    <a href="?lang=en" title="English" style="text-decoration:none; padding: 0.15rem 0.5rem; border-radius: 12px; color: #1e293b; <?php echo current_lang() === 'en' ? 'background: #2563eb; color: #fff; font-weight: 700;' : 'opacity: 0.75;'; ?>">🇺🇸 EN</a>
                    <a href="?lang=es" title="Español" style="text-decoration:none; padding: 0.15rem 0.5rem; border-radius: 12px; color: #1e293b; <?php echo current_lang() === 'es' ? 'background: #2563eb; color: #fff; font-weight: 700;' : 'opacity: 0.75;'; ?>">🇪🇸 ES</a>
                </div>
            </div>

            <div class="lp-form-header">
                <h1><?php echo htmlspecialchars(__('login.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars(__('login.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <!-- ══ DEMO CREDENTIALS BOX ══ -->
            <div class="demo-box" id="demo-credentials-box">
                <div class="demo-box-header">
                    <i class="fas fa-key"></i>
                    Demo Credentials — All passwords: <span style="font-family:monospace;background:rgba(255,255,255,.6);padding:0 5px;border-radius:4px;">admin123</span>
                </div>
                <div class="demo-creds">
                    <div class="demo-cred-row">
                        <span class="demo-cred-role">&#x1F4BC; Admin</span>
                        <div class="demo-cred-pair">
                            <span class="demo-cred-val" title="Click to select">admin@escuela.edu</span>
                        </div>
                    </div>
                    <div class="demo-cred-row">
                        <span class="demo-cred-role">&#x1F3EB; Director</span>
                        <div class="demo-cred-pair">
                            <span class="demo-cred-val" title="Click to select">director@greenfield.edu</span>
                        </div>
                    </div>
                    <div class="demo-cred-row">
                        <span class="demo-cred-role">&#x1F4CB; Preceptor</span>
                        <div class="demo-cred-pair">
                            <span class="demo-cred-val" title="Click to select">preceptor@greenfield.edu</span>
                        </div>
                    </div>
                    <div class="demo-cred-row">
                        <span class="demo-cred-role">&#x1F4D6; Teacher</span>
                        <div class="demo-cred-pair">
                            <span class="demo-cred-val" title="Click to select">p.williams@greenfield.edu</span>
                        </div>
                    </div>
                </div>
                <p class="demo-box-note">&#x1F4A1; Click any email to select it, then paste into the field above.</p>
            </div>



            <?php if ($error !== ''): ?>
            <div class="lp-alert lp-alert--error">
                <i class="fas fa-times-circle"></i>
                <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="lp-form" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="lp-field">
                    <label for="username"><i class="fas fa-user"></i> <?php echo htmlspecialchars(__('login.username'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="lp-input-wrap">
                        <i class="fas fa-user lp-input-icon"></i>
                        <input type="text" id="username" name="username" required
                               placeholder="<?php echo htmlspecialchars(__('login.username'), ENT_QUOTES, 'UTF-8'); ?>"
                               autocomplete="username"
                               value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="lp-field">
                    <label for="password"><i class="fas fa-lock"></i> <?php echo htmlspecialchars(__('login.password'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="lp-input-wrap">
                        <i class="fas fa-lock lp-input-icon"></i>
                        <input type="password" id="password" name="password" required
                               placeholder="<?php echo htmlspecialchars(__('login.password'), ENT_QUOTES, 'UTF-8'); ?>"
                               autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="lp-submit">
                    <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(__('login.btn_submit'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </form>

            <?php if ($googleOauthActivo): ?>
            <div class="lp-google-wrap" style="margin-top:1.35rem;text-align:center;">
                <p style="font-size:.78rem;color:#64748b;margin-bottom:.65rem;"><?php echo htmlspecialchars(__('login.faculty_staff'), ENT_QUOTES, 'UTF-8'); ?></p>
                <a href="google_login.php" class="lp-google-btn" style="display:inline-flex;align-items:center;gap:.55rem;padding:.72rem 1.25rem;border-radius:999px;border:1.5px solid #dadce0;background:#fff;color:#3c4043;font-size:.88rem;font-weight:600;text-decoration:none;font-family:inherit;box-shadow:0 1px 2px rgba(0,0,0,.08);transition:box-shadow .2s,background .2s;">
                    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6C44.43 39.07 46.98 32.54 46.98 24.55z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                    <?php echo htmlspecialchars(__('login.google_btn'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="lp-footer">
                <p><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars(__('login.system_title'), ENT_QUOTES, 'UTF-8'); ?></p>
                <small><?php echo htmlspecialchars($sysName, ENT_QUOTES, 'UTF-8'); ?> · <?php echo date('Y'); ?></small>
            </div>
            <div class="lp-familia-link">
                <?php echo htmlspecialchars(__('login.family_link'), ENT_QUOTES, 'UTF-8'); ?> <a href="<?php echo htmlspecialchars(app_base_path('/public/portal.php'), ENT_QUOTES, 'UTF-8'); ?>#acceso-familias"><?php echo htmlspecialchars(__('auto.portal_de_familias'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
            <div class="lp-disclaimer" style="margin-top: 1.5rem; font-size: 0.72rem; color: #94a3b8; text-align: center; line-height: 1.45; border-top: 1px dashed rgba(148, 163, 184, 0.22); padding-top: 1rem;">
                <p style="margin-bottom: 0.35rem;"><i class="fas fa-shield-alt" style="color: #3b82f6; margin-right: 0.25rem;"></i> <strong><?php echo htmlspecialchars(__('login.disclaimer_title'), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <p style="margin: 0; padding: 0 0.5rem;"><?php echo htmlspecialchars(__('login.disclaimer_body'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <script nonce="<?php echo htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    /* Partículas en el panel lateral */
    (function () {
        const canvas = document.getElementById('lp-particles');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let W, H, ps;
        function resize() {
            const side = canvas.parentElement;
            W = canvas.width = side.offsetWidth;
            H = canvas.height = side.offsetHeight;
        }
        function init() {
            resize();
            ps = Array.from({length: 45}, () => ({
                x: Math.random() * W, y: Math.random() * H,
                r: Math.random() * 2.5 + .8,
                dx: (Math.random() - .5) * .35, dy: (Math.random() - .5) * .35,
                a: Math.random() * .35 + .08,
                c: ['#38bdf8','#7dd3fc','#93c5fd','#60a5fa','#bae6fd'][Math.floor(Math.random()*5)],
            }));
        }
        function draw() {
            ctx.clearRect(0, 0, W, H);
            ps.forEach(p => {
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
                ctx.fillStyle = p.c; ctx.globalAlpha = p.a; ctx.fill();
                p.x += p.dx; p.y += p.dy;
                if (p.x < 0 || p.x > W) p.dx *= -1;
                if (p.y < 0 || p.y > H) p.dy *= -1;
            });
            ctx.globalAlpha = 1;
            requestAnimationFrame(draw);
        }
        init(); draw();
        window.addEventListener('resize', () => { resize(); ps.forEach(p => { p.x = Math.min(p.x, W); p.y = Math.min(p.y, H); }); });
    })();
    </script>
</body>
</html>
