<?php

namespace SistemaAdmin\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando no se encuentra un estudiante
 */
class EstudianteNoEncontradoException extends Exception
{
    private int $estudianteId;

    public function __construct(int $estudianteId, string $message = "", int $code = 0, ?Exception $previous = null)
    {
        $this->estudianteId = $estudianteId;
        
        if (empty($message)) {
            $message = "No se encontró el estudiante con ID: $estudianteId";
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getEstudianteId(): int
    {
        return $this->estudianteId;
    }
}
