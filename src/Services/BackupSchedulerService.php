<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Programación de backups automáticos según configuracion_sistema (backup.*).
 */
final class BackupSchedulerService extends BaseService
{
    private ConfigurationService $configService;

    private BackupService $backupService;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->configService = new ConfigurationService($database);
        $this->backupService = new BackupService($database);
    }

    public function ensureCronToken(): string
    {
        $tok = trim((string) $this->configService->obtener('backup.cron_token', ''));
        if ($tok === '') {
            $tok = bin2hex(random_bytes(24));
            $this->configService->establecer('backup.cron_token', $tok, null);
        }

        return $tok;
    }

    public function shouldRunAutomaticBackup(): bool
    {
        if (!(bool) $this->configService->obtener('backup.automatico', false)) {
            return false;
        }

        $horaCfg = trim((string) $this->configService->obtener('backup.hora', '03:00'));
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $horaCfg, $m)) {
            $m = [null, '3', '00'];
        }
        $h = max(0, min(23, (int) $m[1]));
        $min = max(0, min(59, (int) $m[2]));

        $now = new \DateTimeImmutable('now');
        $slot = $now->setTime($h, $min, 0);
        if ($now < $slot) {
            return false;
        }

        $last = trim((string) $this->configService->obtener('backup.last_automatic_run', ''));
        if ($last === '') {
            return true;
        }

        try {
            $lastDt = new \DateTimeImmutable($last);
        } catch (\Exception $e) {
            return true;
        }

        $frec = strtolower(trim((string) $this->configService->obtener('backup.frecuencia', 'diario')));
        if ($frec === 'semanal') {
            return ($now->getTimestamp() - $lastDt->getTimestamp() >= 7 * 86400);
        }

        return $lastDt->format('Y-m-d') < $now->format('Y-m-d');
    }

    /**
     * @return array{ran: bool, result: ?array, skipped: string}
     */
    public function runIfDue(): array
    {
        if (!$this->shouldRunAutomaticBackup()) {
            return ['ran' => false, 'result' => null, 'skipped' => 'no_programado'];
        }

        $lockFile = dirname(__DIR__, 2) . '/backups/.backup_auto.lock';
        $fp = @fopen($lockFile, 'c+');
        if ($fp === false) {
            return ['ran' => false, 'result' => null, 'skipped' => 'lock_error'];
        }
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);

            return ['ran' => false, 'result' => null, 'skipped' => 'locked'];
        }

        try {
            if (!$this->shouldRunAutomaticBackup()) {
                return ['ran' => false, 'result' => null, 'skipped' => 'race'];
            }

            $result = $this->backupService->crearBackupCompleto();
            if (!empty($result['success'])) {
                $this->configService->establecer('backup.last_automatic_run', date('Y-m-d H:i:s'), null);
            }

            return ['ran' => true, 'result' => $result, 'skipped' => ''];
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
