<?php

declare(strict_types=1);

namespace SistemaAdmin\Contracts;

/**
 * Provee la fecha de cierre Feb/Mar para un año lectivo (inyectable / testeable).
 */
interface FebruaryMarchClosingProviderInterface
{
    /**
     * Último día inclusive del período de recuperación Feb/Mar.
     * Si no hay registro, la implementación puede lanzar o devolver una fecha por defecto documentada.
     */
    public function getFebruaryMarchClosingDate(int $schoolYear): \DateTimeImmutable;
}
