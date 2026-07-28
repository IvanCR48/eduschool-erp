<?php

namespace SistemaAdmin\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando se intenta crear una calificación inválida
 */
class CalificacionInvalidaException extends Exception
{
    private float $valorInvalido;

    public function __construct(float $valorInvalido, string $message = "", int $code = 0, ?Exception $previous = null)
    {
        $this->valorInvalido = $valorInvalido;
        
        if (empty($message)) {
            $message = "Valor de calificación inválido: $valorInvalido. Debe estar entre 0 y 10";
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getValorInvalido(): float
    {
        return $this->valorInvalido;
    }
}
