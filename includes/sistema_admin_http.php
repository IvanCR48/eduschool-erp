<?php

declare(strict_types=1);

/**
 * Utilidades HTTP compartidas por las páginas web (evita URLs de login duplicadas
 * y headers de seguridad inconsistentes entre index y el resto del sistema).
 */

use SistemaAdmin\Middleware\SecurityHeadersMiddleware;

if (!function_exists('sistema_admin_login_redirect_url')) {
    /**
     * Ruta relativa al portal de inicio según si el script vive en /admin/ o en la raíz del proyecto.
     */
    function sistema_admin_login_redirect_url(): string
    {
        return app_base_path('public/portal.php');
    }
}

if (!function_exists('app_base_path')) {
    /**
     * Devuelve la ruta base de la aplicación para usar en redirecciones HTTP
     * y atributos href/src en HTML.
     *
     * El valor se toma de APP_BASE_PATH en .env (sin barra final).
     *   - Entorno local XAMPP:  APP_BASE_PATH=/SistemaAdmin
     *   - Producción (raíz):   APP_BASE_PATH=/
     *
     * Uso: app_base_path('/courses.php')  =>  '/SistemaAdmin/courses.php'  (local)
     *                                    =>  '/courses.php'               (prod)
     */
    function app_base_path(string $path = ''): string
    {
        $base = rtrim((string) ($_ENV['APP_BASE_PATH'] ?? '/SistemaAdmin'), '/');
        if ($path === '' || $path === '/') {
            return $base . '/';
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('sistema_admin_send_html_security_headers')) {
    /**
     * CSP, COOP, HSTS, etc. para respuestas HTML.
     * No llamar en endpoints que devuelvan JSON, Excel u otros binarios antes de fijar su Content-Type.
     */
    function sistema_admin_send_html_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }
        require_once __DIR__ . '/sistema_admin_autoload.php';
        sistema_admin_load_autoload();
        SecurityHeadersMiddleware::applyHeaders();
    }
}
