<?php

declare(strict_types=1);

/**
 * Impresión del boletín: sesión, CSRF en query, datos vía ServicioBoletinNotas (sin SQL en vista).
 */
require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioBoletinNotas;
use SistemaAdmin\Services\ServicioAsistencia;
use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Mappers\AsistenciaMapper;
use SistemaAdmin\Mappers\EstudianteMapper;

require_once __DIR__ . '/includes/i18n.php';
\SistemaAdmin\Services\I18nService::init();

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$servicioBoletinNotas = new ServicioBoletinNotas($databaseAdapter);
$asistenciaMapper = new AsistenciaMapper($databaseAdapter);
$servicioAsistencia = new ServicioAsistencia($databaseAdapter, $asistenciaMapper);

$usuario = $servicioAutenticacion->verificarSesion();

require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/preceptor_scope.php';
require_once __DIR__ . '/includes/profesor_scope.php';

if (!$usuario) {
    header('Location: ' . app_base_path('/public/login.php'));
    exit();
}

$servicioEstudiantesPrint = new ServicioEstudiantes($databaseAdapter, new EstudianteMapper($databaseAdapter));
$preceptorCidsBoletin = preceptor_curso_ids();
$esProfesorBoletin = es_profesor();
$profesorCidsBoletin = $esProfesorBoletin ? profesor_curso_ids() : [];

if ($esProfesorBoletin) {
    if ($profesorCidsBoletin === []) {
        header('Location: ' . app_base_path('students.php'));
        exit();
    }
} elseif ((($_SESSION['rol'] ?? '') === 'preceptor') && $preceptorCidsBoletin === []) {
    header('Location: ' . app_base_path('students.php'));
    exit();
}

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$estudianteId = ($idGet !== false && $idGet > 0) ? $idGet : 0;

if ($estudianteId < 1) {
    header('Location: ' . app_base_path('students.php'));
    exit();
}

if ($esProfesorBoletin) {
    $cursoAlumno = $servicioEstudiantesPrint->obtenerCursoIdEstudianteActivo($estudianteId);
    if ($cursoAlumno === null || !in_array($cursoAlumno, $profesorCidsBoletin, true)) {
        header('Location: ' . app_base_path('students.php'));
        exit();
    }
} elseif ($preceptorCidsBoletin !== []) {
    $cursoAlumno = $servicioEstudiantesPrint->obtenerCursoIdEstudianteActivo($estudianteId);
    if ($cursoAlumno === null || !in_array($cursoAlumno, $preceptorCidsBoletin, true)) {
        header('Location: ' . app_base_path('students.php'));
        exit();
    }
} elseif (!(hasRole('admin') || hasRole('directivo') || hasRole('director') || hasRole('vicedirector') || hasRole('secretario'))) {
    header('Location: ' . app_base_path('index.php?error=unauthorized'));
    exit();
}

$csrfGet = trim((string) (filter_input(INPUT_GET, 'csrf_token', FILTER_DEFAULT) ?? ''));

if ($estudianteId < 1 || !verifyCSRFToken($csrfGet)) {
    header('Location: ' . app_base_path('students.php'));
    exit();
}

$payload = $servicioBoletinNotas->obtenerBoletinParaImpresion($estudianteId);
if (!$payload['encontrado'] || $payload['estudiante'] === null) {
    header('Location: ' . app_base_path('students.php'));
    exit();
}

$estudiante = $payload['estudiante'];
$notas_estudiante = $payload['notas_por_materia'];
$materias_previas = $payload['materias_previas'] ?? [];
$estadisticas = $payload['estadisticas'];

$fecha_actual = date('d/m/Y');
$anioLectivo = (int) date('Y');

$rangoCuatrimestres = [
    'c1' => [$anioLectivo . '-03-01', $anioLectivo . '-07-31'],
    'c2' => [$anioLectivo . '-08-01', $anioLectivo . '-12-31'],
    'total' => [$anioLectivo . '-03-01', $anioLectivo . '-12-31'],
];

$asistenciaBoletin = [];
foreach ($rangoCuatrimestres as $clave => [$desde, $hasta]) {
    $res = $servicioAsistencia->datosAsistenciaFicha($estudianteId, $desde, $hasta, 5)['resumen'];
    $totalReg = (int) ($res['total'] ?? 0);
    $inasistencias = ((int) ($res['tardanza'] ?? 0) * 0.25) + ((int) ($res['media_falta'] ?? 0) * 0.5) + (int) ($res['ausente_justificado'] ?? 0) + (int) ($res['ausente'] ?? 0);
    $asistio = (float) ($res['presente'] ?? 0) + ((float) ($res['tardanza'] ?? 0) * 0.75) + ((float) ($res['media_falta'] ?? 0) * 0.5);
    $porcentaje = $totalReg > 0
        ? round(($asistio / $totalReg) * 100, 1)
        : -1.0;
    $asistenciaBoletin[$clave] = [
        'inasistencias' => $inasistencias,
        'total' => $totalReg,
        'porcentaje' => $porcentaje,
    ];
}
$c2Iniciado = date('Y-m-d') >= $rangoCuatrimestres['c2'][0];

sistema_admin_send_html_security_headers();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('auto.bolet_n_de_notas'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_base_path('css/imprimir_boletin.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <?php
    $configPrint = new \SistemaAdmin\Services\ConfigurationService($databaseAdapter);
    $sysNamePrint = (string) $configPrint->obtener('sistema.nombre', 'EduSchool ERP');
    $sysSubPrint = (string) $configPrint->obtener('sistema.subtitulo', 'School Management System');
    $watermarkText = (string) strtok($sysNamePrint, ' ');
    ?>
    <div class="watermark"><?php echo htmlspecialchars($watermarkText, ENT_QUOTES, 'UTF-8'); ?></div>
    
    <div class="boletin-container">
        <div class="header">
            <div class="school-logo"><?php echo htmlspecialchars(substr($watermarkText, 0, 4), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="school-info">
                <h1><?php echo htmlspecialchars($sysNamePrint, ENT_QUOTES, 'UTF-8'); ?></h1>
                <h2><?php echo htmlspecialchars($sysSubPrint, ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <div class="boletin-title"><?php echo htmlspecialchars(__('auto.bolet_n_de_calificaciones'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        
        <div class="student-info-section" style="display: flex; gap: 20px; align-items: center;">
            <div class="student-info-grid" style="flex: 1; margin-bottom: 0;">
                <div class="info-group">
                    <div class="info-label"><?php echo htmlspecialchars(__('auto.estudiante'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-value"><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label"><?php echo htmlspecialchars(__('auto.dni'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-value"><?php echo htmlspecialchars($estudiante['dni']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label"><?php echo htmlspecialchars(__('auto.curso'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-value"><?php echo htmlspecialchars(
                        ($estudiante['anio'] ?? '') . '° ' . ($estudiante['division'] ?? '')
                        . (!empty($estudiante['especialidad']) ? ' - ' . $estudiante['especialidad'] : '')
                    ); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label"><?php echo htmlspecialchars(__('auto.fecha'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-value"><?php echo $fecha_actual; ?></div>
                </div>
            </div>

            <?php
            $fotoPath = '';
            if (!empty($estudiante['foto'])) {
                $fotoPath = (string) $estudiante['foto'];
                if ($fotoPath !== '' && !str_starts_with($fotoPath, 'http://')
                    && !str_starts_with($fotoPath, 'https://')
                    && !str_starts_with($fotoPath, '//')
                    && !str_starts_with($fotoPath, '/')) {
                    $fotoPath = app_base_path(ltrim($fotoPath, '/'));
                }
            }
            ?>
            <div class="student-photo-container" style="width: 80px; height: 100px; border: 2px solid #1a4b84; display: flex; align-items: center; justify-content: center; background: #e9ecef; border-radius: 4px; overflow: hidden; flex-shrink: 0;">
                <?php if ($fotoPath !== ''): ?>
                    <img src="<?php echo htmlspecialchars($fotoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="font-size: 8px; color: #6c757d; text-align: center; text-transform: uppercase; font-weight: bold; font-family: sans-serif; padding: 4px;"><?php echo htmlspecialchars(__('auto.sin_foto'), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="academic-year"><?php echo htmlspecialchars(__('auto.a_o_lectivo'), ENT_QUOTES, 'UTF-8'); ?><?php echo date('Y'); ?>
        </div>
        
        <div class="grades-section">
            <?php if (!empty($notas_estudiante)): ?>
            <?php
            $termsCountPrint = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount($databaseAdapter->getPdo());
            ?>
            <table class="grades-table">
                <thead>
                    <tr>
                        <th class="cuatrimestre-cell"><?php echo htmlspecialchars(__('nav.academic'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <th class="materia-header">
                            <strong><?php
                            $palabrasMateria = explode(' ', (string) $datos['materia']['nombre']);
                            $nombreCortoMateria = implode(' ', array_slice($palabrasMateria, 0, 4));
                            if (count($palabrasMateria) > 4) {
                                $nombreCortoMateria .= '...';
                            }
                            echo htmlspecialchars($nombreCortoMateria, ENT_QUOTES, 'UTF-8');
                            ?></strong>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($termsCountPrint === 3 || $termsCountPrint === 4): ?>
                        <?php for ($tNum = 1; $tNum <= $termsCountPrint; $tNum++): ?>
                        <tr>
                            <td class="cuatrimestre-cell">
                                <strong><?php echo htmlspecialchars(__('student.term' . $tNum), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                            <td>
                                <span class="nota-value"><?php
                                    $nT = $datos['cuatrimestres'][$tNum] ?? null;
                                    echo $nT !== null && $nT !== '' ? htmlspecialchars((string) $nT, ENT_QUOTES, 'UTF-8') : '-';
                                ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
                    <?php else: ?>
                        <tr class="avance-row">
                            <td class="cuatrimestre-cell">
                                <strong><?php echo htmlspecialchars(__('student.term1_preview'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                            <td class="avance-cell">
                                <span class="avance-value"><?php
                                    $a1 = $datos['avances']['avance1'] ?? null;
                                    echo $a1 !== null && $a1 !== '' ? htmlspecialchars((string) $a1, ENT_QUOTES, 'UTF-8') : '-';
                                ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <tr>
                            <td class="cuatrimestre-cell">
                                <strong><?php echo htmlspecialchars(__('student.term1'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                            <td>
                                <span class="nota-value"><?php
                                    $n1 = $datos['cuatrimestres'][1] ?? null;
                                    echo $n1 !== null && $n1 !== '' ? htmlspecialchars((string) $n1, ENT_QUOTES, 'UTF-8') : '-';
                                ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <tr class="avance-row">
                            <td class="cuatrimestre-cell">
                                <strong><?php echo htmlspecialchars(__('student.term2_preview'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                            <td class="avance-cell">
                                <span class="avance-value"><?php
                                    $a2 = $datos['avances']['avance2'] ?? null;
                                    echo $a2 !== null && $a2 !== '' ? htmlspecialchars((string) $a2, ENT_QUOTES, 'UTF-8') : '-';
                                ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>

                        <tr>
                            <td class="cuatrimestre-cell">
                                <strong><?php echo htmlspecialchars(__('student.term2'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                            <td>
                                <span class="nota-value"><?php
                                    $n2 = $datos['cuatrimestres'][2] ?? null;
                                    echo $n2 !== null && $n2 !== '' ? htmlspecialchars((string) $n2, ENT_QUOTES, 'UTF-8') : '-';
                                ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                    
                    <tr class="promedio-row">
                        <td class="cuatrimestre-cell">
                            <strong><?php echo htmlspecialchars(__('student.average'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <?php if ($datos['promedio'] !== null): ?>
                                <span class="promedio-value"><?php echo htmlspecialchars((string) $datos['promedio'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!$datos['promedio_completo']): ?>
                                    <div class="promedio-parcial"><?php echo htmlspecialchars(__('auto.parcial'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="promedio-pendiente"><?php echo htmlspecialchars(__('grades.pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr class="estado-row">
                        <td class="cuatrimestre-cell">
                            <strong><?php echo htmlspecialchars(__('student.status'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <?php if ($datos['promedio_completo']): ?>
                                <?php if ($datos['promedio'] >= 7): ?>
                                    <span class="estado aprobado"><?php echo htmlspecialchars(__('student.passed'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span class="estado reprobado"><?php echo htmlspecialchars(__('student.failed'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="estado pendiente"><?php echo htmlspecialchars(__('grades.pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                </tbody>
            </table>
            <?php else: ?>
            <div class="boletin-grades-empty">
                <p><?php echo htmlspecialchars(__('auto.no_hay_notas_registradas_para_este_estudiante'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($materias_previas)): ?>
        <div class="summary-section">
            <div class="attendance-summary">
                <h3><?php echo htmlspecialchars(__('auto.materias_previas'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="table-container">
                    <table class="grades-table grades-table--previas">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(__('auto.materia'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th>Año previo</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materias_previas as $previa): ?>
                            <?php
                            $estadoPrevia = strtolower((string) ($previa['estado'] ?? 'pendiente'));
                            $estadoLabel = match ($estadoPrevia) {
                                'aprobada' => 'Aprobada',
                                'regularizada' => 'Regularizada',
                                default => 'Pendiente',
                            };
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($previa['materia_nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) ($previa['anio_previo'] ?? 0); ?>°</td>
                                <td><?php echo htmlspecialchars($estadoLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($previa['observaciones'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($notas_estudiante)): ?>
        <div class="summary-section">
            <div class="attendance-summary">
                <h3><?php echo htmlspecialchars(__('auto.asistencia_del_estudiante'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="attendance-summary-grid">
                    <div class="attendance-summary-item">
                        <div class="attendance-summary-item__label"><?php echo htmlspecialchars(__('auto.inasistencias_1_cuatr'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="attendance-summary-item__value"><?php echo (float) $asistenciaBoletin['c1']['inasistencias']; ?></div>
                        <div class="attendance-summary-item__meta"><?php echo $asistenciaBoletin['c1']['porcentaje'] >= 0 ? number_format((float) $asistenciaBoletin['c1']['porcentaje'], 1) . '% ' . __('student.attendance') : 'S/D'; ?></div>
                    </div>
                    <div class="attendance-summary-item">
                        <div class="attendance-summary-item__label"><?php echo htmlspecialchars(__('auto.inasistencias_2_cuatr'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="attendance-summary-item__value"><?php echo (float) $asistenciaBoletin['c2']['inasistencias']; ?></div>
                        <div class="attendance-summary-item__meta">
                            <?php
                            if (!$c2Iniciado) {
                                echo htmlspecialchars(__('auto.en_curso_a_n_no_inicia'), ENT_QUOTES, 'UTF-8');
                            } else {
                                echo $asistenciaBoletin['c2']['porcentaje'] >= 0 ? number_format((float) $asistenciaBoletin['c2']['porcentaje'], 1) . '% ' . __('student.attendance') : 'S/D';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="attendance-summary-item attendance-summary-item--total">
                        <div class="attendance-summary-item__label"><?php echo htmlspecialchars(__('auto.total_inasistencias'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="attendance-summary-item__value"><?php echo (float) $asistenciaBoletin['total']['inasistencias']; ?></div>
                        <div class="attendance-summary-item__meta"><?php echo $asistenciaBoletin['total']['porcentaje'] >= 0 ? number_format((float) $asistenciaBoletin['total']['porcentaje'], 1) . '% asistencia' : 'S/D'; ?></div>
                    </div>
                </div>
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label"><?php echo htmlspecialchars(__('grades.passed_subjects'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="summary-value"><?php echo (int) $estadisticas['materias_aprobadas']; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php echo htmlspecialchars(__('grades.failed_subjects'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="summary-value"><?php echo (int) $estadisticas['materias_reprobadas']; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php echo htmlspecialchars(__('grades.pending_subjects'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="summary-value"><?php echo (int) $estadisticas['materias_pendientes']; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php echo htmlspecialchars(__('grades.total_subjects'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="summary-value"><?php echo (int) $estadisticas['total_materias']; ?></div>
                </div>
            </div>
            <?php if ($estadisticas['promedio_general'] !== null): ?>
            <div class="boletin-promedio-general">
                <div class="boletin-promedio-general__label"><?php echo htmlspecialchars(__('grades.general_average'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="boletin-promedio-general__value"><?php echo htmlspecialchars((string) $estadisticas['promedio_general'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?php echo htmlspecialchars(__('auto.preceptor'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?php echo htmlspecialchars(__('auto.director'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?php echo htmlspecialchars(__('auto.sello_institucional'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?php echo htmlspecialchars(__('auto.fecha'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        
        <div class="boletin-footer">
            <?php $schoolNameFooter = \SistemaAdmin\Services\SchoolConfigService::getSchoolName($databaseAdapter->getPdo()); ?>
            <p><strong><?php echo htmlspecialchars($schoolNameFooter, ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p style="margin: 5px 0 0; font-size: 10px; opacity: 0.85; font-style: italic;"><?php echo htmlspecialchars(__('boletin.validity_notice'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin: 5px 0 0;"><?php echo htmlspecialchars(__('boletin.generated_automatically'), ENT_QUOTES, 'UTF-8'); ?><?php echo $fecha_actual; ?></p>
        </div>
    </div>
    
    <script nonce="<?php echo htmlspecialchars($GLOBALS['csp_nonce'] ?? ''); ?>">
        // Imprimir automáticamente cuando se carga la página
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
