<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;

/**
 * Servicio de Carga de Archivos Segura
 * 
 * Implementa todas las medidas de seguridad para la carga de archivos
 * según las mejores prácticas de OWASP
 */
class FileUploadService extends BaseService
{
    private string $uploadDirectory;
    private array $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];
    
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx'];
    private int $maxFileSize = 5242880; // 5MB
    private array $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'war'];

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->uploadDirectory = __DIR__ . '/../../uploads/';
        $this->createUploadDirectory();
    }

    /**
     * Crear directorio de uploads si no existe
     */
    private function createUploadDirectory(): void
    {
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
        
        // Crear archivo .htaccess para prevenir ejecución de scripts
        $htaccessContent = "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
        file_put_contents($this->uploadDirectory . '.htaccess', $htaccessContent);
    }

    /**
     * Subir archivo de forma segura
     */
    public function uploadFile(array $file, string $category = 'general'): array
    {
        try {
            // Validar que el archivo se subió correctamente
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return [
                    'success' => false,
                    'error' => 'Archivo no válido o no subido correctamente'
                ];
            }

            // Validar tamaño
            if ($file['size'] > $this->maxFileSize) {
                return [
                    'success' => false,
                    'error' => 'El archivo excede el tamaño máximo permitido (5MB)'
                ];
            }

            // Validar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!array_key_exists($mimeType, $this->allowedMimeTypes)) {
                return [
                    'success' => false,
                    'error' => 'Tipo de archivo no permitido'
                ];
            }

            // Validar extensión
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $this->allowedExtensions)) {
                return [
                    'success' => false,
                    'error' => 'Extensión de archivo no permitida'
                ];
            }

            // Verificar que la extensión coincida con el MIME type
            if ($this->allowedMimeTypes[$mimeType] !== $extension) {
                return [
                    'success' => false,
                    'error' => 'El tipo de archivo no coincide con su extensión'
                ];
            }

            // Escanear archivo con antivirus (simulado)
            if (!$this->scanFileForMalware($file['tmp_name'])) {
                return [
                    'success' => false,
                    'error' => 'El archivo contiene contenido sospechoso'
                ];
            }

            // Generar nombre único y seguro
            $safeFileName = $this->generateSafeFileName($file['name'], $extension);
            $uploadPath = $this->uploadDirectory . $category . '/' . $safeFileName;

            // Crear directorio de categoría si no existe
            $categoryDir = dirname($uploadPath);
            if (!is_dir($categoryDir)) {
                mkdir($categoryDir, 0755, true);
            }

            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Registrar en base de datos
                $fileId = $this->saveFileRecord($file['name'], $safeFileName, $uploadPath, $file['size'], $mimeType, $category);

                $this->logEvent('INFO', 'Archivo subido exitosamente', [
                    'original_name' => $file['name'],
                    'safe_name' => $safeFileName,
                    'size' => $file['size'],
                    'mime_type' => $mimeType,
                    'category' => $category
                ]);

                return [
                    'success' => true,
                    'file_id' => $fileId,
                    'filename' => $safeFileName,
                    'original_name' => $file['name'],
                    'size' => $file['size'],
                    'path' => $uploadPath
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al guardar el archivo'
                ];
            }

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error en upload de archivo', [
                'error' => $e->getMessage(),
                'file' => $file['name'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Escanear archivo en busca de malware
     */
    private function scanFileForMalware(string $filePath): bool
    {
        try {
            // Usar streaming con buffer optimizado para archivos grandes
            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                return false;
            }

            // Configurar buffering para optimizar I/O
            stream_set_read_buffer($handle, 8192); // Buffer de 8KB
            
            // Leer solo los primeros 1024 bytes para verificar headers
            $header = fread($handle, 1024);
            if ($header === false) {
                fclose($handle);
                return false;
            }

            // Verificar que no sea un archivo PHP disfrazado
            if (strpos($header, '<?php') !== false || 
                strpos($header, '<?=') !== false || 
                strpos($header, '<%') !== false ||
                strpos($header, '<script') !== false) {
                fclose($handle);
                return false;
            }

            // Buscar patrones peligrosos en los primeros 8KB
            $dangerousPatterns = [
                '/eval\s*\(/i',
                '/exec\s*\(/i',
                '/system\s*\(/i',
                '/shell_exec/i',
                '/passthru/i',
                '/file_get_contents\s*\(/i',
                '/include\s*\(/i',
                '/require\s*\(/i'
            ];

            // Leer más contenido si es necesario
            $content = $header;
            $readBytes = 1024;
            $maxBytes = min(filesize($filePath), 8192); // Máximo 8KB

            while ($readBytes < $maxBytes) {
                $chunk = fread($handle, 1024);
                if ($chunk === false) break;
                
                $content .= $chunk;
                $readBytes += 1024;
                
                // Verificar patrones en cada chunk
                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        fclose($handle);
                        return false;
                    }
                }
            }

            fclose($handle);
            return true;

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error escaneando archivo', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generar nombre de archivo seguro
     */
    private function generateSafeFileName(string $originalName, string $extension): string
    {
        // Remover null bytes y caracteres de control
        $originalName = str_replace(["\0", "\x00"], '', $originalName);
        $extension = str_replace(["\0", "\x00"], '', $extension);
        
        // Normalizar Unicode
        if (class_exists('Normalizer')) {
            $originalName = Normalizer::normalize($originalName, Normalizer::FORM_C);
            $extension = Normalizer::normalize($extension, Normalizer::FORM_C);
        }
        
        // Remover caracteres peligrosos y limitar longitud
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $safeName = preg_replace('/_{2,}/', '_', $safeName);
        $safeName = trim($safeName, '._-');
        
        // Limitar longitud del nombre
        $safeName = substr($safeName, 0, 100);
        
        // Sanitizar extensión
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        $extension = substr($extension, 0, 10);

        // Generar nombre único con hash seguro
        $timestamp = time();
        $random = bin2hex(random_bytes(12));
        $hash = hash('sha256', $originalName . $timestamp . $random);
        
        return $timestamp . '_' . substr($hash, 0, 16) . '_' . $safeName . '.' . $extension;
    }

    /**
     * Guardar registro de archivo en base de datos
     */
    private function saveFileRecord(string $originalName, string $safeName, string $path, int $size, string $mimeType, string $category): int
    {
        $sql = "INSERT INTO archivos_subidos (nombre_original, nombre_seguro, ruta, tamaño, tipo_mime, categoria, subido_por, subido_en) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->database->query($sql, [
            $originalName,
            $safeName,
            $path,
            $size,
            $mimeType,
            $category,
            $_SESSION['usuario_id'] ?? null
        ]);

        return $this->database->lastInsertId();
    }

    /**
     * Obtener archivo por ID
     */
    public function getFileById(int $fileId): ?array
    {
        $sql = "SELECT * FROM archivos_subidos WHERE id = ? AND eliminado = 0";
        return $this->database->fetch($sql, [$fileId]);
    }

    /**
     * Eliminar archivo de forma segura
     */
    public function deleteFile(int $fileId): bool
    {
        try {
            $file = $this->getFileById($fileId);
            if (!$file) {
                return false;
            }

            // Validar que la ruta esté dentro del directorio permitido
            $filePath = $file['ruta'];
            $realUploadDir = realpath($this->uploadDirectory);
            $realFilePath = realpath($filePath);
            
            // Verificar que el archivo existe y está dentro del directorio permitido
            if (!$realFilePath || strpos($realFilePath, $realUploadDir) !== 0) {
                $this->logEvent('WARNING', 'Intento de eliminación de archivo fuera del directorio permitido', [
                    'file_id' => $fileId,
                    'requested_path' => $filePath,
                    'upload_directory' => $realUploadDir,
                    'ip' => $this->obtenerIPCliente()
                ]);
                return false;
            }

            // Verificar que el archivo existe físicamente
            if (!file_exists($realFilePath)) {
                $this->logEvent('WARNING', 'Archivo no existe físicamente, eliminando solo registro de BD', [
                    'file_id' => $fileId,
                    'file_path' => $realFilePath,
                    'ip' => $this->obtenerIPCliente()
                ]);
                // Continuar para eliminar el registro de la base de datos
            } else {
                // Eliminar archivo físico de forma segura
                if (!unlink($realFilePath)) {
                    $this->logEvent('ERROR', 'No se pudo eliminar archivo físico', [
                        'file_id' => $fileId,
                        'file_path' => $realFilePath,
                        'ip' => $this->obtenerIPCliente()
                    ]);
                    return false;
                }
            }

            // Marcar como eliminado en base de datos
            $sql = "UPDATE archivos_subidos SET eliminado = 1, eliminado_en = NOW() WHERE id = ?";
            $this->database->query($sql, [$fileId]);

            $this->logEvent('INFO', 'Archivo eliminado', [
                'file_id' => $fileId,
                'filename' => $file['nombre_seguro'],
                'file_path' => $realFilePath
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error eliminando archivo', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Crear tabla de archivos si no existe
     */
    private function createFileTable(): void
    {
        $utilityService = new UtilityService($this->database);
        $utilityService->createFileTable();
    }

    /**
     * Limpiar archivos antiguos
     */
    public function cleanOldFiles(int $daysOld = 90): int
    {
        $sql = "SELECT id, ruta FROM archivos_subidos 
                WHERE eliminado = 0 AND subido_en < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $files = $this->database->fetchAll($sql, [$daysOld]);
        $deleted = 0;

        foreach ($files as $file) {
            if ($this->deleteFile($file['id'])) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
