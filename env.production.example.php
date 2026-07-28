<?php
// ==========================================
// SistemaAdmin - Configuración de Producción
// ==========================================
// Este archivo protege tus credenciales al ser 
// interpretado por PHP en lugar de servido como texto.
// 
// RENOMBRAR ESTE ARCHIVO A: env.php
// ==========================================

return [
    // Institución
    'SCHOOL_NAME' => 'Nombre de la Institución',
    'SCHOOL_SLOGAN' => 'Eslogan o lema institucional',

    // Entorno
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_KEY' => 'CHANGE_ME_GENERATE_A_LONG_RANDOM_KEY',

    // URL pública (Sin barra final)
    'APP_URL' => 'https://tu-dominio.com',
    
    // Ruta base ("/" si corre en raíz; "/SistemaAdmin" si corre en subcarpeta)
    'APP_BASE_PATH' => '/',

    // Base de datos
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'CHANGE_ME_DB_NAME',
    'DB_USER' => 'CHANGE_ME_DB_USER',
    'DB_PASS' => 'CHANGE_ME_DB_PASSWORD',

    // Seguridad de sesión / login
    'SESSION_LIFETIME' => '120',
    'MAX_LOGIN_ATTEMPTS' => '5',

    // Claves de backup/descarga (generar con alta entropía)
    'BACKUP_ENCRYPTION_KEY' => 'CHANGE_ME_BASE64_32_OR_MORE',
    'BACKUP_DOWNLOAD_SECRET' => 'CHANGE_ME_BASE64_32_OR_MORE',

    // Soporte
    'SUPPORT_EMAIL' => 'soporte@tu-dominio.com',

    // Logs
    'LOG_LEVEL' => 'error',
];
