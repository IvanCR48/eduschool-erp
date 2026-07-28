<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Alertas y Notificaciones
 * 
 * Maneja el envío de alertas por diferentes canales (email, Slack, SMS)
 */
class AlertService extends BaseService
{
    private array $channels = [];
    private array $alertRules = [];
    private array $notificationHistory = [];

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
        $this->initializeChannels();
        $this->loadAlertRules();
    }

    /**
     * Inicializar canales de notificación
     */
    private function initializeChannels(): void
    {
        $this->channels = [
            'email' => [
                'enabled' => $this->getConfig('notificaciones.email_habilitado', true),
                'smtp_host' => $this->getConfig('notificaciones.smtp_host', 'localhost'),
                'smtp_port' => $this->getConfig('notificaciones.smtp_port', 587),
                'smtp_user' => $this->getConfig('notificaciones.smtp_user', ''),
                'smtp_pass' => $this->getConfig('notificaciones.smtp_pass', ''),
                'from_email' => $this->getConfig('notificaciones.from_email', ''),
                'from_name' => $this->getConfig('notificaciones.from_name', 'Sistema Escolar')
            ],
            'slack' => [
                'enabled' => !empty($this->getConfig('notificaciones.slack_webhook')),
                'webhook_url' => $this->getConfig('notificaciones.slack_webhook', ''),
                'channel' => $this->getConfig('notificaciones.slack_channel', '#sistema-admin'),
                'username' => $this->getConfig('notificaciones.slack_username', 'SistemaBot')
            ],
            'sms' => [
                'enabled' => !empty($this->getConfig('notificaciones.sms_api_key')),
                'api_key' => $this->getConfig('notificaciones.sms_api_key', ''),
                'provider' => $this->getConfig('notificaciones.sms_provider', 'twilio')
            ]
        ];
    }

    /**
     * Cargar reglas de alertas
     */
    private function loadAlertRules(): void
    {
        $this->alertRules = [
            'memory_high' => [
                'condition' => 'memory_usage > 90',
                'channels' => ['email', 'slack'],
                'priority' => 'high',
                'message' => 'Uso de memoria crítico: {memory_usage}%',
                'cooldown' => 300 // 5 minutos
            ],
            'disk_full' => [
                'condition' => 'disk_usage > 90',
                'channels' => ['email', 'slack', 'sms'],
                'priority' => 'critical',
                'message' => 'Espacio en disco crítico: {disk_usage}%',
                'cooldown' => 600 // 10 minutos
            ],
            'database_error' => [
                'condition' => 'database_errors > 10',
                'channels' => ['email', 'slack'],
                'priority' => 'high',
                'message' => 'Múltiples errores de base de datos detectados: {database_errors}',
                'cooldown' => 1800 // 30 minutos
            ],
            'login_attacks' => [
                'condition' => 'failed_logins > 50',
                'channels' => ['email', 'slack', 'sms'],
                'priority' => 'critical',
                'message' => 'Posible ataque de fuerza bruta detectado: {failed_logins} intentos fallidos',
                'cooldown' => 900 // 15 minutos
            ],
            'backup_failed' => [
                'condition' => 'backup_status == "failed"',
                'channels' => ['email', 'slack'],
                'priority' => 'high',
                'message' => 'Backup automático falló: {backup_error}',
                'cooldown' => 3600 // 1 hora
            ],
            'service_down' => [
                'condition' => 'service_status == "down"',
                'channels' => ['email', 'slack', 'sms'],
                'priority' => 'critical',
                'message' => 'Servicio crítico caído: {service_name}',
                'cooldown' => 300 // 5 minutos
            ]
        ];
    }

    /**
     * Enviar alerta personalizada
     */
    public function sendAlert(string $type, array $data, array $channels = []): bool
    {
        try {
            if (!isset($this->alertRules[$type])) {
                $this->logEvent('WARNING', "Tipo de alerta desconocido: {$type}");
                return false;
            }

            $rule = $this->alertRules[$type];
            
            // Verificar cooldown
            if ($this->isInCooldown($type, $rule['cooldown'])) {
                $this->logEvent('DEBUG', "Alerta {$type} en cooldown, omitiendo");
                return false;
            }

            // Preparar mensaje
            $message = $this->prepareMessage($rule['message'], $data);
            
            // Determinar canales a usar
            $targetChannels = empty($channels) ? $rule['channels'] : $channels;
            
            // Enviar por cada canal
            $success = true;
            foreach ($targetChannels as $channel) {
                if ($this->channels[$channel]['enabled']) {
                    $result = $this->sendToChannel($channel, $message, $rule['priority'], $data);
                    if (!$result) {
                        $success = false;
                    }
                }
            }

            // Registrar envío
            $this->recordNotification($type, $message, $targetChannels, $success);

            return $success;

        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error enviando alerta {$type}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar alerta por email
     */
    public function sendEmailAlert(array $recipients, string $subject, string $message, string $priority = 'normal'): bool
    {
        try {
            if (!$this->channels['email']['enabled']) {
                return false;
            }

            $emailConfig = $this->channels['email'];
            
            // Preparar headers
            $headers = [
                'From: ' . $emailConfig['from_name'] . ' <' . $emailConfig['from_email'] . '>',
                'Reply-To: ' . $emailConfig['from_email'],
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailer: SchoolAdmin v3.0.0'
            ];

            // Agregar prioridad
            if ($priority === 'high') {
                $headers[] = 'X-Priority: 1';
                $headers[] = 'X-MSMail-Priority: High';
            } elseif ($priority === 'critical') {
                $headers[] = 'X-Priority: 1';
                $headers[] = 'X-MSMail-Priority: High';
                $subject = '[CRÍTICO] ' . $subject;
            }

            // Preparar mensaje HTML
            $htmlMessage = $this->formatHtmlMessage($message, $priority);

            // Enviar a cada destinatario
            $success = true;
            foreach ($recipients as $recipient) {
                $result = mail($recipient, $subject, $htmlMessage, implode("\r\n", $headers));
                if (!$result) {
                    $success = false;
                    $this->logEvent('ERROR', "Error enviando email a {$recipient}");
                }
            }

            return $success;

        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error en sendEmailAlert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar alerta por Slack
     */
    public function sendSlackAlert(string $message, string $priority = 'normal', array $data = []): bool
    {
        try {
            if (!$this->channels['slack']['enabled']) {
                return false;
            }

            $slackConfig = $this->channels['slack'];
            
            // Determinar color y emoji
            $color = 'good';
            $emoji = '✅';
            
            switch ($priority) {
                case 'high':
                    $color = 'warning';
                    $emoji = '⚠️';
                    break;
                case 'critical':
                    $color = 'danger';
                    $emoji = '🚨';
                    break;
            }

            // Preparar payload
            $payload = [
                'channel' => $slackConfig['channel'],
                'username' => $slackConfig['username'],
                'icon_emoji' => ':computer:',
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => "{$emoji} Sistema Administrativo E.E.S.T N°2",
                        'text' => $message,
                        'fields' => [
                            [
                                'title' => 'Prioridad',
                                'value' => strtoupper($priority),
                                'short' => true
                            ],
                            [
                                'title' => 'Timestamp',
                                'value' => date('Y-m-d H:i:s'),
                                'short' => true
                            ],
                            [
                                'title' => 'Servidor',
                                'value' => gethostname(),
                                'short' => true
                            ]
                        ],
                        'footer' => 'Sistema de Monitoreo',
                        'ts' => time()
                    ]
                ]
            ];

            // Agregar campos adicionales si existen
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $payload['attachments'][0]['fields'][] = [
                        'title' => ucfirst(str_replace('_', ' ', $key)),
                        'value' => $value,
                        'short' => true
                    ];
                }
            }

            // Enviar a Slack
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $slackConfig['webhook_url']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                $this->logEvent('ERROR', "Error enviando a Slack. HTTP {$httpCode}: {$response}");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error en sendSlackAlert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar alerta por SMS
     */
    public function sendSmsAlert(array $recipients, string $message, string $priority = 'normal'): bool
    {
        try {
            if (!$this->channels['sms']['enabled']) {
                return false;
            }

            $smsConfig = $this->channels['sms'];
            $success = true;

            foreach ($recipients as $recipient) {
                $result = $this->sendSmsViaProvider($recipient, $message, $smsConfig);
                if (!$result) {
                    $success = false;
                }
            }

            return $success;

        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error en sendSmsAlert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Evaluar condiciones y enviar alertas automáticas
     */
    public function evaluateConditions(): void
    {
        try {
            // Obtener métricas del sistema
            $metrics = $this->getSystemMetrics();

            foreach ($this->alertRules as $type => $rule) {
                if ($this->evaluateCondition($rule['condition'], $metrics)) {
                    $this->sendAlert($type, $metrics, $rule['channels']);
                }
            }

        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error evaluando condiciones: " . $e->getMessage());
        }
    }

    /**
     * Enviar alerta de disaster recovery
     */
    public function sendDisasterAlert(int $level, string $message, array $additionalData = []): bool
    {
        $data = array_merge([
            'level' => $level,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => gethostname()
        ], $additionalData);

        // Determinar canales según nivel
        $channels = ['email', 'slack'];
        if ($level >= 3) {
            $channels[] = 'sms';
        }

        $priority = ($level >= 3) ? 'critical' : 'high';
        $subject = "[Nivel {$level}] Alerta de Disaster Recovery";

        $success = true;

        // Email a equipo completo
        $teamEmails = $this->getTeamEmails();
        $success &= $this->sendEmailAlert($teamEmails, $subject, $message, $priority);

        // Slack
        $success &= $this->sendSlackAlert($message, $priority, $data);

        // SMS para niveles críticos
        if ($level >= 3) {
            $criticalContacts = $this->getCriticalContacts();
            $success &= $this->sendSmsAlert($criticalContacts, $message, $priority);
        }

        return $success;
    }

    /**
     * Obtener métricas del sistema
     */
    private function getSystemMetrics(): array
    {
        $metrics = [];

        // Memoria
        $metrics['memory_usage'] = round((memory_get_usage(true) / memory_get_peak_usage(true)) * 100, 2);
        
        // Disco
        $diskTotal = disk_total_space(__DIR__);
        $diskFree = disk_free_space(__DIR__);
        $metrics['disk_usage'] = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

        // Base de datos
        try {
            $result = $this->database->fetch("SHOW STATUS LIKE 'Aborted_connects'");
            $metrics['database_errors'] = $result ? (int)$result['Value'] : 0;
        } catch (\Exception $e) {
            $metrics['database_errors'] = 999; // Error de conexión
        }

        // Logins fallidos (última hora)
        try {
            $result = $this->database->fetch(
                "SELECT COUNT(*) as count FROM logs_seguridad 
                 WHERE evento = 'login_fallido' AND creado_en >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $metrics['failed_logins'] = $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            $metrics['failed_logins'] = 0;
        }

        // Estado de servicios
        $metrics['service_status'] = $this->checkServiceStatus();
        $metrics['backup_status'] = $this->checkBackupStatus();

        return $metrics;
    }

    /**
     * Evaluar condición
     */
    private function evaluateCondition(string $condition, array $metrics): bool
    {
        $condition = trim($condition);

        // Soporte explícito: <variable> <operador> <literal>// Operadores permitidos: > < == != >=<=
        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(>=|<=|==|!=|>|<)\s*(.+)$/', $condition, $matches)) {
            $this->logEvent('WARNING', "Condición inválida o no soportada: {$condition}");
            return false;
        }

        $metricKey = $matches[1];
        $operator = $matches[2];
        $rawExpected = trim($matches[3]);

        if (!array_key_exists($metricKey, $metrics)) {
            $this->logEvent('WARNING', "Métrica inexistente en condición: {$metricKey}");
            return false;
        }

        $actualValue = $metrics[$metricKey];
        $expectedValue = $this->parseConditionValue($rawExpected);

        switch ($operator) {
            case '>':
                return is_numeric($actualValue) && is_numeric($expectedValue)
                    ? (float) $actualValue > (float) $expectedValue
                    : false;
            case '<':
                return is_numeric($actualValue) && is_numeric($expectedValue)
                    ? (float) $actualValue < (float) $expectedValue
                    : false;
            case '>=':
                return is_numeric($actualValue) && is_numeric($expectedValue)
                    ? (float) $actualValue >= (float) $expectedValue
                    : false;
            case '<=':
                return is_numeric($actualValue) && is_numeric($expectedValue)
                    ? (float) $actualValue <= (float) $expectedValue
                    : false;
            case '==':
                if (is_numeric($actualValue) && is_numeric($expectedValue)) {
                    return (float) $actualValue == (float) $expectedValue;
                }
                return (string) $actualValue === (string) $expectedValue;
            case '!=':
                if (is_numeric($actualValue) && is_numeric($expectedValue)) {
                    return (float) $actualValue != (float) $expectedValue;
                }
                return (string) $actualValue !== (string) $expectedValue;
            default:
                return false;
        }
    }

    /**
     * Parsea valores literales de condición de forma segura.
     */
    private function parseConditionValue(string $rawValue)
    {
        $rawValue = trim($rawValue);

        // Strings entre comillas simples o dobles
        if (preg_match('/^"(.*)"$/', $rawValue, $m) === 1) {
            return stripcslashes($m[1]);
        }
        if (preg_match("/^'(.*)'$/", $rawValue, $m) === 1) {
            return stripcslashes($m[1]);
        }

        // Booleanos explícitos
        $lower = strtolower($rawValue);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }

        // Números (int/float)
        if (is_numeric($rawValue)) {
            return str_contains($rawValue, '.') ? (float) $rawValue : (int) $rawValue;
        }

        // Fallback: string sin comillas
        return $rawValue;
    }

    /**
     * Verificar si está en cooldown
     */
    private function isInCooldown(string $type, int $cooldownSeconds): bool
    {
        try {
            $sql = "
                SELECT creado_en FROM notificaciones_enviadas 
                WHERE tipo = ? AND enviado = 1 
                ORDER BY creado_en DESC LIMIT 1
            ";
            $lastSent = $this->database->fetch($sql, [$type]);

            if (!$lastSent) {
                return false;
            }

            $lastSentTime = strtotime($lastSent['creado_en']);
            $cooldownTime = $lastSentTime + $cooldownSeconds;

            return time() < $cooldownTime;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Registrar notificación enviada
     */
    private function recordNotification(string $type, string $message, array $channels, bool $success): void
    {
        try {
            $sql = "
                INSERT INTO notificaciones_enviadas 
                (tipo, mensaje, canales, enviado, creado_en) 
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $this->database->query($sql, [
                $type,
                $message,
                json_encode($channels),
                $success ? 1 : 0
            ]);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', "Error registrando notificación: " . $e->getMessage());
        }
    }

    /**
     * Formatear mensaje HTML para email
     */
    private function formatHtmlMessage(string $message, string $priority): string
    {
        $color = '#28a745'; // Verde por defecto
        
        switch ($priority) {
            case 'high':
                $color = '#ffc107'; // Amarillo
                break;
            case 'critical':
                $color = '#dc3545'; // Rojo
                break;
        }

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .alert { padding: 15px; border-radius: 5px; border-left: 5px solid {$color}; background-color: #f8f9fa; }
                .footer { margin-top: 20px; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='alert'>
                <h3>Sistema Administrativo E.E.S.T N°2</h3>
                <p>{$message}</p>
                <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>Servidor:</strong> " . gethostname() . "</p>
            </div>
            <div class='footer'>Este es un mensaje automático del sistema de monitoreo.</div>
        </body>
        </html>
        ";
    }

    /**
     * Obtener emails del equipo
     */
    private function getTeamEmails(): array
    {
        // Configurable desde el panel de administración (notificaciones.team_emails)
        $configEmails = $this->getConfig('notificaciones.team_emails', '');
        if ($configEmails !== '') {
            return array_filter(array_map('trim', explode(',', $configEmails)));
        }
        return [];
    }

    /**
     * Obtener contactos críticos para SMS
     */
    private function getCriticalContacts(): array
    {
        // Configurable desde el panel de administración (notificaciones.sms_contacts)
        $configContacts = $this->getConfig('notificaciones.sms_contacts', '');
        if ($configContacts !== '') {
            return array_filter(array_map('trim', explode(',', $configContacts)));
        }
        return [];
    }

    /**
     * Verificar estado de servicios
     */
    private function checkServiceStatus(): string
    {
        // Verificar Apache
        $apacheStatus = shell_exec('systemctl is-active apache2 2>/dev/null');
        if (trim($apacheStatus) !== 'active') {
            return 'down';
        }

        // Verificar MySQL
        $mysqlStatus = shell_exec('systemctl is-active mysql 2>/dev/null');
        if (trim($mysqlStatus) !== 'active') {
            return 'down';
        }

        return 'up';
    }

    /**
     * Verificar estado de backups
     */
    private function checkBackupStatus(): string
    {
        try {
            $sql = "
                SELECT estado FROM historial_backups 
                ORDER BY creado_en DESC LIMIT 1
            ";
            $result = $this->database->fetch($sql);
            
            return $result ? $result['estado'] : 'unknown';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Obtener configuración
     */
    private function getConfig(string $key, $default = null)
    {
        try {
            $sql = "SELECT valor FROM configuraciones_sistema WHERE clave = ?";
            $result = $this->database->fetch($sql, [$key]);
            return $result ? $result['valor'] : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Enviar a canal específico
     */
    private function sendToChannel(string $channel, string $message, string $priority, array $data): bool
    {
        switch ($channel) {
            case 'email':
                $recipients = $this->getTeamEmails();
                return $this->sendEmailAlert($recipients, "Alerta del Sistema", $message, $priority);
                
            case 'slack':
                return $this->sendSlackAlert($message, $priority, $data);
                
            case 'sms':
                if ($priority === 'critical') {
                    $recipients = $this->getCriticalContacts();
                    return $this->sendSmsAlert($recipients, $message, $priority);
                }
                return true; // No enviar SMS para prioridades menores
                
            default:
                return false;
        }
    }

    /**
     * Enviar SMS via proveedor
     */
    private function sendSmsViaProvider(string $recipient, string $message, array $config): bool
    {
        // Implementación básica - se puede extender con Twilio, etc.
        $this->logEvent('INFO', "SMS enviado a {$recipient}: {$message}");
        return true;
    }

    /**
     * Preparar mensaje con variables
     */
    private function prepareMessage(string $template, array $data): string
    {
        $message = $template;
        
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
}
?>
