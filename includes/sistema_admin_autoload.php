<?php

declare(strict_types=1);

/**
 * Punto único de entrada para autoload y dependencias de terceros.
 * - Si existe Composer (`vendor/autoload.php`), se usa como PSR-4 principal (incluye `SistemaAdmin\` → `src/`).
 * - Siempre se incluye `src/autoload.php` para bootstrap de entorno (config, timezone, mb) sin duplicar el PSR-4 manual cuando Composer está activo.
 */

if (!function_exists('sistema_admin_load_autoload')) {
    function sistema_admin_load_autoload(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $root = dirname(__DIR__);
        if (!defined('SISTEMA_ADMIN_AUTOLOAD_MODE')) {
            $composerAutoload = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (is_file($composerAutoload)) {
                require_once $composerAutoload;
                define('SISTEMA_ADMIN_AUTOLOAD_MODE', 'composer');
            } else {
                define('SISTEMA_ADMIN_AUTOLOAD_MODE', 'embedded');
            }
        }

        require_once $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'autoload.php';
        $loaded = true;
    }
}
