<?php

declare(strict_types=1);

/**
 * API JSON: encolar exportación de llamados a Excel y consultar estado.
 */

require_once __DIR__ . '/../includes/database_bootstrap.php';
require_once __DIR__ . '/../includes/csrf_functions.php';

use SistemaAdmin\Bootstrap\ReportServicesBootstrap;
use SistemaAdmin\Middleware\SecurityHeadersMiddleware;
use SistemaAdmin\Services\ServicioAutenticacion;

SecurityHeadersMiddleware::applyAPIHeaders();
header('Content-Type: application/json; charset=UTF-8');

$adapter = sistema_admin_db_adapter();
$auth = new ServicioAutenticacion($adapter);
$usuario = $auth->verificarSesion();

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../includes/preceptor_scope.php';

$informes = ReportServicesBootstrap::fromDatabase($adapter);
$servicioReportes = $informes->servicioReportes();
$reportesController = $informes->reportesController();
$usuarioId = (int) ($usuario['id'] ?? 0);
$preceptorCids = preceptor_curso_ids();

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

try {
    if ($action === 'status') {
        $exportId = (int) ($_GET['export_id'] ?? 0);
        if ($exportId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'export_id inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $estado = $servicioReportes->obtenerEstadoExportacion($exportId, $usuarioId);
        echo json_encode($estado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action !== 'enqueue') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Use POST'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $raw = (string) file_get_contents('php://input');
    $body = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($body)) {
        $body = $_POST;
    }

    $csrf = (string) ($body['csrf_token'] ?? '');
    if (!verifyCSRFToken($csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o vencido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fuente = [
        'fecha_desde' => $body['fecha_desde'] ?? null,
        'fecha_hasta' => $body['fecha_hasta'] ?? null,
        'curso' => $body['curso'] ?? null,
        'estudiante' => $body['estudiante'] ?? null,
        'motivo' => $body['motivo'] ?? null,
        'tiene_sancion' => $body['tiene_sancion'] ?? null,
    ];

    $filtros = $reportesController->construirFiltrosExportLlamadosDesdeFuente($fuente, $preceptorCids);
    $out = $reportesController->solicitarExportacionAsincrona('llamados', 'excel', $filtros, $usuarioId);

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor.'], JSON_UNESCAPED_UNICODE);
}
