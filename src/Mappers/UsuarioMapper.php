<?php

declare(strict_types=1);

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Usuario;

/**
 * Mapper para la tabla usuarios
 */
class UsuarioMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Usuario
    {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToUsuario($row);
    }

    public function findByDni(string $dni): ?Usuario
    {
        $sql = "SELECT * FROM usuarios WHERE dni = ?";
        $row = $this->database->fetch($sql, [$dni]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToUsuario($row);
    }

    public function findByEmail(string $email): ?Usuario
    {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $row = $this->database->fetch($sql, [$email]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToUsuario($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM usuarios ORDER BY nombre, apellido";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToUsuario'], $rows);
    }

    public function save(Usuario $usuario): Usuario
    {
        if ($usuario->getId() === null) {
            $sql = "INSERT INTO usuarios (dni, apellido, nombre, email, password_hash, must_change_password, rol, activo, turno_asignado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->database->query($sql, [
                $usuario->getDni(),
                $usuario->getApellido(),
                $usuario->getNombre(),
                $usuario->getEmail(),
                $usuario->getPasswordHash(),
                $usuario->mustChangePassword() ? 1 : 0,
                $usuario->getRol(),
                $usuario->esActivo() ? 1 : 0,
                $usuario->getTurnoAsignado()
            ]);
            
            $usuario->setId((int)$this->database->lastInsertId());
        } else {
            $sql = "UPDATE usuarios 
                    SET dni = ?, apellido = ?, nombre = ?, email = ?, password_hash = ?, must_change_password = ?, rol = ?, activo = ?, turno_asignado = ? 
                    WHERE id = ?";
            
            $this->database->query($sql, [
                $usuario->getDni(),
                $usuario->getApellido(),
                $usuario->getNombre(),
                $usuario->getEmail(),
                $usuario->getPasswordHash(),
                $usuario->mustChangePassword() ? 1 : 0,
                $usuario->getRol(),
                $usuario->esActivo() ? 1 : 0,
                $usuario->getTurnoAsignado(),
                $usuario->getId()
            ]);
        }
        
        return $usuario;
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    private function mapRowToUsuario(array $row): Usuario
    {
        $usuario = new Usuario(
            (string) $row['dni'],
            (string) $row['apellido'],
            (string) $row['nombre'],
            $row['email'] !== null ? (string)$row['email'] : null,
            (string) $row['password_hash'],
            (string) $row['rol'],
            (int)($row['activo'] ?? 1) === 1,
            (string)($row['turno_asignado'] ?? 'ambos'),
            (int) $row['id']
        );

        $usuario->setTelefono($row['telefono'] !== null ? (string)$row['telefono'] : null);
        $usuario->setMustChangePassword((int)($row['must_change_password'] ?? 1) === 1);
        $usuario->setUltimoAcceso($row['ultimo_acceso'] !== null ? (string)$row['ultimo_acceso'] : null);
        $usuario->setIntentosFallidos((int)($row['intentos_fallidos'] ?? 0));
        $usuario->setBloqueadoHasta($row['bloqueado_hasta'] !== null ? (string)$row['bloqueado_hasta'] : null);
        $usuario->setCreadoEn($row['creado_en'] !== null ? (string)$row['creado_en'] : null);
        $usuario->setActualizadoEn($row['actualizado_en'] !== null ? (string)$row['actualizado_en'] : null);

        return $usuario;
    }
}
