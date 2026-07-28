<?php

namespace SistemaAdmin\Models;

use DateTime;

/**
 * TDC (Tipo de Dato Concreto) - Modelo Nota
 * 
 * Representa una calificación/nota del sistema con validaciones internas
 * y métodos de negocio encapsulados.
 */
class Nota
{
    private ?int $id;
    private int $estudianteId;
    private int $materiaId;
    private float $valor;
    private string $bimestre;
    private DateTime $fecha;
    private ?string $observaciones;

    // Constantes para validación
    public const VALOR_MINIMO = 0.0;
    public const VALOR_MAXIMO = 10.0;
    public const VALOR_APROBACION = 6.0;
    
    /** Cuatrimestres / períodos regulares para carga por la grilla clásica. */
    public const BIMESTRES_VALIDOS = ['1', '2', '3', '4'];

    /**
     * Convención en tabla `notas`: 0 = intensificación/recuperación (no es un cuatrimestre regular).
     * Incluido solo para hidratar filas existentes; la carga vía {@see ServicioNotas::cargarNota()} no debe usar 0.
     */
    public const BIMESTRE_INTENSIFICACION = '0';

    /** Valores de `bimestre` admitidos al mapear desde la base (incluye intensificaciones). */
    public const BIMESTRES_ACEPTADOS_DESDE_BD = ['0', '1', '2', '3', '4'];

    public function __construct(
        int $estudianteId,
        int $materiaId,
        float $valor,
        string $bimestre,
        ?string $observaciones = null,
        ?DateTime $fecha = null
    ) {
        $this->id = null;
        $this->estudianteId = $estudianteId;
        $this->materiaId = $materiaId;
        $this->setValor($valor);
        $this->setBimestre($bimestre);
        $this->fecha = $fecha ?? new DateTime();
        $this->observaciones = $observaciones;
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

    public function getMateriaId(): int
    {
        return $this->materiaId;
    }

    public function getValor(): float
    {
        return $this->valor;
    }

    public function getBimestre(): string
    {
        return $this->bimestre;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    // Setters con validaciones
    public function setValor(float $valor): void
    {
        if (!$this->validarValor($valor)) {
            throw new \InvalidArgumentException(
                "Valor de nota inválido: $valor. Debe estar entre " . 
                self::VALOR_MINIMO . " y " . self::VALOR_MAXIMO
            );
        }
        $this->valor = $valor;
    }

    public function setBimestre(string $bimestre): void
    {
        if (!in_array($bimestre, self::BIMESTRES_ACEPTADOS_DESDE_BD, true)) {
            throw new \InvalidArgumentException(
                "Bimestre inválido: $bimestre. Debe ser uno de: " .
                implode(', ', self::BIMESTRES_ACEPTADOS_DESDE_BD)
            );
        }
        $this->bimestre = $bimestre;
    }

    public function setObservaciones(?string $observaciones): void
    {
        if ($observaciones !== null && strlen($observaciones) > 500) {
            throw new \InvalidArgumentException("Las observaciones no pueden exceder 500 caracteres");
        }
        $this->observaciones = $observaciones;
    }

    public function setFecha(DateTime $fecha): void
    {
        $this->fecha = $fecha;
    }

    // Métodos de negocio
    public function validarValor(float $valor): bool
    {
        return $valor >= self::VALOR_MINIMO && $valor <= self::VALOR_MAXIMO;
    }

    public function esAprobada(): bool
    {
        return $this->valor >= self::VALOR_APROBACION;
    }

    public function getConcepto(): string
    {
        if ($this->valor >= 9.0) {
            return __('grade.concept_outstanding');
        } elseif ($this->valor >= 8.0) {
            return __('grade.concept_very_good');
        } elseif ($this->valor >= 7.0) {
            return __('grade.concept_good');
        } elseif ($this->valor >= 6.0) {
            return __('grade.concept_satisfactory');
        } elseif ($this->valor >= 4.0) {
            return __('grade.concept_insufficient');
        } else {
            return __('grade.concept_very_insufficient');
        }
    }

    public function esNotaNumerica(): bool
    {
        return $this->valor >= self::VALOR_MINIMO && $this->valor<= self::VALOR_MAXIMO;
    }

    public function esNotaReciente(int $dias = 30): bool
    {
        $hoy = new DateTime();
        $diferencia = $hoy->diff($this->fecha);
        return $diferencia->days<= $dias;
    }

    public function getValorFormateado(): string
    {
        return number_format($this->valor, 1, ',', '.');
    }

    // Método para establecer ID (solo para uso interno de mappers)
    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException("No se puede modificar el ID de una nota existente");
        }
        $this->id = $id;
    }

    // Método para convertir a array (útil para serialización)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'estudiante_id' => $this->estudianteId,
            'materia_id' => $this->materiaId,
            'valor' => $this->valor,
            'valor_formateado' => $this->getValorFormateado(),
            'bimestre' => $this->bimestre,
            'fecha' => $this->fecha->format('Y-m-d H:i:s'),
            'observaciones' => $this->observaciones,
            'es_aprobada' => $this->esAprobada(),
            'concepto' => $this->getConcepto(),
            'es_reciente' => $this->esNotaReciente()
        ];
    }
}
