<?php

namespace SistemaAdmin\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando no se encuentra un profesor
 */
class ProfesorNoEncontradoException extends Exception
{
    private int $profesorId;

    public function __construct(int $profesorId, string $message = "", int $code = 0, ?Exception $previous = null)
    {
        $this->profesorId = $profesorId;
        
        if (empty($message)) {
            $message = "No se encontró el profesor con ID: $profesorId";
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getProfesorId(): int
    {
        return $this->profesorId;
    }
}
