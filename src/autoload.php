<?php
/**
 * Autoloader PSR-4 para el Sistema Administrativo E.E.S.T N°2
 * 
 * Este archivo maneja la carga automática de clases siguiendo el estándar PSR-4
 */

// Verificar versión mínima de PHP
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('Este sistema requiere PHP 8.0 o superior. Versión actual: ' . PHP_VERSION);
}

// Definir el directorio raíz del namespace
$rootDir = __DIR__;

/**
 * PSR-4 manual solo si no se cargó `SistemaAdmin\` vía Composer
 * (véase `includes/sistema_admin_autoload.php` y constante `SISTEMA_ADMIN_AUTOLOAD_MODE`).
 */
$usarPsr4Embebido = !defined('SISTEMA_ADMIN_AUTOLOAD_MODE') || SISTEMA_ADMIN_AUTOLOAD_MODE !== 'composer';

if ($usarPsr4Embebido) {
    spl_autoload_register(function ($className) use ($rootDir) {
        $prefix = 'SistemaAdmin\\';
        $baseDir = $rootDir . '/';

        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            return;
        }

        $relativeClass = substr($className, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

// Cargar archivos de configuración y utilidades
if (file_exists($rootDir . '/config.php')) {
    require_once $rootDir . '/config.php';
}

// Inicializar configuración de errores para desarrollo
if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'production') {
    // En desarrollo, mostrar errores pero de forma segura
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

// Configurar zona horaria
if (!ini_get('date.timezone')) {
    date_default_timezone_set('America/Argentina/Buenos_Aires');
}

// Configurar encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
?>