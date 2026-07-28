<?php

namespace SistemaAdmin\Middleware;

class FileSecurityMiddleware
{
    /**
     * Validar y sanitizar rutas de archivos
     */
    public static function validateFilePath(string $filePath, string $allowedBasePath = ''): array
    {
        $errors = [];
        
        // PROTECCIÓN CONTRA DIRECTORY TRAVERSAL
        // Decodificar URL encoding
        $decodedPath = urldecode($filePath);
        
        // Normalizar la ruta
        $normalizedPath = self::normalizePath($decodedPath);
        
        // Verificar caracteres peligrosos
        if (preg_match('/[\/\\:*?"<>|]/', $normalizedPath)) {
            $errors[] = 'Caracteres peligrosos en la ruta del archivo';
        }
        
        // Verificar intentos de directory traversal
        if (self::containsDirectoryTraversal($normalizedPath)) {
            $errors[] = 'Intento de directory traversal detectado';
        }
        
        // Verificar que la ruta esté dentro del directorio permitido
        if (!empty($allowedBasePath)) {
            $realBasePath = realpath($allowedBasePath);
            $realFilePath = realpath($normalizedPath);
            
            if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
                $errors[] = 'Ruta fuera del directorio permitido';
            }
        }
        
        // Verificar que el archivo exista
        if (!file_exists($normalizedPath)) {
            $errors[] = 'Archivo no encontrado';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized_path' => $normalizedPath
        ];
    }
    
    /**
     * Detectar intentos de directory traversal
     */
    private static function containsDirectoryTraversal(string $path): bool
    {
        $traversalPatterns = [
            '/\.\./',           // ../
            '/\.\.\\/',         // ..\
            '/%2e%2e%2f/i',     // URL encoded ../
            '/%2e%2e%5c/i',     // URL encoded ..\
            '/\.\.%2f/i',       // Mixed encoding
            '/\.\.%5c/i',       // Mixed encoding
            '/%252e%252e%252f/i', // Double URL encoding
            '/%252e%252e%255c/i', // Double URL encoding
            '/\.\.%c0%af/i',    // Unicode encoding
            '/\.\.%c1%9c/i',    // Unicode encoding
            '/\.\.%2e%2e/i',    // Additional patterns
            '/%2e%2e%2e%2e/i',  // Multiple dots
            '/\.\.%00/',        // Null byte injection
            '/%00\.\./',        // Null byte injection
        ];
        
        foreach ($traversalPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        
        // Verificar secuencias de .. al inicio
        if (preg_match('/^\.\.+/', $path)) {
            return true;
        }
        
        // Verificar secuencias de .. en cualquier parte
        if (preg_match('/\.\.{2,}/', $path)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Normalizar ruta de archivo
     */
    private static function normalizePath(string $path): string
    {
        // Remover caracteres de control
        $path = preg_replace('/[\x00-\x1F\x7F]/', '', $path);
        
        // Normalizar separadores
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        
        // Resolver referencias relativas
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $resolved = [];
        
        foreach ($parts as $part) {
            if ($part === '.' || $part === '') {
                continue;
            } elseif ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }
        
        return implode(DIRECTORY_SEPARATOR, $resolved);
    }
    
    /**
     * Validar tipo de archivo por contenido (no solo extensión)
     */
    public static function validateFileType(string $filePath, array $allowedTypes): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Obtener MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Verificar si el MIME type está permitido
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // Verificación adicional para archivos ejecutables
        if (self::isExecutableFile($filePath)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar si un archivo es ejecutable
     */
    private static function isExecutableFile(string $filePath): bool
    {
        $executableExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js',
            'php', 'asp', 'jsp', 'py', 'rb', 'pl', 'sh', 'cgi'
        ];
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return in_array($extension, $executableExtensions);
    }
    
    /**
     * Sanitizar nombre de archivo
     */
    public static function sanitizeFileName(string $fileName): string
    {
        // Remover caracteres peligrosos
        $fileName = preg_replace('/[\/\\:*?"<>|]/', '_', $fileName);
        
        // Remover caracteres de control
        $fileName = preg_replace('/[\x00-\x1F\x7F]/', '', $fileName);
        
        // Limitar longitud
        $fileName = substr($fileName, 0, 255);
        
        // Remover espacios al inicio y final
        $fileName = trim($fileName);
        
        // Asegurar que no esté vacío
        if (empty($fileName)) {
            $fileName = 'unnamed_file';
        }
        
        return $fileName;
    }
    
    /**
     * Crear archivo temporal seguro
     */
    public static function createSecureTempFile(string $content = '', string $prefix = 'secure_'): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, $prefix);
        
        if ($tempFile === false) {
            throw new \Exception('No se pudo crear archivo temporal');
        }
        
        // Configurar permisos restrictivos
        chmod($tempFile, 0600);
        
        if (!empty($content)) {
            file_put_contents($tempFile, $content, LOCK_EX);
        }
        
        return $tempFile;
    }
    
    /**
     * Limpiar archivos temporales antiguos
     */
    public static function cleanupOldTempFiles(string $directory, int $maxAge = 3600): int
    {
        $cleaned = 0;
        $currentTime = time();
        
        if (!is_dir($directory)) {
            return 0;
        }
        
        $files = glob($directory . '/secure_*');
        
        foreach ($files as $file) {
            if (is_file($file) && ($currentTime - filemtime($file)) > $maxAge) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}
