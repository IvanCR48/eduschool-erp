<?php

namespace SistemaAdmin\Models;

/**
 * TDC (Tipo de Dato Concreto) - Modelo Curso
 * 
 * Representa un curso/división del sistema con validaciones internas
 * y métodos de negocio encapsulados.
 */
class Curso
{
    private ?int $id;
    private int $anio;
    private string $division;
    private ?int $especialidadId;
    private ?int $turnoId;
    private bool $activo;

    public function __construct(
        int $anio,
        string $division,
        ?int $especialidadId = null,
        ?int $turnoId = null,
        bool $activo = true
    ) {
        $this->id = null;
        $this->setAnio($anio);
        $this->setDivision($division);
        $this->especialidadId = $especialidadId;
        $this->turnoId = $turnoId;
        $this->activo = $activo;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnio(): int
    {
        return $this->anio;
    }

    public function getDivision(): string
    {
        return $this->division;
    }

    public function getNombreCompleto(): string
    {
        return $this->anio . '° ' . $this->division;
    }

    public function getEspecialidadId(): ?int
    {
        return $this->especialidadId;
    }

    public function getTurnoId(): ?int
    {
        return $this->turnoId;
    }

    public function esActivo(): bool
    {
        return $this->activo;
    }

    // Setters con validaciones
    public function setAnio(int $anio): void
    {
        if ($anio < 1 || $anio > 7) {
            throw new \InvalidArgumentException("El año debe estar entre 1 y 7");
        }
        $this->anio = $anio;
    }

    public function setDivision(string $division): void
    {
        $division = trim(strtoupper($division));
        if (empty($division)) {
            throw new \InvalidArgumentException("La división no puede estar vacía");
        }
        if (strlen($division) > 10) {
            throw new \InvalidArgumentException("La división no puede exceder 10 caracteres");
        }
        $this->division = $division;
    }

    public function setEspecialidad(?int $especialidadId): void
    {
        if ($especialidadId !== null && $especialidadId <= 0) {
            throw new \InvalidArgumentException("ID de especialidad inválido");
        }
        $this->especialidadId = $especialidadId;
    }

    public function setTurno(?int $turnoId): void
    {
        if ($turnoId !== null && $turnoId <= 0) {
            throw new \InvalidArgumentException("ID de turno inválido");
        }
        $this->turnoId = $turnoId;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    // Métodos de negocio
    public function esPrimerAnio(): bool
    {
        return $this->anio === 1;
    }

    public function esUltimoAnio(): bool
    {
        return $this->anio === 7;
    }

    public function tieneEspecialidad(): bool
    {
        return $this->especialidadId !== null;
    }

    public function tieneTurno(): bool
    {
        return $this->turnoId !== null;
    }

    public function esCicloBasico(): bool
    {
        return $this->anio >= 1 && $this->anio<= 3;
    }

    public function esCicloSuperior(): bool
    {
        return $this->anio >= 4 && $this->anio<= 7;
    }

    public function getNivel(): string
    {
        if ($this->esCicloBasico()) {
            return 'Ciclo Básico';
        } elseif ($this->esCicloSuperior()) {
            return 'Ciclo Superior';
        }
        return 'Indefinido';
    }

    // Método para establecer ID (solo para uso interno de mappers)
    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException("No se puede modificar el ID de un curso existente");
        }
        $this->id = $id;
    }

    // Método para convertir a array (útil para serialización)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'anio' => $this->anio,
            'division' => $this->division,
            'nombre_completo' => $this->getNombreCompleto(),
            'especialidad_id' => $this->especialidadId,
            'turno_id' => $this->turnoId,
            'activo' => $this->activo,
            'es_primer_anio' => $this->esPrimerAnio(),
            'es_ultimo_anio' => $this->esUltimoAnio(),
            'tiene_especialidad' => $this->tieneEspecialidad(),
            'tiene_turno' => $this->tieneTurno(),
            'es_ciclo_basico' => $this->esCicloBasico(),
            'es_ciclo_superior' => $this->esCicloSuperior(),
            'nivel' => $this->getNivel()
        ];
    }
}
