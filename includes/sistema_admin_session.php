<?php

declare(strict_types=1);

/**
 * Carga BD, aplica timezone/config en globals e inicia sesión con parámetros alineados a seguridad.sesion_duracion.
 * Sustituye el par session_start() + database_bootstrap en páginas web estándar.
 */

require_once __DIR__ . '/database_bootstrap.php';
require_once __DIR__ . '/sistema_admin_error_handler.php';
require_once __DIR__ . '/i18n.php';

use SistemaAdmin\Bootstrap\AppRequestInit;
use SistemaAdmin\Services\I18nService;

sistema_admin_error_handler_register();
I18nService::init();

if (session_status() === PHP_SESSION_NONE) {
    AppRequestInit::configureSessionIni(sistema_admin_db_adapter());
    if (!headers_sent()) {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $isSecure = in_array($https, ['on', '1', 'true'], true) || str_contains($forwardedProto, 'https');

        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('sistema_admin_debe_forzar_cambio_password')) {
    function sistema_admin_debe_forzar_cambio_password(): bool
    {
        $usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        if ($usuarioId < 1) {
            return false;
        }

        if (isset($_SESSION['must_change_password'])) {
            return (int) $_SESSION['must_change_password'] === 1;
        }

        try {
            $row = sistema_admin_db_adapter()->fetch(
                'SELECT must_change_password FROM usuarios WHERE id = ? LIMIT 1',
                [$usuarioId]
            );
            $_SESSION['must_change_password'] = (!empty($row) && (int) ($row['must_change_password'] ?? 0) === 1) ? 1 : 0;
        } catch (\Throwable) {
            $_SESSION['must_change_password'] = 0;
        }

        return (int) $_SESSION['must_change_password'] === 1;
    }
}

if (!function_exists('sistema_admin_redireccion_cambio_password_obligatorio')) {
    function sistema_admin_redireccion_cambio_password_obligatorio(): void
    {
        $script = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $permitidos = [
            '/public/login.php',
            '/public/logout.php',
            '/logout.php',
            '/force_password_change.php',
        ];

        foreach ($permitidos as $permitido) {
            if (str_ends_with($script, $permitido)) {
                return;
            }
        }

        if (sistema_admin_debe_forzar_cambio_password() && !headers_sent()) {
            $destino = function_exists('app_base_path')
                ? app_base_path('/force_password_change.php')
                : '/SistemaAdmin/force_password_change.php';
            header('Location: ' . $destino);
            exit();
        }
    }
}

sistema_admin_redireccion_cambio_password_obligatorio();
