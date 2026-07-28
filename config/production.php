<?php
/**
 * Configuración de Producción
 * 
 * Configuraciones específicas para el entorno de producción
 */

return [
    // Configuraciones de PHP
    'php' => [
        'display_errors' => false,
        'log_errors' => true,
        'error_reporting' => E_ALL & ~E_DEPRECATED & ~E_STRICT,
        'memory_limit' => '256M',
        'max_execution_time' => 300,
        'max_input_time' => 60,
        'post_max_size' => '50M',
        'upload_max_filesize' => '10M',
        'max_file_uploads' => 20,
        'expose_php' => false,
        'allow_url_fopen' => false,
        'allow_url_include' => false
    ],
    
    // Configuraciones de seguridad
    'security' => [
        'enable_mfa' => true,
        'enable_captcha' => true,
        'max_login_attempts' => 5,
        'login_timeout' => 900, // 15 minutos
        'session_timeout' => 1800, // 30 minutos
        'password_min_length' => 12,
        'password_require_special' => true,
        'enable_rate_limiting' => true,
        'rate_limit_requests' => 100,
        'rate_limit_window' => 3600, // 1 hora
        'enable_file_upload_security' => true,
        'max_file_size' => 5242880, // 5MB
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        'enable_audit_logging' => true,
        'log_retention_days' => 90
    ],
    
    // Configuraciones de base de datos
    'database' => [
        'enable_query_logging' => false,
        'enable_slow_query_log' => true,
        'slow_query_threshold' => 2.0, // segundos
        'connection_timeout' => 30,
        'enable_ssl' => true,
        'enable_compression' => true
    ],
    
    // Configuraciones de cache
    'cache' => [
        'enable_memory_cache' => true,
        'enable_database_cache' => true,
        'default_ttl' => 3600, // 1 hora
        'max_cache_size' => 104857600, // 100MB
        'enable_cache_compression' => true
    ],
    
    // Configuraciones de logging
    'logging' => [
        'log_level' => 'INFO',
        'enable_file_logging' => true,
        'enable_database_logging' => true,
        'log_rotation' => true,
        'max_log_file_size' => 10485760, // 10MB
        'max_log_files' => 10,
        'enable_error_logging' => true,
        'enable_security_logging' => true,
        'enable_audit_logging' => true
    ],
    
    // Configuraciones de monitoreo
    'monitoring' => [
        'enable_performance_monitoring' => true,
        'enable_security_monitoring' => true,
        'enable_error_monitoring' => true,
        'enable_uptime_monitoring' => true,
        'alert_on_errors' => true,
        'alert_on_security_events' => true,
        'alert_threshold_errors' => 10,
        'alert_threshold_security' => 5
    ],
    
    // Configuraciones de backup
    'backup' => [
        'enable_automatic_backup' => true,
        'backup_frequency' => 'daily',
        'backup_retention_days' => 30,
        'enable_database_backup' => true,
        'enable_file_backup' => true,
        'backup_compression' => true,
        'backup_encryption' => true
    ],
    
    // Configuraciones de mantenimiento
    'maintenance' => [
        'enable_maintenance_mode' => false,
        'maintenance_message' => 'Sistema en mantenimiento. Intente nuevamente más tarde.',
        'enable_automatic_cleanup' => true,
        'cleanup_frequency' => 'daily',
        'cleanup_old_logs' => true,
        'cleanup_old_sessions' => true,
        'cleanup_temp_files' => true
    ]
];
