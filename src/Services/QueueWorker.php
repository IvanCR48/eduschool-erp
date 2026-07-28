<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Jobs\QueuedJobInterface;

/**
 * Consume jobs de `system_queue_jobs` (ejecutar desde cron o CLI).
 */
final class QueueWorker extends BaseService
{
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * @return array{processed: int, failed: int, errors: list<string>}
     */
    public function runBatch(string $queue = 'default', int $maxJobs = 10): array
    {
        (new QueueService($this->database))->ensureSchema();

        $maxJobs = max(1, min(100, $maxJobs));
        $processed = 0;
        $failed = 0;
        $errors = [];

        for ($i = 0; $i < $maxJobs; $i++) {
            $job = $this->reserveNext($queue);
            if ($job === null) {
                break;
            }

            $id = (int) $job['id'];
            $class = (string) $job['job_class'];
            $rawPayload = $job['payload'] ?? null;
            if (is_array($rawPayload)) {
                $payload = $rawPayload;
            } elseif (is_string($rawPayload)) {
                $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR) ?: [];
            } else {
                $payload = [];
            }

            try {
                if (!is_subclass_of($class, QueuedJobInterface::class, true)) {
                    throw new \RuntimeException('Job inválido: ' . $class);
                }
                $class::handle($payload, $this->database);
                $this->deleteJob($id);
                $processed++;
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $errors[] = "Job #{$id} ({$class}): {$msg}";
                $this->markJobOutcome($job, $msg);
                $failed++;
            }
        }

        return ['processed' => $processed, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function reserveNext(string $queue): ?array
    {
        $out = null;

        $this->database->transaction(function (...$__) use ($queue, &$out): void {
            $row = $this->database->fetch(
                'SELECT * FROM system_queue_jobs
                 WHERE queue = ? AND failed_at IS NULL
                   AND reserved_at IS NULL AND available_at <= NOW()
                 ORDER BY id ASC LIMIT 1 FOR UPDATE',
                [$queue]
            );

            if ($row === null) {
                return;
            }

            $this->database->query(
                'UPDATE system_queue_jobs SET reserved_at = NOW(), attempts = attempts + 1 WHERE id = ?',
                [(int) $row['id']]
            );

            $row['attempts'] = (int) $row['attempts'] + 1;
            $out = $row;
        });

        return $out;
    }

    private function deleteJob(int $id): void
    {
        $this->database->query('DELETE FROM system_queue_jobs WHERE id = ?', [$id]);
    }

    /**
     * @param array<string, mixed> $job
     */
    private function markJobOutcome(array $job, string $errorMessage): void
    {
        $id = (int) ($job['id'] ?? 0);
        $attempts = (int) ($job['attempts'] ?? 0);
        $max = (int) ($job['max_attempts'] ?? 3);
        $err = mb_substr($errorMessage, 0, 65000);

        if ($attempts >= $max) {
            $this->database->query(
                'UPDATE system_queue_jobs SET reserved_at = NULL, last_error = ?, failed_at = NOW() WHERE id = ?',
                [$err, $id]
            );

            return;
        }

        $delaySec = (int) min(3600, pow(2, min($attempts, 8)) * 30);
        $next = date('Y-m-d H:i:s', time() + $delaySec);

        $this->database->query(
            'UPDATE system_queue_jobs SET reserved_at = NULL, last_error = ?, available_at = ? WHERE id = ?',
            [$err, $next, $id]
        );
    }
}
