<?php

namespace SistemaAdmin\Services;

class ValidationService extends BaseService
{
    /**
     * Sanitiza una entrada de texto
     */
    public function sanitizeInput(string $input): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->sanitizeInput($input);
    }

    /**
     * Valida un nombre de archivo para prevenir path traversal
     */
    public function validateFileName(string $filename): bool
    {
        // No permitir caracteres peligrosos
        if (preg_match('/[\/\\:*?"<>|]/', $filename)) {
            return false;
        }
        
        // No permitir nombres que empiecen con punto
        if (strpos($filename, '.') === 0) {
            return false;
        }
        
        // Backups generados por el sistema (cifrados o no)
        if (preg_match('/^backup_completo_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.(zip|tar\.gz)(\.enc)?$/', $filename)) {
            return true;
        }

        // Solo permitir extensiones seguras
        $allowedExtensions = ['zip', 'sql', 'json'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $allowedExtensions, true);
    }

    /**
     * Valida una acción permitida
     */
    public function validateAction(string $action): bool
    {
        $allowedActions = [
            'dashboard',
            'crear_backup',
            'restaurar_backup',
            'eliminar_backup',
            'descargar_backup',
            'actualizar_config',
            'limpiar_cache',
            'optimizar_db'
        ];
        
        return in_array($action, $allowedActions);
    }

    /**
     * Valida un token CSRF
     */
    public function validateCSRFToken(string $token): bool
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->validateCSRFToken($token);
    }

    /**
     * Genera un token CSRF
     */
    public function generateCSRFToken(): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->generateCSRFToken();
    }

    /**
     * Rate limiting básico
     */
    public function checkRateLimit(string $action, string $ip): bool
    {
        $key = "rate_limit_{$action}_{$ip}";
        $currentTime = time();
        $window = 300; // 5 minutos
        $maxRequests = 10; // máximo 10 requests por ventana
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => $currentTime
            ];
            return true;
        }
        
        $rateLimitData = $_SESSION[$key];
        
        // Reset si la ventana expiró
        if ($currentTime - $rateLimitData['start_time'] > $window) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => $currentTime
            ];
            return true;
        }
        
        // Verificar límite
        if ($rateLimitData['count'] >= $maxRequests) {
            return false;
        }
        
        // Incrementar contador
        $_SESSION[$key]['count']++;
        
        return true;
    }
}
