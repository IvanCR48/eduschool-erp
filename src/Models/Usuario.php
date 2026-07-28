<?php

declare(strict_types=1);

namespace SistemaAdmin\Models;

/**
 * Modelo Usuario
 * 
 * Representa a un usuario del sistema (docente, directivo, preceptor, admin)
 */
class Usuario
{
    private ?int $id;
    private string $dni;
    private string $apellido;
    private string $nombre;
    private ?string $email;
    private ?string $telefono;
    private string $passwordHash;
    private bool $mustChangePassword;
    private string $rol;
    private bool $activo;
    private ?string $ultimoAcceso;
    private int $intentosFallidos;
    private ?string $bloqueadoHasta;
    private string $turnoAsignado;
    private ?string $creadoEn;
    private ?string $actualizadoEn;

    public function __construct(
        string $dni,
        string $apellido,
        string $nombre,
        ?string $email,
        string $passwordHash,
        string $rol,
        bool $activo = true,
        string $turnoAsignado = 'ambos',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->setDni($dni);
        $this->setApellido($apellido);
        $this->setNombre($nombre);
        $this->setEmail($email);
        $this->passwordHash = $passwordHash;
        $this->setRol($rol);
        $this->activo = $activo;
        $this->setTurnoAsignado($turnoAsignado);
        $this->telefono = null;
        $this->mustChangePassword = true;
        $this->ultimoAcceso = null;
        $this->intentosFallidos = 0;
        $this->bloqueadoHasta = null;
        $this->creadoEn = null;
        $this->actualizadoEn = null;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getNombreCompleto(): string
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function esActivo(): bool
    {
        return $this->activo;
    }

    public function getUltimoAcceso(): ?string
    {
        return $this->ultimoAcceso;
    }

    public function getIntentosFallidos(): int
    {
        return $this->intentosFallidos;
    }

    public function getBloqueadoHasta(): ?string
    {
        return $this->bloqueadoHasta;
    }

    public function getTurnoAsignado(): string
    {
        return $this->turnoAsignado;
    }

    public function getCreadoEn(): ?string
    {
        return $this->creadoEn;
    }

    public function getActualizadoEn(): ?string
    {
        return $this->actualizadoEn;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setDni(string $dni): void
    {
        $dni = trim($dni);
        if ($dni === '') {
            throw new \InvalidArgumentException('El DNI no puede estar vacío');
        }
        $this->dni = $dni;
    }

    public function setApellido(string $apellido): void
    {
        $apellido = trim($apellido);
        if ($apellido === '') {
            throw new \InvalidArgumentException('El apellido no puede estar vacío');
        }
        $this->apellido = $apellido;
    }

    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre no puede estar vacío');
        }
        $this->nombre = $nombre;
    }

    public function setEmail(?string $email): void
    {
        if ($email !== null) {
            $email = trim($email);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('El formato de correo no es válido');
            }
        }
        $this->email = $email;
    }

    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono !== null ? trim($telefono) : null;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function setMustChangePassword(bool $mustChangePassword): void
    {
        $this->mustChangePassword = $mustChangePassword;
    }

    public function setRol(string $rol): void
    {
        $rol = strtolower(trim($rol));
        $rolesPermitidos = ['admin', 'directivo', 'profesor', 'preceptor', 'secretario'];
        if (!in_array($rol, $rolesPermitidos, true)) {
            throw new \InvalidArgumentException('Rol no permitido: ' . $rol);
        }
        $this->rol = $rol;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    public function setUltimoAcceso(?string $ultimoAcceso): void
    {
        $this->ultimoAcceso = $ultimoAcceso;
    }

    public function setIntentosFallidos(int $intentosFallidos): void
    {
        $this->intentosFallidos = max(0, $intentosFallidos);
    }

    public function setBloqueadoHasta(?string $bloqueadoHasta): void
    {
        $this->bloqueadoHasta = $bloqueadoHasta;
    }

    public function setTurnoAsignado(string $turnoAsignado): void
    {
        $turnoAsignado = strtolower(trim($turnoAsignado));
        if (!in_array($turnoAsignado, ['mañana', 'tarde', 'ambos'], true)) {
            $turnoAsignado = 'ambos';
        }
        $this->turnoAsignado = $turnoAsignado;
    }

    public function setCreadoEn(?string $creadoEn): void
    {
        $this->creadoEn = $creadoEn;
    }

    public function setActualizadoEn(?string $actualizadoEn): void
    {
        $this->actualizadoEn = $actualizadoEn;
    }
}
