<?php

declare(strict_types=1);

namespace SistemaAdmin\Bootstrap;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Controllers\DashboardAnalyticsController;
use SistemaAdmin\Controllers\ReportesController;
use SistemaAdmin\Services\ServicioConsultasAvanzadas;
use SistemaAdmin\Services\ServicioReportes;

/**
 * Ensambla servicios y controladores de informes/exportaciones con una sola instancia compartida de {@see ServicioReportes}.
 * Evita duplicar el cableado en `export_discipline.php`, APIs y demos.
 */
final class ReportServicesBootstrap
{
    private function __construct(
        private readonly DatabaseInterface $database,
        private readonly ServicioReportes $servicioReportes,
        private readonly ServicioConsultasAvanzadas $servicioConsultasAvanzadas,
        private readonly ReportesController $reportesController,
        private readonly DashboardAnalyticsController $dashboardAnalyticsController,
    ) {
    }

    public static function fromDatabase(DatabaseInterface $database): self
    {
        $servicioReportes = new ServicioReportes($database);
        $consultas = new ServicioConsultasAvanzadas($database);

        return new self(
            $database,
            $servicioReportes,
            $consultas,
            new ReportesController($database, $servicioReportes),
            new DashboardAnalyticsController($database, $servicioReportes, $consultas),
        );
    }

    public function database(): DatabaseInterface
    {
        return $this->database;
    }

    public function servicioReportes(): ServicioReportes
    {
        return $this->servicioReportes;
    }

    public function servicioConsultasAvanzadas(): ServicioConsultasAvanzadas
    {
        return $this->servicioConsultasAvanzadas;
    }

    public function reportesController(): ReportesController
    {
        return $this->reportesController;
    }

    public function dashboardAnalyticsController(): DashboardAnalyticsController
    {
        return $this->dashboardAnalyticsController;
    }
}
