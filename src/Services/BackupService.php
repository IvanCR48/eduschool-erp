<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Backup y Restauración
 * 
 * Maneja backups automáticos de la base de datos y archivos
 */
class BackupService extends BaseService
{
    private string $downloadSecret;

    public function generarTokenDescarga(string $archivo, int $ttlSegundos = 600): string
    {
        $expira = time() + $ttlSegundos;
        $payload = $archivo . '|' . $expira;
        $firma = hash_hmac('sha256', $payload, $this->downloadSecret);
        return base64_encode($payload . '|' . $firma);
    }

    public function validarTokenDescarga(string $token): array
    {
        $dec = base64_decode($token, true);
        if ($dec === false) {
            return ['ok' => false, 'error' => 'Token inválido'];
        }
        $parts = explode('|', $dec);
        if (count($parts) !== 3) {
            return ['ok' => false, 'error' => 'Token malformado'];
        }
        [$archivo, $expira, $firma] = $parts;
        if (!ctype_digit($expira) || (int)$expira < time()) {
            return ['ok' => false, 'error' => 'Token expirado'];
        }
        $calc = hash_hmac('sha256', $archivo . '|' . $expira, $this->downloadSecret);
        if (!hash_equals($calc, $firma)) {
            return ['ok' => false, 'error' => 'Firma inválida'];
        }
        return ['ok' => true, 'archivo' => $archivo];
    }
    private string $backupDir;
    private int $maxBackups = 30; // Mantener últimos 30 backups
    
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->backupDir = __DIR__ . '/../../backups';
        $cfg = new ConfigurationService($database);
        $this->maxBackups = max(1, min(365, (int) round((float) $cfg->obtener('backup.max_backups', 30))));
        $this->downloadSecret = $this->resolveSecret(
            'BACKUP_DOWNLOAD_SECRET',
            'download_secret'
        );
        $this->ensureBackupDirectory();
    }
    
    /**
     * Asegurar que el directorio de backups existe
     */
    private function ensureBackupDirectory(): void
    {
        $utilityService = new UtilityService($this->database);
        $utilityService->ensureDirectory($this->backupDir);
    }
    
    /**
     * Crear backup completo del sistema con cifrado AES-256
     */
    public function crearBackupCompleto(): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = "backup_completo_{$timestamp}";
            $backupPath = "{$this->backupDir}/{$backupName}";
            
            // Crear directorio temporal para el backup
            mkdir($backupPath, 0755, true);
            
            // 1. Backup de base de datos
            $dbBackupFile = "{$backupPath}/database.sql";
            $this->backupDatabase($dbBackupFile);
            
            // 2. Backup de configuración
            $this->backupConfiguration($backupPath);
            
            // 3. Backup de archivos importantes
            $this->backupFiles($backupPath);
            
            // 4. Comprimir (ZIP si hay extensión zip; si no, tar.gz vía sistema/Phar)
            $archivePath = $this->createArchiveFromDirectory($backupPath, "{$this->backupDir}/{$backupName}.zip");

            // 5. Cifrar el backup con AES-256
            $encryptedFile = $this->encryptBackup($archivePath);

            // 6. Eliminar archivo sin cifrar
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
            
            // 7. Limpiar directorio temporal
            $this->deleteDirectory($backupPath);
            
            // 8. Limpiar backups antiguos
            $this->cleanOldBackups();
            
            // 9. Registrar backup cifrado
            $this->registrarBackup($backupName, filesize($encryptedFile), true);
            
            $this->logEvent('INFO', 'Backup completo creado y cifrado', [
                'archivo' => basename($encryptedFile),
                'tamaño' => $this->formatBytes(filesize($encryptedFile)),
                'cifrado' => 'AES-256'
            ]);
            
            return [
                'success' => true,
                'mensaje' => 'Backup creado y cifrado exitosamente',
                'archivo' => basename($encryptedFile),
                'tamaño' => filesize($encryptedFile),
                'ruta' => $encryptedFile
            ];
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error creando backup', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'mensaje' => 'Error al crear backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Backup de la base de datos
     */
    private function backupDatabase(string $outputFile): void
    {
        $dbConfig = $this->getDbConfig();

        $defaultsFile = tempnam(sys_get_temp_dir(), 'mysqldump_');
        if ($defaultsFile === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal para credenciales de mysqldump');
        }

        $defaultsContent = "[client]\n"
            . 'user="' . str_replace('"', '\"', (string) $dbConfig['username']) . '"' . "\n"
            . 'password="' . str_replace('"', '\"', (string) $dbConfig['password']) . '"' . "\n";

        file_put_contents($defaultsFile, $defaultsContent);
        @chmod($defaultsFile, 0600);

        try {
            $command = sprintf(
                'mysqldump --defaults-extra-file=%s --host=%s %s > %s 2>&1',
                escapeshellarg($defaultsFile),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($outputFile)
            );

            // Intentar con mysqldump sin exponer credenciales en CLI
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputFile) || filesize($outputFile) === 0) {
                // Si mysqldump falla, usar backup manual con PHP
                $this->backupDatabaseManual($outputFile);
            }
        } finally {
            if (is_file($defaultsFile)) {
                @unlink($defaultsFile);
            }
        }
    }
    
    /**
     * Backup manual de la base de datos usando PHP
     */
    private function backupDatabaseManual(string $outputFile): void
    {
        $sql = "-- Backup de Base de Datos\n";
        $sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Obtener todas las tablas y vistas
        $tables = $this->database->fetchAll("SHOW FULL TABLES");
        
        foreach ($tables as $table) {
            $tableValues = array_values($table);
            $tableName = $tableValues[0] ?? null;
            $tableType = strtoupper($tableValues[1] ?? 'BASE TABLE');

            if ($tableName === null) {
                $this->logEvent('WARNING', 'Nombre de tabla indefinido durante backup', [
                    'registro_tabla' => $table
                ]);
                continue;
            }

            $createStatement = null;
            $createKey = null;
            $dropStatement = null;

            if ($tableType === 'VIEW') {
                $createStatement = $this->database->fetch("SHOW CREATE VIEW `{$tableName}`");
                $createKey = 'Create View';
                $dropStatement = "DROP VIEW IF EXISTS `{$tableName}`;";
            } else {
                $createStatement = $this->database->fetch("SHOW CREATE TABLE `{$tableName}`");
                $createKey = 'Create Table';
                $dropStatement = "DROP TABLE IF EXISTS `{$tableName}`;";
            }

            if (empty($createStatement) || !isset($createStatement[$createKey])) {
                $this->logEvent('WARNING', 'No se pudo obtener la definición de la tabla para backup', [
                    'tabla' => $tableName,
                    'tipo' => $tableType,
                    'disponible' => array_keys((array)$createStatement)
                ]);
                continue;
            }
            
            // Estructura de la tabla
            $sql .= "\n-- Tabla: {$tableName}\n";
            $sql .= "{$dropStatement}\n";
            $sql .= $createStatement[$createKey] . ";\n\n";

            // Las vistas no necesitan respaldo de datos
            if ($tableType === 'VIEW') {
                continue;
            }
            
            // Datos de la tabla
            $rows = $this->database->fetchAll("SELECT * FROM `{$tableName}`");
            
            if (!empty($rows)) {
                $sql .= "-- Datos de {$tableName}\n";
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }, array_values($row));
                    
                    $sql .= sprintf(
                        "INSERT INTO `%s` VALUES (%s);\n",
                        $tableName,
                        implode(', ', $values)
                    );
                }
                
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        file_put_contents($outputFile, $sql);
    }
    
    /**
     * Backup de archivos de configuración
     */
    private function backupConfiguration(string $backupPath): void
    {
        $configDir = __DIR__ . '/../../config';
        $targetDir = "{$backupPath}/config";
        
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Copiar archivos de configuración (excepto con contraseñas sensibles)
        $files = glob("{$configDir}/*.php");
        foreach ($files as $file) {
            copy($file, "{$targetDir}/" . basename($file));
        }
    }
    
    /**
     * Backup de archivos importantes
     */
    private function backupFiles(string $backupPath): void
    {
        // Backup de logs
        $logsDir = __DIR__ . '/../../logs';
        if (file_exists($logsDir)) {
            $targetDir = "{$backupPath}/logs";
            mkdir($targetDir, 0755, true);
            
            $logFiles = glob("{$logsDir}/*.log");
            foreach ($logFiles as $file) {
                copy($file, "{$targetDir}/" . basename($file));
            }
        }
        
        // Backup de uploads (si existen)
        $uploadsDir = __DIR__ . '/../../uploads';
        if (file_exists($uploadsDir)) {
            $this->copyDirectory($uploadsDir, "{$backupPath}/uploads");
        }
    }
    
    /**
     * Crea un .zip o .tar.gz con el contenido del directorio (sin depender solo de ext-zip).
     *
     * @return string ruta del archivo creado (.zip o .tar.gz)
     */
    private function createArchiveFromDirectory(string $source, string $preferredZipPath): string
    {
        $sourceReal = realpath($source);
        if ($sourceReal === false || !is_dir($sourceReal)) {
            throw new \Exception('Directorio de backup inválido');
        }

        if (extension_loaded('zip')) {
            $this->createZipWithZipArchive($sourceReal, $preferredZipPath);

            return $preferredZipPath;
        }

        if ($this->tryShellTarCreateZip($sourceReal, $preferredZipPath)) {
            return $preferredZipPath;
        }

        $tarGzPath = preg_replace('/\.zip$/i', '.tar.gz', $preferredZipPath);
        if ($tarGzPath === $preferredZipPath) {
            $tarGzPath = $preferredZipPath . '.tar.gz';
        }

        if ($this->tryShellTarCreateTarGz($sourceReal, $tarGzPath)) {
            return $tarGzPath;
        }

        if ($this->tryPharTarGz($sourceReal, $tarGzPath)) {
            return $tarGzPath;
        }

        throw new \Exception(
            'No se pudo comprimir el backup (sin extensión ZIP ni tar del sistema). '
            . 'En XAMPP: abra php.ini, descomente o agregue extension=zip, reinicie Apache, '
            . 'o asegúrese de que el comando "tar" esté disponible en el PATH de Windows.'
        );
    }

    private function createZipWithZipArchive(string $source, string $destination): void
    {
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            throw new \Exception('No se pudo crear archivo ZIP');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if ($filePath === false) {
                    continue;
                }
                $relativePath = substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    private function tryShellTarCreateZip(string $sourceReal, string $destinationZip): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $parent = dirname($sourceReal);
        $base = basename($sourceReal);
        $old = @getcwd();
        if ($old === false || !@chdir($parent)) {
            return false;
        }

        $dest = str_replace('/', DIRECTORY_SEPARATOR, $destinationZip);
        $cmd = 'tar -a -c -f ' . escapeshellarg($dest) . ' ' . escapeshellarg($base) . ' 2>&1';
        @exec($cmd, $unused, $code);
        @chdir($old);

        return $code === 0 && is_file($destinationZip) && filesize($destinationZip) > 0;
    }

    private function tryShellTarCreateTarGz(string $sourceReal, string $destinationTarGz): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $parent = dirname($sourceReal);
        $base = basename($sourceReal);
        $old = @getcwd();
        if ($old === false || !@chdir($parent)) {
            return false;
        }

        $dest = str_replace('/', DIRECTORY_SEPARATOR, $destinationTarGz);
        $cmd = 'tar -czf ' . escapeshellarg($dest) . ' ' . escapeshellarg($base) . ' 2>&1';
        @exec($cmd, $unused, $code);
        @chdir($old);

        return $code === 0 && is_file($destinationTarGz) && filesize($destinationTarGz) > 0;
    }

    private function tryPharTarGz(string $sourceReal, string $destinationTarGz): bool
    {
        if (!class_exists(\PharData::class, false)) {
            return false;
        }

        $tarPath = $destinationTarGz;
        if (str_ends_with(strtolower($tarPath), '.gz')) {
            $tarPath = substr($destinationTarGz, 0, -3);
        } else {
            $tarPath = $destinationTarGz . '.tmp.tar';
        }

        try {
            if (is_file($tarPath)) {
                @unlink($tarPath);
            }
            if (is_file($destinationTarGz)) {
                @unlink($destinationTarGz);
            }

            $phar = new \PharData($tarPath);
            $phar->buildFromDirectory($sourceReal);
            $phar->compress(\Phar::GZ);
            @unlink($tarPath);

            return is_file($destinationTarGz) && filesize($destinationTarGz) > 0;
        } catch (\Throwable $e) {
            @unlink($tarPath);
            if (is_file($destinationTarGz)) {
                @unlink($destinationTarGz);
            }

            return false;
        }
    }
    
    /**
     * Obtener configuración de la base de datos
     */
    private function getDbConfig(): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'school_admin',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? ''
        ];
    }
    
    /**
     * Limpiar backups antiguos
     */
    private function cleanOldBackups(): void
    {
        $patterns = [
            "{$this->backupDir}/backup_completo_*.zip.enc",
            "{$this->backupDir}/backup_completo_*.tar.gz.enc",
            "{$this->backupDir}/backup_completo_*.zip",
            "{$this->backupDir}/backup_completo_*.tar.gz",
        ];
        $backups = [];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $path) {
                $backups[$path] = true;
            }
        }
        $backups = array_keys($backups);

        if (count($backups) <= $this->maxBackups) {
            return;
        }

        usort($backups, static function ($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });

        $toDelete = array_slice($backups, 0, count($backups) - $this->maxBackups);
        foreach ($toDelete as $backup) {
            if (is_file($backup)) {
                @unlink($backup);
            }
        }
    }
    
    /**
     * Cifrar backup con AES-256
     */
    private function encryptBackup(string $filePath): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16); // IV aleatorio para cada archivo
        
        // Leer contenido del archivo
        $data = file_get_contents($filePath);
        
        // Cifrar con AES-256-CBC
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        if ($encrypted === false) {
            throw new \Exception('Error cifrando el backup');
        }
        
        // Crear archivo cifrado con IV prependido
        $encryptedFile = $filePath . '.enc';
        $result = file_put_contents($encryptedFile, $iv . $encrypted);
        
        if ($result === false) {
            throw new \Exception('Error escribiendo archivo cifrado');
        }
        
        return $encryptedFile;
    }
    
    /**
     * Descifrar backup
     */
    public function decryptBackup(string $encryptedFilePath, string $outputPath): bool
    {
        try {
            $key = $this->getEncryptionKey();
            
            // Leer archivo cifrado
            $encryptedData = file_get_contents($encryptedFilePath);
            
            if ($encryptedData === false) {
                throw new \Exception('No se pudo leer el archivo cifrado');
            }
            
            // Extraer IV (primeros 16 bytes)
            $iv = substr($encryptedData, 0, 16);
            $encrypted = substr($encryptedData, 16);
            
            // Descifrar
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
            
            if ($decrypted === false) {
                throw new \Exception('Error descifrando el backup');
            }
            
            // Escribir archivo descifrado
            $result = file_put_contents($outputPath, $decrypted);
            
            return $result !== false;
            
        } catch (\Exception $e) {
            error_log("Error descifrando backup: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Resuelve un secreto desde .env con fallback a APP_KEY.
     * Emite un warning en el log si usa el fallback en lugar del valor dedicado.
     */
    private function resolveSecret(string $envKey, string $purpose): string
    {
        $value = (string) ($_ENV[$envKey] ?? getenv($envKey) ?: '');
        if ($value !== '') {
            return $value;
        }

        // Fallback: derivar del APP_KEY
        $appKey = (string) ($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '');
        if ($appKey !== '') {
            error_log(
                "[BackupService] AVISO: {$envKey} no está configurado en .env. "
                . "Se usa un valor derivado de APP_KEY. "
                . "Se recomienda definir {$envKey} explícitamente en producción."
            );
            return hash('sha256', $purpose . '|' . $appKey);
        }

        // Último recurso: clave genérica fija + aviso (nunca lanza excepción)
        error_log(
            "[BackupService] ADVERTENCIA: Ni {$envKey} ni APP_KEY están configurados. "
            . "Se usa una clave de emergencia no segura para {$purpose}. "
            . "Configure APP_KEY o {$envKey} en .env."
        );
        return hash('sha256', $purpose . '|sistema_admin_fallback_insecure');
    }

    /**
     * Obtener clave de cifrado
     */
    private function getEncryptionKey(): string
    {
        $key = $this->resolveSecret('BACKUP_ENCRYPTION_KEY', 'backup_encryption');
        return substr(hash('sha256', $key), 0, 32);
    }

    /**
     * Registrar backup en la base de datos
     */
    private function registrarBackup(string $nombre, int $tamaño, bool $cifrado = false): void
    {
        // Crear tabla si no existe
        $this->database->query("
            CREATE TABLE IF NOT EXISTS backups_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                tamaño BIGINT NOT NULL,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                tipo ENUM('manual', 'automatico') DEFAULT 'manual',
                cifrado BOOLEAN DEFAULT FALSE,
                usuario_id INT,
                INDEX idx_fecha (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $this->database->query(
            "INSERT INTO backups_log (nombre, tamaño, cifrado, tipo) VALUES (?, ?, ?, 'manual')",
            [$nombre, $tamaño, $cifrado]
        );
    }
    
    /**
     * Listar backups disponibles
     */
    public function listarBackups(): array
    {
        $patterns = [
            "{$this->backupDir}/backup_completo_*.zip.enc",
            "{$this->backupDir}/backup_completo_*.tar.gz.enc",
            "{$this->backupDir}/backup_completo_*.zip",
            "{$this->backupDir}/backup_completo_*.tar.gz",
        ];
        $seen = [];
        $result = [];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $backup) {
                if (isset($seen[$backup]) || !is_file($backup)) {
                    continue;
                }
                $seen[$backup] = true;
                $result[] = [
                    'nombre' => basename($backup),
                    'tamaño' => filesize($backup),
                    'tamaño_formateado' => $this->formatBytes((int) filesize($backup)),
                    'fecha' => date('Y-m-d H:i:s', filemtime($backup)),
                    'ruta' => $backup,
                ];
            }
        }
        
        // Ordenar por fecha (más recientes primero)
        usort($result, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        return $result;
    }

    /**
     * Nombres de archivo generados por el sistema (misma regla que ValidationService::validateFileName).
     */
    private function esNombreBackupSistema(string $nombre): bool
    {
        return (bool) preg_match(
            '/^backup_completo_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.(zip|tar\.gz)(\.enc)?$/',
            $nombre
        );
    }

    /**
     * Elimina un archivo de backup del disco (solo dentro del directorio de backups).
     */
    public function eliminarArchivoBackup(string $nombreArchivo): array
    {
        if (!$this->esNombreBackupSistema($nombreArchivo)) {
            return ['success' => false, 'mensaje' => 'Nombre de archivo no válido'];
        }

        $dirReal = realpath($this->backupDir);
        if ($dirReal === false || !is_dir($dirReal)) {
            return ['success' => false, 'mensaje' => 'Directorio de backups no disponible'];
        }

        $ruta = $this->backupDir . DIRECTORY_SEPARATOR . $nombreArchivo;
        $fileReal = realpath($ruta);
        if ($fileReal === false || !is_file($fileReal)) {
            return ['success' => false, 'mensaje' => 'Archivo de backup no encontrado'];
        }

        $dirNorm = str_replace('\\', '/', $dirReal);
        $fileNorm = str_replace('\\', '/', $fileReal);
        if (!str_starts_with($fileNorm, rtrim($dirNorm, '/') . '/')) {
            return ['success' => false, 'mensaje' => 'Ruta no permitida'];
        }

        if (!@unlink($fileReal)) {
            return ['success' => false, 'mensaje' => 'No se pudo eliminar el archivo'];
        }

        $nombreLog = preg_replace('/\.(zip|tar\.gz)(\.enc)?$/', '', $nombreArchivo);
        try {
            $this->database->query('DELETE FROM backups_log WHERE nombre = ?', [$nombreLog]);
        } catch (\Throwable $e) {
            // Tabla opcional / sin filas: no fallar el borrado del archivo
        }

        $this->logEvent('INFO', 'Backup eliminado por administrador', ['archivo' => $nombreArchivo]);

        return ['success' => true, 'mensaje' => 'Backup eliminado correctamente'];
    }
    
    /**
     * Restaurar backup
     */
    public function restaurarBackup(string $backupFile): array
    {
        $tempDir = null;
        $decryptedPath = null;
        try {
            if (!file_exists($backupFile)) {
                throw new \Exception('Archivo de backup no encontrado');
            }

            $workPath = $backupFile;
            if (str_ends_with(strtolower($backupFile), '.enc')) {
                $decryptedPath = $this->backupDir . '/temp_decrypted_' . time() . '_' . bin2hex(random_bytes(4));
                if (!$this->decryptBackup($backupFile, $decryptedPath)) {
                    throw new \Exception('No se pudo descifrar el backup');
                }
                $workPath = $decryptedPath;
            }

            $tempDir = "{$this->backupDir}/temp_restore_" . time();
            mkdir($tempDir, 0755, true);

            $this->extractArchiveToDirectory($workPath, $tempDir);

            $dbFile = "{$tempDir}/database.sql";
            if (file_exists($dbFile)) {
                $this->restaurarDatabase($dbFile);
            }

            $this->deleteDirectory($tempDir);
            $tempDir = null;

            $this->logEvent('INFO', 'Backup restaurado', [
                'archivo' => basename($backupFile),
            ]);

            return [
                'success' => true,
                'mensaje' => 'Backup restaurado exitosamente',
            ];
        } catch (\Exception $e) {
            if ($tempDir !== null && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            $this->logEvent('ERROR', 'Error restaurando backup', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error al restaurar backup: ' . $e->getMessage(),
            ];
        } finally {
            if ($decryptedPath !== null && is_file($decryptedPath)) {
                @unlink($decryptedPath);
            }
        }
    }

    /**
     * Extrae un .zip (ZipArchive) o .tar.gz (PharData) al directorio indicado.
     */
    private function extractArchiveToDirectory(string $archivePath, string $destDir): void
    {
        $fh = fopen($archivePath, 'rb');
        if ($fh === false) {
            throw new \Exception('No se pudo leer el archivo de backup');
        }
        $magic = fread($fh, 4);
        fclose($fh);
        if ($magic === false || $magic === '') {
            throw new \Exception('Archivo de backup vacío o ilegible');
        }

        $isPkZip = strlen($magic) >= 2 && $magic[0] === 'P' && $magic[1] === 'K';
        $isGzip = strlen($magic) >= 2 && ord($magic[0]) === 0x1f && ord($magic[1]) === 0x8b;

        if ($isPkZip) {
            if (!extension_loaded('zip')) {
                throw new \Exception(
                    'Este backup está en formato ZIP. Active extension=zip en php.ini para restaurarlo desde aquí, '
                    . 'o descárguelo, descifre y descomprima manualmente.'
                );
            }
            $zip = new \ZipArchive();
            if ($zip->open($archivePath) !== true) {
                throw new \Exception('No se pudo abrir el archivo ZIP');
            }
            $zip->extractTo($destDir);
            $zip->close();

            return;
        }

        if ($isGzip) {
            if (!class_exists(\PharData::class, false)) {
                throw new \Exception('No se puede extraer tar.gz: la extensión Phar no está disponible.');
            }
            try {
                $phar = new \PharData($archivePath);
                $phar->extractTo($destDir, null, true);
            } catch (\Throwable $e) {
                throw new \Exception('No se pudo extraer el archivo tar.gz: ' . $e->getMessage());
            }

            return;
        }

        throw new \Exception('Formato de backup no reconocido (se esperaba ZIP o tar.gz).');
    }
    
    /**
     * Restaurar base de datos desde archivo SQL
     */
    private function restaurarDatabase(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);
        
        // Dividir en statements individuales
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !str_starts_with($stmt, '--');
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->database->query($statement);
            }
        }
    }
    
    /**
     * Eliminar directorio recursivamente
     */
    private function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = "{$dir}/{$file}";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
    
    /**
     * Copiar directorio recursivamente
     */
    private function copyDirectory(string $src, string $dst): void
    {
        if (!file_exists($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $files = array_diff(scandir($src), ['.', '..']);
        
        foreach ($files as $file) {
            $srcPath = "{$src}/{$file}";
            $dstPath = "{$dst}/{$file}";
            
            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    
    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->formatBytes($bytes, $precision);
    }
    
    /**
     * Descargar backup
     */
    public function descargarBackup(string $backupFile): void
    {
        if (!file_exists($backupFile)) {
            throw new \Exception('Archivo de backup no encontrado');
        }

        $base = basename($backupFile);
        $ct = 'application/octet-stream';
        if (str_ends_with(strtolower($base), '.zip')) {
            $ct = 'application/zip';
        } elseif (str_ends_with(strtolower($base), '.tar.gz')) {
            $ct = 'application/gzip';
        }

        header('Content-Type: ' . $ct);
        header('Content-Disposition: attachment; filename="' . $base . '"');
        header('Content-Length: ' . filesize($backupFile));

        readfile($backupFile);
        exit;
    }
}
