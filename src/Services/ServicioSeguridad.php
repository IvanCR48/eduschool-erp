<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Middleware\SecurityHeadersMiddleware;

/**
 * Servicio de Seguridad
 * 
 * Maneja tokens CSRF, rate limiting y otras medidas de seguridad
 */
class ServicioSeguridad extends BaseService
{
    private string $sessionKey = 'csrf_token';
    private string $rateLimitKey = 'rate_limit_';
    private int $maxAttempts = 5;
    private int $timeWindow = 300; // 5 minutos
    private int $maxRequestSize = 10485760; // 10MB
    private int $maxHeaderSize = 8192; // 8KB
    private array $suspiciousPatterns = [
        '/\.\.\//',           // Path traversal
        '/<script/i',         // XSS attempts
        '/union\s+select/i',  // SQL injection
        '/eval\s*\(/i',       // Code injection
        '/system\s*\(/i',     // Command injection
        '/javascript:/i',     // JavaScript protocol
        '/vbscript:/i',       // VBScript protocol
        '/data:text\/html/i'  // Data URI HTML
    ];

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
    }

    /**
     * Generar token CSRF
     */
    public function generarTokenCSRF(): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->generateCSRFToken();
    }

    /**
     * Verificar token CSRF
     */
    public function verificarTokenCSRF(string $token): bool
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->validateCSRFToken($token);
    }

    /**
     * Obtener token CSRF actual
     */
    public function obtenerTokenCSRF(): ?string
    {
        // Asumir que la sesión ya está iniciada
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }

        return $_SESSION[$this->sessionKey] ?? null;
    }

    /**
     * Verificar rate limiting
     */
    public function verificarRateLimit(string $identifier, string $action = 'default'): array
    {
        $key = $this->rateLimitKey . $action . '_' . $identifier;
        $now = time();

        // Obtener intentos actuales
        $attempts = $this->obtenerIntentos($key);

        // Limpiar intentos antiguos
        $attempts = array_filter($attempts, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });

        // Verificar si excede el límite
        if (count($attempts) >= $this->maxAttempts) {
            $tiempoRestante = $this->timeWindow - ($now - min($attempts));
            
            return [
                'allowed' => false,
                'attempts' => count($attempts),
                'max_attempts' => $this->maxAttempts,
                'time_remaining' => $tiempoRestante,
                'message' => "Demasiados intentos. Intente nuevamente en {$tiempoRestante} segundos."
            ];
        }

        // Registrar intento actual
        $attempts[] = $now;
        $this->guardarIntentos($key, $attempts);

        return [
            'allowed' => true,
            'attempts' => count($attempts),
            'max_attempts' => $this->maxAttempts,
            'time_remaining' => 0
        ];
    }

    /**
     * Limpiar rate limiting para un identificador
     */
    public function limpiarRateLimit(string $identifier, string $action = 'default'): void
    {
        $key = $this->rateLimitKey . $action . '_' . $identifier;
        $this->guardarIntentos($key, []);
    }

    /**
     * Obtener intentos de rate limiting
     */
    private function obtenerIntentos(string $key): array
    {
        // Asumir que la sesión ya está iniciada
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }

        return $_SESSION[$key] ?? [];
    }

    /**
     * Guardar intentos de rate limiting
     */
    private function guardarIntentos(string $key, array $attempts): void
    {
        // Asumir que la sesión ya está iniciada
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }

        $_SESSION[$key] = $attempts;
    }

    /**
     * Sanitizar entrada de usuario
     */
    public function sanitizarEntrada(string $input): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->sanitizeText($input);
    }

    /**
     * Validar entrada de usuario
     */
    public function validarEntrada(string $input, array $reglas = []): array
    {
        $errores = [];

        // Validación básica
        if (empty(trim($input))) {
            $errores[] = 'El campo no puede estar vacío';
        }

        // Validaciones específicas
        foreach ($reglas as $regla => $valor) {
            switch ($regla) {
                case 'min_length':
                    if (strlen($input) < $valor) {
                        $errores[] = "El campo debe tener al menos {$valor} caracteres";
                    }
                    break;
                case 'max_length':
                    if (strlen($input) > $valor) {
                        $errores[] = "El campo no puede exceder {$valor} caracteres";
                    }
                    break;
                case 'email':
                    if (!filter_var($input, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
                        $errores[] = 'El formato del email no es válido';
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($input)) {
                        $errores[] = 'El campo debe ser numérico';
                    }
                    break;
                case 'regex':
                    if (!preg_match($valor, $input)) {
                        $errores[] = 'El formato del campo no es válido';
                    }
                    break;
            }
        }

        return $errores;
    }

    /**
     * Generar hash seguro para contraseñas
     */
    public function hashPassword(string $password): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->hashPassword($password);
    }

    /**
     * Verificar contraseña
     */
    public function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generar token de recuperación de contraseña
     */
    public function generarTokenRecuperacion(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validar token de recuperación
     */
    public function validarTokenRecuperacion(string $token): bool
    {
        // Verificar formato
        if (strlen($token) !== 64) {
            return false;
        }

        // Verificar que solo contenga caracteres hexadecimales
        return ctype_xdigit($token);
    }

    /**
     * Obtener IP del cliente
     */
    public function obtenerIPCliente(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Verificar si la IP está en lista negra
     */
    public function verificarIPListaNegra(string $ip): bool
    {
        // Lista de IPs bloqueadas (en producción esto vendría de la base de datos)
        $ipsBloqueadas = [
            '127.0.0.1', // Solo para testing
        ];

        return in_array($ip, $ipsBloqueadas);
    }

    /**
     * Configurar headers de seguridad (CSP unificado vía SecurityHeadersMiddleware + CORS/cache propios).
     */
    public function configurarHeadersSeguridad(): void
    {
        if (headers_sent()) {
            return;
        }
        SecurityHeadersMiddleware::applyHeaders();
        $this->configurarCORS();
        $this->configurarHeadersCache();
    }

    /**
     * Configurar CORS de forma segura
     */
    private function configurarCORS(): void
    {
        // Solo permitir orígenes específicos (no usar '*')
        $allowedOrigins = [
            'https://tu-dominio.com',
            'https://www.tu-dominio.com',
            'https://admin.tu-dominio.com'
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Sanitizar origen para prevenir header injection
        $origin = $this->sanitizeHeaderValue($origin);
        
        // Validar formato de URL del origen
        if (!empty($origin) && filter_var($origin, FILTER_VALIDATE_URL)) {
            $parsedOrigin = parse_url($origin);
            
            // Verificar que sea HTTPS y tenga un dominio válido
            if (isset($parsedOrigin['scheme']) && 
                $parsedOrigin['scheme'] === 'https' && 
                isset($parsedOrigin['host']) &&
                $this->isValidDomain($parsedOrigin['host'])) {
                
                if (in_array($origin, $allowedOrigins)) {
                    header("Access-Control-Allow-Origin: {$origin}");
                }
            }
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 horas
    }

    /**
     * Sanitizar valor de header para prevenir injection
     */
    private function sanitizeHeaderValue(string $value): string
    {
        $utilityService = new UtilityService($this->database);
        $value = $utilityService->sanitizeHeaderValue($value);
        
        // Limitar longitud
        $value = substr($value, 0, 2000);
        
        return trim($value);
    }

    /**
     * Validar que el dominio sea válido
     */
    private function isValidDomain(string $domain): bool
    {
        // Verificar formato básico del dominio
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $domain)) {
            return false;
        }
        
        // Verificar que no sea una IP privada
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        return true;
    }

    /**
     * Configurar headers de cache para datos sensibles
     */
    private function configurarHeadersCache(): void
    {
        // Para páginas con datos sensibles
        $sensitivePages = ['/login.php', '/dashboard.php', '/perfil.php', '/admin/'];
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($sensitivePages as $page) {
            if (strpos($currentPath, $page) !== false) {
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
                return;
            }
        }
        
        // Para recursos estáticos
        $staticExtensions = ['.css', '.js', '.png', '.jpg', '.gif', '.ico', '.svg'];
        foreach ($staticExtensions as $ext) {
            if (strpos($currentPath, $ext) !== false) {
                header('Cache-Control: public, max-age=31536000'); // 1 año
                return;
            }
        }
        
        // Por defecto, cache moderado
        header('Cache-Control: private, max-age=3600'); // 1 hora
    }

    /**
     * Verificar si la conexión es HTTPS
     */
    private function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ||
               isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ||
               isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ||
               (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }

    /**
     * Verificar límites de tamaño de request
     */
    public function verificarLimitesRequest(): array
    {
        $errors = [];
        
        // Verificar tamaño del request
        $requestSize = strlen(file_get_contents('php://input'));
        if ($requestSize > $this->maxRequestSize) {
            $errors[] = 'Request demasiado grande';
        }
        
        // Verificar tamaño de headers
        $headerSize = 0;
        $headers = $this->getAllHeaders();
        foreach ($headers as $name => $value) {
            $headerSize += strlen($name . ': ' . $value);
        }
        if ($headerSize > $this->maxHeaderSize) {
            $errors[] = 'Headers demasiado grandes';
        }
        
        // Verificar tamaño de archivos subidos
        if (isset($_FILES)) {
            foreach ($_FILES as $file) {
                if (isset($file['size']) && $file['size'] > $this->maxRequestSize) {
                    $errors[] = 'Archivo demasiado grande';
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->logEvent('WARNING', 'Request excedió límites de tamaño', [
                'request_size' => $requestSize,
                'header_size' => $headerSize,
                'max_request_size' => $this->maxRequestSize,
                'max_header_size' => $this->maxHeaderSize
            ]);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'request_size' => $requestSize,
            'header_size' => $headerSize
        ];
    }

    /**
     * Detectar patrones sospechosos en el request
     */
    public function detectarPatronesSospechosos(): array
    {
        $suspicious = [];
        $allInput = '';
        
        try {
            // Recopilar todo el input del request de forma segura
            $allInput .= $_SERVER['REQUEST_URI'] ?? '';
            $allInput .= serialize($_GET ?? []);
            $allInput .= serialize($_POST ?? []);
            
            // Leer php://input de forma segura con límite
            $inputStream = fopen('php://input', 'rb');
            if ($inputStream) {
                $inputData = stream_get_contents($inputStream, 8192); // Máximo 8KB
                fclose($inputStream);
                $allInput .= $inputData;
            }
            
            // Verificar patrones sospechosos de forma segura
            foreach ($this->suspiciousPatterns as $pattern => $description) {
                try {
                    if (preg_match($pattern, $allInput)) {
                        $suspicious[] = [
                            'pattern' => $pattern,
                            'description' => $description,
                            'severity' => $this->getPatternSeverity($pattern)
                        ];
                    }
                } catch (\Exception $e) {
                    // Si hay error en regex, continuar con el siguiente patrón
                    $this->logEvent('WARNING', 'Error en patrón regex', [
                        'pattern' => $pattern,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if (!empty($suspicious)) {
                $this->logEvent('WARNING', 'Patrones sospechosos detectados', [
                    'patterns' => $suspicious,
                    'ip' => $this->obtenerIPCliente(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error detectando patrones sospechosos', [
                'error' => $e->getMessage()
            ]);
        }
        
        return [
            'suspicious' => !empty($suspicious),
            'patterns' => $suspicious,
            'count' => count($suspicious)
        ];
    }

    /**
     * Obtener severidad de un patrón
     */
    private function getPatternSeverity(string $pattern): string
    {
        $highSeverity = ['/union\s+select/i', '/eval\s*\(/i', '/system\s*\(/i'];
        $mediumSeverity = ['/<script/i', '/javascript:/i', '/vbscript:/i'];
        
        if (in_array($pattern, $highSeverity)) {
            return 'HIGH';
        } elseif (in_array($pattern, $mediumSeverity)) {
            return 'MEDIUM';
        }
        
        return 'LOW';
    }

    /**
     * Verificar rate limiting avanzado por IP y acción
     */
    public function verificarRateLimitAvanzado(string $action = 'default'): array
    {
        $ip = $this->obtenerIPCliente();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Rate limiting por IP
        $ipLimit = $this->verificarRateLimit($ip, $action);
        
        // Rate limiting por User-Agent (detectar bots)
        $botSignature = $this->detectarBotSignature($userAgent);
        if ($botSignature) {
            $botLimit = $this->verificarRateLimit($botSignature, 'bot_' . $action);
            if (!$botLimit['allowed']) {
                return [
                    'allowed' => false,
                    'reason' => 'Bot rate limit exceeded',
                    'attempts' => $botLimit['attempts'],
                    'time_remaining' => $botLimit['time_remaining']
                ];
            }
        }
        
        // Rate limiting por combinación IP + User-Agent (usar hash rápido)
        $combinedId = hash('xxh3', $ip . $userAgent) ?: hash('crc32b', $ip . $userAgent);
        $combinedLimit = $this->verificarRateLimit($combinedId, 'combined_' . $action);
        
        return [
            'allowed' => $ipLimit['allowed'] && $combinedLimit['allowed'],
            'ip_limit' => $ipLimit,
            'combined_limit' => $combinedLimit,
            'bot_detected' => $botSignature !== null
        ];
    }

    /**
     * Detectar si el User-Agent es de un bot
     */
    private function detectarBotSignature(string $userAgent): ?string
    {
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/java/i',
            '/go-http/i',
            '/php/i'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return hash('sha256', $userAgent);
            }
        }
        
        return null;
    }

    /**
     * Obtener todos los headers HTTP de forma compatible
     */
    private function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }
        
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headerName = ucwords(strtolower($headerName), '-');
                $headers[$headerName] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * Limpiar tokens CSRF expirados
     */
    public function limpiarTokensExpirados(): void
    {
        // Asumir que la sesión ya está iniciada
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }

        // Limpiar tokens CSRF si la sesión es muy antigua (más de 1 hora)
        if (isset($_SESSION['csrf_token_time'])) {
            if (time() - $_SESSION['csrf_token_time'] > 3600) {
                unset($_SESSION[$this->sessionKey]);
                unset($_SESSION['csrf_token_time']);
            }
        } else {
            $_SESSION['csrf_token_time'] = time();
        }
    }
}
