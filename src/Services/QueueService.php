<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Jobs\QueuedJobInterface;

/**
 * Cola persistente (MySQL): encolar trabajos pesados sin bloquear la petición HTTP.
 */
final class QueueService extends BaseService
{
    private ConfigurationService $config;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->config = new ConfigurationService($database);
        $this->ensureTable();
    }

    public function ensureCronToken(): string
    {
        $tok = trim((string) $this->config->obtener('queue.cron_token', ''));
        if ($tok === '') {
            $tok = bin2hex(random_bytes(24));
            $this->config->establecer('queue.cron_token', $tok, null);
        }

        return $tok;
    }

    /**
     * Encola un job. Solo se aceptan clases bajo {@see QueuedJobInterface} en `SistemaAdmin\Jobs\`.
     *
     * @param class-string<QueuedJobInterface> $jobClass
     * @param array<string, mixed> $payload
     */
    public function dispatch(
        string $jobClass,
        array $payload = [],
        string $queue = 'default',
        int $delaySeconds = 0,
        int $maxAttempts = 3
    ): int {
        if (!str_starts_with($jobClass, 'SistemaAdmin\\Jobs\\')) {
            throw new \InvalidArgumentException('Solo se permiten jobs en el namespace SistemaAdmin\\Jobs\\');
        }
        if (!class_exists($jobClass)) {
            throw new \InvalidArgumentException('Clase de job inexistente: ' . $jobClass);
        }
        if (!is_subclass_of($jobClass, QueuedJobInterface::class, true)) {
            throw new \InvalidArgumentException('El job debe implementar QueuedJobInterface');
        }

        $queue = preg_replace('/[^a-zA-Z0-9_\-]/', '', $queue) ?: 'default';
        $availableAt = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->database->insert(
            'INSERT INTO system_queue_jobs (queue, job_class, payload, attempts, max_attempts, available_at, created_at)
             VALUES (?, ?, ?, 0, ?, ?, NOW())',
            [$queue, $jobClass, $payloadJson, max(1, min(10, $maxAttempts)), $availableAt]
        );
    }

    public function pendingCount(string $queue = 'default'): int
    {
        $row = $this->database->fetch(
            'SELECT COUNT(*) AS c FROM system_queue_jobs
             WHERE queue = ? AND failed_at IS NULL AND reserved_at IS NULL AND available_at <= NOW()',
            [$queue]
        );

        return (int) ($row['c'] ?? 0);
    }

    public function failedCount(string $queue = 'default', int $sinceHours = 168): int
    {
        $row = $this->database->fetch(
            'SELECT COUNT(*) AS c FROM system_queue_jobs
             WHERE queue = ? AND failed_at IS NOT NULL AND failed_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)',
            [$queue, max(1, $sinceHours)]
        );

        return (int) ($row['c'] ?? 0);
    }

    /** Asegura que exista la tabla de colas (idempotente). */
    public function ensureSchema(): void
    {
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        try {
            $this->database->query(
                'CREATE TABLE IF NOT EXISTS system_queue_jobs (
                  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                  queue VARCHAR(64) NOT NULL DEFAULT \'default\',
                  job_class VARCHAR(255) NOT NULL,
                  payload JSON NOT NULL,
                  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                  max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
                  available_at DATETIME NOT NULL,
                  reserved_at DATETIME NULL DEFAULT NULL,
                  created_at DATETIME NOT NULL,
                  last_error TEXT NULL,
                  failed_at DATETIME NULL,
                  PRIMARY KEY (id),
                  KEY idx_queue_poll (queue, failed_at, reserved_at, available_at),
                  KEY idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (\Throwable $e) {
            $this->logEvent('WARNING', 'No se pudo asegurar tabla system_queue_jobs', ['error' => $e->getMessage()]);
        }
    }
}
