<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Repositorio de Usuarios
 * 
 * Maneja únicamente las operaciones de persistencia de usuarios
 */
class UsuarioRepository extends BaseService
{
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * Busca usuario por DNI (todos los roles) o por email (rol profesor).
     * Los profesores inician sesión con su correo en lugar del DNI.
     */
    public function findByUsername(string $username): ?array
    {
        $username = trim($username);
        return $this->database->fetch(
            "SELECT * FROM usuarios WHERE (email = ? OR dni = ?) AND activo = 1 LIMIT 1",
            [$username, $username]
        );
    }

    /**
     * Incrementa intentos fallidos y aplica bloqueo si alcanza el máximo configurado.
     */
    public function registrarFalloLogin(int $usuarioId, int $maxIntentos, int $bloqueoMinutos): void
    {
        if ($usuarioId < 1 || $maxIntentos < 1) {
            return;
        }
        $this->database->query(
            'UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?',
            [$usuarioId]
        );
        $row = $this->database->fetch('SELECT intentos_fallidos FROM usuarios WHERE id = ?', [$usuarioId]);
        $intentos = (int) ($row['intentos_fallidos'] ?? 0);
        if ($intentos >= $maxIntentos) {
            $mins = max(1, min(1440, $bloqueoMinutos));
            $this->database->query(
                'UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
                [$mins, $usuarioId]
            );
        }
    }

    public function resetIntentosLogin(int $usuarioId): void
    {
        if ($usuarioId < 1) {
            return;
        }
        $this->database->query(
            'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?',
            [$usuarioId]
        );
    }

    /**
     * Buscar usuario por ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE id = ? AND activo = 1";
        return $this->database->fetch($sql, [$id]);
    }

    /**
     * Busca usuario activo por email normalizado (minúsculas, sin espacios extremos).
     */
    public function findActiveByEmailNormalized(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->database->fetch(
            'SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = ? AND activo = 1 LIMIT 1',
            [$email]
        );
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso(int $usuarioId): bool
    {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
            $stmt = $this->database->query($sql, [$usuarioId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Si la columna ultimo_acceso no existe, no es crítico
            return true;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(int $usuarioId, string $nuevaPassword): bool
    {
        $sql = "UPDATE usuarios SET password_hash = ?, must_change_password = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$nuevaPassword, $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Verificar contraseña actual
     */
    public function verificarPassword(int $usuarioId, string $password): bool
    {
        $usuario = $this->findById($usuarioId);
        if (!$usuario) {
            return false;
        }

        return password_verify($password, $usuario['password_hash']);
    }

    /**
     * Crear nuevo usuario
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO usuarios (dni, password_hash, nombre, apellido, email, rol, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->database->query($sql, [
            $datos['dni'],
            $datos['password_hash'],
            $datos['nombre'],
            $datos['apellido'],
            $datos['email'],
            $datos['rol'],
            $datos['activo'] ?? 1
        ]);

        return $this->database->lastInsertId();
    }

    /**
     * Actualizar usuario
     */
    public function actualizar(int $usuarioId, array $datos): bool
    {
        $campos = [];
        $valores = [];

        foreach ($datos as $campo => $valor) {
            if (in_array($campo, ['dni', 'nombre', 'apellido', 'email', 'rol', 'activo'])) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
        }

        if (empty($campos)) {
            return false;
        }

        $valores[] = $usuarioId;
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
        
        $stmt = $this->database->query($sql, $valores);
        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar usuario (soft delete)
     * 
     * Permite eliminar cualquier usuario, incluyendo directivos.
     * La validación de permisos debe hacerse en la capa superior (controladores/servicios).
     * Solo se previene eliminar a sí mismo mediante el parámetro $usuarioActualId.
     * 
     * @param int $usuarioId ID del usuario a eliminar
     * @param int|null $usuarioActualId ID del usuario que está realizando la eliminación (opcional, para prevenir auto-eliminación)
     * @return bool
     */
    public function eliminar(int $usuarioId, ?int $usuarioActualId = null): bool
    {
        // Prevenir auto-eliminación
        if ($usuarioActualId !== null && $usuarioId === $usuarioActualId) {
            throw new \InvalidArgumentException('No puedes eliminarte a ti mismo');
        }
        
        // Verificar que el usuario existe
        $usuario = $this->database->fetch("SELECT id, rol FROM usuarios WHERE id = ?", [$usuarioId]);
        if (!$usuario) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }
        
        // Admin puede eliminar cualquier usuario (incluyendo directivos)
        // No hay restricciones por rol aquí, la validación de permisos se hace en la capa superior
        
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Listar usuarios
     */
    public function listar(int $limite = 50, int $offset = 0): array
    {
        $sql = "SELECT id, dni, nombre, apellido, email, rol, activo, ultimo_acceso 
                FROM usuarios 
                ORDER BY nombre, apellido 
                LIMIT ? OFFSET ?";
        
        return $this->database->fetchAll($sql, [$limite, $offset]);
    }

    /**
     * Contar usuarios
     */
    public function contar(): int
    {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
        $resultado = $this->database->fetch($sql);
        return (int) $resultado['total'];
    }
}
