<?php

declare(strict_types=1);

/**
 * Cargador de variables de entorno desde archivo .env
 *
 * Este archivo carga las variables de entorno desde .env
 * y las hace disponibles en $_ENV y $_SERVER
 */
class EnvLoader
{
    private static bool $loaded = false;

    /**
     * Carga las variables de entorno desde .env y aplica defaults de Railway
     */
    public static function load(?string $dir = null): bool
    {
        if (self::$loaded) {
            return true;
        }

        $baseDir = $dir ?? dirname(__DIR__);
        $envFile = $baseDir . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#') {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        [$key, $value] = explode('=', $line, 2);
                        $key   = trim($key);
                        $value = trim($value);
                        if (strlen($value) >= 2 &&
                            (($value[0] === '"' && $value[-1] === '"') ||
                             ($value[0] === "'" && $value[-1] === "'"))) {
                            $value = substr($value, 1, -1);
                        }
                        if (!isset($_ENV[$key]))       { $_ENV[$key]    = $value; }
                        if (!isset($_SERVER[$key]))    { $_SERVER[$key] = $value; }
                        if (getenv($key) === false)    { putenv("$key=$value");   }
                    }
                }
            }
        }

        // Detectar entorno Railway y aplicar defaults de base de datos
        $isRailway = (
            getenv('RAILWAY_ENVIRONMENT') !== false ||
            getenv('RAILWAY_PUBLIC_DOMAIN') !== false ||
            getenv('RAILWAY_STATIC_URL') !== false ||
            getenv('PORT') !== false
        );

        $host  = getenv('MYSQLHOST')     ?: (getenv('MYSQL_HOST')     ?: ($isRailway ? 'sakura.proxy.rlwy.net' : 'localhost'));
        $port  = getenv('MYSQLPORT')     ?: (getenv('MYSQL_PORT')     ?: ($isRailway ? '48834' : '3306'));
        $db    = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: ($isRailway ? 'railway' : 'school_admin'));
        $user  = getenv('MYSQLUSER')     ?: (getenv('MYSQL_USER')     ?: 'root');
        $pass  = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: ($isRailway ? 'QfSCTskshvUOOGrqlbjHpowxhahCoBxW' : ''));

        $defaults = [
            'DB_HOST'            => $host,
            'DB_PORT'            => $port,
            'DB_NAME'            => $db,
            'DB_USER'            => $user,
            'DB_PASS'            => $pass,
            'APP_ENV'            => $isRailway ? 'production' : 'development',
            'APP_DEBUG'          => $isRailway ? 'false' : 'true',
            'SESSION_LIFETIME'   => '120',
            'MAX_LOGIN_ATTEMPTS' => '5',
            'APP_BASE_PATH'      => $isRailway ? '/' : '/SistemaAdmin',
            'APP_URL'            => getenv('RAILWAY_PUBLIC_DOMAIN')
                                        ? ('https://' . getenv('RAILWAY_PUBLIC_DOMAIN'))
                                        : 'http://localhost/SistemaAdmin',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($_ENV[$key])    || $_ENV[$key]    === '') { $_ENV[$key]    = $value; }
            if (!isset($_SERVER[$key]) || $_SERVER[$key] === '') { $_SERVER[$key] = $value; }
            if (getenv($key) === false || getenv($key)   === '') { putenv("$key=$value");   }
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Obtiene una variable de entorno con valor por defecto
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    /**
     * Verifica si una variable de entorno existe
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }
        return (getenv($key) !== false && getenv($key) !== '')
            || isset($_ENV[$key])
            || isset($_SERVER[$key]);
    }

    /**
     * Aplica los valores por defecto del entorno (por compatibilidad, ya inlinado en load())
     */
    public static function setDefaultEnv(): void
    {
        self::load();
    }
}
