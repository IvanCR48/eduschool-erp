<?php

namespace SistemaAdmin\Middleware;

class HTTPParameterPollutionProtectionMiddleware
{
    /**
     * Limpiar parámetros HTTP para prevenir HPP (HTTP Parameter Pollution)
     */
    public static function cleanParameters(array $parameters): array
    {
        $cleaned = [];
        
        foreach ($parameters as $key => $value) {
            // PROTECCIÓN CONTRA HTTP PARAMETER POLLUTION
            // Si el parámetro es un array (múltiples valores), tomar solo el primero
            if (is_array($value)) {
                $cleaned[$key] = self::sanitizeValue($value[0]);
                
                // Log de posible HPP attack
                if (count($value) > 1) {
                    error_log("HPP Protection: Múltiples valores detectados para parámetro '{$key}': " . implode(', ', $value));
                }
            } else {
                $cleaned[$key] = self::sanitizeValue($value);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Sanitizar valor individual
     */
    private static function sanitizeValue(mixed $value): string
    {
        if (!is_string($value)) {
            return (string)$value;
        }
        
        // Remover caracteres de control
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        
        // Limitar longitud
        if (strlen($value) > 1000) {
            $value = substr($value, 0, 1000);
        }
        
        // Escapar caracteres HTML
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $value;
    }
    
    /**
     * Validar parámetros GET
     */
    public static function validateGETParameters(): array
    {
        return self::cleanParameters($_GET);
    }
    
    /**
     * Validar parámetros POST
     */
    public static function validatePOSTParameters(): array
    {
        return self::cleanParameters($_POST);
    }
    
    /**
     * Validar parámetros de cookies
     */
    public static function validateCookieParameters(): array
    {
        return self::cleanParameters($_COOKIE);
    }
    
    /**
     * Detectar intentos de HPP en una URL
     */
    public static function detectHPPAttempt(string $url): bool
    {
        // Buscar parámetros duplicados en la URL
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['query'])) {
            return false;
        }
        
        parse_str($parsedUrl['query'], $params);
        
        // Verificar si hay parámetros duplicados
        $paramNames = array_keys($params);
        $uniqueNames = array_unique($paramNames);
        
        if (count($paramNames) !== count($uniqueNames)) {
            error_log("HPP Protection: Parámetros duplicados detectados en URL: {$url}");
            return true;
        }
        
        // Verificar si algún parámetro tiene múltiples valores
        foreach ($params as $key => $value) {
            if (is_array($value) && count($value) > 1) {
                error_log("HPP Protection: Parámetro con múltiples valores detectado: {$key}");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Limpiar URL de posibles HPP attacks
     */
    public static function cleanURL(string $url): string
    {
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['query'])) {
            return $url;
        }
        
        parse_str($parsedUrl['query'], $params);
        
        // Limpiar parámetros
        $cleanedParams = self::cleanParameters($params);
        
        // Reconstruir URL
        $parsedUrl['query'] = http_build_query($cleanedParams);
        
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
     * Validar parámetros específicos contra HPP
     */
    public static function validateSpecificParameters(array $parameters, array $allowedParameters): array
    {
        $validated = [];
        
        foreach ($allowedParameters as $param) {
            if (isset($parameters[$param])) {
                $validated[$param] = $parameters[$param];
            }
        }
        
        // Verificar si hay parámetros no permitidos
        $extraParams = array_diff_key($parameters, array_flip($allowedParameters));
        if (!empty($extraParams)) {
            error_log("HPP Protection: Parámetros no permitidos detectados: " . implode(', ', array_keys($extraParams)));
        }
        
        return $validated;
    }
    
    /**
     * Limpiar headers HTTP para prevenir HPP
     */
    public static function cleanHTTPHeaders(): array
    {
        $cleaned = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('HTTP_', '', $key);
                $headerName = str_replace('_', '-', $headerName);
                $headerName = ucwords(strtolower($headerName), '-');
                
                $cleaned[$headerName] = self::sanitizeValue($value);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Validar tamaño de parámetros
     */
    public static function validateParameterSize(array $parameters): bool
    {
        $totalSize = 0;
        
        foreach ($parameters as $key => $value) {
            $totalSize += strlen($key) + strlen($value);
        }
        
        // Límite de 10KB para todos los parámetros
        if ($totalSize > 10240) {
            error_log("HPP Protection: Tamaño total de parámetros excede límite: {$totalSize} bytes");
            return false;
        }
        
        return true;
    }
}
