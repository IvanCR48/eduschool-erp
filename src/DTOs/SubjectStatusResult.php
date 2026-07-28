<?php

declare(strict_types=1);

namespace SistemaAdmin\DTOs;

use SistemaAdmin\Academic\SubjectComputedStatus;

/**
 * Resultado inmutable del cálculo de estado de materia.
 *
 * effectiveSemester1 / effectiveSemester2: calificaciones EFECTIVAS tras aplicar
 * las reglas de intensificación/recuperación. Son los valores a mostrar en el boletín
 * y en la ficha del estudiante (reemplazan las notas base cuando corresponde).
 */
final readonly class SubjectStatusResult
{
    /**
     * @param list<string> $log Traza de decisiones aplicadas (solo para depuración)
     */
    public function __construct(
        public SubjectComputedStatus $status,
        public ?float $effectiveSemester1,
        public ?float $effectiveSemester2,
        public bool $passed,
        public array $log = [],
    ) {
    }
}
