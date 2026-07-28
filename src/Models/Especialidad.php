<?php

declare(strict_types=1);

namespace SistemaAdmin\Models;

/**
 * Especialidad de la institución (ciclo superior, talleres, etc.).
 */
class Especialidad
{
    private ?int $id;
    private string $nombre;
    private ?string $descripcion;
    private bool $activa;

    public function __construct(string $nombre, ?string $descripcion = null, bool $activa = true)
    {
        $this->id = null;
        $this->setNombre($nombre);
        $this->descripcion = $descripcion === null || $descripcion === '' ? null : trim($descripcion);
        $this->activa = $activa;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function estaActiva(): bool
    {
        return $this->activa;
    }

    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre no puede estar vacío.');
        }
        $this->nombre = $nombre;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion === null || trim($descripcion) === '' ? null : trim($descripcion);
    }

    public function setActiva(bool $activa): void
    {
        $this->activa = $activa;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException('No se puede modificar el ID de una especialidad existente.');
        }
        $this->id = $id;
    }

    /**
     * @return array{id?: int, nombre: string, descripcion: ?string, activa: bool}
     */
    public function toArray(): array
    {
        $row = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activa' => $this->activa,
        ];
        if ($this->id !== null) {
            $row['id'] = $this->id;
        }

        return $row;
    }
}
