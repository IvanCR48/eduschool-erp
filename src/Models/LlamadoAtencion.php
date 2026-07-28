<?php

namespace SistemaAdmin\Models;

use DateTime;

/**
 * TDC (Tipo de Dato Concreto) - Modelo LlamadoAtencion
 * 
 * Representa un llamado de atención a un estudiante con validaciones internas
 * y métodos de negocio encapsulados.
 */
class LlamadoAtencion
{
    private ?int $id;
    private int $estudianteId;
    private DateTime $fecha;
    private string $motivo;
    private string $descripcion;
    private ?string $sancion;
    private int $usuarioId;

    public function __construct(
        int $estudianteId,
        string $motivo,
        string $descripcion,
        int $usuarioId,
        ?string $sancion = null,
        ?DateTime $fecha = null
    ) {
        $this->id = null;
        $this->estudianteId = $estudianteId;
        $this->setMotivo($motivo);
        $this->setDescripcion($descripcion);
        $this->usuarioId = $usuarioId;
        $this->sancion = $sancion;
        $this->fecha = $fecha ?? new DateTime();
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEstudianteId(): int
    {
        return $this->estudianteId;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getMotivo(): string
    {
        return $this->motivo;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getSancion(): ?string
    {
        return $this->sancion;
    }

    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }

    // Setters con validaciones
    public function setMotivo(string $motivo): void
    {
        $motivo = trim($motivo);
        if (empty($motivo)) {
            throw new \InvalidArgumentException("El motivo no puede estar vacío");
        }
        if (strlen($motivo) > 200) {
            throw new \InvalidArgumentException("El motivo no puede exceder 200 caracteres");
        }
        $this->motivo = $motivo;
    }

    public function setDescripcion(string $descripcion): void
    {
        $descripcion = trim($descripcion);
        if (empty($descripcion)) {
            throw new \InvalidArgumentException("La descripción no puede estar vacía");
        }
        if (strlen($descripcion) > 1000) {
            throw new \InvalidArgumentException("La descripción no puede exceder 1000 caracteres");
        }
        $this->descripcion = $descripcion;
    }

    public function setSancion(?string $sancion): void
    {
        if ($sancion !== null && strlen($sancion) > 500) {
            throw new \InvalidArgumentException("La sanción no puede exceder 500 caracteres");
        }
        $this->sancion = $sancion;
    }

    public function setFecha(DateTime $fecha): void
    {
        $this->fecha = $fecha;
    }

    // Métodos de negocio
    public function esReciente(int $dias = 7): bool
    {
        $hoy = new DateTime();
        $diferencia = $hoy->diff($this->fecha);
        return $diferencia->days<= $dias;
    }

    public function tieneSancion(): bool
    {
        return !empty($this->sancion);
    }

    public function esDelMes(): bool
    {
        $hoy = new DateTime();
        return $this->fecha->format('Y-m') === $hoy->format('Y-m');
    }

    public function esDelAnio(): bool
    {
        $hoy = new DateTime();
        return $this->fecha->format('Y') === $hoy->format('Y');
    }

    public function getDiasTranscurridos(): int
    {
        $hoy = new DateTime();
        return $hoy->diff($this->fecha)->days;
    }

    public function getFechaFormateada(): string
    {
        return $this->fecha->format('d/m/Y H:i');
    }

    public function esGrave(): bool
    {
        $motivosGraves = [
            'agresión', 'violencia', 'drogas', 'alcohol', 'robo', 'hurto',
            'amenaza', 'acoso', 'discriminación', 'vandalismo'
        ];
        
        $motivoLower = strtolower($this->motivo);
        foreach ($motivosGraves as $motivoGrave) {
            if (strpos($motivoLower, $motivoGrave) !== false) {
                return true;
            }
        }
        
        return false;
    }

    public function requiereSancion(): bool
    {
        return $this->esGrave() || $this->tieneSancion();
    }

    // Método para establecer ID (solo para uso interno de mappers)
    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException("No se puede modificar el ID de un llamado existente");
        }
        $this->id = $id;
    }

    // Método para convertir a array (útil para serialización)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'estudiante_id' => $this->estudianteId,
            'fecha' => $this->fecha->format('Y-m-d'),
            'fecha_formateada' => $this->getFechaFormateada(),
            'motivo' => $this->motivo,
            'descripcion' => $this->descripcion,
            'sancion' => $this->sancion,
            'usuario_id' => $this->usuarioId,
            'es_reciente' => $this->esReciente(),
            'tiene_sancion' => $this->tieneSancion(),
            'es_del_mes' => $this->esDelMes(),
            'es_del_anio' => $this->esDelAnio(),
            'dias_transcurridos' => $this->getDiasTranscurridos(),
            'es_grave' => $this->esGrave(),
            'requiere_sancion' => $this->requiereSancion()
        ];
    }
}
