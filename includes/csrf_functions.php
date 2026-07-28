<?php
/**
 * Funciones CSRF para el sistema
 * Este archivo contiene solo las funciones necesarias para CSRF
 * sin generar HTML
 */

// Función global para obtener token CSRF
function getCSRFToken() {
    if (!isset($GLOBALS['servicioSeguridad'])) {
        require_once __DIR__ . '/database_bootstrap.php';
        $GLOBALS['servicioSeguridad'] = new SistemaAdmin\Services\ServicioSeguridad(sistema_admin_db_adapter());
    }
    return $GLOBALS['servicioSeguridad']->generarTokenCSRF();
}

// Función global para verificar token CSRF
function verifyCSRFToken($token) {
    if (!isset($GLOBALS['servicioSeguridad'])) {
        require_once __DIR__ . '/database_bootstrap.php';
        $GLOBALS['servicioSeguridad'] = new SistemaAdmin\Services\ServicioSeguridad(sistema_admin_db_adapter());
    }
    return $GLOBALS['servicioSeguridad']->verificarTokenCSRF($token);
}
?>
