<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Logging
 *
 * Separación explícita:
 * - **Logs legales / auditoría**: `logs_auditoria` y `audit.log` — inmutables en este servicio: no se purgan
 *   ni rotan (sin borrado automático). La retención a largo plazo es vía archivo frío (`archiveHistoricalAudit`).
 * - **Logs efímeros**: `logs_seguridad`, `security.log`, `error.log` (y rotaciones numeradas) — mantenimiento
 *   por antigüedad para no saturar disco.
 */
class ServicioLogging extends BaseService
{
    /** Archivo de auditoría legal: sin rotación ni purga automática. */
    private const AUDIT_LOG_FILENAME = 'audit.log.php';

    /** Prefijos de archivos de log operativos (pueden rotarse y purgarse rotaciones antiguas). */
    private const EPHEMERAL_LOG_PREFIXES = ['security.log.php', 'error.log.php'];

    private string $logDirectory;
    private int $maxLogSize = 10485760; // 10MB
    private int $maxLogFiles = 5;

    public function __construct(DatabaseInterface $database, string $logDirectory = 'logs')
    {
        parent::__construct($database);
        $this->logDirectory = $logDirectory;
        
        // Crear directorio de logs si no existe
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }

    /**
     * Registrar evento de seguridad
     */
    public function registrarEventoSeguridad(string $tipo, string $descripcion, array $datos = []): void
    {
        $evento = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'ip' => $this->obtenerIPCliente(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'datos' => $datos
        ];

        // Guardar en archivo de log
        $this->escribirLogArchivo('security.log.php', $evento);

        // Guardar en base de datos si es crítico
        if (in_array($tipo, ['LOGIN_FAILED', 'MFA_FAILED', 'UNAUTHORIZED_ACCESS', 'CSRF_ATTACK', 'RATE_LIMIT_EXCEEDED'])) {
            $this->guardarEnBaseDatos($evento);
        }
    }

    /**
     * Registrar evento de auditoría
     */
    public function registrarEventoAuditoria(string $accion, string $entidad, ?int $entidadId = null, array $datos = []): void
    {
        $datos = $this->limitarTamanoDatosAuditoria($datos);

        $evento = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => 'AUDIT',
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'ip' => $this->obtenerIPCliente(),
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'datos' => $datos,
        ];

        // Guardar en archivo de log
        $this->escribirLogArchivo(self::AUDIT_LOG_FILENAME, $evento);

        // Guardar en base de datos
        $this->guardarEnBaseDatos($evento);
    }

    /**
     * Registrar error del sistema
     */
    public function registrarError(string $mensaje, string $archivo = '', int $linea = 0, array $contexto = []): void
    {
        // Sanitizar entrada para prevenir log injection
        $mensaje = $this->sanitizeLogMessage($mensaje);
        $archivo = $this->sanitizeLogMessage($archivo);
        
        // Sanitizar contexto
        $contexto = $this->sanitizeLogContext($contexto);
        
        $evento = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => 'ERROR',
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'linea' => $linea,
            'ip' => $this->obtenerIPCliente(),
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'contexto' => $contexto
        ];

        // Guardar en archivo de log
        $this->escribirLogArchivo('error.log.php', $evento);
    }

    /**
     * Sanitizar mensaje de log para prevenir log injection
     */
    private function sanitizeLogMessage(string $message): string
    {
        $utilityService = new UtilityService($this->database);
        $message = $utilityService->sanitizeLogMessage($message);
        
        // Remover caracteres de escape peligrosos
        $message = str_replace(['\\', '/', '"', "'", '`'], '', $message);
        
        // Limitar longitud
        $message = substr($message, 0, 5000);
        
        return trim($message);
    }

    /**
     * Sanitizar contexto de log
     */
    private function sanitizeLogContext(array $context): array
    {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            $cleanKey = $this->sanitizeLogMessage((string)$key);
            
            if (is_string($value)) {
                $cleanValue = $this->sanitizeLogMessage($value);
            } elseif (is_array($value)) {
                $cleanValue = $this->sanitizeLogContext($value);
            } else {
                $cleanValue = $value;
            }
            
            $sanitized[$cleanKey] = $cleanValue;
        }
        
        return $sanitized;
    }

    /**
     * Obtener logs de seguridad
     */
    public function obtenerLogsSeguridad(int $limite = 100, string $tipo = null): array
    {
        $sql = "SELECT * FROM logs_seguridad";
        $params = [];

        if ($tipo) {
            $sql .= " WHERE tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limite;

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Obtener logs de auditoría
     */
    public function obtenerLogsAuditoria(int $limite = 100, string $entidad = null): array
    {
        $sql = "SELECT * FROM logs_auditoria";
        $params = [];

        if ($entidad) {
            $sql .= " WHERE entidad = ?";
            $params[] = $entidad;
        }

        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limite;

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Purga solo logs **efímeros** (operativos). No toca `logs_auditoria` ni `audit.log`.
     *
     * @return int Filas borradas en BD + archivos de rotación efímeros eliminados
     */
    public function limpiarLogsAntiguos(int $dias = 30): int
    {
        $fecha = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $sql = 'DELETE FROM logs_seguridad WHERE timestamp < ?';
        $stmt = $this->database->query($sql, [$fecha]);
        $eliminados = $stmt->rowCount();

        $eliminados += $this->limpiarRotacionesEphemerasAntiguas($dias);

        return $eliminados;
    }

    /**
     * Alias en inglés de {@see limpiarLogsAntiguos} (misma política: auditoría excluida).
     */
    public function cleanOldLogs(int $days = 30): int
    {
        return $this->limpiarLogsAntiguos($days);
    }

    /**
     * Exporta auditoría histórica a CSV o SQL **sin** borrar filas de la BD por defecto.
     *
     * Política: los DELETE masivos sobre `logs_auditoria` están prohibidos en el flujo habitual;
     * tras verificar el archivo en almacenamiento frío y normativa interna, un operador humano
     * podría ejecutar purgas puntuales fuera de este servicio.
     *
     * @param string $hastaExclusivo Fecha/datetime ISO: se exportan filas con `timestamp` estrictamente anteriores
     * @param string $directorioDestino Directorio donde escribir (se crea si no existe)
     * @param 'csv'|'sql' $formato
     * @param int $loteMáximo Máximo de filas por consulta (memoria)
     * @return array{ok: bool, path?: string, filas?: int, error?: string}
     */
    public function archiveHistoricalAudit(
        string $hastaExclusivo,
        string $directorioDestino,
        string $formato = 'csv',
        int $loteMáximo = 2000
    ): array {
        $formato = strtolower($formato);
        if (!in_array($formato, ['csv', 'sql'], true)) {
            return ['ok' => false, 'error' => 'formato debe ser csv o sql'];
        }

        $ts = strtotime($hastaExclusivo);
        if ($ts === false) {
            return ['ok' => false, 'error' => 'Fecha hasta inválida'];
        }
        $hastaSql = date('Y-m-d H:i:s', $ts);

        if (!is_dir($directorioDestino)) {
            if (!@mkdir($directorioDestino, 0750, true) && !is_dir($directorioDestino)) {
                return ['ok' => false, 'error' => 'No se pudo crear el directorio de destino'];
            }
        }

        $slug = preg_replace('/[^0-9T\-_]/', '_', $hastaExclusivo);
        $suffix = $formato === 'csv' ? 'csv' : 'sql';
        $nombre = 'audit_archive_' . $slug . '_' . date('Ymd_His') . '.' . $suffix;
        $ruta = rtrim($directorioDestino, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nombre;

        $fh = @fopen($ruta, 'wb');
        if ($fh === false) {
            return ['ok' => false, 'error' => 'No se pudo abrir el archivo de destino'];
        }

        $total = 0;
        $ultimoId = 0;

        try {
            if ($formato === 'csv') {
                fwrite($fh, "\xEF\xBB\xBF");
                fputcsv($fh, ['id', 'timestamp', 'accion', 'entidad', 'entidad_id', 'ip', 'usuario_id', 'datos_json'], ';');
            } else {
                fwrite($fh, "-- Archivo generado por SistemaAdmin::archiveHistoricalAudit\n");
                fwrite($fh, '-- Hasta (exclusivo): ' . $hastaSql . "\n");
                fwrite($fh, "-- NO ejecutar DELETE en origen sin proceso legal verificado.\n\n");
                fwrite($fh, "SET NAMES utf8mb4;\n\n");
            }

            while (true) {
                $sql = 'SELECT id, timestamp, accion, entidad, entidad_id, ip, usuario_id, datos
                        FROM logs_auditoria
                        WHERE timestamp < ? AND id > ?
                        ORDER BY id ASC
                        LIMIT ' . max(1, min(10000, $loteMáximo));
                $filas = $this->database->fetchAll($sql, [$hastaSql, $ultimoId]);
                if ($filas === []) {
                    break;
                }

                foreach ($filas as $fila) {
                    $ultimoId = (int) ($fila['id'] ?? 0);
                    $datosJson = is_string($fila['datos'] ?? null)
                        ? (string) $fila['datos']
                        : json_encode($fila['datos'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    if ($formato === 'csv') {
                        fputcsv($fh, [
                            (string) ($fila['id'] ?? ''),
                            (string) ($fila['timestamp'] ?? ''),
                            (string) ($fila['accion'] ?? ''),
                            (string) ($fila['entidad'] ?? ''),
                            (string) ($fila['entidad_id'] ?? ''),
                            (string) ($fila['ip'] ?? ''),
                            (string) ($fila['usuario_id'] ?? ''),
                            $datosJson,
                        ], ';');
                    } else {
                        $eid = $fila['entidad_id'] ?? null;
                        $eidSql = ($eid === null || $eid === '') ? 'NULL' : (string) (int) $eid;
                        $uid = $fila['usuario_id'] ?? null;
                        $uidSql = ($uid === null || $uid === '') ? 'NULL' : (string) (int) $uid;
                        $vals = [
                            $this->sqlQuote((string) ($fila['timestamp'] ?? '')),
                            $this->sqlQuote((string) ($fila['accion'] ?? '')),
                            $this->sqlQuote((string) ($fila['entidad'] ?? '')),
                            $eidSql,
                            $this->sqlQuote((string) ($fila['ip'] ?? '')),
                            $uidSql,
                            $this->sqlQuote($datosJson),
                        ];
                        fwrite(
                            $fh,
                            'INSERT INTO logs_auditoria (timestamp, accion, entidad, entidad_id, ip, usuario_id, datos) VALUES ('
                            . implode(', ', $vals) . ");\n"
                        );
                    }
                    $total++;
                }
            }

            fclose($fh);

            return ['ok' => true, 'path' => $ruta, 'filas' => $total];
        } catch (\Throwable $e) {
            if (isset($fh) && is_resource($fh)) {
                fclose($fh);
            }
            if (isset($ruta) && is_file($ruta)) {
                @unlink($ruta);
            }

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function sqlQuote(string $s): string
    {
        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $s) . "'";
    }

    /**
     * Elimina solo rotaciones numeradas de logs efímeros (security.log.N, error.log.N) más antiguas que $días.
     * No elimina el archivo principal en uso ni ningún archivo de auditoría.
     */
    private function limpiarRotacionesEphemerasAntiguas(int $dias): int
    {
        $cutoff = strtotime("-{$dias} days");
        if ($cutoff === false) {
            return 0;
        }

        $eliminados = 0;
        $dir = rtrim($this->logDirectory, DIRECTORY_SEPARATOR);
        foreach (self::EPHEMERAL_LOG_PREFIXES as $prefix) {
            $pat = $dir . DIRECTORY_SEPARATOR . $prefix . '.*';
            foreach (glob($pat, GLOB_NOSORT) ?: [] as $path) {
                if (!is_file($path)) {
                    continue;
                }
                $base = basename($path);
                if (!preg_match('/^' . preg_quote($prefix, '/') . '\.(\d+)$/', $base)) {
                    continue;
                }
                $mt = @filemtime($path);
                if ($mt !== false && $mt < $cutoff) {
                    if (@unlink($path)) {
                        $eliminados++;
                    }
                }
            }
        }

        return $eliminados;
    }

    /**
     * Evita líneas de auditoría gigantes en disco (p. ej. listas masivas de asistencia).
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function limitarTamanoDatosAuditoria(array $datos): array
    {
        if (isset($datos['cambios']) && is_array($datos['cambios']) && count($datos['cambios']) > 100) {
            $total = count($datos['cambios']);
            $datos['cambios'] = array_slice($datos['cambios'], 0, 100);
            $datos['cambios_total'] = $total;
            $datos['cambios_truncados'] = true;
        }

        return $datos;
    }

    private function escribirLogArchivo(string $archivo, array $evento): void
    {
        $rutaArchivo = $this->logDirectory . '/' . $archivo;

        // Auditoría legal: sin rotación automática (evita unlink / pérdida de trazas en frío caliente).
        if ($archivo !== self::AUDIT_LOG_FILENAME) {
            if (file_exists($rutaArchivo) && filesize($rutaArchivo) > $this->maxLogSize) {
                $this->rotarArchivoLog($rutaArchivo);
            }
        }

        $esNuevo = !file_exists($rutaArchivo);
        $linea = json_encode($evento) . "\n";
        
        if ($esNuevo) {
            $linea = "<?php exit; ?>\n" . $linea;
        }

        file_put_contents($rutaArchivo, $linea, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotar archivo de log
     */
    private function rotarArchivoLog(string $archivo): void
    {
        // Mover archivos existentes
        for ($i = $this->maxLogFiles - 1; $i > 0; $i--) {
            $archivoActual = $archivo . '.' . $i;
            $archivoSiguiente = $archivo . '.' . ($i + 1);
            
            if (file_exists($archivoActual)) {
                if ($i === $this->maxLogFiles - 1) {
                    unlink($archivoActual);
                } else {
                    rename($archivoActual, $archivoSiguiente);
                }
            }
        }

        // Mover archivo principal
        if (file_exists($archivo)) {
            rename($archivo, $archivo . '.1');
        }
    }

    /**
     * Guardar evento en base de datos
     */
    private function guardarEnBaseDatos(array $evento): void
    {
        try {
            if ($evento['tipo'] === 'AUDIT') {
                $sql = "INSERT INTO logs_auditoria (timestamp, accion, entidad, entidad_id, ip, usuario_id, datos) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $datosJson = json_encode(
                    $evento['datos'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                );
                if ($datosJson === false) {
                    $datosJson = '{"_error":"json_encode_failed"}';
                } elseif (strlen($datosJson) > 65535) {
                    $datosJson = json_encode([
                        '_truncated' => true,
                        'accion' => $evento['accion'],
                        'entidad' => $evento['entidad'],
                        'preview' => mb_substr($datosJson, 0, 8000),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $params = [
                    $evento['timestamp'],
                    $evento['accion'],
                    $evento['entidad'],
                    $evento['entidad_id'],
                    $evento['ip'],
                    $evento['usuario_id'],
                    $datosJson,
                ];
            } else {
                $sql = "INSERT INTO logs_seguridad (timestamp, tipo, descripcion, ip, usuario_id, datos) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $params = [
                    $evento['timestamp'],
                    $evento['tipo'],
                    $evento['descripcion'],
                    $evento['ip'],
                    $evento['usuario_id'],
                    json_encode($evento['datos'])
                ];
            }

            $this->database->query($sql, $params);
        } catch (\Exception $e) {
            // Si falla la base de datos, al menos guardar en archivo
            error_log("Error guardando log en BD: " . $e->getMessage());
        }
    }

    /**
     * Obtener IP del cliente
     */
    protected function obtenerIPCliente(): string
    {
        $utilityService = new UtilityService($this->database);
        return $utilityService->obtenerIPCliente();
    }

    /**
     * Crear tablas de logs si no existen
     */
    public function crearTablasLogs(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS logs_seguridad (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                descripcion TEXT NOT NULL,
                ip VARCHAR(45) NOT NULL,
                usuario_id INT NULL,
                datos JSON NULL,
                INDEX idx_timestamp (timestamp),
                INDEX idx_tipo (tipo),
                INDEX idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->database->query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS logs_auditoria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                accion VARCHAR(100) NOT NULL,
                entidad VARCHAR(100) NOT NULL,
                entidad_id INT NULL,
                ip VARCHAR(45) NOT NULL,
                usuario_id INT NULL,
                datos JSON NULL,
                INDEX idx_timestamp (timestamp),
                INDEX idx_entidad (entidad),
                INDEX idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->database->query($sql);
    }
}
