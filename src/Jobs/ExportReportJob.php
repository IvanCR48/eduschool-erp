<?php

declare(strict_types=1);

namespace SistemaAdmin\Jobs;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\ServicioReportes;

/**
 * Genera un archivo de exportación previamente registrado en `system_background_exports`.
 * El payload solo incluye `export_id` (sin datos sensibles repetidos).
 */
final class ExportReportJob implements QueuedJobInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function handle(array $payload, DatabaseInterface $database): void
    {
        $exportId = (int) ($payload['export_id'] ?? 0);
        if ($exportId < 1) {
            throw new \InvalidArgumentException('export_id inválido');
        }

        $reportes = new ServicioReportes($database);
        $reportes->procesarExportacionEnColaPorId($exportId);
    }
}
