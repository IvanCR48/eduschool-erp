<?php

namespace SistemaAdmin\Middleware;

class SSRFProtectionMiddleware
{
    // PROTECCIÓN CONTRA SSRF (Server-Side Request Forgery)
    private static $blockedIPRanges = [
        // Localhost
        ['127.0.0.0', '127.255.255.255'],
        ['::1', '::1'],
        
        // Private networks
        ['10.0.0.0', '10.255.255.255'],
        ['172.16.0.0', '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
        
        // Link-local
        ['169.254.0.0', '169.254.255.255'],
        ['fe80::', 'fe80::ffff:ffff:ffff:ffff'],
        
        // Multicast
        ['224.0.0.0', '239.255.255.255'],
        ['ff00::', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'],
        
        // Loopback adicional
        ['0.0.0.0', '0.255.255.255'],
        
        // Documentación RFC
        ['198.51.100.0', '198.51.100.255'],
        ['203.0.113.0', '203.0.113.255'],
    ];
    
    private static $allowedProtocols = ['http', 'https'];
    
    private static $blockedDomains = [
        'localhost',
        '0.0.0.0',
        'metadata.google.internal',  // GCP metadata
        '169.254.169.254',            // AWS/Azure metadata
    ];
    
    /**
     * Validar URL contra SSRF
     */
    public static function validateURL(string $url): array
    {
        $errors = [];
        
        // PROTECCIÓN CONTRA SSRF
        // Verificar que la URL no esté vacía
        if (empty($url)) {
            $errors[] = 'URL vacía';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Parsear URL
        $parsedURL = parse_url($url);
        
        if ($parsedURL === false) {
            $errors[] = 'URL inválida';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Verificar protocolo
        if (!isset($parsedURL['scheme']) || !in_array(strtolower($parsedURL['scheme']), self::$allowedProtocols)) {
            $errors[] = 'Protocolo no permitido';
        }
        
        // Verificar host
        if (!isset($parsedURL['host'])) {
            $errors[] = 'Host no especificado';
            return ['valid' => false, 'errors' => $errors];
        }
        
        $host = strtolower($parsedURL['host']);
        
        // Verificar dominios bloqueados
        if (self::isBlockedDomain($host)) {
            $errors[] = 'Dominio bloqueado';
        }
        
        // Resolver IP y verificar rangos bloqueados
        $ips = self::resolveHost($host);
        
        if (empty($ips)) {
            $errors[] = 'No se pudo resolver el host';
        } else {
            foreach ($ips as $ip) {
                if (self::isBlockedIP($ip)) {
                    error_log("SSRF Protection: Intento de acceso a IP bloqueada {$ip} desde host {$host}");
                    $errors[] = "IP bloqueada: {$ip}";
                }
            }
        }
        
        // Verificar puerto (si se especifica)
        if (isset($parsedURL['port'])) {
            if (!self::isAllowedPort($parsedURL['port'])) {
                $errors[] = 'Puerto no permitido';
            }
        }
        
        // Verificar caracteres peligrosos
        if (self::containsDangerousCharacters($url)) {
            $errors[] = 'URL contiene caracteres peligrosos';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'parsed' => $parsedURL,
            'resolved_ips' => $ips ?? []
        ];
    }
    
    /**
     * Verificar si un dominio está bloqueado
     */
    private static function isBlockedDomain(string $domain): bool
    {
        foreach (self::$blockedDomains as $blocked) {
            if ($domain === $blocked || str_ends_with($domain, '.' . $blocked)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Resolver host a IPs
     */
    private static function resolveHost(string $host): array
    {
        // Intentar resolver como IPv4
        $records = @dns_get_record($host, DNS_A);
        $ips = [];
        
        if ($records !== false) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }
        
        // Intentar resolver como IPv6
        $records = @dns_get_record($host, DNS_AAAA);
        
        if ($records !== false) {
            foreach ($records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }
        
        // Si no se pudo resolver via DNS, intentar gethostbyname
        if (empty($ips)) {
            $ip = @gethostbyname($host);
            if ($ip !== $host) {
                $ips[] = $ip;
            }
        }
        
        return $ips;
    }
    
    /**
     * Verificar si una IP está bloqueada
     */
    private static function isBlockedIP(string $ip): bool
    {
        // Verificar si es una IP privada o reservada
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        
        // Verificar rangos bloqueados manualmente
        foreach (self::$blockedIPRanges as $range) {
            if (self::ipInRange($ip, $range[0], $range[1])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si una IP está en un rango
     */
    private static function ipInRange(string $ip, string $rangeStart, string $rangeEnd): bool
    {
        // Convertir IPs a enteros para comparación
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $startLong = ip2long($rangeStart);
            $endLong = ip2long($rangeEnd);
            
            return ($ipLong >= $startLong && $ipLong<= $endLong);
        }
        
        // Para IPv6, usar comparación de strings (simplificado)
        return false;
    }
    
    /**
     * Verificar si un puerto está permitido
     */
    private static function isAllowedPort(int $port): bool
    {
        $allowedPorts = [80, 443, 8080, 8443];
        return in_array($port, $allowedPorts);
    }
    
    /**
     * Detectar caracteres peligrosos en URL
     */
    private static function containsDangerousCharacters(string $url): bool
    {
        $dangerousPatterns = [
            '/[\x00-\x1F\x7F]/',        // Caracteres de control
            '/@.*@/',                   // Múltiples @ (bypass de autenticación)
            '/\s/',                     // Espacios en blanco
            '/[<>]/',                   // Brackets (XSS)
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Realizar petición HTTP segura
     */
    public static function makeSecureRequest(string $url, array $options = []): array
    {
        // Validar URL
        $validation = self::validateURL($url);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => 'URL no válida: ' . implode(', ', $validation['errors'])
            ];
        }
        
        try {
            // Configurar opciones de cURL
            $ch = curl_init($url);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);  // No seguir redirecciones
            curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);            // Timeout de 10 segundos
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);      // Timeout de conexión
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // Verificar SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            // User-Agent
            curl_setopt($ch, CURLOPT_USERAGENT, 'SistemaAdmin/1.0');
            
            // Ejecutar petición
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($response === false) {
                return [
                    'success' => false,
                    'error' => 'Error en la petición: ' . $error
                ];
            }
            
            return [
                'success' => true,
                'response' => $response,
                'http_code' => $httpCode
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Añadir dominio a lista de bloqueados
     */
    public static function blockDomain(string $domain): void
    {
        if (!in_array($domain, self::$blockedDomains)) {
            self::$blockedDomains[] = strtolower($domain);
        }
    }
    
    /**
     * Añadir rango de IPs a lista de bloqueados
     */
    public static function blockIPRange(string $start, string $end): void
    {
        self::$blockedIPRanges[] = [$start, $end];
    }
}
