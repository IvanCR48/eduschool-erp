<?php

namespace SistemaAdmin\Middleware;

use SistemaAdmin\Services\MonitoringService;

/**
 * Middleware de Monitoreo
 * 
 * Intercepta requests para recopilar métricas automáticamente
 */
class MonitoringMiddleware
{
    private MonitoringService $monitoringService;
    private float $startTime;
    private int $startMemory;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Ejecutar middleware antes del request
     */
    public function beforeRequest(): void
    {
        // Registrar inicio de request
        $this->monitoringService->incrementarContador('requests_total', 1, [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    }

    /**
     * Ejecutar middleware después del request
     */
    public function afterRequest(): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $tiempoEjecucion = $endTime - $this->startTime;
        $memoriaUsada = $endMemory - $this->startMemory;
        
        // Registrar métricas de rendimiento
        $this->monitoringService->registrarTiempoEjecucion('request', $tiempoEjecucion, [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'status_code' => http_response_code()
        ]);
        
        $this->monitoringService->registrarMetrica('memoria_request', $memoriaUsada / 1024, [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN'
        ]);
    }

    /**
     * Registrar error
     */
    public function onError(\Throwable $error): void
    {
        $this->monitoringService->registrarErrorSistema(
            get_class($error),
            $error->getMessage(),
            [
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ]
        );
    }

    /**
     * Registrar evento de usuario
     */
    public function onUserAction(string $action, int $userId = null): void
    {
        $this->monitoringService->registrarEventoUsuario($action, $userId ?? 0);
    }
}
