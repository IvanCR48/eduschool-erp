<?php

namespace SistemaAdmin\Middleware;

class SecurityHeadersMiddleware
{
    /**
     * Aplica headers de seguridad HTTP
     */
    public static function applyHeaders(): void
    {
        // Configurar charset UTF-8
        header('Content-Type: text/html; charset=utf-8');
        
        // Generar nonce CSP por request y exponerlo
        if (empty($GLOBALS['csp_nonce'])) {
            $GLOBALS['csp_nonce'] = bin2hex(random_bytes(16));
        }
        $nonce = $GLOBALS['csp_nonce'];
        // PROTECCIÓN CONTRA CLICKJACKING
        header('X-Frame-Options: DENY');
        
        // PROTECCIÓN CONTRA MIME SNIFFING
        header('X-Content-Type-Options: nosniff');
        
        // Activar XSS protection del navegador
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (sin inline/eval; permitir nonce para scripts propios)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'nonce-{$nonce}' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://use.fontawesome.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://use.fontawesome.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://cdn.jsdelivr.net https://www.google.com/recaptcha/; " .
               "frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self';";
        
        header("Content-Security-Policy: $csp");
        
        // Un solo Permissions-Policy: () = denegado; (self) permitido solo al mismo origen.
        // No usar (none): no es sintaxis válida del structured header parser.
        // Omitir directivas retiradas o no reconocidas por Chromium (evita warnings en consola):
        // ambient-light-sensor, battery.
        $permissionsPolicy = [
            'accelerometer' => [],
            'autoplay' => [],
            'camera' => [],
            'display-capture' => [],
            'encrypted-media' => [],
            'fullscreen' => ['self'],
            'geolocation' => [],
            'gyroscope' => [],
            'keyboard-map' => [],
            'magnetometer' => [],
            'microphone' => [],
            'midi' => [],
            'payment' => [],
            'picture-in-picture' => [],
            'publickey-credentials-get' => [],
            'screen-wake-lock' => [],
            'sync-xhr' => [],
            'usb' => [],
            'web-share' => [],
            'xr-spatial-tracking' => [],
        ];
        $ppParts = [];
        foreach ($permissionsPolicy as $name => $allow) {
            $ppParts[] = $name . '=' . (count($allow) === 0 ? '()' : '(' . implode(' ', $allow) . ')');
        }
        header('Permissions-Policy: ' . implode(', ', $ppParts));
        
               // HSTS (solo en HTTPS)
               if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                   header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
               }
               
               // Cross-Origin Embedder Policy (COEP) - unsafe-none para permitir CDNs externos
               header('Cross-Origin-Embedder-Policy: unsafe-none');
               
               // Cross-Origin Opener Policy (COOP)
               header('Cross-Origin-Opener-Policy: same-origin');
               
               // Cross-Origin Resource Policy (CORP)
               header('Cross-Origin-Resource-Policy: same-origin');
               
               // Origin Agent Cluster
               header('Origin-Agent-Cluster: ?1');
               
               // Clear-Site-Data (para logout)
               if (isset($_GET['logout']) || isset($_POST['logout'])) {
                   header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
               }
               
               // Expect-CT (Certificate Transparency)
               header('Expect-CT: max-age=86400, enforce');
        
        // PROTECCIÓN CONTRA CACHE POISONING
        // Cache control para páginas sensibles
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/admin') !== false || 
            strpos($requestUri, 'admin_tools') !== false) {
            header('Cache-Control: no-cache, no-store, must-revalidate, private');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // PROTECCIÓN CONTRA INFORMATION DISCLOSURE
        // Ocultar información del servidor
        header_remove('X-Powered-By');
        header_remove('Server');
        header('X-Powered-By: '); // Vacío para ocultar
        header('Server: '); // Vacío para ocultar
        
        // PROTECCIÓN CONTRA OPEN REDIRECTS
        // Validar redirecciones en headers
        if (isset($_GET['redirect']) || isset($_POST['redirect'])) {
            $redirectURL = $_GET['redirect'] ?? $_POST['redirect'] ?? '/';
            if (!self::isValidRedirect($redirectURL)) {
                error_log("Open Redirect Protection: Intento de redirección no válida: {$redirectURL}");
                // Redirigir a página segura
                header('Location: /public/errors/error403.php');
                exit;
            }
        }
    }
    
    /**
     * Headers específicos para APIs
     */
    public static function applyAPIHeaders(): void
    {
        self::applyHeaders();
        
        // Content type para APIs
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS básico (ajustar según necesidades)
        header('Access-Control-Allow-Origin: ' . self::getAllowedOrigin());
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Preflight OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Obtiene el origen permitido para CORS
     */
    private static function getAllowedOrigin(): string
    {
        $allowedOrigins = [
            'http://localhost',
            'https://localhost',
            'http://127.0.0.1',
            'https://127.0.0.1'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }
        
        return 'null';
    }
    
    /**
     * Validar redirección para prevenir Open Redirects
     */
    private static function isValidRedirect(string $url): bool
    {
        // Si es una URL relativa, validar que esté en paths permitidos
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $allowedPaths = [
                '/',
                '/index.php',
                '/login.php',
                '/logout.php',
                '/admin/',
                '/public/',
                '/students.php',
                '/teachers.php',
                '/courses.php',
                '/subjects.php',
                '/grades.php',
                '/schedules.php',
                '/attendance.php',
                '/attendance_reports.php',
                '/advisors.php',
                '/users.php',
                '/discipline.php',
                '/export_discipline.php',
                '/staff.php',
                '/student_profile.php',
                '/teacher_profile.php',
                '/student_certificate.php',
                '/print_report_card.php',
                '/force_password_change.php',
                '/public/errors/'
            ];
            
            $path = strtok($url, '?');
            foreach ($allowedPaths as $allowedPath) {
                if (strpos($path, $allowedPath) === 0) {
                    return true;
                }
            }
            
            return false;
        }
        
        // Si es una URL absoluta, validar dominio
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            // Construir lista dinámica desde el host actual y APP_URL
            $allowedDomains = ['localhost', '127.0.0.1'];
            $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
            if ($currentHost !== '') {
                $hostOnly = strtok($currentHost, ':');
                if ($hostOnly !== false && !in_array($hostOnly, $allowedDomains, true)) {
                    $allowedDomains[] = $hostOnly;
                }
            }
            $appUrl = (string) ($_ENV['APP_URL'] ?? '');
            if ($appUrl !== '') {
                $parsed = parse_url($appUrl);
                if (isset($parsed['host'])) {
                    $appHost = strtolower($parsed['host']);
                    if (!in_array($appHost, $allowedDomains, true)) {
                        $allowedDomains[] = $appHost;
                    }
                }
            }

            $parsedUrl = parse_url($url);
            if ($parsedUrl === false || !isset($parsedUrl['host'])) {
                return false;
            }

            $host = strtolower($parsedUrl['host']);
            foreach ($allowedDomains as $allowedDomain) {
                if ($host === $allowedDomain || strpos($host, '.' . $allowedDomain) !== false) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
    
    /**
     * Headers para descarga de archivos
     */
    public static function applyDownloadHeaders(string $filename, string $mimeType): void
    {
        self::applyHeaders();
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filename));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
}
