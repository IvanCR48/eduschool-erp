<?php

declare(strict_types=1);

namespace SistemaAdmin\Jobs;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\ServicioLogging;

/**
 * Job de prueba / latido: verifica que el worker y la tabla de colas funcionan.
 */
final class PingQueueJob implements QueuedJobInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function handle(array $payload, DatabaseInterface $database): void
    {
        $log = new ServicioLogging($database);
        $log->registrarEventoSeguridad(
            'QUEUE_PING',
            'Worker de colas ejecutó PingQueueJob',
            $payload
        );
    }
}
