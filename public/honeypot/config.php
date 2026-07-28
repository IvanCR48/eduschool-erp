<?php
/**
 * HONEYPOT - Fake Configuration File
 * Este archivo simula un archivo de configuración expuesto
 * para detectar bots que buscan archivos sensibles.
 */

// Log de acceso sospechoso
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'honeypot' => 'config_file',
    'suspicious' => true,
    'severity' => 'HIGH'
];

// Escribir a log de honeypot
$logFile = __DIR__ . '/../../logs/honeypot.log';
$logLine = json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n";
file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// Headers para simular archivo de configuración
header('Content-Type: text/plain');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Simular archivo de configuración con datos falsos
echo "# Fake Configuration File - HONEYPOT\n";
echo "# This file is designed to detect automated attacks\n";
echo "# Any access attempt will be logged and monitored\n\n";

echo "<?php\n";
echo "// Database Configuration (FAKE)\n";
echo "define('DB_HOST', 'localhost');\n";
echo "define('DB_USER', 'admin');\n";
echo "define('DB_PASS', 'super_secret_password_123');\n";
echo "define('DB_NAME', 'sistema_admin');\n\n";

echo "// API Keys (FAKE)\n";
echo "define('API_KEY', 'sk-1234567890abcdef');\n";
echo "define('SECRET_KEY', 'secret_key_12345');\n";
echo "define('JWT_SECRET', 'jwt_secret_67890');\n\n";

echo "// Admin Credentials (FAKE)\n";
echo "define('ADMIN_USER', 'admin');\n";
echo "define('ADMIN_PASS', 'admin123');\n";
echo "define('SUPER_ADMIN', 'root');\n";
echo "define('SUPER_PASS', 'root_password');\n\n";

echo "// Server Configuration (FAKE)\n";
echo "define('SERVER_IP', '192.168.1.100');\n";
echo "define('BACKUP_PATH', '/var/backups/');\n";
echo "define('LOG_PATH', '/var/logs/');\n";
echo "define('UPLOAD_PATH', '/var/uploads/');\n\n";

echo "// Security Settings (FAKE)\n";
echo "define('ENCRYPTION_KEY', 'encryption_key_abcdef');\n";
echo "define('SESSION_SECRET', 'session_secret_xyz');\n";
echo "define('CSRF_SECRET', 'csrf_secret_123');\n\n";

echo "// External Services (FAKE)\n";
echo "define('EMAIL_SMTP', 'smtp.gmail.com');\n";
echo "define('EMAIL_USER', 'admin@example.com');\n";
echo "define('EMAIL_PASS', 'email_password_123');\n";
echo "define('SLACK_WEBHOOK', '');\n";
echo "define('AWS_ACCESS_KEY', 'YOUR_AWS_ACCESS_KEY');\n";
echo "define('AWS_SECRET_KEY', 'YOUR_AWS_SECRET_KEY');\n\n";

echo "// Development Settings (FAKE)\n";
echo "define('DEBUG_MODE', true);\n";
echo "define('SHOW_ERRORS', true);\n";
echo "define('LOG_LEVEL', 'DEBUG');\n";
echo "define('CACHE_ENABLED', false);\n\n";

echo "// WARNING: This is a HONEYPOT file!\n";
echo "// All credentials above are FAKE and used for attack detection\n";
echo "// Your IP and user agent have been logged: " . $_SERVER['REMOTE_ADDR'] . "\n";
echo "// Access time: " . date('Y-m-d H:i:s') . "\n";
?>
