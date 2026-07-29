<?php

namespace SistemaAdmin\Middleware;

class SessionSecurityMiddleware
{
    /**
     * Configuración segura de sesiones
     */
    public static function configureSecureSession(): void
    {
        // Solo configurar parámetros de sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar parámetros de sesión seguros
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_lifetime', 0); // Sesión hasta que se cierre el navegador
            ini_set('session.gc_maxlifetime', 3600); // 1 hora máximo
        }
        
        // PROTECCIÓN CONTRA SESSION FIXATION
        // Regenerar ID de sesión inmediatamente después del login
        if (isset($_POST['usuario']) && isset($_POST['password'])) {
            session_regenerate_id(true);
            $_SESSION['session_fixed'] = true;
        }
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        
        // Regenerar ID cada 15 minutos
        if (time() - $_SESSION['last_regeneration'] > 900) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Verificar integridad de la sesión
     */
    public static function validateSessionIntegrity(): bool
    {
        // PROTECCIÓN CONTRA SESSION HIJACKING
        // Verificar que el usuario no cambió de IP
        if (isset($_SESSION['user_ip'])) {
            if ($_SESSION['user_ip'] !== self::getClientIP()) {
                // Log de posible session hijacking
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown';
                error_log("Session Hijacking Alert: IP mismatch for user {$userId}. Expected: {$_SESSION['user_ip']}, Got: " . self::getClientIP());
                return false;
            }
        } else {
            $_SESSION['user_ip'] = self::getClientIP();
        }
        
        $currentUA = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Verificar User-Agent
        if (!empty($_SESSION['user_agent']) && $currentUA !== '') {
            if ($_SESSION['user_agent'] !== $currentUA) {
                // Log de posible session hijacking
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown';
                error_log("Session Hijacking Alert: User Agent mismatch for user {$userId}");
                return false;
            }
        } elseif ($currentUA !== '') {
            $_SESSION['user_agent'] = $currentUA;
        }
        
        $maxAge = 3600;
        if (isset($GLOBALS['SA_SESSION_MAX_AGE_SECONDS'])) {
            $maxAge = max(300, min(604800, (int) $GLOBALS['SA_SESSION_MAX_AGE_SECONDS']));
        }

        if (isset($_SESSION['created_at'])) {
            if (time() - (int) $_SESSION['created_at'] > $maxAge) {
                return false;
            }
        } else {
            $_SESSION['created_at'] = time();
        }
        
        return true;
    }
    
    /**
     * Limpiar sesión de forma segura
     */
    public static function destroySession(): void
    {
        // Destruir todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    /**
     * Generar token CSRF seguro
     */
    public static function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time();
        
        // Limpiar tokens antiguos (más de 1 hora)
        foreach ($_SESSION['csrf_tokens'] as $key => $timestamp) {
            if (time() - $timestamp > 3600) {
                unset($_SESSION['csrf_tokens'][$key]);
            }
        }
        
        return $token;
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }
        
        // Verificar que el token no sea muy antiguo
        if (time() - $_SESSION['csrf_tokens'][$token] > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }
        
        // Token de un solo uso
        unset($_SESSION['csrf_tokens'][$token]);
        
        return true;
    }
    
    /**
     * Obtener IP real del cliente
     */
    private static function getClientIP(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Detectar actividad sospechosa
     */
    public static function detectSuspiciousActivity(): array
    {
        $suspicious = [];
        
        // Verificar múltiples intentos de login
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        $ip = self::getClientIP();
        $currentTime = time();
        
        // Limpiar intentos antiguos
        $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($timestamp) use ($currentTime) {
            return $currentTime - $timestamp < 3600; // Última hora
        });
        
        // Contar intentos de esta IP
        $attempts = array_filter($_SESSION['login_attempts'], function($timestamp, $attemptIP) use ($ip) {
            return $attemptIP === $ip;
        }, ARRAY_FILTER_USE_BOTH);
        
        if (count($attempts) > 5) {
            $suspicious[] = 'Múltiples intentos de login desde la misma IP';
        }
        
        // Verificar cambios bruscos de User-Agent
        if (isset($_SESSION['previous_user_agents'])) {
            $currentUA = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $previousUAs = $_SESSION['previous_user_agents'];
            
            if (!in_array($currentUA, $previousUAs) && count($previousUAs) > 0) {
                $suspicious[] = 'User-Agent cambió inesperadamente';
            }
            
            // Mantener solo los últimos 3 User-Agents
            $_SESSION['previous_user_agents'] = array_slice(array_merge($previousUAs, [$currentUA]), -3);
        } else {
            $_SESSION['previous_user_agents'] = [isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''];
        }
        
        return $suspicious;
    }
}
