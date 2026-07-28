<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Controllers\EstudianteController;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioEstudiantes;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

$usuario = $servicioAutenticacion->verificarSesion();
if ($usuario === null) {
    header('Location: ' . app_base_path('public/login.php'));
    exit();
}

require_once __DIR__ . '/includes/preceptor_scope.php';
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/profesor_scope.php';

$preceptor_cids = preceptor_curso_ids();
$es_profesor_est = es_profesor();
$profesor_cids_est = $es_profesor_est ? profesor_curso_ids() : [];
$cursos_alcance_estudiantes = null;
if ($es_profesor_est) {
    $cursos_alcance_estudiantes = $profesor_cids_est;
} elseif (($usuario['rol'] ?? '') === 'preceptor') {
    $cursos_alcance_estudiantes = $preceptor_cids;
}
$preceptor_sin_curso = (($usuario['rol'] ?? '') === 'preceptor') && $preceptor_cids === [];
$profesor_sin_curso = $es_profesor_est && $profesor_cids_est === [];
$bloqueado_sin_cursos = $preceptor_sin_curso || $profesor_sin_curso;
$pageTitle = __('students.title') . ' - ' . \SistemaAdmin\Bootstrap\AppRequestInit::systemName();

$estudianteMapper = new EstudianteMapper($databaseAdapter);
$servicioEstudiantes = new ServicioEstudiantes($databaseAdapter, $estudianteMapper);
$estudianteController = new EstudianteController($databaseAdapter, $servicioEstudiantes);

$action = trim((string) (filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?? ''));
if ($action === 'nuevo' && $es_profesor_est) {
    header('Location: ' . app_base_path('students.php'));
    exit();
}

$success_message = '';
$error_message = '';
$csrfToken = getCSRFToken();

$form_values = [
    'dni' => '',
    'dni_responsable' => '',
    'apellido' => '',
    'nombre' => '',
    'fecha_nacimiento' => '',
    'grupo_sanguineo' => '',
    'obra_social' => '',
    'domicilio' => '',
    'telefono_fijo' => '',
    'telefono_celular' => '',
    'email' => '',
    'curso_id' => '',
    'grupo_taller' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['importar_excel'])) {
        if ($es_profesor_est) {
            $error_message = 'Los docentes no pueden importar estudiantes.';
            $action = 'importar';
        } elseif (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
            $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
            $action = 'importar';
        } else {
            $res = $estudianteController->importarExcel($_FILES['excel_file'] ?? [], $preceptor_cids);
            if ($res['success']) {
                $success_message = sprintf(
                    '¡Importación completada con éxito para el curso %s! Estudiantes importados: %d. Omitidos por DNI duplicado: %d.',
                    $res['curso'],
                    $res['importados'],
                    $res['duplicados']
                );
                if (!empty($res['errores'])) {
                    $error_message = "Algunos registros tuvieron errores:\n" . implode("\n", $res['errores']);
                }
                $action = '';
            } else {
                $error_message = $res['error'] ?? 'Error desconocido al importar.';
                $action = 'importar';
            }
        }
    } elseif (isset($_POST['guardar_estudiante']) || isset($_POST['eliminar_estudiante'])) {
        if ($es_profesor_est) {
            $error_message = 'Los docentes no pueden crear ni eliminar estudiantes.';
            if (isset($_POST['guardar_estudiante'])) {
                $action = '';
            }
        } elseif (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
            $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
            if (isset($_POST['guardar_estudiante'])) {
                $action = 'nuevo';
                $form_values = $estudianteController->formValuesEstudianteCreacionDesdePost($_POST);
            }
        } else {
            $postOutcome = $estudianteController->procesarPostEstudiantes($_POST, $preceptor_cids, $preceptor_sin_curso);
            if ($postOutcome['redirect'] !== null) {
                header('Location: ' . $postOutcome['redirect']);
                exit();
            }
            $error_message = $postOutcome['error'];
            if ($postOutcome['action'] !== null) {
                $action = $postOutcome['action'];
            }
            if ($postOutcome['form_values'] !== null) {
                $form_values = $postOutcome['form_values'];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $successKey = trim((string) (filter_input(INPUT_GET, 'success', FILTER_DEFAULT) ?? ''));
    if ($successKey !== '') {
        switch ($successKey) {
            case 'creado':
                $success_message = 'Estudiante registrado correctamente';
                break;
            case 'eliminado':
                $nombreEsc = htmlspecialchars(
                    (string) (filter_input(INPUT_GET, 'nombre', FILTER_DEFAULT) ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                );
                if ($nombreEsc !== '') {
                    $success_message = 'Estudiante ' . $nombreEsc . ' eliminado correctamente';
                } else {
                    $success_message = 'Estudiante eliminado correctamente';
                }
                break;
        }
    }
}

$search = trim((string) (filter_input(INPUT_GET, 'search', FILTER_DEFAULT) ?? ''));
$cursoGet = filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT);
$cursoFromGet = ($cursoGet !== false && $cursoGet > 0) ? (string) $cursoGet : '';
$curso_filter = EstudianteController::resolverFiltroCursoPreceptor($cursos_alcance_estudiantes, $cursoFromGet);

$grupoTallerFilter = trim((string) (filter_input(INPUT_GET, 'grupo_taller', FILTER_DEFAULT) ?? ''));
if (!in_array($grupoTallerFilter, ['sin_grupo', 'A', 'B', 'C', 'D', 'E'], true)) {
    $grupoTallerFilter = '';
}

$pageListado = max(1, (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$perPageListado = 20;

$cursos = $estudianteController->cursosParaVistaEstudiantes($cursos_alcance_estudiantes);
$listadoPaginado = $estudianteController->datosListadoPaginadoParaVista(
    $search,
    $curso_filter,
    $cursos_alcance_estudiantes,
    $bloqueado_sin_cursos,
    $pageListado,
    $perPageListado,
    $grupoTallerFilter
);
$estudiantes_data = $estudianteController->enriquecerFilasListadoParaVista($listadoPaginado['filas'], $cursos);
$pagination = $listadoPaginado['pagination'];
$total_listado_filtrado = $listadoPaginado['total_filtrado'];

$estudiantesUrlParams = [];
if ($search !== '') {
    $estudiantesUrlParams['search'] = $search;
}
if ($curso_filter !== '') {
    $estudiantesUrlParams['curso'] = $curso_filter;
}
if ($grupoTallerFilter !== '') {
    $estudiantesUrlParams['grupo_taller'] = $grupoTallerFilter;
}

$estadisticas = $estudianteController->estadisticas();
$totalesVista = $estudianteController->totalesEncabezadoVista($total_listado_filtrado, $curso_filter, $cursos_alcance_estudiantes, $estadisticas);
$total_estudiantes = $totalesVista['total_estudiantes'];
$estudiantes_sin_curso = $totalesVista['estudiantes_sin_curso'];

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="' . htmlspecialchars(app_base_path('css/estudiantes.css'), ENT_QUOTES, 'UTF-8') . '">' . "\n";

sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="estudiantes-section">
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('students.management_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="header-actions" style="display: flex; gap: 0.5rem; align-items: center;">
            <?php if (!$bloqueado_sin_cursos && !$es_profesor_est): ?>
            <a href="<?php echo htmlspecialchars(app_base_path('students.php?action=importar'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary" style="background-color: #107c41; color: white; border-color: #107c41; display: inline-flex; align-items: center; gap: 0.35rem;">
                <i class="fas fa-file-excel"></i> <span class="btn-text"><?php echo htmlspecialchars(__('students.import_excel'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <a href="<?php echo htmlspecialchars(app_base_path('students.php?action=nuevo'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                <i class="fas fa-plus"></i> <span class="btn-text"><?php echo htmlspecialchars(__('students.add_student'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($es_profesor_est && $profesor_cids_est !== []): ?>
    <div class="alert alert-info alert-preceptor">
        <i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.vista_docente_solo_estudiantes_de_los_cursos'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php elseif ($es_profesor_est && $profesor_sin_curso): ?>
    <div class="alert alert-error alert-preceptor">
        <i class="fas fa-exclamation-triangle"></i> No tiene cursos asignados como docente. Solicite en secretaría que le asignen materias en <strong>Profesores</strong> (relación curso–materia).
    </div>
    <?php elseif ($preceptor_cids !== []): ?>
    <div class="alert alert-info alert-preceptor">
        <i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.vista_de_preceptor_solo_sus_cursos_asignados'), ENT_QUOTES, 'UTF-8'); ?><strong><?php echo htmlspecialchars(preceptor_curso_etiqueta()); ?></strong>).
    </div>
    <?php elseif ($preceptor_sin_curso): ?>
    <div class="alert alert-error alert-preceptor">
        <i class="fas fa-exclamation-triangle"></i> Su usuario de preceptor no tiene cursos de trabajo asignados. Solicite al administrador o a dirección que los configuren en la sección Preceptores.
    </div>
    <?php endif; ?>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo nl2br(htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8')); ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_estudiantes, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('students.total_students'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <?php if ($estudiantes_sin_curso > 0): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($estudiantes_sin_curso, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('students.unassigned_students'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($action === 'importar'): ?>
    <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid #107c41;">
        <div class="card-header" style="background-color: #f3fcf7;">
            <h3 class="card-title" style="color: #107c41; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-file-excel"></i><?php echo htmlspecialchars(__('auto.importar_estudiantes_desde_plantilla_excel'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="POST" class="form-container" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-info" style="margin-bottom: 1rem;">
                <p>Cargue un archivo de plantilla Excel con macros (.xlsm) con el formato predeterminado. El sistema realizará las siguientes acciones automáticamente:</p>
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; list-style-type: disc;">
                    <li>Detectará el curso en la celda <strong>A1</strong> (por ejemplo: "3ro. 1ra."). Si el curso no existe o está desactivado, el sistema lo creará/activará de manera automática.</li>
                    <li>Buscará el turno en <strong>C1</strong> y especialidad en <strong>D1</strong> para configurar el curso si es nuevo.</li>
                    <li><?php echo htmlspecialchars(__('auto.importar_todos_los_estudiantes_listados_des'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><?php echo htmlspecialchars(__('auto.omitir_registros_con_dni_que_ya_existan_en'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><?php echo htmlspecialchars(__('auto.establecer_el_dni_del_estudiante_como_su_dn'), ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="excel_file"><?php echo htmlspecialchars(__('auto.archivo_de_plantilla_xlsm'), ENT_QUOTES, 'UTF-8'); ?><span class="required">*</span></label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsm" required 
                           style="padding: 0.5rem; border: 1.5px dashed #107c41; border-radius: 6px; width: 100%; background: #fafdfb;">
                </div>
            </div>
            <div class="form-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <button type="submit" name="importar_excel" class="btn btn-primary" style="background-color: #107c41; border-color: #107c41; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="fas fa-upload"></i><?php echo htmlspecialchars(__('auto.procesar_e_importar'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="<?php echo htmlspecialchars(app_base_path('students.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i><?php echo htmlspecialchars(__('auto.cancelar'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.add_student'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="POST" class="form-container" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-info">
                <p><span class="required">*</span> <?php echo htmlspecialchars(__('students.required_fields'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="dni"><?php echo htmlspecialchars(__('students.th_dni'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="dni" id="dni" required maxlength="20"
                           placeholder="12345678" pattern="[0-9A-Za-z\.\-]{5,20}"
                           title="5 a 20 caracteres"
                           value="<?php echo htmlspecialchars($form_values['dni'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="dni_responsable"><?php echo htmlspecialchars(__('students.guardian_dni'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="dni_responsable" id="dni_responsable" required maxlength="20"
                           placeholder="Documento / ID para ingresar al portal"
                           pattern="[0-9A-Za-z\.\-]{5,20}" title="5 a 20 caracteres"
                           value="<?php echo htmlspecialchars($form_values['dni_responsable'], ENT_QUOTES, 'UTF-8'); ?>">
                    <small class="form-hint"><?php echo htmlspecialchars(__('students.guardian_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>

                <div class="form-group">
                    <label for="apellido"><?php echo htmlspecialchars(__('students.last_name'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="apellido" id="apellido" required maxlength="100"
                           minlength="2" title="Mínimo 2 caracteres"
                           value="<?php echo htmlspecialchars($form_values['apellido'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="nombre"><?php echo htmlspecialchars(__('students.first_name'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="nombre" id="nombre" required maxlength="100"
                           minlength="2" title="Mínimo 2 caracteres"
                           value="<?php echo htmlspecialchars($form_values['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_nacimiento"><?php echo htmlspecialchars(__('students.birth_date'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                           value="<?php echo htmlspecialchars($form_values['fecha_nacimiento'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="grupo_sanguineo"><?php echo htmlspecialchars(__('students.blood_type'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="grupo_sanguineo" id="grupo_sanguineo">
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php
                        $gs = $form_values['grupo_sanguineo'];
                        foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $opt):
                        ?>
                        <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $gs === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="obra_social"><?php echo htmlspecialchars(__('students.health_insurance'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="text" name="obra_social" id="obra_social" maxlength="100"
                           value="<?php echo htmlspecialchars($form_values['obra_social'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefono_fijo"><?php echo htmlspecialchars(__('students.landline_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="tel" name="telefono_fijo" id="telefono_fijo" maxlength="20"
                           value="<?php echo htmlspecialchars($form_values['telefono_fijo'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="telefono_celular"><?php echo htmlspecialchars(__('students.mobile_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="tel" name="telefono_celular" id="telefono_celular" maxlength="20"
                           value="<?php echo htmlspecialchars($form_values['telefono_celular'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(__('students.email'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="email" name="email" id="email" maxlength="100"
                           value="<?php echo htmlspecialchars($form_values['email'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="domicilio"><?php echo htmlspecialchars(__('students.address'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <textarea name="domicilio" id="domicilio" placeholder="<?php echo htmlspecialchars(__('students.address_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($form_values['domicilio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="curso_id"><?php echo htmlspecialchars(__('students.course'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="curso_id" id="curso_id">
                        <option value=""><?php echo htmlspecialchars(__('students.assign_later'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos as $curso): ?>
                        <?php
                            $cursoEspecialidad = !empty($curso['especialidad']) ? $curso['especialidad'] : '';
                            $cid = (int) $curso['id'];
                        ?>
                        <option value="<?php echo $cid; ?>"
                                <?php echo $form_values['curso_id'] !== '' && $form_values['curso_id'] === (string) $cid ? 'selected' : ''; ?>>
                            <?php echo (int) $curso['anio'] . '° ' . htmlspecialchars((string) ($curso['division'] ?? ''), ENT_QUOTES, 'UTF-8') . ($cursoEspecialidad !== '' ? ' - ' . htmlspecialchars((string) $cursoEspecialidad, ENT_QUOTES, 'UTF-8') : ''); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="foto"><?php echo htmlspecialchars(__('students.profile_photo'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/gif">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="guardar_estudiante" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('students.save_student'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="<?php echo htmlspecialchars(app_base_path('students.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.search_filters'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><?php echo htmlspecialchars(__('action.search'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="text" name="search" id="search"
                           value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="<?php echo htmlspecialchars(__('students.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="curso"><?php echo htmlspecialchars(__('courses.division'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="curso" id="curso">
                        <option value=""><?php echo htmlspecialchars(__('students.all_courses'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos as $curso): ?>
                        <?php
                            $cursoEspecialidad = !empty($curso['especialidad']) ? $curso['especialidad'] : '';
                            $cid = (int) $curso['id'];
                        ?>
                        <option value="<?php echo $cid; ?>"
                                <?php echo $curso_filter !== '' && $curso_filter === (string) $cid ? 'selected' : ''; ?>>
                            <?php echo (int) $curso['anio'] . '° ' . htmlspecialchars((string) ($curso['division'] ?? ''), ENT_QUOTES, 'UTF-8') . ($cursoEspecialidad !== '' ? ' - ' . htmlspecialchars((string) $cursoEspecialidad, ENT_QUOTES, 'UTF-8') : ''); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="<?php echo htmlspecialchars(app_base_path('students.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('students.btn_clear'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.registered_students'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo number_format($total_listado_filtrado, 0, ',', '.'); ?>)</h3>
            <?php if ($total_listado_filtrado > 0 && (int) $pagination['total_pages'] > 1): ?>
            <p class="pagination-summary" style="margin: 0.35rem 0 0; font-size: 0.9rem; color: var(--secondary-color, #64748b);"><?php echo htmlspecialchars(__('auto.p_gina'), ENT_QUOTES, 'UTF-8'); ?><?php echo (int) $pagination['current_page']; ?> de <?php echo (int) $pagination['total_pages']; ?>
                · <?php echo (int) $pagination['start_item']; ?>–<?php echo (int) $pagination['end_item']; ?> de <?php echo number_format($total_listado_filtrado, 0, ',', '.'); ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(__('students.th_dni'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('students.th_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('students.th_course'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('students.th_contact'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('students.th_actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($estudiantes_data)): ?>
                        <?php foreach ($estudiantes_data as $estudiante): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) ($estudiante['dni'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars((string) (($estudiante['apellido'] ?? '') . ', ' . ($estudiante['nombre'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($estudiante['fecha_nacimiento']) && !empty($estudiante['fecha_nacimiento_dmY'])): ?>
                                <br><small>
                                    <i class="fas fa-birthday-cake"></i>
                                    <?php echo htmlspecialchars((string) $estudiante['fecha_nacimiento_dmY'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo (int) ($estudiante['edad'] ?? 0); ?><?php echo htmlspecialchars(__('auto.a_os'), ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($estudiante['listado_curso_sin_asignar'])): ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_asignar'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php elseif (empty($estudiante['listado_curso_ok'])): ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.curso_no_encontrado'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                     <span class="status status-success">
                                         <?php echo htmlspecialchars((string) $estudiante['listado_curso_anio_div'], ENT_QUOTES, 'UTF-8'); ?>
                                     </span>
                                     <?php if (!empty($estudiante['listado_curso_especialidad'])): ?>
                                         <?php
                                             $esp = (string) $estudiante['listado_curso_especialidad'];
                                             $esp_class = 'status-esp-default';
                                             if (stripos($esp, 'informática') !== false || stripos($esp, 'programación') !== false || stripos($esp, 'inf') !== false) {
                                                 $esp_class = 'status-esp-informatica';
                                             } elseif (stripos($esp, 'electromecánica') !== false || stripos($esp, 'emc') !== false) {
                                                 $esp_class = 'status-esp-electromecanica';
                                             } elseif (stripos($esp, 'construcción') !== false || stripos($esp, 'construcciones') !== false || stripos($esp, 'con') !== false) {
                                                 $esp_class = 'status-esp-construcciones';
                                             } elseif (stripos($esp, 'química') !== false || stripos($esp, 'qui') !== false) {
                                                 $esp_class = 'status-esp-quimica';
                                             } elseif (stripos($esp, 'básico') !== false || stripos($esp, 'basico') !== false) {
                                                 $esp_class = 'status-esp-basico';
                                             }
                                         ?>
                                         <br><span class="status-especialidad <?php echo $esp_class; ?>"><?php echo htmlspecialchars($esp, ENT_QUOTES, 'UTF-8'); ?></span>
                                     <?php endif; ?>
                                    <?php if (!empty($estudiante['grupo_taller'])): ?>
                                        <br>
                                        <span class="status status-primary" style="background-color: #e0f2fe; color: #0369a1; border-color: #bae6fd; font-size: 0.75rem; font-weight: 600; padding: 1px 4px; border-radius: 3px; display: inline-block; margin-top: 3px;">
                                            Grupo <?php echo htmlspecialchars((string) $estudiante['grupo_taller'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <td>
                                <?php if (!empty($estudiante['telefono_celular'])): ?>
                                    <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars((string) $estudiante['telefono_celular'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php elseif (!empty($estudiante['telefono_fijo'])): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars((string) $estudiante['telefono_fijo'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php else: ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_tel_fono'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($estudiante['email'])): ?>
                                    <br><small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string) $estudiante['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acciones-estudiante">
                                <a href="<?php echo htmlspecialchars(app_base_path('student_profile.php?' . http_build_query(['id' => (int) $estudiante['id']])), ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn btn-sm btn-primary" title="Ver ficha completa">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars(app_base_path('student_certificate.php?' . http_build_query(['estudiante_id' => (int) $estudiante['id']])), ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn btn-sm btn-info" target="_blank" title="<?php echo htmlspecialchars(__('auto.imprimir_certificado'), ENT_QUOTES, 'UTF-8'); ?>" style="background-color: #0284c7; color: white; border-color: #0284c7;">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <?php if (!$es_profesor_est): ?>
                                <a href="<?php echo htmlspecialchars(app_base_path('discipline.php?' . http_build_query(['estudiante' => (int) $estudiante['id']])), ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn btn-sm btn-warning" title="Ver llamados">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </a>
                                <?php include __DIR__ . '/includes/estudiantes/partials/form_eliminar_estudiante.php'; ?>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center tabla-vacia">
                                <i class="fas fa-users tabla-vacia__icono"></i>
                                <br><?php echo htmlspecialchars(__('students.no_students_found'), ENT_QUOTES, 'UTF-8'); ?>
                                <br><small><?php echo htmlspecialchars(__('students.no_students_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_listado_filtrado > 0 && (int) $pagination['total_pages'] > 1): ?>
        <nav class="pagination-nav" aria-label="Paginación de estudiantes" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 0.35rem; padding: 1rem 1rem 1.25rem; border-top: 1px solid var(--medium-gray, #e2e8f0);">
            <?php
            $pn = (int) $pagination['current_page'];
            $tp = (int) $pagination['total_pages'];
            $linkBase = $estudiantesUrlParams;
            $mk = static function (array $base, int $p): string {
                $base['page'] = $p;
                $rel = 'students.php?' . http_build_query($base);

                return function_exists('app_base_path') ? app_base_path($rel) : $rel;
            };
            ?>
            <?php if (!empty($pagination['has_previous'])): ?>
            <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($mk($linkBase, $pn - 1), ENT_QUOTES, 'UTF-8'); ?>" rel="prev">« Anterior</a>
            <?php else: ?>
            <span class="btn btn-sm btn-secondary" style="opacity: 0.5; cursor: not-allowed; pointer-events: none;">« Anterior</span>
            <?php endif; ?>

            <?php foreach ($pagination['page_numbers'] as $num): ?>
                <?php $num = (int) $num; ?>
                <?php if ($num === $pn): ?>
            <span class="btn btn-sm btn-primary" aria-current="page"><?php echo $num; ?></span>
                <?php else: ?>
            <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($mk($linkBase, $num), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $num; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($pagination['has_next'])): ?>
            <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($mk($linkBase, $pn + 1), ENT_QUOTES, 'UTF-8'); ?>" rel="next">Siguiente »</a>
            <?php else: ?>
            <span class="btn btn-sm btn-secondary" style="opacity: 0.5; cursor: not-allowed; pointer-events: none;">Siguiente »</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
