<?php

namespace SistemaAdmin\Services;

// Verificar versión mínima de PHP
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('Este sistema requiere PHP 8.0 o superior. Versión actual: ' . PHP_VERSION);
}

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\ErrorHandlerService;
use SistemaAdmin\Services\ServicioLogging;

/**
 * Clase base para todos los servicios
 * 
 * Proporciona funcionalidad común y tipado fuerte para todos los servicios
 */
abstract class BaseService
{
    protected DatabaseInterface $database;
    protected ?ErrorHandlerService $errorHandler;
    protected ?ServicioLogging $logger;

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        $this->database = $database;
        
        // Evitar recursión infinita - usar lazy loading para servicios
        $this->errorHandler = $errorHandler;
        $this->logger = $logger;
        
        // Solo crear servicios si no se pasan como parámetros y no estamos en una clase específica
        if ($errorHandler === null && !($this instanceof ErrorHandlerService) && !($this instanceof ServicioLogging)) {
            // Lazy loading - crear solo cuando se necesite
            $this->errorHandler = null;
        }
        
        if ($logger === null && !($this instanceof ServicioLogging) && !($this instanceof ErrorHandlerService)) {
            // Lazy loading - crear solo cuando se necesite
            $this->logger = null;
        }
    }

    /**
     * Obtener ErrorHandlerService con lazy loading
     */
    protected function getErrorHandler(): ErrorHandlerService
    {
        if ($this->errorHandler === null) {
            $this->errorHandler = new ErrorHandlerService($this->database);
        }
        return $this->errorHandler;
    }

    /**
     * Obtener ServicioLogging con lazy loading
     */
    protected function getLogger(): ServicioLogging
    {
        if ($this->logger === null) {
            $this->logger = new ServicioLogging($this->database);
        }
        return $this->logger;
    }

    /**
     * Manejar errores de forma consistente
     */
    protected function handleError(\Throwable $error, array $context = []): array
    {
        return $this->getErrorHandler()->manejarError($error, $context);
    }

    /**
     * Log de eventos de forma consistente
     */
    protected function logEvent(string $level, string $message, array $context = []): void
    {
        switch (strtoupper($level)) {
            case 'ERROR':
                $this->getLogger()->registrarError($message, $context['file'] ?? '', $context['line'] ?? 0, $context);
                break;
            case 'WARNING':
                $this->getLogger()->registrarEventoSeguridad($level, $message, $context);
                break;
            case 'INFO':
            case 'DEBUG':
            default:
                $this->getLogger()->registrarEventoSeguridad($level, $message, $context);
                break;
        }
    }

    /**
     * Registrar evento de auditoría (archivo audit.log + tabla logs_auditoria vía ServicioLogging).
     * Incluye contexto HTTP; use claves `before` y `after` en $datos para trazabilidad de cambios.
     *
     * @param string $accion   Acción realizada (CREAR, ACTUALIZAR, ELIMINAR, etc.)
     * @param string $entidad  Nombre de la entidad afectada (ej. nota, estudiante)
     * @param int|null $entidadId  Identificador principal de la entidad (PK cuando aplique)
     * @param array<string, mixed> $datos  Contexto: before, after, user_agent (sobrescrito), etc.
     */
    protected function registrarAuditoria(string $accion, string $entidad, ?int $entidadId = null, array $datos = []): void
    {
        try {
            $ctx = [
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400),
                'request_uri' => mb_substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 1000),
                'request_method' => mb_substr((string) ($_SERVER['REQUEST_METHOD'] ?? ''), 0, 16),
            ];
            $merged = array_merge($ctx, $datos);
            $this->getLogger()->registrarEventoAuditoria(
                strtoupper(trim($accion)),
                strtolower(trim($entidad)),
                $entidadId,
                $merged
            );
        } catch (\Throwable $e) {
            $this->logEvent('WARNING', 'Error registrando auditoría', [
                'accion' => $accion,
                'entidad' => $entidad,
                'entidad_id' => $entidadId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validar parámetros requeridos
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Faltan parámetros requeridos: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Sanitizar entrada de texto
     */
    protected function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validar DNI argentino
     */
    protected function validateDNI(string $dni): bool
    {
        // DNI debe tener entre 7 y 8 dígitos
        if (!preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $dni)) {
            return false;
        }
        
        return true;
    }


    /**
     * Sanitizar texto con normalización Unicode
     */
    protected function sanitizeText(string $text): string
    {
        // Normalizar Unicode para prevenir ataques de homógrafos
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        }
        
        // Remover caracteres de control y caracteres peligrosos
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Remover caracteres Unicode peligrosos (homógrafos comunes)
        $dangerousUnicode = [
            '\u200B', '\u200C', '\u200D', '\uFEFF', // Zero-width characters
            '\u202A', '\u202B', '\u202C', '\u202D', '\u202E', // Directional overrides
            '\u2066', '\u2067', '\u2068', '\u2069' // Isolates
        ];
        
        foreach ($dangerousUnicode as $char) {
            $text = str_replace(json_decode('"' . $char . '"'), '', $text);
        }
        
        // Limitar longitud para prevenir ataques de memoria
        $text = substr($text, 0, 10000);
        
        // Sanitizar HTML básico
        $text = trim(strip_tags($text));
        
        // Remover caracteres de escape peligrosos
        $text = str_replace(['\0', '\x00', '\r'], '', $text);
        
        return $text;
    }

    /**
     * Validar email
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false;
    }


    /**
     * Formatear DNI argentino
     */
    protected function formatDni(string $dni): string
    {
        $dni = preg_replace('/[.\-]/', '', $dni);
        if (strlen($dni) === 7) {
            return substr($dni, 0, 2) . '.' . substr($dni, 2, 3) . '.' . substr($dni, 5, 3);
        } elseif (strlen($dni) === 8) {
            return substr($dni, 0, 2) . '.' . substr($dni, 2, 3) . '.' . substr($dni, 5, 3);
        }
        return $dni;
    }

    /**
     * Validar contraseña
     */
    protected function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula';
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial';
        }
        
        return $errors;
    }

    /**
     * Generar hash seguro de contraseña
     */
    protected function hashPassword(string $password): string
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
    protected function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Obtener timestamp actual en formato MySQL
     */
    protected function getCurrentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Formatear fecha para mostrar
     */
    protected function formatDate(string $date, string $format = 'd/m/Y'): string
    {
        try {
            return date($format, strtotime($date));
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Formatear fecha y hora para mostrar
     */
    protected function formatDateTime(string $dateTime, string $format = 'd/m/Y H:i'): string
    {
        try {
            return date($format, strtotime($dateTime));
        } catch (\Exception $e) {
            return $dateTime;
        }
    }

    /**
     * Obtener configuración de seguridad
     */
    protected function getSecurityConfig(string $key, $default = null)
    {
        try {
            $sql = "SELECT valor FROM configuraciones_sistema WHERE clave = ?";
            $result = $this->database->fetch($sql, [$key]);
            
            if ($result) {
                $value = $result['valor'];
                
                // Intentar decodificar JSON
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                
                // Convertir tipos básicos
                if (is_numeric($value)) {
                    return strpos($value, '.') !== false ? (float)$value : (int)$value;
                }
                
                if (in_array(strtolower($value), ['true', 'false'])) {
                    return strtolower($value) === 'true';
                }
                
                return $value;
            }
            
            return $default;
        } catch (\Exception $e) {
            $this->logEvent('WARNING', "No se pudo obtener configuración: {$key}", [
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Establecer configuración de seguridad
     */
    protected function setSecurityConfig(string $key, $value): bool
    {
        try {
            $jsonValue = is_array($value) ? json_encode($value) : (string)$value;
            
            $sql = "INSERT INTO configuraciones_sistema (clave, valor) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
            
            $this->database->query($sql, [$key, $jsonValue]);
            return true;
        } catch (\Exception $e) {
            $this->logEvent('ERROR', "No se pudo establecer configuración: {$key}", [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
            return false;
        }
    }

    /**
     * Obtener IP del cliente
     */
    protected function obtenerIPCliente(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                   'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                
                // Sanitizar IP
                $ip = trim($ip);
                if (empty($ip)) continue;
                
                // Manejar múltiples IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    foreach ($ips as $singleIp) {
                        $singleIp = trim($singleIp);
                        if ($this->isValidPublicIp($singleIp)) {
                            return $singleIp;
                        }
                    }
                } else {
                    if ($this->isValidPublicIp($ip)) {
                        return $ip;
                    }
                }
            }
        }
        
        // Fallback a REMOTE_ADDR pero validar
        $fallbackIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return $this->isValidPublicIp($fallbackIp) ? $fallbackIp : '0.0.0.0';
    }

    /**
     * Validar si una IP es pública y válida
     */
    private function isValidPublicIp(string $ip): bool
    {
        // Verificar formato básico
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Rechazar IPs privadas y reservadas
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        
        return false;
    }

    /**
     * Obtener User Agent del cliente
     */
    protected function getClientUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
