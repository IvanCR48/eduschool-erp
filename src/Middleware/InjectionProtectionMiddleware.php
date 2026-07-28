<?php

namespace SistemaAdmin\Middleware;

class InjectionProtectionMiddleware
{
    /**
     * Detectar intentos de SQL Injection
     */
    public static function detectSQLInjection(string $input): bool
    {
        // PROTECCIÓN CONTRA INJECTION ATTACKS
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(;|\-\-|\/\*|\*\/|xp_|sp_)/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
            '/(\b1\s*=\s*1\b)/i',
            '/(\b1\s*=\s*0\b)/i',
            '/(\'.*OR.*\'.*=.*\')/i',
            '/(\bCONCAT\b.*\()/i',
            '/(\bCAST\b.*\()/i',
            '/(\bCHAR\b.*\()/i',
            '/(\bSLEEP\b.*\()/i',
            '/(\bBENCHMARK\b.*\()/i',
            '/(\bINFORMATION_SCHEMA\b)/i',
            '/(\bSYSDATE\b|\bNOW\b)/i',
            '/(\bDATABASE\b.*\()/i',
            '/(\bUSER\b.*\()/i',
            '/(\bVERSION\b.*\()/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detectar intentos de LDAP Injection
     */
    public static function detectLDAPInjection(string $input): bool
    {
        $ldapPatterns = [
            '/[*()\\\\]/',              // Caracteres especiales LDAP
            '/(\||\&|\!)/',             // Operadores lógicos
            '/(\bOR\b|\bAND\b)/i',      // Operadores
            '/(objectClass=\*)/i',      // Búsquedas amplias
            '/(\)\(|\)\&|\)\|)/',       // Construcciones maliciosas
            '/(\bCN\b.*=.*\*)/i',       // Búsquedas con comodín
            '/(\bOU\b.*=.*\*)/i',       // Búsquedas con comodín
            '/(\bDC\b.*=.*\*)/i',       // Búsquedas con comodín
            '/(\bUID\b.*=.*\*)/i',      // Búsquedas con comodín
            '/(\bMAIL\b.*=.*\*)/i',     // Búsquedas con comodín
        ];
        
        foreach ($ldapPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detectar intentos de Command Injection
     */
    public static function detectCommandInjection(string $input): bool
    {
        $commandPatterns = [
            '/(;|\||&|`|\$\(|\$\{)/',   // Separadores de comandos
            '/(\n|\r)/',                 // Nuevas líneas
            '/(>|<|>>)/',                // Redirecciones
            '/(cat|ls|pwd|id|whoami|uname)/i',  // Comandos Unix
            '/(dir|type|net|ping)/i',    // Comandos Windows
            '/(wget|curl|nc|netcat)/i',  // Herramientas de red
            '/(rm|del|rmdir)/i',         // Comandos de borrado
            '/(chmod|chown|chgrp)/i',    // Comandos de permisos
            '/(\\\x[0-9a-f]{2})/i',      // Caracteres hexadecimales
            '/(eval|exec|system|shell_exec|passthru|proc_open)/i', // Funciones PHP peligrosas
            '/(base64_decode|gzinflate|str_rot13)/i', // Funciones de decodificación
            '/(file_get_contents|file_put_contents)/i', // Funciones de archivo
            '/(include|require|include_once|require_once)/i', // Inclusiones de archivo
        ];
        
        foreach ($commandPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitizar entrada contra SQL Injection
     */
    public static function sanitizeSQLInput(string $input): string
    {
        // Escapar caracteres especiales
        $input = addslashes($input);
        
        // Remover comentarios SQL
        $input = preg_replace('/--.*$/m', '', $input);
        $input = preg_replace('/\/\*.*?\*\//s', '', $input);
        
        // Remover caracteres de control
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        
        return $input;
    }
    
    /**
     * Sanitizar entrada contra LDAP Injection
     */
    public static function sanitizeLDAPInput(string $input): string
    {
        // Escapar caracteres especiales LDAP
        $ldapSpecialChars = [
            '\\' => '\\5c',
            '*'  => '\\2a',
            '('  => '\\28',
            ')'  => '\\29',
            "\0" => '\\00'
        ];
        
        return str_replace(array_keys($ldapSpecialChars), array_values($ldapSpecialChars), $input);
    }
    
    /**
     * Sanitizar entrada contra Command Injection
     */
    public static function sanitizeCommandInput(string $input): string
    {
        // Escapar caracteres de shell
        return escapeshellarg($input);
    }
    
    /**
     * Validar entrada contra múltiples tipos de inyección
     */
    public static function validateInput(string $input, array $checkTypes = ['sql', 'ldap', 'command']): array
    {
        $threats = [];
        
        if (in_array('sql', $checkTypes) && self::detectSQLInjection($input)) {
            $threats[] = 'SQL Injection detectado';
        }
        
        if (in_array('ldap', $checkTypes) && self::detectLDAPInjection($input)) {
            $threats[] = 'LDAP Injection detectado';
        }
        
        if (in_array('command', $checkTypes) && self::detectCommandInjection($input)) {
            $threats[] = 'Command Injection detectado';
        }
        
        return [
            'safe' => empty($threats),
            'threats' => $threats,
            'sanitized_sql' => self::sanitizeSQLInput($input),
            'sanitized_ldap' => self::sanitizeLDAPInput($input),
            'sanitized_command' => self::sanitizeCommandInput($input)
        ];
    }
    
    /**
     * Validar parámetros de array
     */
    public static function validateArrayParameters(array $parameters, array $checkTypes = ['sql']): array
    {
        $results = [];
        $allSafe = true;
        
        foreach ($parameters as $key => $value) {
            if (is_string($value)) {
                $validation = self::validateInput($value, $checkTypes);
                if (!$validation['safe']) {
                    $allSafe = false;
                    $results[$key] = $validation['threats'];
                }
            } elseif (is_array($value)) {
                $nestedValidation = self::validateArrayParameters($value, $checkTypes);
                if (!$nestedValidation['safe']) {
                    $allSafe = false;
                    $results[$key] = $nestedValidation['threats'];
                }
            }
        }
        
        return [
            'safe' => $allSafe,
            'threats' => $results
        ];
    }
}
