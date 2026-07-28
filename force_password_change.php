<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Services\ServicioAutenticacion;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$usuario = $servicioAutenticacion->verificarSesion();

if ($usuario === null) {
    header('Location: ' . app_base_path('/public/login.php'));
    exit();
}

if ((int) ($_SESSION['must_change_password'] ?? 0) !== 1) {
    header('Location: ' . app_base_path('/'));
    exit();
}

$error = '';
$success = '';
$csrfToken = getCSRFToken();
$esPost = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';

if ($esPost) {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!verifyCSRFToken($token)) {
        $error = 'La solicitud no pudo validarse. Actualizá la página e intentá nuevamente.';
    } else {
        $passwordActual = (string) ($_POST['password_actual'] ?? '');
        $passwordNuevo = (string) ($_POST['password_nuevo'] ?? '');
        $passwordConfirmacion = (string) ($_POST['password_confirmacion'] ?? '');

        if ($passwordNuevo === '' || $passwordConfirmacion === '' || $passwordActual === '') {
            $error = 'Completá todos los campos.';
        } elseif ($passwordNuevo !== $passwordConfirmacion) {
            $error = 'La nueva contraseña y su confirmación no coinciden.';
        } else {
            $resultado = $servicioAutenticacion->cambiarPassword((int) $usuario['id'], $passwordActual, $passwordNuevo);
            if (!empty($resultado['success'])) {
                $_SESSION['must_change_password'] = 0;
                $success = 'Contraseña actualizada correctamente. Ya podés ingresar al sistema.';
                header('Location: ' . app_base_path('/?password_updated=1'));
                exit();
            }
            $error = (string) ($resultado['error'] ?? 'No se pudo actualizar la contraseña.');
        }
    }
}

sistema_admin_send_html_security_headers();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(function_exists('current_lang') ? current_lang() : 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('auto.cambio_obligatorio_de_contrase_a'), ENT_QUOTES, 'UTF-8'); ?> · EduSchool ERP</title>
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
            align-items: center;
            justify-content: center;
            background: linear-gradient(155deg, #080f25 0%, #0f2952 35%, #1255a3 68%, #0e8dc4 100%);
            padding: 1.5rem;
        }
        /* Background shapes */
        .bg-shape {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .15;
            pointer-events: none;
        }
        .bg-shape-a { width: 500px; height: 500px; background: #38bdf8; top: -180px; right: -100px; animation: float 18s ease-in-out infinite alternate; }
        .bg-shape-b { width: 380px; height: 380px; background: #6366f1; bottom: -120px; left: -80px; animation: float 14s ease-in-out 4s infinite alternate; }
        @keyframes float {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(30px, 40px) scale(1.08); }
        }
        /* Card */
        .card {
            width: 100%;
            max-width: 480px;
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.15);
            padding: 2.5rem 2.25rem;
            position: relative;
            z-index: 1;
            animation: card-in .55s cubic-bezier(.4,0,.2,1) both;
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: none; }
        }
        /* Header */
        .card-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #6366f1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.4rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px rgba(37,99,235,.35);
        }
        .card h1 {
            font-size: 1.5rem; font-weight: 800;
            color: #0f172a; letter-spacing: -.03em;
            margin-bottom: .4rem;
        }
        .card > p {
            font-size: .875rem; color: #64748b;
            line-height: 1.6; margin-bottom: 1.5rem;
        }
        /* Alerts */
        .alert {
            display: flex; align-items: flex-start; gap: .65rem;
            padding: .85rem 1rem; border-radius: 12px;
            font-size: .84rem; line-height: 1.55;
            margin-bottom: 1.25rem;
        }
        .alert i { flex-shrink: 0; margin-top: .15rem; }
        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        /* Form */
        .form { display: flex; flex-direction: column; gap: 1rem; }
        .field { display: flex; flex-direction: column; gap: .35rem; }
        .field label {
            font-size: .78rem; font-weight: 600; color: #334155;
            display: flex; align-items: center; gap: .4rem;
        }
        .field label i { color: #3b82f6; font-size: .78rem; }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: .9rem; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; font-size: .88rem; pointer-events: none;
        }
        .field input {
            width: 100%;
            padding: .8rem 1rem .8rem 2.6rem;
            border: 1.5px solid rgba(148,163,184,.3);
            border-radius: 11px;
            font-size: .92rem; font-family: inherit;
            color: #0f172a; background: #f8fafc;
            outline: none;
            transition: border-color .22s, box-shadow .22s, background .22s;
        }
        .field input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3.5px rgba(59,130,246,.13);
            background: #fff;
        }
        /* Submit */
        .btn-submit {
            width: 100%; padding: .9rem;
            border-radius: 999px;
            font-size: .93rem; font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #6366f1 100%);
            border: none; cursor: pointer;
            font-family: inherit;
            box-shadow: 0 4px 20px rgba(37,99,235,.32);
            transition: all .25s;
            display: flex; align-items: center; justify-content: center; gap: .55rem;
            margin-top: .5rem;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,.42); }
        .btn-submit:active { transform: none; }
        /* Requirements hint */
        .req-hint {
            font-size: .73rem; color: #94a3b8;
            line-height: 1.5; margin-top: .5rem;
            padding: .65rem .85rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px dashed rgba(148,163,184,.4);
        }
        .req-hint i { color: #3b82f6; margin-right: .25rem; }
        @media (max-width: 520px) {
            .card { padding: 2rem 1.5rem; border-radius: 18px; }
        }
    </style>
</head>
<body>
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <div class="card">
        <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>Password Change Required</h1>
        <p><?php echo htmlspecialchars(__('auto.por_seguridad_deb_s_cambiar_la_contrase_a'), ENT_QUOTES, 'UTF-8'); ?></p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="field">
                <label for="password_actual"><i class="fas fa-lock"></i> <?php echo htmlspecialchars(__('auto.contrase_a_actual_temporal'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password_actual" name="password_actual" required autocomplete="current-password">
                </div>
            </div>

            <div class="field">
                <label for="password_nuevo"><i class="fas fa-key"></i> <?php echo htmlspecialchars(__('auto.nueva_contrase_a'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-wrap">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="password_nuevo" name="password_nuevo" required autocomplete="new-password" minlength="8">
                </div>
            </div>

            <div class="field">
                <label for="password_confirmacion"><i class="fas fa-check-double"></i> <?php echo htmlspecialchars(__('auto.confirmar_nueva_contrase_a'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-wrap">
                    <i class="fas fa-check-double input-icon"></i>
                    <input type="password" id="password_confirmacion" name="password_confirmacion" required autocomplete="new-password" minlength="8">
                </div>
            </div>

            <div class="req-hint">
                <i class="fas fa-info-circle"></i>
                Minimum 8 characters. Use a mix of letters, numbers, and symbols for a strong password.
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i>
                <?php echo htmlspecialchars(__('auto.guardar_nueva_contrase_a'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>
    </div>
</body>
</html>

