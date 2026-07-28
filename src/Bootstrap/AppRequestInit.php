<?php

declare(strict_types=1);

namespace SistemaAdmin\Bootstrap;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\ConfigurationService;

/**
 * Aplica timezone, nombre del sistema y parámetros de sesión leídos de configuracion_sistema.
 * Debe ejecutarse justo después de obtener el DatabaseInterface (p. ej. en database_bootstrap).
 */
final class AppRequestInit
{
    private static bool $applied = false;

    public static function applyFromDatabase(DatabaseInterface $database): void
    {
        if (self::$applied) {
            return;
        }
        self::$applied = true;

        $config = new ConfigurationService($database);

        $tz = (string) $config->obtener('sistema.timezone', 'America/Argentina/Buenos_Aires');
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            date_default_timezone_set($tz);
        }

        $GLOBALS['SA_SYSTEM_NAME'] = (string) $config->obtener('sistema.nombre', 'EduSchool ERP');
        $GLOBALS['SA_SYSTEM_SUBTITLE'] = (string) $config->obtener('sistema.subtitulo', 'School Management System');
        $GLOBALS['SA_SYSTEM_LOGO'] = (string) $config->obtener('sistema.logo', 'img/logo.png');
        $GLOBALS['SA_CURRENCY_SYMBOL'] = (string) $config->obtener('sistema.moneda_simbolo', '$');

        $mins = (int) round((float) $config->obtener('seguridad.sesion_duracion', 480));
        if ($mins < 5) {
            $mins = 5;
        }
        if ($mins > 10080) {
            $mins = 10080;
        }
        $secs = $mins * 60;
        $GLOBALS['SA_SESSION_INACTIVITY_SECONDS'] = $secs;
        $GLOBALS['SA_SESSION_MAX_AGE_SECONDS'] = $secs;
    }

    /**
     * Llamar antes de session_start() la primera vez en la petición.
     */
    public static function configureSessionIni(DatabaseInterface $database): void
    {
        self::applyFromDatabase($database);

        $lifetime = (int) ($GLOBALS['SA_SESSION_INACTIVITY_SECONDS'] ?? 1800);
        if ($lifetime < 300) {
            $lifetime = 300;
        }

        ini_set('session.gc_maxlifetime', (string) $lifetime);
    }

    public static function systemName(): string
    {
        return (string) ($GLOBALS['SA_SYSTEM_NAME'] ?? 'EduSchool ERP');
    }

    public static function systemSubtitle(): string
    {
        return (string) ($GLOBALS['SA_SYSTEM_SUBTITLE'] ?? 'School Management System');
    }

    public static function systemLogo(): string
    {
        return (string) ($GLOBALS['SA_SYSTEM_LOGO'] ?? 'img/logo.png');
    }

    public static function currencySymbol(): string
    {
        return (string) ($GLOBALS['SA_CURRENCY_SYMBOL'] ?? '$');
    }

    public static function sessionInactivitySeconds(): int
    {
        $s = (int) ($GLOBALS['SA_SESSION_INACTIVITY_SECONDS'] ?? 1800);

        return max(300, min(604800, $s));
    }
}
