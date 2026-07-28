<?php

declare(strict_types=1);

namespace SistemaAdmin\Exceptions;

use Exception;

/**
 * Lanzada cuando no existe una especialidad con el ID indicado.
 */
class EspecialidadNoEncontradaException extends Exception
{
    private int $especialidadId;

    public function __construct(int $especialidadId, string $message = '', int $code = 0, ?Exception $previous = null)
    {
        $this->especialidadId = $especialidadId;
        if ($message === '') {
            $message = "No se encontró la especialidad con ID: {$especialidadId}";
        }
        parent::__construct($message, $code, $previous);
    }

    public function getEspecialidadId(): int
    {
        return $this->especialidadId;
    }
}
