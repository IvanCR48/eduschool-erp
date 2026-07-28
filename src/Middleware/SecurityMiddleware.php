<?php

namespace SistemaAdmin\Middleware;

use SistemaAdmin\Services\ServicioSeguridad;
use SistemaAdmin\Services\ServicioLogging;
use SistemaAdmin\Services\FileUploadService;
use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Middleware de Seguridad Avanzado
 * 
 * Implementa todas las verificaciones de seguridad
 * según las reglas 11-20 de OWASP
 */
class SecurityMiddleware
{
    private ServicioSeguridad $servicioSeguridad;
    private ServicioLogging $servicioLogging;
    private FileUploadService $fileUploadService;
    private array $securityConfig;

    public function __construct(DatabaseInterface $database, ServicioSeguridad $servicioSeguridad, ServicioLogging $servicioLogging)
    {
        $this->servicioSeguridad = $servicioSeguridad;
        $this->servicioLogging = $servicioLogging;
        $this->fileUploadService = new FileUploadService($database);
        $this->loadSecurityConfig();
    }

    /**
     * Ejecutar todas las verificaciones de seguridad
     */
    public function handle(): array
    {
        $results = [
            'secure' => false,
            'errors' => [],
            'timestamp' => time()
        ];

        try {
            // 1. Configurar headers de seguridad (Regla 16)
            $this->servicioSeguridad->configurarHeadersSeguridad();

            // 2. Verificar límites de request (Regla 20)
            try {
                $results['request_limits'] = $this->servicioSeguridad->verificarLimitesRequest();
            } catch (\Exception $e) {
                $results['errors'][] = 'Error verificando límites de request: ' . $e->getMessage();
                $results['request_limits'] = ['valid' => false, 'error' => true];
            }

            // 3. Detectar patrones sospechosos (Regla 20)
            try {
                $results['suspicious_patterns'] = $this->servicioSeguridad->detectarPatronesSospechosos();
            } catch (\Exception $e) {
                $results['errors'][] = 'Error detectando patrones sospechosos: ' . $e->getMessage();
                $results['suspicious_patterns'] = ['suspicious' => false, 'error' => true];
            }

            // 4. Verificar rate limiting avanzado (Regla 20)
            try {
                $results['rate_limiting'] = $this->servicioSeguridad->verificarRateLimitAvanzado();
            } catch (\Exception $e) {
                $results['errors'][] = 'Error verificando rate limiting: ' . $e->getMessage();
                $results['rate_limiting'] = ['allowed' => false, 'error' => true];
            }

            // 5. Verificar IP en lista negra
            try {
                $ip = $this->servicioSeguridad->obtenerIPCliente();
                $results['ip_blocked'] = $this->servicioSeguridad->verificarIPListaNegra($ip);
            } catch (\Exception $e) {
                $results['errors'][] = 'Error verificando IP: ' . $e->getMessage();
                $results['ip_blocked'] = true; // Fail-safe: bloquear si hay error
            }

            // 6. Validar token CSRF si es necesario
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $results['csrf_valid'] = $this->validateCSRFToken();
                } catch (\Exception $e) {
                    $results['errors'][] = 'Error validando CSRF: ' . $e->getMessage();
                    $results['csrf_valid'] = false; // Fail-safe
                }
            }

            // 7. Verificar User-Agent sospechoso
            try {
                $results['user_agent_valid'] = $this->validateUserAgent();
            } catch (\Exception $e) {
                $results['errors'][] = 'Error validando User-Agent: ' . $e->getMessage();
                $results['user_agent_valid'] = false; // Fail-safe
            }

            // 8. Verificar tamaño de archivos subidos
            if (isset($_FILES) && !empty($_FILES)) {
                try {
                    $results['file_upload_valid'] = $this->validateFileUploads();
                } catch (\Exception $e) {
                    $results['errors'][] = 'Error validando archivos: ' . $e->getMessage();
                    $results['file_upload_valid'] = ['valid' => false, 'error' => true];
                }
            }

            // Determinar si el request es seguro
            $results['secure'] = $this->isRequestSecure($results);

            // Log de seguridad si hay problemas
            if (!$results['secure'] || !empty($results['errors'])) {
                $this->logSecurityViolation($results);
            }

        } catch (\Exception $e) {
            // Error crítico en el middleware
            $results['errors'][] = 'Error crítico en middleware de seguridad: ' . $e->getMessage();
            $results['secure'] = false;
            
            $this->servicioLogging->registrarEventoSeguridad(
                'CRITICAL',
                'Error crítico en middleware de seguridad',
                [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ip' => $this->servicioSeguridad->obtenerIPCliente()
                ]
            );
        }

        return $results;
    }

    /**
     * Validar token CSRF
     */
    private function validateCSRFToken(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token) {
            return false;
        }

        return $this->servicioSeguridad->verificarTokenCSRF($token);
    }

    /**
     * Validar User-Agent
     */
    private function validateUserAgent(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // User-Agent no puede estar vacío
        if (empty($userAgent)) {
            return false;
        }

        // User-Agent no puede ser demasiado largo
        if (strlen($userAgent) > 500) {
            return false;
        }

        // Sanitizar User-Agent para evitar inyección de regex (método más seguro)
        $userAgent = preg_replace('/[^\p{L}\p{N}\s\-\.\/\(\)\[\]]/u', '', $userAgent);

        // Detectar patrones sospechosos en User-Agent
        $suspiciousPatterns = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'havij',
            'wget',
            'curl',
            'python',
            'java',
            'perl',
            'ruby'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validar archivos subidos
     */
    private function validateFileUploads(): array
    {
        $results = ['valid' => true, 'errors' => []];

        foreach ($_FILES as $fieldName => $file) {
            // Verificar que el archivo se subió correctamente
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $results['errors'][] = "Error en archivo {$fieldName}: " . $this->getUploadErrorMessage($file['error']);
                $results['valid'] = false;
                continue;
            }

            // Verificar tamaño
            if ($file['size'] > $this->securityConfig['max_file_size']) {
                $results['errors'][] = "Archivo {$fieldName} demasiado grande";
                $results['valid'] = false;
            }

            // Verificar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->securityConfig['allowed_mime_types'])) {
                $results['errors'][] = "Tipo de archivo {$fieldName} no permitido: {$mimeType}";
                $results['valid'] = false;
            }

            // Verificar extensión
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $this->securityConfig['allowed_extensions'])) {
                $results['errors'][] = "Extensión de archivo {$fieldName} no permitida: {$extension}";
                $results['valid'] = false;
            }
        }

        return $results;
    }

    /**
     * Determinar si el request es seguro
     */
    private function isRequestSecure(array $results): bool
    {
        // Verificar límites de request
        if (!$results['request_limits']['valid']) {
            return false;
        }

        // Verificar patrones sospechosos
        if ($results['suspicious_patterns']['suspicious']) {
            return false;
        }

        // Verificar rate limiting
        if (!$results['rate_limiting']['allowed']) {
            return false;
        }

        // Verificar IP bloqueada
        if ($results['ip_blocked']) {
            return false;
        }

        // Verificar CSRF para POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$results['csrf_valid']) {
            return false;
        }

        // Verificar User-Agent
        if (!$results['user_agent_valid']) {
            return false;
        }

        // Verificar archivos subidos
        if (isset($results['file_upload_valid']) && !$results['file_upload_valid']['valid']) {
            return false;
        }

        return true;
    }

    /**
     * Log de violación de seguridad
     */
    private function logSecurityViolation(array $results): void
    {
        $violations = [];

        if (!$results['request_limits']['valid']) {
            $violations[] = 'Request limits exceeded';
        }

        if ($results['suspicious_patterns']['suspicious']) {
            $violations[] = 'Suspicious patterns detected';
        }

        if (!$results['rate_limiting']['allowed']) {
            $violations[] = 'Rate limiting exceeded';
        }

        if ($results['ip_blocked']) {
            $violations[] = 'IP blocked';
        }

        if (isset($results['csrf_valid']) && !$results['csrf_valid']) {
            $violations[] = 'CSRF token invalid';
        }

        if (!$results['user_agent_valid']) {
            $violations[] = 'Invalid User-Agent';
        }

        if (isset($results['file_upload_valid']) && !$results['file_upload_valid']['valid']) {
            $violations[] = 'Invalid file upload';
        }

        $this->servicioLogging->registrarEventoSeguridad(
            'SECURITY_VIOLATION',
            'Multiple security violations detected: ' . implode(', ', $violations),
            [
                'violations' => $violations,
                'results' => $results,
                'ip' => $this->servicioSeguridad->obtenerIPCliente(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
            ]
        );
    }

    /**
     * Cargar configuración de seguridad
     */
    private function loadSecurityConfig(): void
    {
        $this->securityConfig = [
            'max_file_size' => 5242880, // 5MB
            'allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/pdf',
                'text/plain'
            ],
            'allowed_extensions' => [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'pdf',
                'txt'
            ]
        ];
    }

    /**
     * Obtener mensaje de error de upload
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Archivo excede upload_max_filesize';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Archivo excede MAX_FILE_SIZE';
            case UPLOAD_ERR_PARTIAL:
                return 'Archivo subido parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se subió ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Directorio temporal faltante';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error escribiendo archivo';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload detenido por extensión';
            default:
                return 'Error desconocido';
        }
    }

    /**
     * Bloquear request inseguro
     */
    public function blockInsecureRequest(array $results): void
    {
        http_response_code(403);
        
        $response = [
            'success' => false,
            'error' => 'Request bloqueado por medidas de seguridad',
            'code' => 'SECURITY_VIOLATION'
        ];

        // En modo debug, incluir más detalles
        if (!$this->isProduction()) {
            $response['details'] = $results;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Verificar si está en producción
     */
    private function isProduction(): bool
    {
        return $_SERVER['HTTP_HOST'] !== 'localhost' && 
               strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false;
    }
}
