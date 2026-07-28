<?php

namespace SistemaAdmin\Middleware;

class UploadSecurityMiddleware
{
    private static $allowedImageTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    private static $allowedDocumentTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'phps',
        'asp', 'aspx', 'jsp', 'jspx',
        'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js',
        'py', 'rb', 'pl', 'sh', 'cgi'
    ];
    
    /**
     * Validar archivo subido de forma segura
     */
    public static function validateUploadedFile(array $file, string $allowedCategory = 'image'): array
    {
        $errors = [];
        
        // PROTECCIÓN CONTRA FILE UPLOAD VULNERABILITIES
        // Verificar que no haya errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Verificar tamaño del archivo
        if ($file['size'] > self::getMaxFileSize($allowedCategory)) {
            $errors[] = 'El archivo es demasiado grande';
        }
        
        // Verificar que el archivo no esté vacío
        if ($file['size'] === 0) {
            $errors[] = 'El archivo está vacío';
        }
        
        // Verificar nombre del archivo
        $sanitizedFileName = FileSecurityMiddleware::sanitizeFileName($file['name']);
        if ($sanitizedFileName !== $file['name']) {
            $errors[] = 'Nombre de archivo no válido';
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($extension, self::$dangerousExtensions)) {
            $errors[] = 'Tipo de archivo no permitido';
        }
        
        // Verificar doble extensión (ej: archivo.php.jpg)
        if (preg_match('/\.(php|phtml|asp|aspx|jsp|exe|bat|cmd)\./i', $file['name'])) {
            $errors[] = 'Nombre de archivo con doble extensión peligrosa detectado';
        }
        
        // Verificar MIME type real (no solo extensión)
        $realMimeType = self::getRealMimeType($file['tmp_name']);
        if (!$realMimeType) {
            $errors[] = 'No se pudo verificar el tipo de archivo';
        } else {
            $allowedTypes = self::getAllowedTypes($allowedCategory);
            if (!in_array($realMimeType, $allowedTypes)) {
                $errors[] = 'Tipo de archivo no permitido: ' . $realMimeType;
            }
        }
        
        // Verificar contenido del archivo
        $contentValidation = self::validateFileContent($file['tmp_name'], $allowedCategory);
        if (!$contentValidation['valid']) {
            $errors = array_merge($errors, $contentValidation['errors']);
        }
        
        // Verificar si el archivo contiene código ejecutable
        if (self::containsExecutableCode($file['tmp_name'])) {
            $errors[] = 'El archivo contiene código ejecutable';
        }
        
        // Verificar contenido malicioso en imágenes (EXIF, etc.)
        if (strpos($realMimeType, 'image/') === 0) {
            if (self::containsMaliciousImageContent($file['tmp_name'])) {
                $errors[] = 'Contenido malicioso detectado en la imagen';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_name' => $sanitizedFileName,
            'real_mime_type' => $realMimeType
        ];
    }
    
    /**
     * Obtener MIME type real del archivo
     */
    private static function getRealMimeType(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        return $mimeType ?: null;
    }
    
    /**
     * Validar contenido del archivo
     */
    private static function validateFileContent(string $filePath, string $category): array
    {
        $errors = [];
        $content = file_get_contents($filePath, false, null, 0, 1024); // Leer solo los primeros 1024 bytes
        
        if ($category === 'image') {
            // Verificar que sea realmente una imagen
            if (!self::isValidImageContent($content)) {
                $errors[] = 'El archivo no es una imagen válida';
            }
        }
        
        // Verificar magic bytes
        $magicBytes = bin2hex(substr($content, 0, 4));
        $allowedMagicBytes = [
            'ffd8ffe0', // JPEG
            'ffd8ffe1', // JPEG
            'ffd8ffe2', // JPEG
            '89504e47', // PNG
            '47494638', // GIF
            '25504446', // PDF
            '504b0304', // ZIP/DOCX
            'd0cf11e0'  // DOC/XLS
        ];
        
        if (!in_array($magicBytes, $allowedMagicBytes)) {
            $errors[] = 'Formato de archivo no reconocido';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Verificar si es una imagen válida
     */
    private static function isValidImageContent(string $content): bool
    {
        $imageInfo = @getimagesizefromstring($content);
        return $imageInfo !== false;
    }
    
    /**
     * Detectar código ejecutable en el archivo
     */
    private static function containsExecutableCode(string $filePath): bool
    {
        $content = file_get_contents($filePath, false, null, 0, 8192); // Leer primeros 8KB
        
        $dangerousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i',
            '/passthru/i',
            '/proc_open/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Mover archivo subido de forma segura
     */
    public static function moveUploadedFileSecurely(array $file, string $destinationDir, string $newFileName = null): array
    {
        try {
            // Crear directorio si no existe
            if (!is_dir($destinationDir)) {
                if (!mkdir($destinationDir, 0755, true)) {
                    return ['success' => false, 'error' => 'No se pudo crear el directorio de destino'];
                }
            }
            
            // Generar nombre único si no se proporciona
            if (!$newFileName) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFileName = uniqid('upload_') . '.' . $extension;
            }
            
            // Crear ruta completa
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $newFileName;
            
            // Verificar que el destino esté dentro del directorio permitido
            $realDestination = realpath($destinationDir);
            $realFile = realpath($destinationPath);
            
            if ($realFile === false || strpos($realFile, $realDestination) !== 0) {
                return ['success' => false, 'error' => 'Ruta de destino no válida'];
            }
            
            // Mover el archivo
            if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
                // Configurar permisos restrictivos
                chmod($destinationPath, 0644);
                
                return [
                    'success' => true,
                    'file_path' => $destinationPath,
                    'file_name' => $newFileName
                ];
            } else {
                return ['success' => false, 'error' => 'No se pudo mover el archivo'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al procesar el archivo: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener tipos de archivo permitidos por categoría
     */
    private static function getAllowedTypes(string $category): array
    {
        switch ($category) {
            case 'image':
                return self::$allowedImageTypes;
            case 'document':
                return self::$allowedDocumentTypes;
            default:
                return array_merge(self::$allowedImageTypes, self::$allowedDocumentTypes);
        }
    }
    
    /**
     * Obtener tamaño máximo de archivo por categoría
     */
    private static function getMaxFileSize(string $category): int
    {
        switch ($category) {
            case 'image':
                return 5 * 1024 * 1024; // 5MB
            case 'document':
                return 10 * 1024 * 1024; // 10MB
            default:
                return 2 * 1024 * 1024; // 2MB
        }
    }
    
    /**
     * Obtener mensaje de error de subida
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el servidor';
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el formulario';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se seleccionó ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'No hay directorio temporal disponible';
            case UPLOAD_ERR_CANT_WRITE:
                return 'No se pudo escribir el archivo en el disco';
            case UPLOAD_ERR_EXTENSION:
                return 'La subida fue detenida por una extensión';
            default:
                return 'Error desconocido al subir el archivo';
        }
    }
    
    /**
     * Limpiar archivos subidos huérfanos
     */
    public static function cleanupOrphanedUploads(string $uploadDir, int $maxAge = 3600): int
    {
        $cleaned = 0;
        $currentTime = time();
        
        if (!is_dir($uploadDir)) {
            return 0;
        }
        
        $files = glob($uploadDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file) && ($currentTime - filemtime($file)) > $maxAge) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Verificar contenido malicioso en imágenes
     */
    private static function containsMaliciousImageContent(string $filePath): bool
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        
        try {
            $exif = exif_read_data($filePath);
            if ($exif === false) {
                return false;
            }
            
            // Verificar campos EXIF sospechosos
            $suspiciousFields = ['Artist', 'Copyright', 'UserComment', 'Make', 'Model', 'Software'];
            foreach ($suspiciousFields as $field) {
                if (isset($exif[$field]) && is_string($exif[$field])) {
                    // Buscar patrones de código malicioso
                    if (preg_match('/<\?php|eval\(|base64_decode|shell_exec|system\(/i', $exif[$field])) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error verificando EXIF: " . $e->getMessage());
            return true; // En caso de error, considerar como sospechoso
        }
        
        return false;
    }
}
