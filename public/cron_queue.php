<?php

declare(strict_types=1);

/**
 * Worker HTTP de la cola de trabajos (MySQL).
 * Programar cada 1–5 minutos, por ejemplo:
 *   curl -s "https://su-dominio/SistemaAdmin/public/cron_queue.php?key=TOKEN"
 * Parámetros opcionales: queue=default&limit=10
 */

require_once __DIR__ . '/../includes/database_bootstrap.php';

use SistemaAdmin\Services\ConfigurationService;
use SistemaAdmin\Services\QueueWorker;

header('Content-Type: text/plain; charset=UTF-8');

$db = sistema_admin_db_adapter();
$cfg = new ConfigurationService($db);
$key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
$expected = trim((string) $cfg->obtener('queue.cron_token', ''));

if ($expected === '' || $key === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$queue = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($_GET['queue'] ?? 'default')) ?: 'default';
$limit = (int) ($_GET['limit'] ?? 10);

$worker = new QueueWorker($db);
$result = $worker->runBatch($queue, $limit);

echo 'ok processed=' . $result['processed'] . ' failed=' . $result['failed'];
if ($result['errors'] !== []) {
    echo "\n" . implode("\n", $result['errors']);
}
