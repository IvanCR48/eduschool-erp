<?php

namespace SistemaAdmin\Middleware;

class OpenRedirectProtectionMiddleware
{
    private static $allowedDomains = [
        'localhost',
        '127.0.0.1',
    ];

    /**
     * Retorna la lista de dominios permitidos, añadiendo dinámicamente
     * el host actual y el configurado en APP_URL para que funcione
     * en cualquier instalación sin hardcodear el dominio del cliente.
     */
    private static function getAllowedDomainsResolved(): array
    {
        $domains = self::$allowedDomains;

        // Añadir el host actual del servidor
        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($currentHost !== '' && !in_array($currentHost, $domains, true)) {
            // Strip port if present
            $hostWithoutPort = strtok($currentHost, ':');
            if ($hostWithoutPort !== false && !in_array($hostWithoutPort, $domains, true)) {
                $domains[] = $hostWithoutPort;
            }
        }

        // Añadir el host configurado en APP_URL (env)
        $appUrl = (string) ($_ENV['APP_URL'] ?? '');
        if ($appUrl !== '') {
            $parsed = parse_url($appUrl);
            if (isset($parsed['host'])) {
                $appHost = strtolower($parsed['host']);
                if (!in_array($appHost, $domains, true)) {
                    $domains[] = $appHost;
                }
            }
        }

        return $domains;
    }
    
    private static $allowedPaths = [
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
        '/force_password_change.php'
    ];
    
    /**
     * Validar URL de redirección para prevenir Open Redirects
     */
    public static function validateRedirectURL(string $url): array
    {
        // PROTECCIÓN CONTRA OPEN REDIRECTS
        if (empty($url)) {
            return ['valid' => false, 'error' => 'URL de redirección vacía'];
        }
        
        // Si es una URL relativa, validar que esté en la lista permitida
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return self::validateRelativeURL($url);
        }
        
        // Si es una URL absoluta, validar dominio
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return self::validateAbsoluteURL($url);
        }
        
        // Si no es ni relativa ni absoluta, considerar inválida
        return ['valid' => false, 'error' => 'Formato de URL no válido'];
    }
    
    /**
     * Validar URL relativa
     */
    private static function validateRelativeURL(string $url): array
    {
        // Remover parámetros de consulta para validación
        $path = strtok($url, '?');
        
        // Verificar si el path está en la lista permitida
        foreach (self::$allowedPaths as $allowedPath) {
            if (strpos($path, $allowedPath) === 0) {
                return ['valid' => true, 'url' => $url];
            }
        }
        
        // Verificar si es un path válido (no contiene ..)
        if (strpos($path, '..') !== false) {
            error_log("Open Redirect Protection: Intento de directory traversal en redirección: {$url}");
            return ['valid' => false, 'error' => 'Path no válido'];
        }
        
        // Verificar si es un path de error válido
        if (strpos($path, '/public/errors/') === 0) {
            return ['valid' => true, 'url' => $url];
        }
        
        error_log("Open Redirect Protection: Path no permitido en redirección: {$url}");
        return ['valid' => false, 'error' => 'Path no permitido'];
    }
    
    /**
     * Validar URL absoluta
     */
    private static function validateAbsoluteURL(string $url): array
    {
        $parsedUrl = parse_url($url);
        
        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            return ['valid' => false, 'error' => 'URL malformada'];
        }
        
        $host = strtolower($parsedUrl['host']);

        // Verificar si el dominio está en la lista permitida (dinámica)
        foreach (self::getAllowedDomainsResolved() as $allowedDomain) {
            if ($host === $allowedDomain || strpos($host, '.' . $allowedDomain) !== false) {
                return ['valid' => true, 'url' => $url];
            }
        }
        
        error_log("Open Redirect Protection: Dominio no permitido en redirección: {$host}");
        return ['valid' => false, 'error' => 'Dominio no permitido'];
    }
    
    /**
     * Sanitizar URL de redirección
     */
    public static function sanitizeRedirectURL(string $url): string
    {
        $validation = self::validateRedirectURL($url);
        
        if (!$validation['valid']) {
            // Redirigir a página de error o página principal
            return '/public/errors/error403.php';
        }
        
        return $validation['url'];
    }
    
    /**
     * Obtener URL de redirección segura desde parámetros
     */
    public static function getSafeRedirectURL(string $defaultURL = '/'): string
    {
        $redirectURL = $_GET['redirect'] ?? $_POST['redirect'] ?? $defaultURL;
        
        return self::sanitizeRedirectURL($redirectURL);
    }
    
    /**
     * Validar redirección en formularios
     */
    public static function validateFormRedirect(array $formData): string
    {
        $redirectURL = $formData['redirect'] ?? '/';
        
        return self::sanitizeRedirectURL($redirectURL);
    }
    
    /**
     * Generar URL de redirección segura
     */
    public static function generateSafeRedirectURL(string $targetPage): string
    {
        // Si es una página válida, generar URL segura
        if (in_array($targetPage, self::$allowedPaths)) {
            return $targetPage;
        }
        
        // Si no es válida, redirigir a página principal
        return '/';
    }
    
    /**
     * Validar redirección en headers
     */
    public static function validateHeaderRedirect(string $url): bool
    {
        $validation = self::validateRedirectURL($url);
        
        if (!$validation['valid']) {
            error_log("Open Redirect Protection: Intento de redirección no válida en header: {$url}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Limpiar parámetros de redirección en URL
     */
    public static function cleanRedirectParameters(string $url): string
    {
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['query'])) {
            return $url;
        }
        
        parse_str($parsedUrl['query'], $params);
        
        // Remover parámetros de redirección peligrosos
        $dangerousParams = ['redirect', 'return', 'next', 'continue', 'goto', 'target'];
        foreach ($dangerousParams as $param) {
            if (isset($params[$param])) {
                unset($params[$param]);
            }
        }
        
        // Reconstruir URL
        $parsedUrl['query'] = http_build_query($params);
        
        return self::buildURL($parsedUrl);
    }
    
    /**
     * Reconstruir URL desde array parseado
     */
    private static function buildURL(array $parsedUrl): string
    {
        $url = '';
        
        if (isset($parsedUrl['scheme'])) {
            $url .= $parsedUrl['scheme'] . '://';
        }
        
        if (isset($parsedUrl['host'])) {
            $url .= $parsedUrl['host'];
        }
        
        if (isset($parsedUrl['port'])) {
            $url .= ':' . $parsedUrl['port'];
        }
        
        if (isset($parsedUrl['path'])) {
            $url .= $parsedUrl['path'];
        }
        
        if (isset($parsedUrl['query'])) {
            $url .= '?' . $parsedUrl['query'];
        }
        
        if (isset($parsedUrl['fragment'])) {
            $url .= '#' . $parsedUrl['fragment'];
        }
        
        return $url;
    }
    
    /**
     * Agregar dominio permitido
     */
    public static function addAllowedDomain(string $domain): void
    {
        if (!in_array($domain, self::$allowedDomains)) {
            self::$allowedDomains[] = $domain;
        }
    }
    
    /**
     * Agregar path permitido
     */
    public static function addAllowedPath(string $path): void
    {
        if (!in_array($path, self::$allowedPaths)) {
            self::$allowedPaths[] = $path;
        }
    }
    
    /**
     * Obtener lista de dominios permitidos (incluye host actual)
     */
    public static function getAllowedDomains(): array
    {
        return self::getAllowedDomainsResolved();
    }
    
    /**
     * Obtener lista de paths permitidos
     */
    public static function getAllowedPaths(): array
    {
        return self::$allowedPaths;
    }
}
