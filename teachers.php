<?php 
// Iniciar sesión al principio
require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioHorarios;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);

// Verificar si hay sesión activa
$usuario = $servicioAutenticacion->verificarSesion();
if (!$usuario) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

require_once __DIR__ . '/includes/preceptor_scope.php';
$preceptor_cids = preceptor_curso_ids();
$es_preceptor = ($_SESSION['rol'] ?? '') === 'preceptor';

use SistemaAdmin\Controllers\ProfesorController;
use SistemaAdmin\Mappers\ProfesorMapper;
use SistemaAdmin\Services\ServicioProfesores;

$profesorMapper = new ProfesorMapper($databaseAdapter);
$servicioProfesores = new ServicioProfesores($databaseAdapter, $profesorMapper);
$profesorController = new ProfesorController($databaseAdapter, $servicioProfesores);

$puede_gestionar_profesores = hasRole('admin') || hasRole('directivo');
$usuario_sistema_id = (int) ($usuario['id'] ?? 0);
$csrfToken = getCSRFToken();

$action = trim((string) (filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?? ''));

if ($es_preceptor && $action === 'nuevo') {
    header('Location: teachers.php');
    exit();
}

// Endpoint AJAX para obtener profesores por materia y curso
$ajaxTipo = filter_input(INPUT_GET, 'ajax', FILTER_DEFAULT);
if ($ajaxTipo === 'get_profesores_por_materia') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $materiaIdAjax = filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT);
        $cursoIdAjax = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT);
        $materia_id = ($materiaIdAjax !== false && $materiaIdAjax > 0) ? $materiaIdAjax : 0;
        $curso_id = ($cursoIdAjax !== false && $cursoIdAjax > 0) ? $cursoIdAjax : 0;

        if ($materia_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de materia requerido'
            ]);
            exit;
        }
        if ($curso_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de curso requerido'
            ]);
            exit;
        }

        if ($es_preceptor) {
            if (!preceptor_permitido_curso($curso_id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No autorizado'
                ]);
                exit;
            }
        }

        $servicioHorarios = new ServicioHorarios($databaseAdapter);
        $profesores = $servicioHorarios->listarDocentesParaHorario($materia_id, $curso_id);
        
        echo json_encode([
            'success' => true,
            'profesores' => $profesores
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener profesores: ' . $e->getMessage()
        ]);
        exit;
    }
}

$pageTitle = 'Profesores - Sistema Administrativo E.E.S.T N°2';

$form_values = [
    'dni' => '',
    'apellido' => '',
    'nombre' => '',
    'fecha_nacimiento' => '',
    'domicilio' => '',
    'telefono_fijo' => '',
    'telefono_celular' => '',
    'email' => '',
    'titulo' => '',
    'especialidad_id' => '',
    'fecha_ingreso' => '',
];
$error_message = '';

$esPost = strtoupper((string) (filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_DEFAULT) ?? '')) === 'POST';
if ($esPost && (isset($_POST['guardar_profesor']) || isset($_POST['eliminar_profesor']))) {
    $csrfPost = (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? '');
    if (!verifyCSRFToken($csrfPost)) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
        if (isset($_POST['guardar_profesor'])) {
            $action = 'nuevo';
        }
    } else {
        $postOutcome = $profesorController->procesarPostProfesores(
            $_POST,
            $es_preceptor,
            $puede_gestionar_profesores
        );
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

$success_message = '';
$successParam = filter_input(INPUT_GET, 'success', FILTER_DEFAULT);
if ($successParam === 'creado') {
    $success_message = 'Profesor registrado correctamente';
} elseif ($successParam === 'eliminado') {
    $nombreElim = (string) (filter_input(INPUT_GET, 'nombre', FILTER_DEFAULT) ?? '');
    $nombreEsc = htmlspecialchars($nombreElim, ENT_QUOTES, 'UTF-8');
    $success_message = 'Profesor ' . $nombreEsc . ' eliminado correctamente';
}

$ficha_error_banner = '';
$errorGet = isset($_GET['error']) ? (string) $_GET['error'] : '';
$fichaPid = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;
if ($errorGet === 'ficha' || $errorGet === 'not_found') {
    $ficha_error_banner = 'No se pudo abrir la ficha del docente.';
    if ($fichaPid > 0) {
        $ficha_error_banner .= ' Referencia interna #' . $fichaPid . '.';
    }
    $ficha_error_banner .= ' Compruebe que el enlace use un ID válido (desde el botón «ver» del listado) y que el registro exista en la tabla de profesores. Si el problema continúa, revise el registro de errores de PHP o ejecute la consulta SELECT * FROM profesores WHERE id = … en la base de datos.';
}

// Filtros (GET)
$especialidad_filter = (string) (filter_input(INPUT_GET, 'especialidad', FILTER_DEFAULT) ?? '');
$search = (string) (filter_input(INPUT_GET, 'search', FILTER_DEFAULT) ?? '');
$curso_filter = (string) (filter_input(INPUT_GET, 'curso', FILTER_DEFAULT) ?? '');

$pageListado = max(1, (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$perPageListado = 20;

$vistaListaProfesores = $profesorController->datosVistaListaProfesores(
    $es_preceptor,
    $preceptor_cids,
    $search,
    $especialidad_filter,
    $curso_filter,
    $pageListado,
    $perPageListado
);
$preceptores_mismo_curso = $vistaListaProfesores['preceptores_mismo_curso'];
$profesores_data = $vistaListaProfesores['profesores_data'];
$cursos_por_profesor = $vistaListaProfesores['cursos_por_profesor'];
$total_profesores = $vistaListaProfesores['total_profesores'];
$profesores_sin_cursos = $vistaListaProfesores['profesores_sin_cursos'];
$especialidades = $vistaListaProfesores['especialidades'];
$especialidades_formulario = $vistaListaProfesores['especialidades_formulario'];
$curso_info_etiqueta = $vistaListaProfesores['curso_info_etiqueta'];
$total_listado_filtrado = $vistaListaProfesores['total_filtrado'] ?? 0;
$pagination = $vistaListaProfesores['pagination'] ?? null;

$profesoresUrlParams = [];
if ($search !== '') {
    $profesoresUrlParams['search'] = $search;
}
if ($especialidad_filter !== '') {
    $profesoresUrlParams['especialidad'] = $especialidad_filter;
}
if ($curso_filter !== '') {
    $profesoresUrlParams['curso'] = $curso_filter;
}

$modo_lista_preceptores_equipo = is_array($preceptores_mismo_curso);

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/profesores.css">' . "\n";

sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="profesores-section">
    <div class="section-header">
        <h2>
            <?php if ($modo_lista_preceptores_equipo): ?><?php echo htmlspecialchars(__('auto.preceptores_de_su_curso'), ENT_QUOTES, 'UTF-8'); ?><span style="font-size: 0.8em; color: var(--secondary-color); font-weight: normal;">
                    (<?php echo htmlspecialchars(preceptor_curso_etiqueta()); ?>)
                </span>
            <?php else: ?>
            <?php echo htmlspecialchars(__('teachers.management_title'), ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($curso_filter && !empty($curso_info_etiqueta)): ?>
                    <span style="font-size: 0.8em; color: var(--secondary-color); font-weight: normal;">
                        - <?php echo htmlspecialchars(__('teachers.title'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($curso_info_etiqueta['anio'] ?? '') . '° ' . (string) ($curso_info_etiqueta['division'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($curso_info_etiqueta['especialidad'])): ?>
                            (<?php echo htmlspecialchars((string) $curso_info_etiqueta['especialidad']); ?>)
                        <?php endif; ?>
                    </span>
            <?php endif; ?>
            <?php endif; ?>
        </h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <?php if (!$modo_lista_preceptores_equipo && $curso_filter): ?>
                <a href="courses.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(__('action.back'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
            <?php if (!$modo_lista_preceptores_equipo): ?>
            <a href="teachers.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('teachers.add_teacher'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($modo_lista_preceptores_equipo && $es_preceptor && $preceptor_cids === []): ?>
    <div class="alert alert-error" style="margin-bottom: 1rem;">
        <i class="fas fa-exclamation-triangle"></i> No tiene cursos asignados; no puede ver el listado de preceptores.
    </div>
    <?php elseif ($modo_lista_preceptores_equipo): ?>
    <div class="alert alert-info" style="margin-bottom: 1rem;">
        <i class="fas fa-info-circle"></i> Solo se muestran los preceptores del equipo directivo que comparten <strong>al menos un curso de trabajo</strong><?php echo htmlspecialchars(__('auto.con_usted'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($ficha_error_banner !== ''): ?>
        <div class="alert alert-error" style="margin-bottom: 1rem;">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($ficha_error_banner, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>



    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-<?php echo $modo_lista_preceptores_equipo ? 'user-tie' : 'chalkboard-teacher'; ?>"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_profesores); ?></h3>
                <p><?php echo $modo_lista_preceptores_equipo ? htmlspecialchars(__('preceptors.active'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('teachers.total_teachers'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        
        <?php if (!$modo_lista_preceptores_equipo && $profesores_sin_cursos > 0): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($profesores_sin_cursos); ?></h3>
                <p><?php echo htmlspecialchars(__('teachers.unassigned_teachers'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Formulario nuevo profesor -->
    <?php if (!$modo_lista_preceptores_equipo && $action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('teachers.add_teacher'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="POST" class="form-container">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="guardar_profesor" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="dni"><?php echo htmlspecialchars(__('teachers.th_dni'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="dni" id="dni" required maxlength="20" 
                           placeholder="12345678" pattern="[0-9A-Za-z\.\-]{5,20}" 
                           title="5 a 20 caracteres"
                           value="<?php echo htmlspecialchars($form_values['dni']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="apellido"><?php echo htmlspecialchars(__('students.last_name'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="apellido" id="apellido" required maxlength="100"
                           placeholder="Ej: García"
                           value="<?php echo htmlspecialchars($form_values['apellido']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="nombre"><?php echo htmlspecialchars(__('students.first_name'), ENT_QUOTES, 'UTF-8'); ?>: <span class="required">*</span></label>
                    <input type="text" name="nombre" id="nombre" required maxlength="100"
                           placeholder="Ej: Juan"
                           value="<?php echo htmlspecialchars($form_values['nombre']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_nacimiento"><?php echo htmlspecialchars(__('students.birth_date'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                           value="<?php echo htmlspecialchars($form_values['fecha_nacimiento']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_ingreso"><?php echo htmlspecialchars(__('teachers.hire_date'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso"
                           value="<?php echo htmlspecialchars($form_values['fecha_ingreso']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="titulo"><?php echo htmlspecialchars(__('teachers.degree'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="text" name="titulo" id="titulo" maxlength="200" 
                           placeholder="Ej: Profesor de Matemática"
                           value="<?php echo htmlspecialchars($form_values['titulo']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                
                <div class="form-group">
                    <label for="telefono_fijo"><?php echo htmlspecialchars(__('students.landline_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="tel" name="telefono_fijo" id="telefono_fijo" maxlength="20"
                           placeholder="Ej: 223-456-7890"
                           value="<?php echo htmlspecialchars($form_values['telefono_fijo']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefono_celular"><?php echo htmlspecialchars(__('students.mobile_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="tel" name="telefono_celular" id="telefono_celular" maxlength="20"
                           placeholder="Ej: 223-456-7890"
                           value="<?php echo htmlspecialchars($form_values['telefono_celular']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(__('teachers.google_email'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="email" name="email" id="email" maxlength="100"
                           placeholder="Ej: profesor@gmail.com"
                           value="<?php echo htmlspecialchars($form_values['email'], ENT_QUOTES, 'UTF-8'); ?>">
                    <small><?php echo htmlspecialchars(__('teachers.google_email_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <div class="form-group">
                    <label for="domicilio"><?php echo htmlspecialchars(__('students.address'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <textarea name="domicilio" id="domicilio" placeholder="<?php echo htmlspecialchars(__('students.address_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($form_values['domicilio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('teachers.save_teacher'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="teachers.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <?php if ($modo_lista_preceptores_equipo && $preceptor_cids !== []): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="text" name="search" id="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo htmlspecialchars(__('students.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="teachers.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('students.btn_clear'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php elseif (!$modo_lista_preceptores_equipo): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.search_filters'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="text" name="search" id="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo htmlspecialchars(__('students.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="teachers.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('students.btn_clear'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Lista de profesores / preceptores del curso -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $modo_lista_preceptores_equipo ? htmlspecialchars(__('preceptors.title'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('teachers.registered_teachers'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo number_format($total_profesores); ?>)</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <?php if ($modo_lista_preceptores_equipo): ?>
                        <th><?php echo htmlspecialchars(__('teachers.th_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('courses.division'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('auto.user_account'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('teachers.th_contact'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php else: ?>
                        <th><?php echo htmlspecialchars(__('teachers.th_dni'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('teachers.th_name'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('teachers.th_specialty'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('teachers.th_courses'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('teachers.th_contact'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(__('teachers.th_actions'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($modo_lista_preceptores_equipo && !empty($preceptores_mismo_curso)): ?>
                        <?php foreach ($preceptores_mismo_curso as $prec): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($prec['apellido'] . ', ' . $prec['nombre']); ?></strong>
                                <?php if ($usuario_sistema_id > 0 && (int) ($prec['usuario_id'] ?? 0) === $usuario_sistema_id): ?>
                                <br><span class="status status-info" style="font-size: 0.75rem;"><?php echo htmlspecialchars(__('auto.usted'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(ProfesorController::etiquetaCursoPreceptorEquipo($prec), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php if (!empty($prec['usuario_login'])): ?>
                                    <small><?php echo htmlspecialchars($prec['usuario_login']); ?></small>
                                <?php else: ?>
                                    <span class="status status-warning" style="font-size: 0.75rem;"><?php echo htmlspecialchars(__('auto.sin_usuario'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($prec['telefono'])): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($prec['telefono']); ?>
                                <?php endif; ?>
                                <?php if (!empty($prec['email'])): ?>
                                    <br><small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($prec['email']); ?></small>
                                <?php endif; ?>
                                <?php if (empty($prec['telefono']) && empty($prec['email'])): ?>
                                    <span class="status status-warning" style="font-size: 0.75rem;"><?php echo htmlspecialchars(__('auto.sin_datos'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php elseif (!$modo_lista_preceptores_equipo && !empty($profesores_data)): ?>
                        <?php foreach ($profesores_data as $profesor): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($profesor['dni']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre']); ?></strong>
                                <?php if ($profesor['fecha_nacimiento']): ?>
                                <br><small>
                                    <i class="fas fa-birthday-cake"></i>
                                    <?php echo date('d/m/Y', strtotime($profesor['fecha_nacimiento'])); ?>
                                    (<?php echo $profesor['edad']; ?><?php echo htmlspecialchars(__('auto.a_os'), ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                                <?php if ($profesor['titulo']): ?>
                                <br><small><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($profesor['titulo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($profesor['especialidad']): ?>
                                    <span class="status status-success">
                                        <?php echo htmlspecialchars($profesor['especialidad']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_especificar'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $cursos_profesor = $cursos_por_profesor[$profesor['id']] ?? []; ?>
                                <?php if (!empty($cursos_profesor)): ?>
                                    <div class="course-tags">
                                        <?php foreach ($cursos_profesor as $curso): ?>
                                            <span class="status status-primary" style="display: inline-block; margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars(ProfesorController::etiquetaCursoListadoProfesores($curso), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_cursos_asignados'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($profesor['telefono_celular']): ?>
                                    <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($profesor['telefono_celular']); ?>
                                <?php elseif ($profesor['telefono_fijo']): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($profesor['telefono_fijo']); ?>
                                <?php else: ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_tel_fono'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if ($profesor['email']): ?>
                                    <br><small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profesor['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $fid = (int) ($profesor['id'] ?? 0); ?>
                                <?php if ($fid > 0): ?>
                                <a href="teacher_profile.php?id=<?php echo $fid; ?>"
                                   class="btn btn-sm btn-primary" title="Ver ficha completa">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php else: ?>
                                <span class="btn btn-sm btn-secondary" title="ID no disponible; recargue el listado o contacte soporte" style="cursor: not-allowed; opacity: 0.7;"><i class="fas fa-eye-slash"></i></span>
                                <?php endif; ?>

                                <?php if ($puede_gestionar_profesores): ?>
                                <form method="POST" style="display: inline;" class="js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars('¿Está seguro de que desea eliminar este profesor?', ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="eliminar_profesor" value="1">
                                    <input type="hidden" name="profesor_id" value="<?php echo (int) $profesor['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar profesor">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $modo_lista_preceptores_equipo ? '4' : '6'; ?>" class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-<?php echo $modo_lista_preceptores_equipo ? 'user-tie' : 'chalkboard-teacher'; ?>" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <br><?php
                                if ($modo_lista_preceptores_equipo) {
                                    echo $preceptor_cids !== []
                                        ? 'No hay preceptores que compartan sus cursos o no coincide la búsqueda.'
                                        : 'Necesita tener cursos asignados para ver a los preceptores del grupo.';
                                } else {
                                    echo htmlspecialchars(__('teachers.no_teachers_found'), ENT_QUOTES, 'UTF-8');
                                }
                                ?>
                                <?php if (!$modo_lista_preceptores_equipo): ?>
                                <br><small><?php echo htmlspecialchars(__('teachers.no_teachers_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_listado_filtrado > 0 && $pagination !== null && (int) $pagination['total_pages'] > 1): ?>
        <nav class="pagination-nav" aria-label="Paginación de profesores" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 0.35rem; padding: 1rem 1rem 1.25rem; border-top: 1px solid var(--medium-gray, #e2e8f0);">
            <?php
            $pn = (int) $pagination['current_page'];
            $tp = (int) $pagination['total_pages'];
            $linkBase = $profesoresUrlParams;
            $mk = static function (array $base, int $p): string {
                $base['page'] = $p;
                return 'teachers.php?' . http_build_query($base);
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
