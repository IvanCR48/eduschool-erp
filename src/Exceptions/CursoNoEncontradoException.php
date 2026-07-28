<?php

namespace SistemaAdmin\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando no se encuentra un curso
 */
class CursoNoEncontradoException extends Exception
{
    private int $cursoId;

    public function __construct(int $cursoId, string $message = "", int $code = 0, ?Exception $previous = null)
    {
        $this->cursoId = $cursoId;
        
        if (empty($message)) {
            $message = "No se encontró el curso con ID: $cursoId";
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getCursoId(): int
    {
        return $this->cursoId;
    }
}
