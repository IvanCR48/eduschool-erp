<?php
require_once __DIR__ . '/../includes/sistema_admin_session.php';

use SistemaAdmin\Services\ServicioAutenticacion;

try {
    $databaseAdapter = sistema_admin_db_adapter();
    $servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

    // Cerrar sesión usando el servicio directamente
    $resultado = $servicioAutenticacion->cerrarSesion();
    
    // Log del logout
    if ($resultado) {
        error_log("Logout exitoso para usuario: " . ($_SESSION['username'] ?? 'desconocido'));
    } else {
        error_log("Error en logout para usuario: " . ($_SESSION['username'] ?? 'desconocido'));
    }

} catch (Exception $e) {
    // En caso de error, cerrar sesión de forma básica
    error_log("Error en logout: " . $e->getMessage());
    
    // Limpiar sesión manualmente
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Redirigir al login
header('Location: login.php', true, 302);
exit();
?>