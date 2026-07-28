<?php

declare(strict_types=1);

/**
 * Cargador de variables de entorno desde archivo .env
 * 
 * Este archivo carga las variables de entorno desde .env
 * y las hace disponibles en $_ENV y $_SERVER
 */
class EnvLoader {
    
    private static bool $loaded = false;
    
    /**
     * Carga las variables de entorno desde .env
     */
    public static function load(?string $dir = null): bool {
        if (self::$loaded) {
            return true;
        }

        $baseDir = $dir ?? dirname(__DIR__);
        $envFile = $baseDir . '/.env';
        
        $envData = [];
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                            $value = substr($value, 1, -1);
                        }
                        $envData[$key] = $value;
                    }
                }
            }
        }
        
        foreach ($envData as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $value;
            }
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }
        }

        // Auto-detect Railway / Cloud MySQL environment variables
        $isRailway = getenv('RAILWAY_STATIC_URL') !== false || getenv('RAILWAY_PUBLIC_DOMAIN') !== false || getenv('PORT') !== false || getenv('RAILWAY_ENVIRONMENT') !== false;

        $railwayHost = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: (getenv('RAILWAY_MYSQL_HOST') ?: ($isRailway ? 'altaria.proxy.rlwy.net' : 'localhost')));
        $railwayPort = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: ($isRailway ? '51056' : '3306'));
        $railwayDb   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: (getenv('RAILWAY_MYSQL_DATABASE') ?: ($isRailway ? 'railway' : 'school_admin')));
        $railwayUser = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: (getenv('RAILWAY_MYSQL_USER') ?: 'root'));
        $railwayPass = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: (getenv('RAILWAY_MYSQL_PASSWORD') ?: ($isRailway ? 'FuCJnrHXcSKZigIHjqhughtJcAOoQTHX' : '')));

        $defaults = [
            'DB_HOST' => $railwayHost,
            'DB_PORT' => $railwayPort,
            'DB_NAME' => $railwayDb,
            'DB_USER' => $railwayUser,
            'DB_PASS' => $railwayPass,
            'APP_ENV' => $isRailway ? 'production' : 'development',
            'APP_DEBUG' => $isRailway ? 'false' : 'true',
            'SESSION_LIFETIME' => '120',
            'MAX_LOGIN_ATTEMPTS' => '5',
            'APP_BASE_PATH' => $isRailway ? '/' : '/SistemaAdmin',
            'APP_URL' => getenv('RAILWAY_PUBLIC_DOMAIN') ? ('https://' . getenv('RAILWAY_PUBLIC_DOMAIN')) : 'http://localhost/SistemaAdmin',
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
                $_ENV[$key] = $value;
            }
            if (!isset($_SERVER[$key]) || $_SERVER[$key] === '') {
                $_SERVER[$key] = $value;
            }
            if (getenv($key) === false || getenv($key) === '') {
                putenv($key . '=' . $value);
            }
        }
        
        self::$loaded = true;
        return true;
    }
    
    /**
     * Obtiene una variable de entorno con valor por defecto
     */
    public static function get(string $key, $default = null) {
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
    public static function has(string $key): bool {
        if (!self::$loaded) {
            self::load();
        }

        return (getenv($key) !== false && getenv($key) !== '') || isset($_ENV[$key]) || isset($_SERVER[$key]);
    }
}
