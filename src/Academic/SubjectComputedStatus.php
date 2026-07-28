<?php

declare(strict_types=1);

namespace SistemaAdmin\Academic;

/**
 * Estado de la materia respecto al ciclo regular + intensificaciones.
 */
enum SubjectComputedStatus: string
{
    case Passed = 'Passed';
    case Intensification = 'Intensification';
    case Prerequisite = 'Prerequisite';
}
