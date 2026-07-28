<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Adapters\DatabaseAdapter;

class SecurityLoggingService extends BaseService
{
    private DatabaseAdapter $database;
    private string $logFile;
    private array $logLevels = [
        'EMERGENCY' => 0,
        'ALERT' => 1,
        'CRITICAL' => 2,
        'ERROR' => 3,
        'WARNING' => 4,
        'NOTICE' => 5,
        'INFO' => 6,
        'DEBUG' => 7
    ];

    public function __construct(DatabaseAdapter $database)
    {
        parent::__construct();
        $this->database = $database;
        $this->logFile = __DIR__ . '/../../logs/security.log';
        $this->ensureLogDirectory();
    }

    /**
     * Log de eventos de seguridad críticos
     */
    public function logSecurityEvent(
        string $level,
        string $event,
        array $context = [],
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $userId = null
    ): void {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $userId = $userId ?? $_SESSION['user_id'] ?? 'anonymous';

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'event' => $event,
            'context' => $context,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'user_id' => $userId,
            'session_id' => session_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'server_name' => $_SERVER['SERVER_NAME'] ?? ''
        ];

        // Log a archivo
        $this->writeToFile($logEntry);

        // Log a base de datos para eventos críticos
        if (in_array($level, ['CRITICAL', 'ALERT', 'EMERGENCY'])) {
            $this->writeToDatabase($logEntry);
        }

        // Alertas para eventos críticos
        if (in_array($level, ['CRITICAL', 'ALERT', 'EMERGENCY'])) {
            $this->sendSecurityAlert($logEntry);
        }
    }

    /**
     * Log de intentos de ataque
     */
    public function logAttackAttempt(
        string $attackType,
        array $attackData = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        $this->logSecurityEvent(
            'CRITICAL',
            "ATTACK_ATTEMPT: {$attackType}",
            [
                'attack_type' => $attackType,
                'attack_data' => $attackData,
                'detection_method' => 'WAF/ModSecurity',
                'severity' => 'HIGH'
            ],
            $ip,
            $userAgent
        );
    }

    /**
     * Log de actividad sospechosa
     */
    public function logSuspiciousActivity(
        string $activity,
        array $details = [],
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $userId = null
    ): void {
        $this->logSecurityEvent(
            'WARNING',
            "SUSPICIOUS_ACTIVITY: {$activity}",
            [
                'activity' => $activity,
                'details' => $details,
                'severity' => 'MEDIUM'
            ],
            $ip,
            $userAgent,
            $userId
        );
    }

    /**
     * Log de autenticación
     */
    public function logAuthentication(
        string $event,
        bool $success,
        ?string $username = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        $level = $success ? 'INFO' : 'WARNING';
        
        $this->logSecurityEvent(
            $level,
            "AUTHENTICATION: {$event}",
            [
                'event' => $event,
                'success' => $success,
                'username' => $username,
                'severity' => $success ? 'LOW' : 'MEDIUM'
            ],
            $ip,
            $userAgent
        );
    }

    /**
     * Log de acceso a archivos sensibles
     */
    public function logSensitiveFileAccess(
        string $file,
        bool $authorized,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $userId = null
    ): void {
        $level = $authorized ? 'INFO' : 'WARNING';
        
        $this->logSecurityEvent(
            $level,
            "SENSITIVE_FILE_ACCESS: {$file}",
            [
                'file' => $file,
                'authorized' => $authorized,
                'severity' => $authorized ? 'LOW' : 'HIGH'
            ],
            $ip,
            $userAgent,
            $userId
        );
    }

    /**
     * Log de cambios en configuración
     */
    public function logConfigurationChange(
        string $setting,
        mixed $oldValue,
        mixed $newValue,
        ?string $userId = null
    ): void {
        $this->logSecurityEvent(
            'INFO',
            "CONFIGURATION_CHANGE: {$setting}",
            [
                'setting' => $setting,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'severity' => 'MEDIUM'
            ],
            null,
            null,
            $userId
        );
    }

    /**
     * Log de backups
     */
    public function logBackupOperation(
        string $operation,
        bool $success,
        array $details = [],
        ?string $userId = null
    ): void {
        $level = $success ? 'INFO' : 'ERROR';
        
        $this->logSecurityEvent(
            $level,
            "BACKUP_OPERATION: {$operation}",
            [
                'operation' => $operation,
                'success' => $success,
                'details' => $details,
                'severity' => $success ? 'LOW' : 'HIGH'
            ],
            null,
            null,
            $userId
        );
    }

    /**
     * Escribir log a archivo
     */
    private function writeToFile(array $logEntry): void
    {
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Escribir log a base de datos
     */
    private function writeToDatabase(array $logEntry): void
    {
        try {
            $sql = "INSERT INTO security_logs (
                timestamp, level, event, context, ip_address, 
                user_agent, user_id, session_id, request_uri, 
                request_method, server_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $logEntry['timestamp'],
                $logEntry['level'],
                $logEntry['event'],
                json_encode($logEntry['context']),
                $logEntry['ip'],
                $logEntry['user_agent'],
                $logEntry['user_id'],
                $logEntry['session_id'],
                $logEntry['request_uri'],
                $logEntry['request_method'],
                $logEntry['server_name']
            ];
            
            $this->database->execute($sql, $params);
        } catch (\Exception $e) {
            // Fallback: escribir a archivo si falla la BD
            error_log("Error writing to security log database: " . $e->getMessage());
            $this->writeToFile($logEntry);
        }
    }

    /**
     * Enviar alerta de seguridad
     */
    private function sendSecurityAlert(array $logEntry): void
    {
        // Implementar notificaciones por email/Slack/SMS
        $alertMessage = "ALERTA DE SEGURIDAD - {$logEntry['level']}\n";
        $alertMessage .= "Evento: {$logEntry['event']}\n";
        $alertMessage .= "IP: {$logEntry['ip']}\n";
        $alertMessage .= "Usuario: {$logEntry['user_id']}\n";
        $alertMessage .= "Timestamp: {$logEntry['timestamp']}\n";
        
        // Log de alerta
        error_log("SECURITY ALERT: " . $alertMessage);
        
        // Aquí se podría integrar con servicios de notificación
        // - Email: mail()
        // - Slack: cURL a webhook
        // - SMS: API de SMS
    }

    /**
     * Asegurar que el directorio de logs existe
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Obtener logs de seguridad
     */
    public function getSecurityLogs(
        int $limit = 100,
        ?string $level = null,
        ?string $ip = null,
        ?string $userId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM security_logs WHERE 1=1";
        $params = [];
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        if ($ip) {
            $sql .= " AND ip_address = ?";
            $params[] = $ip;
        }
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        if ($dateFrom) {
            $sql .= " AND timestamp >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND timestamp <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            return $this->database->fetchAll($sql, $params);
        } catch (\Exception $e) {
            error_log("Error retrieving security logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar logs de seguridad con filtros
     */
    public function countSecurityLogs(
        ?string $level = null,
        ?string $ip = null,
        ?string $userId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): int {
        $sql = "SELECT COUNT(*) as count FROM security_logs WHERE 1=1";
        $params = [];
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        if ($ip) {
            $sql .= " AND ip_address = ?";
            $params[] = $ip;
        }
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        if ($dateFrom) {
            $sql .= " AND timestamp >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND timestamp <= ?";
            $params[] = $dateTo;
        }
        
        try {
            $row = $this->database->fetchOne($sql, $params);
            return (int) ($row['count'] ?? 0);
        } catch (\Exception $e) {
            error_log("Error counting security logs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        try {
            $sql = "DELETE FROM security_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $result = $this->database->execute($sql, [$daysToKeep]);
            
            // Limpiar archivo de log también
            $this->cleanupLogFile($daysToKeep);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Error cleaning up security logs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpiar archivo de log
     */
    private function cleanupLogFile(int $daysToKeep): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $newLines = [];
        
        foreach ($lines as $line) {
            $logEntry = json_decode($line, true);
            if ($logEntry && isset($logEntry['timestamp'])) {
                $logTime = strtotime($logEntry['timestamp']);
                if ($logTime >= $cutoffTime) {
                    $newLines[] = $line;
                }
            }
        }
        
        file_put_contents($this->logFile, implode("\n", $newLines) . "\n");
    }

    /**
     * Obtener estadísticas de seguridad
     */
    public function getSecurityStats(): array
    {
        try {
            $stats = [];
            
            // Eventos por nivel en las últimas 24 horas
            $sql = "SELECT level, COUNT(*) as count FROM security_logs 
                   WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                   GROUP BY level";
            $stats['events_by_level'] = $this->database->fetchAll($sql);
            
            // Top IPs con más eventos
            $sql = "SELECT ip_address, COUNT(*) as count FROM security_logs 
                   WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                   GROUP BY ip_address ORDER BY count DESC LIMIT 10";
            $stats['top_ips'] = $this->database->fetchAll($sql);
            
            // Eventos críticos en las últimas 24 horas
            $sql = "SELECT COUNT(*) as count FROM security_logs 
                   WHERE level IN ('CRITICAL', 'ALERT', 'EMERGENCY') 
                   AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $result = $this->database->fetchOne($sql);
            $stats['critical_events_24h'] = $result['count'] ?? 0;
            
            return $stats;
        } catch (\Exception $e) {
            error_log("Error getting security stats: " . $e->getMessage());
            return [];
        }
    }
}
