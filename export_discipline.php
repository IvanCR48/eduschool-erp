<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';

use SistemaAdmin\Services\ServicioAutenticacion;

$adapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($adapter);
if (!$servicioAutenticacion->verificarSesion()) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

require_once __DIR__ . '/includes/preceptor_scope.php';

use SistemaAdmin\Bootstrap\ReportServicesBootstrap;

$informes = ReportServicesBootstrap::fromDatabase($adapter);
$servicioReportes = $informes->servicioReportes();
$reportesController = $informes->reportesController();

$usuario = $servicioAutenticacion->verificarSesion();
$usuarioId = (int) ($usuario['id'] ?? 0);

/** Descarga de exportación generada en segundo plano */
if (isset($_GET['download_export']) && (string) $_GET['download_export'] === '1') {
    $exportId = (int) ($_GET['export_id'] ?? 0);
    if ($exportId < 1) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Solicitud inválida.';
        exit;
    }

    $info = $servicioReportes->resolverRutaDescargaExportacion($exportId, $usuarioId);
    if ($info === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'El archivo no está disponible o aún se está generando.';
        exit;
    }

    $ruta = $info['ruta'];
    $nombre = $info['nombre_descarga'];

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nombre) . '"');
    header('Content-Length: ' . (string) filesize($ruta));
    header('Pragma: public');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    readfile($ruta);
    @unlink($ruta);
    $servicioReportes->marcarExportacionDescargadaOArchivoEliminado($exportId);
    exit;
}

try {
    $preceptor_cids = preceptor_curso_ids();

    $filtros = $reportesController->construirFiltrosExportLlamadosDesdeGet($_GET, $preceptor_cids);

    $resultado = $reportesController->exportarReporte('llamados', 'excel', $filtros);

    if (!$resultado['success']) {
        throw new Exception($resultado['error'] ?? 'No se pudo generar el reporte de llamados.');
    }

    $ruta = $resultado['ruta'];
    $nombre = $resultado['archivo'];

    if (!file_exists($ruta)) {
        throw new Exception('El archivo generado no está disponible.');
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($nombre) . '"');
    header('Content-Length: ' . filesize($ruta));
    header('Pragma: public');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    readfile($ruta);
    @unlink($ruta);
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Error al exportar los llamados: ' . $e->getMessage();
}
