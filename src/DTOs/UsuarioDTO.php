<?php

namespace SistemaAdmin\DTOs;

/**
 * DTO para datos de usuario
 */
class UsuarioDTO
{
    private int $id;
    private string $username;
    private string $nombre;
    private string $apellido;
    private string $email;
    private string $rol;
    private ?string $ultimoAcceso;
    private bool $activo;

    public function __construct(
        int $id,
        string $username,
        string $nombre,
        string $apellido,
        string $email,
        string $rol,
        ?string $ultimoAcceso = null,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->rol = $rol;
        $this->ultimoAcceso = $ultimoAcceso;
        $this->activo = $activo;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function getUltimoAcceso(): ?string
    {
        return $this->ultimoAcceso;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function getNombreCompleto(): string
    {
        return $this->apellido . ', ' . $this->nombre;
    }

    // Crear desde array de base de datos
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            $data['username'],
            $data['nombre'],
            $data['apellido'],
            $data['email'] ?? '',
            $data['rol'],
            $data['ultimo_acceso'] ?? null,
            (bool) ($data['activo'] ?? true)
        );
    }

    // Convertir a array
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'email' => $this->email,
            'rol' => $this->rol,
            'ultimo_acceso' => $this->ultimoAcceso,
            'activo' => $this->activo
        ];
    }

    // Convertir a array sin datos sensibles
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'rol' => $this->rol,
            'ultimo_acceso' => $this->ultimoAcceso
        ];
    }
}
