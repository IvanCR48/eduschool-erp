<?php 
// Iniciar sesión al principio
require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';

use SistemaAdmin\Controllers\CursoController;
use SistemaAdmin\Mappers\CursoMapper;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioCursos;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

// Verificar si hay sesión activa
$usuario = $servicioAutenticacion->verificarSesion();
if (!$usuario) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

require_once __DIR__ . '/includes/preceptor_scope.php';
require_once __DIR__ . '/includes/profesor_scope.php';
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/csrf_functions.php';
require_once __DIR__ . '/includes/cursos/helpers.php';
$preceptor_cids = preceptor_curso_ids();
$cursos_alcance_vista = null;
if (es_profesor()) {
    $cursos_alcance_vista = profesor_curso_ids();
} elseif (($usuario['rol'] ?? '') === 'preceptor') {
    $cursos_alcance_vista = $preceptor_cids;
}

$cursoMapper = new CursoMapper($databaseAdapter);
$servicioCursos = new ServicioCursos($databaseAdapter, $cursoMapper);
$estudianteMapper = new \SistemaAdmin\Mappers\EstudianteMapper($databaseAdapter);
$servicioEstudiantes = new \SistemaAdmin\Services\ServicioEstudiantes($databaseAdapter, $estudianteMapper);
$cursoController = new CursoController($databaseAdapter, $servicioCursos, $servicioEstudiantes);
$pageTitle = __('courses.title') . ' - ' . \SistemaAdmin\Bootstrap\AppRequestInit::systemName();

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';
$form_curso = [
    'anio' => '',
    'division' => '',
    'turno_id' => '',
    'especialidad_id' => '',
];

$csrfToken = getCSRFToken();

// Solo admin y directivo pueden crear cursos
if ($action === 'nuevo' && !(hasRole('admin') || hasRole('directivo'))) {
    header('Location: courses.php?error=unauthorized');
    exit();
}

// Procesar formulario de nuevo curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_curso'])) {
    if (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
        $action = 'nuevo';
        $form_curso = cursos_form_curso_desde_post($_POST);
    } else {
        try {
            $resultadoGuardar = $cursoController->procesarGuardarCursoDesdePost($_POST);
            if ($resultadoGuardar['success']) {
                $success_message = 'Curso creado correctamente';
                $action = '';
            } else {
                $error_message = $resultadoGuardar['error'];
                $action = 'nuevo';
                $form_curso = cursos_form_curso_desde_post($_POST);
            }
        } catch (\Throwable $e) {
            $error_message = 'Error al crear curso: ' . $e->getMessage();
            $action = 'nuevo';
            $form_curso = cursos_form_curso_desde_post($_POST);
        }
    }
}

// Procesar eliminación de curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_curso'])) {
    if (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $puedeEliminar = hasRole('admin') || hasRole('directivo');
        $resultadoEliminar = $cursoController->procesarEliminarCursoDesdePost($_POST, $puedeEliminar);
        if ($resultadoEliminar['success']) {
            $success_message = $resultadoEliminar['message'];
        } else {
            $error_message = $resultadoEliminar['error'];
        }
    }
}

$anio_filter = (string) ($_GET['anio'] ?? '');
$division_filter = (string) ($_GET['division'] ?? '');
$especialidad_filter = (string) ($_GET['especialidad'] ?? '');

$vista = $cursoController->datosVistaGestion($cursos_alcance_vista, $anio_filter, $division_filter, $especialidad_filter);
$cursos = $vista['cursos'];
$cursos_por_grupo = $vista['cursos_por_grupo'];
$anios = $vista['anios'];
$divisiones = $vista['divisiones'];
$especialidades = $vista['especialidades'];
$turnos = $vista['turnos'];
$anio_filter = $vista['anio_filter'];
$division_filter = $vista['division_filter'];
$especialidad_filter = $vista['especialidad_filter'];
$total_cursos = $vista['total_cursos'];
$total_estudiantes = $vista['total_estudiantes'];
$cursos_sin_estudiantes = $vista['cursos_sin_estudiantes'];

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/cursos_gestion.css">' . "\n";

sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="cursos-section">
    <?php if (es_profesor()): ?>
        <?php
        $anioLectivoActual = \SistemaAdmin\Services\NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
        $correctionPeriodInfo = check_grade_correction_period($databaseAdapter, (int) $anioLectivoActual);
        $modificacionHabilitadaDocente = $correctionPeriodInfo['is_open'];
        ?>
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
    <?php if ($preceptor_cids !== []): ?>
    <div class="alert alert-info" style="margin-bottom: 1rem;">
        <i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.como_preceptor_solo_visualiza_sus_cursos_asig'), ENT_QUOTES, 'UTF-8'); ?><strong><?php echo htmlspecialchars(preceptor_curso_etiqueta()); ?></strong>).
    </div>
    <?php endif; ?>
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('auto.gesti_n_de_cursos'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if (hasRole('admin') || hasRole('directivo')): ?>
        <a href="courses.php?action=nuevo" class="btn btn-primary">
            <i class="fas fa-plus"></i><?php echo htmlspecialchars(__('auto.nuevo_curso'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars(__('auto.no_tienes_permisos_para_crear_cursos'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_cursos, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('courses.total_courses'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_estudiantes, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('students.total_students'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        
        <?php if ($cursos_sin_estudiantes > 0): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($cursos_sin_estudiantes, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('courses.courses_without_students'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Formulario nuevo curso -->
    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('courses.add_course'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="POST" class="form-container">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="anio"><?php echo htmlspecialchars(__('courses.year'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="anio" id="anio" required>
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $form_curso['anio'] !== '' && (string) (int) $form_curso['anio'] === (string) $i ? 'selected' : ''; ?>><?php echo $i; ?>°</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="division"><?php echo htmlspecialchars(__('courses.division'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="division" id="division" required>
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $form_curso['division'] !== '' && (string) $form_curso['division'] === (string) $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="especialidad_id"><?php echo htmlspecialchars(__('courses.specialty'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="especialidad_id" id="especialidad_id">
                        <option value=""><?php echo htmlspecialchars(__('teachers.unspecified'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($especialidades as $especialidad): ?>
                        <option value="<?php echo $especialidad['id']; ?>" <?php echo $form_curso['especialidad_id'] !== '' && (string) $form_curso['especialidad_id'] === (string) $especialidad['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($especialidad['nombre'] ?? '')); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="guardar_curso" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="courses.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filtros de búsqueda -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.search_filters'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="anio"><?php echo htmlspecialchars(__('courses.year'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="anio" id="anio">
                        <option value=""><?php echo htmlspecialchars(__('students.all_courses'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $anio_filter === (string) $i ? 'selected' : ''; ?>><?php echo $i; ?>° <?php echo htmlspecialchars(__('courses.year'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="courses.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('students.btn_clear'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de cursos -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('courses.registered_courses'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo number_format($total_cursos, 0, ',', '.'); ?>)</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(__('courses.th_course'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('courses.th_specialty'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('courses.th_shift'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('courses.th_cycle'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('courses.th_students'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('courses.th_actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cursos)): ?>
                        <?php foreach ($cursos as $curso): ?>
                        <tr>
                            <td>
                                <strong><?php echo $curso['anio'] . '° ' . $curso['division']; ?></strong>
                            </td>
                            <td>
                                <?php echo !empty($curso['especialidad']) ? htmlspecialchars((string) $curso['especialidad']) : '—'; ?>
                            </td>
                            <td>
                                <?php if (!empty($curso['turno'])): ?>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo htmlspecialchars((string) $curso['turno']); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($curso['grado'])): ?>
                                    <span class="status <?php echo $curso['grado'] === 'inferior' ? 'status-success' : 'status-warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst((string) $curso['grado'])); ?>
                                    </span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($curso['cantidad_estudiantes'] > 0): ?>
                                    <a href="students.php?curso=<?php echo $curso['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-users"></i> <?php echo $curso['cantidad_estudiantes']; ?><?php echo htmlspecialchars(__('auto.estudiante'), ENT_QUOTES, 'UTF-8'); ?><?php echo $curso['cantidad_estudiantes'] != 1 ? 's' : ''; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_estudiantes'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="schedules.php?curso=<?php echo $curso['id']; ?>" 
                                   class="btn btn-sm btn-success" title="Ver horarios">
                                    <i class="fas fa-clock"></i>
                                </a>
                                <a href="students.php?curso=<?php echo $curso['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Ver estudiantes">
                                    <i class="fas fa-users"></i>
                                </a>
                                <a href="teachers.php?curso=<?php echo $curso['id']; ?>" 
                                   class="btn btn-sm btn-purple" title="Ver profesores">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </a>
                                <?php if (hasRole('admin') || hasRole('directivo')): ?>
                                <a href="grades.php?curso=<?php echo $curso['id']; ?>" 
                                   class="btn btn-sm btn-secondary" title="Ver notas del curso">
                                    <i class="fas fa-clipboard-check"></i>
                                </a>
                                
                                <?php include __DIR__ . '/includes/cursos/partials/form_eliminar_curso.php'; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-graduation-cap" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <br><?php echo htmlspecialchars(__('courses.no_courses_found'), ENT_QUOTES, 'UTF-8'); ?>
                                <br><small><?php echo htmlspecialchars(__('students.no_students_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Vista por grupos -->
    <?php if ($anio_filter !== '' && $division_filter !== ''): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('auto.vista_por_grupos'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars($anio_filter); ?>° <?php echo htmlspecialchars($division_filter); ?>)</h3>
        </div>
        <div class="card-body">
            <div class="grupos-grid">
                <?php if (!empty($cursos_por_grupo)): ?>
                    <?php foreach ($cursos_por_grupo as $grupo_data): ?>
                    <div class="grupo-section">
                        <h4 class="grupo-title" style="color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
                            <span class="grupo-letter" style="font-weight: bold; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-users"></i><?php echo htmlspecialchars(__('auto.grupo'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars($grupo_data['grupo']); ?>
                            </span>
                            <span class="status status-primary" style="font-size: 0.75rem;">
                                <?php echo $grupo_data['cantidad']; ?> estudiante<?php echo $grupo_data['cantidad'] != 1 ? 's' : ''; ?>
                            </span>
                        </h4>
                        
                        <div class="grupo-students-list">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($grupo_data['estudiantes'] as $est_nombre): ?>
                                <li style="padding: 0.5rem 0; border-bottom: 1px dashed var(--border-color); display: flex; align-items: center; gap: 0.5rem; color: var(--text-color);">
                                    <i class="fas fa-user-graduate" style="color: var(--primary-color); opacity: 0.7;"></i>
                                    <?php echo htmlspecialchars($est_nombre); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center" style="padding: 2rem; color: var(--secondary-color); width: 100%;">
                        <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <br><?php echo htmlspecialchars(__('auto.no_hay_grupos_activos_para_este_curso'), ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars(__('auto.asigna_un_grupo_taller_a_e_a_alg_n_estudia'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php
$nonce = htmlspecialchars((string) ($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<script src="js/cursos_gestion.js" defer nonce="<?php echo $nonce; ?>"></script>
<?php include 'includes/footer.php'; ?>
