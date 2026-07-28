<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Sesiones
 * 
 * Maneja únicamente las operaciones de sesión
 */
class SessionService extends BaseService
{
    private int $sessionTimeout = 1800; // sobrescrito en iniciar() según configuración
    private string $lastActivityKey = 'last_activity';

    private function resolverTimeoutSeguridad(): int
    {
        if (isset($GLOBALS['SA_SESSION_INACTIVITY_SECONDS'])) {
            $s = (int) $GLOBALS['SA_SESSION_INACTIVITY_SECONDS'];

            return max(300, min(604800, $s));
        }

        return 1800;
    }

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
        $this->iniciar();
    }

    /**
     * Iniciar sesión
     */
    public function iniciar(): void
    {
        // Siempre alinear con configuración (sesión puede haberse abierto antes vía sistema_admin_session.php).
        $this->sessionTimeout = $this->resolverTimeoutSeguridad();

        if (session_status() === PHP_SESSION_NONE) {
            // Solo configurar si no se han enviado headers
            if (!headers_sent()) {
                ini_set('session.gc_maxlifetime', (string) $this->sessionTimeout);
                ini_set('session.cookie_httponly', 1);
                ini_set('session.cookie_secure', $this->isHttps());
                ini_set('session.use_strict_mode', 1);
                ini_set('session.cookie_samesite', 'Lax');
            }

            session_start();
            
            // Verificar timeout de sesión
            $this->verificarTimeout();
            
            // Actualizar última actividad
            $this->actualizarActividad();
        }
    }

    /**
     * Verificar si hay sesión activa
     */
    public function tieneSesion(): bool
    {
        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            return false;
        }
        
        // Verificar timeout
        if (!$this->verificarTimeout()) {
            return false;
        }
        
        // Verificar contra la base de datos si la sesión sigue activa
        $sessId = session_id();
        if ($sessId) {
            try {
                $sessRow = $this->database->fetch(
                    "SELECT activa FROM sesiones_usuarios WHERE session_id = ? LIMIT 1",
                    [$sessId]
                );
                
                if ($sessRow) {
                    if ((int)($sessRow['activa'] ?? 1) === 0) {
                        $this->cerrar();
                        return false;
                    }
                    
                    // Actualizar última actividad en la base de datos
                    $this->database->query(
                        "UPDATE sesiones_usuarios SET ultima_actividad = NOW() WHERE session_id = ?",
                        [$sessId]
                    );
                } else {
                    // Registrar sesión para compatibilidad
                    $usuarioId = (int)$_SESSION['usuario_id'];
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $this->database->query(
                        "INSERT INTO sesiones_usuarios (usuario_id, session_id, ip_address, user_agent, activa, creado_en, ultima_actividad)
                         VALUES (?, ?, ?, ?, 1, NOW(), NOW())",
                        [$usuarioId, $sessId, $ip, $userAgent]
                    );
                }
            } catch (\Throwable $e) {
                // Silenciar errores de BD para no bloquear la app
            }
        }
        
        // Actualizar actividad en cada verificación
        $this->actualizarActividad();
        
        return true;
    }

    /**
     * Obtener ID del usuario de la sesión
     */
    public function obtenerUsuarioId(): ?int
    {
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Obtener datos del usuario de la sesión
     */
    public function obtenerDatosUsuario(): ?array
    {
        if (!$this->tieneSesion()) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'],
            'username' => $_SESSION['username'] ?? null,
            'nombre' => $_SESSION['nombre'] ?? null,
            'apellido' => $_SESSION['apellido'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'rol' => $_SESSION['rol'] ?? null
        ];
    }

    /**
     * Establecer datos del usuario en la sesión
     */
    public function establecerUsuario(array $usuario): void
    {
        $this->iniciar();
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellido'] = $usuario['apellido'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];
    }

    /**
     * Cerrar sesión
     */
    public function cerrar(): void
    {
        $this->iniciar();
        
        // Marcar sesión como inactiva en la base de datos antes de limpiar
        $sessId = session_id();
        if ($sessId) {
            try {
                $this->database->query(
                    "UPDATE sesiones_usuarios SET activa = 0 WHERE session_id = ?",
                    [$sessId]
                );
            } catch (\Throwable $e) {
                // Silenciar
            }
        }
        
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }

    /**
     * Regenerar ID de sesión
     */
    public function regenerarId(): void
    {
        $this->iniciar();
        session_regenerate_id(true);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol(string $rol): bool
    {
        return ($_SESSION['rol'] ?? '') === $rol;
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public function tieneAlgunRol(array $roles): bool
    {
        $rolUsuario = $_SESSION['rol'] ?? '';
        return in_array($rolUsuario, $roles);
    }

    /**
     * Obtener rol del usuario
     */
    public function obtenerRol(): ?string
    {
        return $_SESSION['rol'] ?? null;
    }

    /**
     * Establecer variable de sesión
     */
    public function establecer(string $clave, $valor): void
    {
        $this->iniciar();
        $_SESSION[$clave] = $valor;
    }

    /**
     * Obtener variable de sesión
     */
    public function obtener(string $clave, $default = null)
    {
        return $_SESSION[$clave] ?? $default;
    }

    /**
     * Eliminar variable de sesión
     */
    public function eliminar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    /**
     * Verificar si existe una variable de sesión
     */
    public function existe(string $clave): bool
    {
        return isset($_SESSION[$clave]);
    }

    /**
     * Verificar timeout de sesión
     */
    private function verificarTimeout(): bool
    {
        if (!isset($_SESSION[$this->lastActivityKey])) {
            return true; // Primera vez, no hay timeout
        }

        $lastActivity = $_SESSION[$this->lastActivityKey];
        $timeElapsed = time() - $lastActivity;

        if ($timeElapsed > $this->sessionTimeout) {
            // Sesión expirada
            $this->cerrar();
            return false;
        }

        return true;
    }

    /**
     * Actualizar timestamp de última actividad
     */
    private function actualizarActividad(): void
    {
        $_SESSION[$this->lastActivityKey] = time();
    }

    /**
     * Verificar si la conexión es HTTPS
     */
    private function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }

    /**
     * Obtener tiempo restante de sesión
     */
    public function obtenerTiempoRestante(): int
    {
        if (!isset($_SESSION[$this->lastActivityKey])) {
            return $this->sessionTimeout;
        }

        $lastActivity = $_SESSION[$this->lastActivityKey];
        $timeElapsed = time() - $lastActivity;
        $timeRemaining = $this->sessionTimeout - $timeElapsed;

        return max(0, $timeRemaining);
    }

    /**
     * Extender sesión (renovar timeout)
     */
    public function extenderSesion(): void
    {
        $this->actualizarActividad();
    }

    /**
     * Configurar timeout personalizado
     */
    public function configurarTimeout(int $segundos): void
    {
        $this->sessionTimeout = $segundos;
    }

    /**
     * Obtener timeout actual
     */
    public function obtenerTimeout(): int
    {
        return $this->sessionTimeout;
    }

    /**
     * Verificar si la sesión está próxima a expirar (últimos 5 minutos)
     */
    public function estaProximaAExpirar(): bool
    {
        $tiempoRestante = $this->obtenerTiempoRestante();
        return $tiempoRestante <= 300; // 5 minutos
    }
}
