<?php

declare(strict_types=1);

namespace SistemaAdmin\Jobs;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Contrato para trabajos ejecutados de forma asíncrona por el worker de colas.
 */
interface QueuedJobInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function handle(array $payload, DatabaseInterface $database): void;
}
