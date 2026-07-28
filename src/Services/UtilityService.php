<?php

namespace SistemaAdmin\Services;

/**
 * Servicio utilitario centralizado para métodos comunes
 * Evita duplicación de código en todo el sistema
 */
class UtilityService extends BaseService
{
    /**
     * Formatear bytes a formato legible
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes >1024 && $i< count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Formatear tiempo de uptime
     */
    public function formatUptime(float $seconds): string
    {
        // Convertir a entero para evitar problemas de precisión
        $totalSeconds = (int)round($seconds);
        
        $days = (int)floor($totalSeconds / 86400);
        $remainingSeconds = $totalSeconds % 86400;
        
        $hours = (int)floor($remainingSeconds / 3600);
        $remainingSeconds = $remainingSeconds % 3600;
        
        $minutes = (int)floor($remainingSeconds / 60);
        $remainingSeconds = $remainingSeconds % 60;
        
        $seconds = $remainingSeconds;
        
        $parts = [];
        if ($days > 0) $parts[] = $days . 'd';
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($minutes > 0) $parts[] = $minutes . 'm';
        if ($seconds > 0 || empty($parts)) $parts[] = $seconds . 's';
        
        return implode(' ', $parts);
    }

    /**
     * Obtener IP del cliente de forma segura
     */
    public function obtenerIPCliente(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                   'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback a IP local si no se encuentra IP pública
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Generar hash seguro para contraseñas
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iteraciones
            'threads' => 3          // 3 hilos
        ]);
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Obtener timestamp actual
     */
    public function getCurrentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Formatear fecha
     */
    public function formatDate(string $date, string $format = 'd/m/Y'): string
    {
        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Formatear fecha y hora
     */
    public function formatDateTime(string $dateTime, string $format = 'd/m/Y H:i'): string
    {
        try {
            $dateObj = new \DateTime($dateTime);
            return $dateObj->format($format);
        } catch (\Exception $e) {
            return $dateTime;
        }
    }

    /**
     * Sanitizar entrada de texto
     */
    public function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validar DNI
     */
    public function validateDNI(string $dni): bool
    {
        // DNI debe tener entre 7 y 8 dígitos
        $dni = preg_replace('/[.\-]/', '', $dni);
        return preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $dni);
    }

    /**
     * Validar email
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false;
    }

    /**
     * Formatear DNI
     */
    public function formatDni(string $dni): string
    {
        $dni = preg_replace('/[.\-]/', '', $dni);
        if (strlen($dni) === 8) {
            return substr($dni, 0, 2) . '.' . substr($dni, 2, 3) . '.' . substr($dni, 5, 3);
        }
        return $dni;
    }

    /**
     * Obtener User Agent del cliente
     */
    public function getClientUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Validar contraseña
     */
    public function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial';
        }
        
        return $errors;
    }

    /**
     * Sanitizar texto normalizando Unicode
     */
    public function sanitizeText(string $text): string
    {
        // Normalizar Unicode para prevenir ataques de homógrafos
        $text = \Normalizer::normalize($text, \Normalizer::FORM_NFC);
        
        // Remover caracteres de control
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
        
        // Limitar longitud
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 1000);
        }
        
        return trim($text);
    }

    /**
     * Generar token CSRF
     */
    public function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Validar token CSRF
     */
    public function validateCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Asegurar que un directorio existe
     */
    public function ensureDirectory(string $directory): bool
    {
        if (!file_exists($directory)) {
            return mkdir($directory, 0755, true);
        }
        return true;
    }

    /**
     * Crear tabla de archivos subidos si no existe
     */
    public function createFileTable(): bool
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS archivos_subidos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre_original VARCHAR(255) NOT NULL,
                nombre_seguro VARCHAR(255) NOT NULL,
                ruta VARCHAR(500) NOT NULL,
                tamaño INT NOT NULL,
                tipo_mime VARCHAR(100) NOT NULL,
                categoria VARCHAR(50) NOT NULL,
                subido_por INT,
                subido_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                eliminado TINYINT(1) DEFAULT 0,
                hash_archivo VARCHAR(64),
                INDEX idx_subido_por (subido_por),
                INDEX idx_categoria (categoria),
                INDEX idx_eliminado (eliminado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            return $this->database->execute($sql);
        } catch (\Exception $e) {
            error_log("Error creando tabla archivos_subidos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear objeto DateTime desde string con formato
     */
    public function createDateTimeFromString(string $dateString, string $format = 'Y-m-d'): ?\DateTime
    {
        if (empty($dateString)) {
            return null;
        }
        
        $dateTime = \DateTime::createFromFormat($format, $dateString);
        return $dateTime ?: null;
    }

    /**
     * Validar fecha con formato específico
     */
    public function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateTime = \DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    /**
     * Sanitizar nombre de tabla
     */
    public function sanitizeTableName(string $table): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    }

    /**
     * Sanitizar nombre de campo
     */
    public function sanitizeFieldName(string $field): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $field);
    }

    /**
     * Sanitizar valor de header HTTP
     */
    public function sanitizeHeaderValue(string $value): string
    {
        return preg_replace('/[\r\n\t\x00-\x1F\x7F]/', '', $value);
    }

    /**
     * Sanitizar mensaje de log
     */
    public function sanitizeLogMessage(string $message): string
    {
        return preg_replace('/[\r\n\t\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $message);
    }

    /**
     * Sanitizar contexto de log
     */
    public function sanitizeLogContext(array $context): array
    {
        $sanitized = [];
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeLogMessage($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeLogContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
