<?php

/**
 * Cargador de variables de entorno desde archivo .env
 * 
 * Este archivo carga las variables de entorno desde .env
 * y las hace disponibles en $_ENV y $_SERVER
 */
class EnvLoader {
    
    private static $loaded = false;
    
    /**
     * Carga las variables de entorno desde .env
     * 
     * @param string $path Ruta al archivo .env
     * @return bool True si se cargaron correctamente
     */
    public static function load(string $path = '.env'): bool {
        if (self::$loaded) {
            return true;
        }
        
        // Buscar archivos en diferentes ubicaciones relativas
        $baseDirs = [
            '',
            __DIR__ . '/../',
            __DIR__ . '/../../',
            dirname(__DIR__) . '/'
        ];
        
        $envData = null;
        
        // 1. Priorizar env.php (seguro en cualquier servidor web)
        $phpPath = str_replace('.env', 'env.php', $path);
        if ($phpPath === $path) {
            $phpPath = 'env.php'; // Fallback por si la ruta no contiene .env
        }
        
        foreach ($baseDirs as $dir) {
            $p = $dir . $phpPath;
            if (is_file($p)) {
                $envData = require $p;
                break;
            }
        }
        
        // 2. Fallback a .env clásico (XAMPP local)
        if ($envData === null || !is_array($envData)) {
            foreach ($baseDirs as $dir) {
                $p = $dir . $path;
                if (is_file($p)) {
                    $lines = file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines !== false) {
                        $envData = [];
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line) || strpos($line, '#') === 0) continue;
                            
                            $pos = strpos($line, '=');
                            if ($pos === false) continue;
                            
                            $key = trim(substr($line, 0, $pos));
                            $value = trim(substr($line, $pos + 1));
                            
                            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                                $value = substr($value, 1, -1);
                            }
                            $envData[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }
        
        if ($envData === null || !is_array($envData)) {
            // Si no existe ni env.php ni .env, usar valores por defecto
            self::setDefaultEnv();
            self::$loaded = true;
            return true;
        }
        
        // Cargar variables en el entorno
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
        
        self::$loaded = true;
        return true;
    }
    
    /**
     * Establece valores por defecto si no existe .env
     */
    private static function setDefaultEnv(): void {
        $defaults = [
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_NAME' => 'school_admin',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'SESSION_LIFETIME' => '120',
            'MAX_LOGIN_ATTEMPTS' => '5',
            // Base path used in HTTP Location headers and HTML asset paths.
            // In XAMPP local dev this is '/SistemaAdmin'. In production (domain root) set to '/'.
            'APP_BASE_PATH' => '/SistemaAdmin',
            // Full public URL of the app (no trailing slash) used for QR codes, OAuth callbacks, etc.
            'APP_URL' => 'http://localhost/SistemaAdmin',
        ];
        
        foreach ($defaults as $key => $value) {
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
    }
    
    /**
     * Obtiene una variable de entorno con valor por defecto
     * 
     * @param string $key Clave de la variable
     * @param mixed $default Valor por defecto
     * @return mixed Valor de la variable o valor por defecto
     */
    public static function get(string $key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    
    /**
     * Verifica si una variable de entorno existe
     * 
     * @param string $key Clave de la variable
     * @return bool True si existe
     */
    public static function has(string $key): bool {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset($_ENV[$key]) || isset($_SERVER[$key]);
    }
}
