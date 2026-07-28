<?php

declare(strict_types=1);

/**
 * Punto de entrada para tareas programadas (backup automático).
 * Programar en el sistema operativo cada hora, por ejemplo:
 *   curl -s "https://su-dominio/SistemaAdmin/public/cron_backup.php?key=TOKEN"
 * El TOKEN se muestra en Herramientas admin → pestaña Backups.
 */

require_once __DIR__ . '/../includes/database_bootstrap.php';

use SistemaAdmin\Services\BackupSchedulerService;
use SistemaAdmin\Services\ConfigurationService;

header('Content-Type: text/plain; charset=UTF-8');

$db = sistema_admin_db_adapter();
$cfg = new ConfigurationService($db);
$key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
$expected = trim((string) $cfg->obtener('backup.cron_token', ''));

if ($expected === '' || $key === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$sched = new BackupSchedulerService($db);
$out = $sched->runIfDue();

if (!$out['ran']) {
    echo 'noop ' . ($out['skipped'] ?? '');

    exit;
}

echo $out['result']['success'] ?? false
    ? 'ok ' . (string) ($out['result']['archivo'] ?? '')
    : 'error ' . (string) ($out['result']['mensaje'] ?? '');
