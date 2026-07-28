<?php

namespace SistemaAdmin\Models;

/**
 * TDC (Tipo de Dato Concreto) - Modelo Materia
 * 
 * Representa una materia/asignatura del sistema con validaciones internas
 * y métodos de negocio encapsulados.
 */
class Materia
{
    private ?int $id;
    private string $nombre;
    private ?string $codigo;
    private ?int $horasSemanales;
    private ?int $especialidadId;
    private bool $activo;

    public function __construct(
        string $nombre,
        ?string $codigo = null,
        ?int $horasSemanales = null,
        ?int $especialidadId = null,
        bool $activo = true
    ) {
        $this->id = null;
        $this->setNombre($nombre);
        $this->setCodigo($codigo);
        $this->setHorasSemanales($horasSemanales);
        $this->especialidadId = $especialidadId;
        $this->activo = $activo;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function getHorasSemanales(): ?int
    {
        return $this->horasSemanales;
    }

    public function getEspecialidadId(): ?int
    {
        return $this->especialidadId;
    }

    public function esActiva(): bool
    {
        return $this->activo;
    }

    // Setters con validaciones
    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if (empty($nombre)) {
            throw new \InvalidArgumentException("El nombre de la materia no puede estar vacío");
        }
        if (strlen($nombre) > 200) {
            throw new \InvalidArgumentException("El nombre de la materia no puede exceder 200 caracteres");
        }
        $this->nombre = $nombre;
    }

    public function setCodigo(?string $codigo): void
    {
        if ($codigo !== null) {
            $codigo = trim(strtoupper($codigo));
            if (empty($codigo)) {
                $codigo = null;
            } elseif (strlen($codigo) > 20) {
                throw new \InvalidArgumentException("El código no puede exceder 20 caracteres");
            }
        }
        $this->codigo = $codigo;
    }

    public function setHorasSemanales(?int $horasSemanales): void
    {
        if ($horasSemanales !== null && ($horasSemanales < 1 || $horasSemanales > 40)) {
            throw new \InvalidArgumentException("Las horas semanales deben estar entre 1 y 40");
        }
        $this->horasSemanales = $horasSemanales;
    }

    public function setEspecialidad(?int $especialidadId): void
    {
        if ($especialidadId !== null && $especialidadId <= 0) {
            throw new \InvalidArgumentException("ID de especialidad inválido");
        }
        $this->especialidadId = $especialidadId;
    }

    public function setActiva(bool $activa): void
    {
        $this->activo = $activa;
    }

    // Métodos de negocio
    public function tieneCodigo(): bool
    {
        return !empty($this->codigo);
    }

    public function tieneHorasSemanales(): bool
    {
        return $this->horasSemanales !== null && $this->horasSemanales > 0;
    }

    public function tieneEspecialidad(): bool
    {
        return $this->especialidadId !== null;
    }

    public function esMateriaComun(): bool
    {
        return $this->especialidadId === null;
    }

    public function esMateriaEspecifica(): bool
    {
        return $this->especialidadId !== null;
    }

    public function getNombreCompleto(): string
    {
        if ($this->tieneCodigo()) {
            return $this->codigo . ' - ' . $this->nombre;
        }
        return $this->nombre;
    }

    public function getCargaHorariaMensual(): ?int
    {
        if (!$this->tieneHorasSemanales()) {
            return null;
        }
        // Asumiendo 4 semanas por mes
        return $this->horasSemanales * 4;
    }

    public function getCargaHorariaAnual(): ?int
    {
        if (!$this->tieneHorasSemanales()) {
            return null;
        }
        // Asumiendo 40 semanas de clase por año
        return $this->horasSemanales * 40;
    }

    public function esMateriaIntensiva(): bool
    {
        return $this->horasSemanales !== null && $this->horasSemanales >= 6;
    }

    public function esMateriaBasica(): bool
    {
        return $this->horasSemanales !== null && $this->horasSemanales<= 2;
    }

    // Método para establecer ID (solo para uso interno de mappers)
    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException("No se puede modificar el ID de una materia existente");
        }
        $this->id = $id;
    }

    // Método para convertir a array (útil para serialización)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'nombre_completo' => $this->getNombreCompleto(),
            'horas_semanales' => $this->horasSemanales,
            'especialidad_id' => $this->especialidadId,
            'activo' => $this->activo,
            'tiene_codigo' => $this->tieneCodigo(),
            'tiene_horas_semanales' => $this->tieneHorasSemanales(),
            'tiene_especialidad' => $this->tieneEspecialidad(),
            'es_materia_comun' => $this->esMateriaComun(),
            'es_materia_especifica' => $this->esMateriaEspecifica(),
            'carga_horaria_mensual' => $this->getCargaHorariaMensual(),
            'carga_horaria_anual' => $this->getCargaHorariaAnual(),
            'es_materia_intensiva' => $this->esMateriaIntensiva(),
            'es_materia_basica' => $this->esMateriaBasica()
        ];
    }
}
