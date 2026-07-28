<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';

use SistemaAdmin\Controllers\NotasBoletinController;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Mappers\NotaMapper;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioBoletinNotas;
use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioNotas;
use SistemaAdmin\Services\NotasSubjectGradesPayloadBuilder;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

$usuario = $servicioAutenticacion->verificarSesion();
if (!$usuario) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/csrf_functions.php';
require_once __DIR__ . '/includes/profesor_scope.php';
require_once __DIR__ . '/includes/preceptor_scope.php';

$notaMapper = new NotaMapper($databaseAdapter);
$estudianteMapper = new EstudianteMapper($databaseAdapter);
$servicioEstudiantes = new ServicioEstudiantes($databaseAdapter, $estudianteMapper);
$servicioNotas = new ServicioNotas($databaseAdapter, $notaMapper, $estudianteMapper);
$notasBoletinController = new NotasBoletinController($databaseAdapter, $servicioEstudiantes, $servicioNotas);

$pageTitle = 'Boletín de Notas - Sistema Administrativo E.E.S.T N°2';
$mainContentExtraClass = 'main-content--boletin';

$action = trim((string) (filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?? ''));
$curso_filter = trim((string) (filter_input(INPUT_GET, 'curso', FILTER_DEFAULT) ?? ''));
$estudiante_filter = trim((string) (filter_input(INPUT_GET, 'estudiante', FILTER_DEFAULT) ?? ''));
$cuatrimestre_filter = trim((string) (filter_input(INPUT_GET, 'trimestre', FILTER_DEFAULT) ?? ''));
$success_message = '';
$error_message = '';
$materias_previas_generadas = 0;
$csrfToken = getCSRFToken();

$es_profesor_notas = es_profesor();
$profesor_cids_notas = $es_profesor_notas ? profesor_curso_ids() : [];
$es_preceptor_notas = hasRole('preceptor');
$preceptor_cids_notas = $es_preceptor_notas ? preceptor_curso_ids() : [];

// Para el rol profesor, redirige si intenta ver un curso fuera de su alcance
if ($es_profesor_notas && $curso_filter !== '' && !in_array((int) $curso_filter, $profesor_cids_notas, true)) {
    $curso_filter = '';
}
// Para el rol profesor sin cursos asignados, no hay nada que mostrar
if ($es_profesor_notas && $profesor_cids_notas === []) {
    $error_message = 'No tenés cursos asignados. Contactá a la administración.';
}

if ($es_preceptor_notas && $curso_filter !== '' && !in_array((int) $curso_filter, $preceptor_cids_notas, true)) {
    $curso_filter = '';
}
if ($es_preceptor_notas && $preceptor_cids_notas === [] && $error_message === '') {
    $error_message = 'No tenés cursos asignados como preceptor. Contactá a la administración.';
}

$form_values = [
    'curso_id' => '',
    'estudiante_id' => '',
    'materia_id' => '',
    'trimestre' => '',
    'nota' => '',
    'observaciones' => '',
    'tipo_registro' => 'numerica',
    'valor_avance' => '',
    'etapa_avance' => '',
];

$resFiltros = $notasBoletinController->aplicarFiltrosDesdeEstudiante($estudiante_filter, $curso_filter, $cuatrimestre_filter);
$curso_filter = $resFiltros['curso_filter'];
$cuatrimestre_filter = $resFiltros['cuatrimestre_filter'];
$estudiante_info = $resFiltros['estudiante_info'];

$puede_gestionar_notas = hasRole('admin') || hasRole('directivo') || $es_profesor_notas || $es_preceptor_notas;

$anioLectivoActual = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
$correctionPeriodInfo = check_grade_correction_period($databaseAdapter, (int) $anioLectivoActual);
$modificacionHabilitadaDocente = true;

if ($es_profesor_notas) {
    $modificacionHabilitadaDocente = $correctionPeriodInfo['is_open'];
    if (!$modificacionHabilitadaDocente) {
        $puede_gestionar_notas = false;
    }
}

$esPost = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST';
$postAccionNotas =
    filter_input(INPUT_POST, 'actualizar_nota', FILTER_DEFAULT) !== null
    || filter_input(INPUT_POST, 'insertar_nota', FILTER_DEFAULT) !== null
    || filter_input(INPUT_POST, 'guardar_avance', FILTER_DEFAULT) !== null
    || filter_input(INPUT_POST, 'eliminar_nota', FILTER_DEFAULT) !== null
    || filter_input(INPUT_POST, 'guardar_recuperatorio', FILTER_DEFAULT) !== null;

// Para profesor: validar que la acción POST afecta solo materias/cursos de su alcance y que el período de corrección esté abierto
$puede_post_esta_accion = $puede_gestionar_notas;
if ($es_profesor_notas && !$modificacionHabilitadaDocente && $esPost && $postAccionNotas) {
    $puede_post_esta_accion = false;
    $error_message = 'El período de corrección de notas está cerrado.';
} elseif ($es_profesor_notas && $esPost && $postAccionNotas) {
    $postCursoId = (int) (filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0);
    $postMateriaId = (int) (filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT) ?? 0);
    if ($postCursoId > 0 && !in_array($postCursoId, $profesor_cids_notas, true)) {
        $puede_post_esta_accion = false;
        $error_message = 'No tenés permiso para modificar notas de ese curso.';
    } elseif ($postCursoId > 0 && $postMateriaId > 0
        && !profesor_puede_editar_materia_en_curso($postCursoId, $postMateriaId)) {
        $puede_post_esta_accion = false;
        $error_message = 'No tenés permiso para modificar notas de esa materia.';
    }
}

if ($es_preceptor_notas && $esPost && $postAccionNotas && $puede_post_esta_accion) {
    $postCursoIdPre = (int) (filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0);
    if ($postCursoIdPre > 0 && !preceptor_permitido_curso($postCursoIdPre)) {
        $puede_post_esta_accion = false;
        $error_message = 'No tenés permiso para modificar notas de ese curso.';
    }
}

// ── Respuesta AJAX (petición sin recarga de página) ──────────────────────────
if ($esPost && (string) ($_POST['ajax_nota'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$puede_post_esta_accion) {
        echo json_encode(['success' => false, 'error' => $error_message ?: 'Sin permiso.']);
        exit();
    }
    $csrfAjax = (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? '');
    if (!verifyCSRFToken($csrfAjax)) {
        echo json_encode(['success' => false, 'error' => 'La solicitud no pudo validarse.']);
        exit();
    }
    $resAjax = $notasBoletinController->procesarPost($puede_gestionar_notas, true, $_POST, $form_values);
    if (!empty($resAjax['error_message'])) {
        echo json_encode(['success' => false, 'error' => $resAjax['error_message']]);
        exit();
    }
    $jsonResp = ['success' => true, 'message' => $resAjax['success_message'] ?? ''];
    // Para insertar_nota devolvemos el id recién creado para que el JS actualice el formulario
    // y las siguientes ediciones usen actualizar_nota en lugar de volver a insertar.
    if (isset($_POST['insertar_nota'])) {
        $eidAjax = (int) ($_POST['estudiante_id'] ?? 0);
        $midAjax = (int) ($_POST['materia_id'] ?? 0);
        $bimAjax = (string) ($_POST['trimestre'] ?? '');
        if ($eidAjax > 0 && $midAjax > 0 && $bimAjax !== '') {
            $filaReciente = $databaseAdapter->fetch(
                'SELECT id FROM notas WHERE estudiante_id = ? AND materia_id = ? AND bimestre = ? ORDER BY id DESC LIMIT 1',
                [$eidAjax, $midAjax, $bimAjax]
            );
            if ($filaReciente !== null) {
                $jsonResp['nota_id'] = (int) $filaReciente['id'];
            }
        }
    }
    echo json_encode($jsonResp);
    exit();
}

$postEstado = [
    'success_message' => '',
    'error_message' => $error_message,
    'form_values' => $form_values,
];
if ($esPost && $postAccionNotas && $puede_post_esta_accion) {
    $csrfPost = (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? '');
    if (!verifyCSRFToken($csrfPost)) {
        $postEstado['error_message'] = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $postEstado = $notasBoletinController->procesarPost($puede_gestionar_notas, true, $_POST, $form_values);
    }
}
$success_message = $postEstado['success_message'];
if ($error_message === '') {
    $error_message = $postEstado['error_message'];
}
$form_values = $postEstado['form_values'];

$vistaBoletin = $notasBoletinController->datosVista($curso_filter, $estudiante_filter, $cuatrimestre_filter);
$cursos = $vistaBoletin['cursos'];
$estudiantes = $vistaBoletin['estudiantes'];
$materias = $vistaBoletin['materias'];
$materias_catalogo = $vistaBoletin['materias_catalogo'];
$boletin_organizado = $vistaBoletin['boletin_organizado'];
$total_estudiantes = $vistaBoletin['total_estudiantes'];
$curso_info_boletin = $vistaBoletin['curso_info_boletin'];

// Para profesor: filtrar listas a solo sus cursos y materias asignadas
if ($es_profesor_notas) {
    $cursos = array_values(array_filter($cursos, static fn ($c) => in_array((int) $c['id'], $profesor_cids_notas, true)));
    $estudiantes = array_values(array_filter($estudiantes, static fn ($e) => in_array((int) ($e['curso_id'] ?? 0), $profesor_cids_notas, true)));
    if ($curso_filter !== '') {
        $profesor_mids_curso = profesor_materia_ids_en_curso((int) $curso_filter);
        $materias = array_values(array_filter($materias, static fn ($m) => in_array((int) $m['id'], $profesor_mids_curso, true)));
    }
}

if ($es_preceptor_notas) {
    $cursos = array_values(array_filter($cursos, static fn ($c) => in_array((int) $c['id'], $preceptor_cids_notas, true)));
    $estudiantes = array_values(array_filter($estudiantes, static fn ($e) => in_array((int) ($e['curso_id'] ?? 0), $preceptor_cids_notas, true)));
}

$estudiantes_modal_recup = [];
if ($curso_filter !== '') {
    $estudiantes_modal_recup = array_values(array_filter(
        $estudiantes,
        static fn ($e) => (string) ($e['curso_id'] ?? '') === (string) $curso_filter
    ));
}
if ($estudiante_filter !== '') {
    $estudiantes_modal_recup = array_values(array_filter(
        $estudiantes_modal_recup,
        static fn ($e) => (string) ($e['id'] ?? '') === (string) $estudiante_filter
    ));
}

$boletin_tabla = $notasBoletinController->prepararVistaTablaBoletin(
    $boletin_organizado,
    $materias,
    $cuatrimestre_filter,
    $puede_gestionar_notas,
    $vistaBoletin['restricciones_estudiantes'] ?? []
);

$titulo_boletin_linea = '';
if ($curso_info_boletin !== null) {
    $espNombreBoletin = !empty($curso_info_boletin['especialidad'])
        ? (string) $curso_info_boletin['especialidad']
        : '';
    $titulo_boletin_linea = (string) $curso_info_boletin['anio'] . '° ' . (string) $curso_info_boletin['division'] . ($espNombreBoletin !== '' ? ' - ' . $espNombreBoletin : '');
} else {
    $titulo_boletin_linea = 'Curso';
}
if ($estudiante_filter !== '' && $estudiante_info !== null) {
    $titulo_boletin_linea .= ' - ' . $estudiante_info['apellido'] . ', ' . $estudiante_info['nombre'];
}

$GLOBALS['css_path'] = 'css/style.css?v=' . filemtime(__DIR__ . '/css/style.css');
$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/notas.css?v=' . filemtime(__DIR__ . '/css/notas.css') . '">' . "\n";

sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="notas-section">
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('grades.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="notas-section__header-actions">
        <?php if ($puede_gestionar_notas): ?>
        <a href="grades.php?action=nueva" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('grades.add_grade'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <?php endif; ?>

        <?php if ($estudiante_filter !== '' && $estudiante_info !== null): ?>
        <a href="print_report_card.php?id=<?php echo (int) $estudiante_info['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
           target="_blank"
           class="btn btn-success">
            <i class="fas fa-print"></i><?php echo htmlspecialchars(__('auto.imprimir_bolet_n'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
        </div>
    </div>

    <?php if ($es_profesor_notas): ?>
        <div class="grade-correction-status-alert <?php echo $modificacionHabilitadaDocente ? 'grade-correction-status-alert--open' : 'grade-correction-status-alert--closed'; ?>" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem; border-radius: 12px; border: 1px solid; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); <?php echo $modificacionHabilitadaDocente ? 'background-color: #ecfdf5; border-color: #a7f3d0;' : 'background-color: #fffbeb; border-color: #fde68a;'; ?>">
            <div class="status-alert-icon" style="font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">
                <?php if ($modificacionHabilitadaDocente): ?>
                    <i class="fas fa-unlock-alt" style="color: #059669;"></i>
                <?php else: ?>
                    <i class="fas fa-lock" style="color: #d97706;"></i>
                <?php endif; ?>
            </div>
            <div class="status-alert-content" style="flex: 1;">
                <h4 style="margin: 0 0 0.25rem; font-weight: 700; font-size: 1rem; color: <?php echo $modificacionHabilitadaDocente ? '#065f46' : '#92400e'; ?>;">
                    <?php echo $modificacionHabilitadaDocente ? 'Período de modificaciones habilitado' : 'Período de modificaciones cerrado'; ?>
                </h4>
                <p style="margin: 0; font-size: 0.875rem; line-height: 1.4; color: <?php echo $modificacionHabilitadaDocente ? '#047857' : '#b45309'; ?>;">
                    <?php
                    if ($modificacionHabilitadaDocente) {
                        if ($correctionPeriodInfo['status'] === 'open_manual') {
                            echo 'Las modificaciones de notas están habilitadas temporalmente por la dirección o preceptoría.';
                        } else {
                            $endDateFormatted = $correctionPeriodInfo['end_date'] ? date('d/m/Y', strtotime($correctionPeriodInfo['end_date'])) : '';
                            echo 'Las modificaciones de notas están habilitadas hasta el <strong>' . htmlspecialchars($endDateFormatted, ENT_QUOTES, 'UTF-8') . '</strong> inclusive.';
                        }
                    } else {
                        if ($correctionPeriodInfo['start_date']) {
                            $startDateFormatted = date('d/m/Y', strtotime($correctionPeriodInfo['start_date']));
                            echo 'Las modificaciones de notas están deshabilitadas. Próxima apertura programada: <strong>' . htmlspecialchars($startDateFormatted, ENT_QUOTES, 'UTF-8') . '</strong>.';
                        } else {
                            echo 'Actualmente no se permiten modificaciones de notas por parte de los docentes.';
                        }
                    }
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    


    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_estudiantes); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.estudiantes_en_el_curso'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-book"></i></div>
            <div class="stat-content">
                <h3><?php echo count($materias); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.materias'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('grades.report_card_filters'), ENT_QUOTES, 'UTF-8'); ?><?php if ($estudiante_filter && isset($estudiante_info)): ?>
                    <span class="badge badge-info notas-filtro-badge"><?php echo htmlspecialchars(__('students.student'), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($estudiante_info['apellido'] . ', ' . $estudiante_info['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
            </h3>
        </div>
        <form method="GET" class="form-container">
            <?php if ($estudiante_filter): ?>
                <input type="hidden" name="estudiante" value="<?php echo htmlspecialchars($estudiante_filter, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="curso"><?php echo htmlspecialchars(__('courses.course'), ENT_QUOTES, 'UTF-8'); ?> *</label>
                    <select name="curso" id="curso" required>
                        <option value=""><?php echo htmlspecialchars(__('courses.select_course'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos as $curso): ?>
                        <?php $cursoEspecialidad = $curso['especialidad'] ? $curso['especialidad'] : 'Sin especialidad'; ?>
                        <option value="<?php echo (int) $curso['id']; ?>" <?php echo $curso_filter == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $curso['anio'] . '° ' . (string) $curso['division'] . ' - ' . (string) $cursoEspecialidad, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="boletin_cuatrimestre_filtro"><?php echo htmlspecialchars(__('grades.period'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <?php $termsCountConfig = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount($databaseAdapter->getPdo()); ?>
                    <select name="trimestre" id="boletin_cuatrimestre_filtro">
                        <option value=""><?php echo htmlspecialchars(__('grades.all_terms'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php if ($termsCountConfig === 3): ?>
                            <option value="1" <?php echo $cuatrimestre_filter == '1' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term1'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="2" <?php echo $cuatrimestre_filter == '2' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term2'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="3" <?php echo $cuatrimestre_filter == '3' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term3'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php elseif ($termsCountConfig === 4): ?>
                            <option value="1" <?php echo $cuatrimestre_filter == '1' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term1'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="2" <?php echo $cuatrimestre_filter == '2' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term2'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="3" <?php echo $cuatrimestre_filter == '3' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term3'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="4" <?php echo $cuatrimestre_filter == '4' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term4'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php else: ?>
                            <option value="avance1" <?php echo $cuatrimestre_filter === 'avance1' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term1_preview'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="1" <?php echo $cuatrimestre_filter == '1' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term1'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="avance2" <?php echo $cuatrimestre_filter === 'avance2' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term2_preview'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="2" <?php echo $cuatrimestre_filter == '2' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('student.term2'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endif; ?>
                        <option value="final" <?php echo $cuatrimestre_filter == 'final' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.nota_final_promedio'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <p class="form-label-like form-label-heading-notas" id="info_cuatrimestre_heading"><?php echo htmlspecialchars(__('grades.info'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <div id="info_cuatrimestre" class="form-info-box form-info-box-notas" role="note" aria-labelledby="info_cuatrimestre_heading">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(__('grades.report_card_info_text'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
        </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <?php echo htmlspecialchars(__('grades.show_report_card'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="grades.php" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo htmlspecialchars(__('students.btn_clear'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>

    <?php if ($curso_filter && !empty($boletin_organizado)): ?>
    <div class="card card--boletin">
        <div class="card-header">
             <h3 class="card-title">
                 📋 <?php echo htmlspecialchars(__('grades.full_report_card'), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($titulo_boletin_linea, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars(__('grades.all_terms'), ENT_QUOTES, 'UTF-8'); ?>)
             </h3>
        </div>

        <div class="boletin-container" id="boletin-scroll-area">
            <table class="boletin-table boletin-table--vertical">
                <thead>
                    <tr>
                        <th class="estudiante-col"><?php echo htmlspecialchars(__('auto.estudiante'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th class="materia-name-col"><?php echo htmlspecialchars(__('auto.materia'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php foreach ($boletin_tabla['subcolumnas'] as $sub): ?>
                        <th class="<?php echo htmlspecialchars($sub['clase_th'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($sub['etiqueta'], ENT_QUOTES, 'UTF-8'); ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $colspanMateria = (int) $boletin_tabla['colspan_por_materia'];
                    $totalMaterias = count($materias);
                    ?>
                    <?php
                    $backQuery = [];
                    if ($curso_filter !== '') { $backQuery['curso'] = $curso_filter; }
                    if ($cuatrimestre_filter !== '') { $backQuery['periodo'] = $cuatrimestre_filter; }
                    if ($estudiante_filter !== '') { $backQuery['estudiante'] = $estudiante_filter; }
                    $backToUrl = $backQuery !== [] ? 'grades.php?' . http_build_query($backQuery) : 'grades.php';
                    ?>
                    <?php foreach ($boletin_tabla['filas'] as $fila):
                        $estudianteNombre = htmlspecialchars($fila['estudiante']['apellido'] . ', ' . $fila['estudiante']['nombre'], ENT_QUOTES, 'UTF-8');
                        $estudianteDni = htmlspecialchars((string) ($fila['estudiante']['dni'] ?? ''), ENT_QUOTES, 'UTF-8');
                    ?>
                    <?php foreach ($materias as $mi => $materia):
                        $celdasMateria = array_slice($fila['celdas'], $mi * $colspanMateria, $colspanMateria);
                        $palabrasMateria = explode(' ', (string) $materia['nombre']);
                        $nombreCortoMateria = implode(' ', array_slice($palabrasMateria, 0, 4));
                        if (count($palabrasMateria) > 4) { $nombreCortoMateria .= '...'; }
                    ?>
                    <tr class="<?php echo $mi === 0 ? 'boletin-row--first' : ''; ?> <?php echo $mi === $totalMaterias - 1 ? 'boletin-row--last' : ''; ?>">
                        <?php if ($mi === 0): ?>
                        <td class="estudiante-cell" rowspan="<?php echo $totalMaterias; ?>">
                            <div class="estudiante-cell__head">
                                <strong class="estudiante-cell__nombre">
                                    <a href="student_profile.php?id=<?php echo (int) $fila['estudiante_id']; ?>&back_to=<?php echo urlencode($backToUrl); ?>" class="estudiante-link" title="Ver ficha del estudiante">
                                        <?php echo $estudianteNombre; ?>
                                    </a>
                                </strong>
                                <a href="print_report_card.php?id=<?php echo (int) $fila['estudiante_id']; ?>&csrf_token=<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                                   target="_blank"
                                   class="estudiante-cell__print-btn"
                                   title="Imprimir boletín"
                                   aria-label="Imprimir boletín">
                                    <i class="fas fa-print" aria-hidden="true"></i>
                                </a>

                            </div>
                            <small class="estudiante-cell__dni"><?php echo htmlspecialchars(__('auto.dni'), ENT_QUOTES, 'UTF-8'); ?><?php echo $estudianteDni; ?></small>
                        </td>
                        <?php endif; ?>
                        <td class="materia-name-cell"><?php echo htmlspecialchars($nombreCortoMateria, ENT_QUOTES, 'UTF-8'); ?></td>
                        <?php foreach ($celdasMateria as $celda):
                            $tdClassCelda = 'nota-cell';
                            if (!empty($celda['td_class_extra'])) {
                                $tdClassCelda .= ' ' . $celda['td_class_extra'];
                            }
                        ?>
                        <td class="<?php echo htmlspecialchars($tdClassCelda, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($celda['tipo'] === 'form_cuatrimestre'): ?>
                                <form method="POST" class="nota-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="estudiante_id" value="<?php echo (int) $celda['estudiante_id']; ?>">
                                    <input type="hidden" name="materia_id" value="<?php echo (int) $celda['materia_id']; ?>">
                                    <input type="hidden" name="trimestre" value="<?php echo htmlspecialchars((string) $celda['cuatrimestre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (!empty($celda['nota_id'])): ?>
                                        <input type="hidden" name="nota_id" value="<?php echo (int) $celda['nota_id']; ?>">
                                    <?php endif; ?>
                                    <div class="nota-input-group">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               max="10"
                                               name="nota"
                                               value="<?php echo htmlspecialchars($celda['nota_valor'], ENT_QUOTES, 'UTF-8'); ?>"
                                               placeholder="-"
                                               class="nota-input">
                                        <input type="text"
                                               name="observaciones"
                                               value="<?php echo htmlspecialchars($celda['observaciones'], ENT_QUOTES, 'UTF-8'); ?>"
                                               placeholder="Obs."
                                               class="obs-input"
                                               title="Observaciones">
                                    </div>
                                    <input type="hidden" name="<?php echo !empty($celda['usa_actualizar']) ? 'actualizar_nota' : 'insertar_nota'; ?>" value="1">
                                </form>
                            <?php elseif ($celda['tipo'] === 'form_avance'): ?>
                                <div class="avance-selector">
                                    <form method="POST" class="avance-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="estudiante_id" value="<?php echo (int) $celda['estudiante_id']; ?>">
                                        <input type="hidden" name="materia_id" value="<?php echo (int) $celda['materia_id']; ?>">
                                        <input type="hidden" name="etapa" value="<?php echo htmlspecialchars($celda['etapa'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="avance-input-group">
                                            <select name="valor" class="avance-select">
                                                <option value="" <?php echo $celda['valor_actual'] === '' ? 'selected' : ''; ?>>-</option>
                                                <option value="TEA" <?php echo $celda['valor_actual'] === 'TEA' ? 'selected' : ''; ?><?php echo htmlspecialchars(__('auto.tea'), ENT_QUOTES, 'UTF-8'); ?></option>
                                                <option value="TEP" <?php echo $celda['valor_actual'] === 'TEP' ? 'selected' : ''; ?><?php echo htmlspecialchars(__('auto.tep'), ENT_QUOTES, 'UTF-8'); ?></option>
                                                <option value="TED" <?php echo $celda['valor_actual'] === 'TED' ? 'selected' : ''; ?><?php echo htmlspecialchars(__('auto.ted'), ENT_QUOTES, 'UTF-8'); ?></option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="guardar_avance" value="1">
                                    </form>
                                </div>
                            <?php elseif ($celda['tipo'] === 'vista_avance'): ?>
                                <div class="nota-display">
                                    <span class="nota-value"><?php echo htmlspecialchars($celda['texto'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="nota-display">
                                    <span class="nota-value<?php echo !empty($celda['clase_span_extra']) ? ' ' . htmlspecialchars($celda['clase_span_extra'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                                        <?php echo htmlspecialchars($celda['linea1'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <?php if (!empty($celda['observaciones'])): ?>
                                        <br><small class="obs-display"><?php echo htmlspecialchars($celda['observaciones'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($curso_filter): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-center"><?php echo htmlspecialchars(__('auto.a_n_no_hay_estudiantes_en_este_curso'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($puede_gestionar_notas && $action === 'nueva'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?php echo htmlspecialchars(__('auto.cargar_nueva_nota_individual'), ENT_QUOTES, 'UTF-8'); ?></h3></div>
        <form method="POST" class="form-container">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="curso_id_nota">Curso *</label>
                    <select name="curso_id" id="curso_id_nota" required>
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos as $curso): ?>
                        <?php $cursoEspecialidad = $curso['especialidad'] ? $curso['especialidad'] : 'Sin especialidad'; ?>
                        <option value="<?php echo (int) $curso['id']; ?>" <?php echo $form_values['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $curso['anio'] . '° ' . (string) $curso['division'] . ' - ' . (string) $cursoEspecialidad, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estudiante_id">Estudiante *</label>
                    <select name="estudiante_id" id="estudiante_id" required>
                        <option value=""><?php echo htmlspecialchars(__('auto.seleccionar'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($estudiantes as $est): ?>
                        <?php 
                            $cursoAsignado = $est['curso_id'] ?? '';
                            $etiquetaCurso = (isset($est['anio']) && $est['anio'])
                                ? $est['anio'] . '° ' . $est['division']
                                : 'Sin curso';
                        ?>
                        <option value="<?php echo (int) $est['id']; ?>" 
                                data-curso-id="<?php echo htmlspecialchars((string) $cursoAsignado, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $form_values['estudiante_id'] == $est['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($est['apellido'] . ', ' . $est['nombre'] . ' - ' . ($etiquetaCurso), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="materia_id">Materia *</label>
                    <select name="materia_id" id="materia_id" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($materias_catalogo as $mat): ?>
                        <option value="<?php echo (int) $mat['id']; ?>" 
                                data-cursos="<?php echo htmlspecialchars((string) $mat['cursos_asignados'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $form_values['materia_id'] == $mat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $mat['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_registro">Tipo de registro *</label>
                    <select name="tipo_registro" id="tipo_registro" required>
                        <option value="numerica" <?php echo $form_values['tipo_registro'] === 'numerica' ? 'selected' : ''; ?><?php echo htmlspecialchars(__('auto.nota_num_rica'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="avance" <?php echo $form_values['tipo_registro'] === 'avance' ? 'selected' : ''; ?><?php echo htmlspecialchars(__('auto.avance_tea_tep_ted'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                <div class="form-group nota-only">
                    <label for="nota_cuatrimestre">Cuatrimestre *</label>
                    <select name="trimestre" id="nota_cuatrimestre">
                        <option value="">Seleccionar</option>
                        <option value="1" <?php echo $form_values['trimestre'] == '1' ? 'selected' : ''; ?>>1° Cuatrimestre</option>
                        <option value="2" <?php echo $form_values['trimestre'] == '2' ? 'selected' : ''; ?>>2° Cuatrimestre</option>
                    </select>
                </div>
                <div class="form-group avance-only">
                    <label for="etapa_avance">Etapa de avance *</label>
                    <select name="etapa_avance" id="etapa_avance">
                        <option value="">Seleccionar</option>
                        <option value="avance1" <?php echo $form_values['etapa_avance'] == 'avance1' ? 'selected' : ''; ?>>Avance 1 (1° cuatrimestre)</option>
                        <option value="avance2" <?php echo $form_values['etapa_avance'] == 'avance2' ? 'selected' : ''; ?>>Avance 2 (2° cuatrimestre)</option>
                    </select>
                </div>
                <div class="form-group avance-only">
                    <label for="valor_avance">Valor *</label>
                    <select name="valor_avance" id="valor_avance">
                        <option value="">Seleccionar</option>
                        <option value="TEA" <?php echo $form_values['valor_avance'] == 'TEA' ? 'selected' : ''; ?>>TEA</option>
                        <option value="TEP" <?php echo $form_values['valor_avance'] == 'TEP' ? 'selected' : ''; ?>>TEP</option>
                        <option value="TED" <?php echo $form_values['valor_avance'] == 'TED' ? 'selected' : ''; ?>>TED</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group nota-only">
                    <label for="nota"><?php echo htmlspecialchars(__('auto.nota_num_rica'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="number" step="0.01" min="0" max="10" name="nota" id="nota" 
                           value="<?php echo htmlspecialchars($form_values['nota'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej: 7.50">
                </div>
                <div class="form-group">
                    <label for="observaciones"><?php echo htmlspecialchars(__('auto.observaciones'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="observaciones" id="observaciones" 
                           value="<?php echo htmlspecialchars($form_values['observaciones'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Observaciones (opcional)">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="insertar_nota" class="btn btn-primary"><i class="fas fa-save"></i><?php echo htmlspecialchars(__('auto.guardar'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="grades.php" class="btn btn-secondary"><i class="fas fa-times"></i><?php echo htmlspecialchars(__('auto.cancelar'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>
    <?php endif; ?>


</section>

<?php
$notasNonce = htmlspecialchars((string) ($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8');
$filtrosDependientesVersion = @filemtime(__DIR__ . '/js/filtros_dependientes.js') ?: time();
$notasVersion = @filemtime(__DIR__ . '/js/notas.js') ?: time();
?>
<script src="js/filtros_dependientes.js?v=<?php echo rawurlencode((string) $filtrosDependientesVersion); ?>" defer nonce="<?php echo $notasNonce; ?>"></script>
<script src="js/notas.js?v=<?php echo rawurlencode((string) $notasVersion); ?>" defer nonce="<?php echo $notasNonce; ?>"></script>
<?php include 'includes/footer.php'; ?> 
