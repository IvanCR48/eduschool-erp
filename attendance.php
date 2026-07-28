<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Controllers\AsistenciaController;
use SistemaAdmin\Mappers\AsistenciaMapper;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Services\ServicioAsistencia;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioEstudiantes;

require_once __DIR__ . '/includes/auth_helpers.php';

$databaseAdapter        = sistema_admin_db_adapter();
$servicioAutenticacion  = new ServicioAutenticacion($databaseAdapter);
$usuario                = $servicioAutenticacion->verificarSesion();

if (!$usuario) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

require_once __DIR__ . '/includes/preceptor_scope.php';

$rolSesion   = strtolower((string) ($_SESSION['rol'] ?? ''));
$puedeEditar = in_array($rolSesion, ['admin', 'preceptor'], true);
$puedeVer    = in_array($rolSesion, ['admin', 'preceptor', 'directivo', 'director', 'vicedirector'], true)
               || puedeGestionarPreceptoresCurso()
               || can('ver_asistencia');

if (!$puedeVer) {
    header('Location: index.php?error=unauthorized');
    exit();
}

// Instanciar capa de dominio
$moduloAsistenciaDisponible = true;
$mensajeModuloAsistencia = '';
$asistenciaMapper = null;
$servicioAsistencia = null;
$asistenciaController = null;
$servicioEstudiantes = null;
try {
    $asistenciaMapper = new AsistenciaMapper($databaseAdapter);
    $servicioAsistencia = new ServicioAsistencia($databaseAdapter, $asistenciaMapper);
    $asistenciaController = new AsistenciaController($databaseAdapter, $servicioAsistencia);
    
    $estudianteMapper = new EstudianteMapper($databaseAdapter);
    $servicioEstudiantes = new ServicioEstudiantes($databaseAdapter, $estudianteMapper);
} catch (\Throwable $e) {
    $moduloAsistenciaDisponible = false;
    $mensajeModuloAsistencia = 'Modulo no implementado. Contactar a soporte: ' . sistema_admin_support_email();
    error_log('[Asistencia] Inicializacion no disponible: ' . $e->getMessage());
}

$pageTitle    = 'Asistencia - Sistema Administrativo E.E.S.T N°2';
$filtrosDependientesVersion = @filemtime(__DIR__ . '/js/filtros_dependientes.js') ?: time();
$asistenciaVirtualVersion = @filemtime(__DIR__ . '/js/asistencia_virtual.js') ?: time();
$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/asistencia_virtual.css">' . "\n";

$csrfToken       = getCSRFToken();
$error_message   = '';
$success_message = '';

$tablaExiste = false;
if ($moduloAsistenciaDisponible && $servicioAsistencia !== null) {
    try {
        $tablaExiste = $servicioAsistencia->tablaAsistenciaExiste();
    } catch (\Throwable $e) {
        $moduloAsistenciaDisponible = false;
        $mensajeModuloAsistencia = 'Modulo no implementado. Contactar a soporte: ' . sistema_admin_support_email();
        error_log('[Asistencia] Error de tabla/asistencia: ' . $e->getMessage());
    }
}

// Alcance de cursos por rol
$scopeCursos = [];

if ($rolSesion === 'preceptor') {
    $scopeCursos = preceptor_curso_ids();
}
$scopeCursosFiltro = $rolSesion === 'preceptor' ? $scopeCursos : null;
$cursosDisponibles = [];
if ($moduloAsistenciaDisponible && $servicioAsistencia !== null) {
    try {
        $cursosDisponibles = $servicioAsistencia->cursosActivosParaAsistencia($scopeCursosFiltro);
    } catch (\Throwable $e) {
        $moduloAsistenciaDisponible = false;
        $mensajeModuloAsistencia = 'Modulo no implementado. Contactar a soporte: ' . sistema_admin_support_email();
        error_log('[Asistencia] Error cargando cursos: ' . $e->getMessage());
    }
}

// Parámetros GET
$cursoSeleccionado = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
$materiaSeleccionada = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$estudianteSeleccionado = isset($_GET['estudiante_id']) ? (int) $_GET['estudiante_id'] : 0;
$trimestreSeleccionado = (string) (filter_input(INPUT_GET, 'trimestre', FILTER_DEFAULT) ?? '');
if (!in_array($trimestreSeleccionado, ['', '1', '2'], true)) {
    $trimestreSeleccionado = '';
}

$fechaSeleccionada = $moduloAsistenciaDisponible && $asistenciaController !== null
    ? $asistenciaController->resolverFechaSeleccionada($_GET)
    : date('Y-m-d');
if ($rolSesion === 'preceptor' && $cursoSeleccionado > 0 && !in_array($cursoSeleccionado, $scopeCursos, true)) {
    $cursoSeleccionado = 0;
}

$materiasDisponibles = [];
if ($moduloAsistenciaDisponible && $servicioAsistencia !== null && $cursoSeleccionado > 0) {
    $materiasDisponibles = $servicioAsistencia->materiasActivasPorCurso($cursoSeleccionado);
}
$materiaSeleccionada = $moduloAsistenciaDisponible && $asistenciaController !== null
    ? $asistenciaController->normalizarMateriaSeleccionada($materiaSeleccionada, $materiasDisponibles)
    : 0;

$materiaEsTaller = false;
$grupoTallerSeleccionado = '';
foreach ($materiasDisponibles as $md) {
    if ((int)$md['id'] === $materiaSeleccionada) {
        $materiaEsTaller = ((int)($md['es_taller'] ?? 0) === 1);
        break;
    }
}
if ($materiaEsTaller) {
    $grupoTallerSeleccionado = (string) (filter_input(INPUT_GET, 'grupo_taller', FILTER_DEFAULT) ?? '');
}
$diasConHorarioMateria = [];
if ($moduloAsistenciaDisponible && $servicioAsistencia !== null && $asistenciaController !== null && $cursoSeleccionado > 0 && $materiaSeleccionada > 0) {
    $diasConHorarioMateria = $servicioAsistencia->diasConHorarioMateria($cursoSeleccionado, $materiaSeleccionada);
    $fechaSeleccionada = $asistenciaController->resolverFechaSeleccionada($_GET, $diasConHorarioMateria);
}

// Vista principal (toma)
$tabActiva = 'toma';

// POST AJAX — guardar asistencia sin recargar
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $moduloAsistenciaDisponible
    && $tablaExiste
    && $puedeEditar
    && (string) ($_POST['ajax_guardar'] ?? '') === '1'
) {
    header('Content-Type: application/json; charset=utf-8');
    $csrfPost = (string) ($_POST['csrf_token'] ?? '');
    if (!verifyCSRFToken($csrfPost)) {
        echo json_encode(['success' => false, 'error' => 'La solicitud no pudo validarse.']);
        exit();
    }

    $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
    $resultado = $asistenciaController->procesarPostAjax(
        $_POST,
        $_FILES,
        $usuarioId,
        $scopeCursos,
        $rolSesion
    );
    echo json_encode($resultado);
    exit();
}

// POST — guardar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $moduloAsistenciaDisponible && $tablaExiste && $puedeEditar) {
    $csrfPost = (string) ($_POST['csrf_token'] ?? '');
    if (!verifyCSRFToken($csrfPost)) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $cursoPost  = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;
        $materiaPost = isset($_POST['materia_id']) ? (int) $_POST['materia_id'] : 0;
        $fechaPost  = isset($_POST['fecha'])    ? trim((string) $_POST['fecha']) : '';
        $usuarioId  = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($cursoPost <= 0 || $materiaPost <= 0 || $usuarioId <= 0) {
            $error_message = 'Los datos enviados no son válidos.';
        } else {
            $resultado = $asistenciaController->procesarPost(
                $_POST,
                $_FILES,
                $cursoPost,
                $materiaPost,
                $fechaPost,
                $usuarioId,
                $scopeCursos,
                $rolSesion
            );
            if ($resultado['redirect'] !== null) {
                header('Location: ' . app_base_path($resultado['redirect']), true, 303);
                exit();
            }
            $error_message = $resultado['error'];
        }
    }
}

if (isset($_GET['ok']) && (string) $_GET['ok'] === '1') {
    $success_message = 'Asistencia guardada correctamente.';
}

// Datos de vista
$filasAsistencia = [];
$contadores      = ['presentes' => 0, 'tardanzas' => 0, 'media_falta' => 0, 'aus_justificados' => 0, 'ausentes' => 0];
$alumnosFiltro   = [];

if ($moduloAsistenciaDisponible && $servicioAsistencia !== null && $cursoSeleccionado > 0) {
    $alumnosFiltro = $servicioAsistencia->alumnosActivosPorCurso($cursoSeleccionado);
}

$materiasFiltroTodas = [];
$alumnosFiltroTodos = [];
try {
    if ($rolSesion === 'preceptor') {
        if ($scopeCursos !== []) {
            $placeholdersScope = implode(',', array_fill(0, count($scopeCursos), '?'));
            $materiasFiltroTodas = $databaseAdapter->fetchAll(
                'SELECT DISTINCT m.id, m.nombre, m.es_taller, mc.curso_id
                 FROM materias m
                 INNER JOIN materia_curso mc ON mc.materia_id = m.id AND mc.activo = 1
                 INNER JOIN cursos c ON c.id = mc.curso_id AND c.activo = 1
                 WHERE m.activa = 1 AND mc.curso_id IN (' . $placeholdersScope . ')
                 ORDER BY m.nombre',
                array_map('intval', $scopeCursos)
            );
            $alumnosFiltroTodos = $databaseAdapter->fetchAll(
                'SELECT e.id, e.apellido, e.nombre, e.curso_id
                 FROM estudiantes e
                 INNER JOIN cursos c ON c.id = e.curso_id AND c.activo = 1
                 WHERE e.activo = 1 AND e.curso_id IN (' . $placeholdersScope . ')
                 ORDER BY e.apellido, e.nombre',
                array_map('intval', $scopeCursos)
            );
        }
    } else {
        $materiasFiltroTodas = $databaseAdapter->fetchAll(
            'SELECT DISTINCT m.id, m.nombre, m.es_taller, mc.curso_id
             FROM materias m
             INNER JOIN materia_curso mc ON mc.materia_id = m.id AND mc.activo = 1
             INNER JOIN cursos c ON c.id = mc.curso_id AND c.activo = 1
             WHERE m.activa = 1
             ORDER BY m.nombre',
            []
        );
        $alumnosFiltroTodos = $databaseAdapter->fetchAll(
            'SELECT e.id, e.apellido, e.nombre, e.curso_id
             FROM estudiantes e
             INNER JOIN cursos c ON c.id = e.curso_id AND c.activo = 1
             WHERE e.activo = 1
             ORDER BY e.apellido, e.nombre',
            []
        );
    }
} catch (\Throwable $e) {
    $materiasFiltroTodas = [];
    $alumnosFiltroTodos = [];
}

if ($moduloAsistenciaDisponible && $tablaExiste && $cursoSeleccionado > 0 && $materiaSeleccionada > 0 && $asistenciaController !== null) {
    if ($materiaEsTaller && $grupoTallerSeleccionado === '') {
        $filasAsistencia = [];
        $error_message = 'Debe seleccionar un grupo de taller para esta materia.';
    } else {
        [$desdeRangoAsistencia, $hastaRangoAsistencia] = $asistenciaController->rangoCuatrimestreDesdeFecha($fechaSeleccionada, $trimestreSeleccionado);
        $filasAsistencia = $asistenciaController->datosVistaToma(
            $cursoSeleccionado,
            $materiaSeleccionada,
            $fechaSeleccionada,
            $desdeRangoAsistencia,
            $hastaRangoAsistencia,
            $grupoTallerSeleccionado
        );
        $filasAsistencia = $asistenciaController->filtrarFilasPorEstudiante($filasAsistencia, $estudianteSeleccionado);
    }

    $contadores = $asistenciaController->contadoresDia($filasAsistencia);

    $bloqueosEstudiantes = [];
    $schoolYear = (int) date('Y', strtotime($fechaSeleccionada) ?: time());
    if ($filasAsistencia !== [] && $servicioEstudiantes !== null) {
        foreach ($filasAsistencia as $alumno) {
            $eid = (int) $alumno['id'];
            $regularCursoId = (int) ($alumno['regular_curso_id'] ?? 0);
            
            $bloqueosEstudiantes[$eid] = [
                'es_recursante' => ($regularCursoId !== $cursoSeleccionado),
                'solapado' => false,
                'motivo' => '',
            ];
            
            if ($regularCursoId === $cursoSeleccionado) {
                $solapados = $servicioEstudiantes->verificarSolapamientosEstudiante($eid, $schoolYear);
                foreach ($solapados as $s) {
                    if ((int) $s['materia_id'] === $materiaSeleccionada) {
                        $bloqueosEstudiantes[$eid]['solapado'] = true;
                        $bloqueosEstudiantes[$eid]['motivo'] = 'Solapado con ' . $s['recursada_materia_nombre'];
                        break;
                    }
                }
            }
        }
    }

}

// Helpers de vista
$estadosConfig = [
    'Presente'            => ['label' => __('attendance.present'),        'clase' => 'av5--presente',    'icono' => 'fa-check-circle'],
    'Tardanza'            => ['label' => __('attendance.tardiness'),      'clase' => 'av5--tardanza',    'icono' => 'fa-clock'],
    'Media falta'         => ['label' => __('attendance.half_absence'),   'clase' => 'av5--media',       'icono' => 'fa-adjust'],
    'Ausente justificado' => ['label' => __('attendance.excused_absence'), 'clase' => 'av5--justificado', 'icono' => 'fa-file-medical'],
    'Ausente'             => ['label' => __('attendance.absent'),          'clase' => 'av5--ausente',     'icono' => 'fa-times-circle'],
];

$isEn = (function_exists('current_lang') && current_lang() === 'en');
$fechaLabel = (function (string $f) use ($isEn): string {
    $ts = strtotime($f);
    if ($ts === false) return $f;
    if ($isEn) {
        $dias = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        return $dias[(int) date('w', $ts)] . ', ' . date('M j, Y', $ts);
    }
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    return ucfirst($dias[(int) date('w', $ts)]) . ', ' . date('d/m/Y', $ts);
})($fechaSeleccionada);

$anioSeleccionado = (int) date('Y', strtotime($fechaSeleccionada) ?: time());
$mesSeleccionado = (int) date('n', strtotime($fechaSeleccionada) ?: time());
$diaSeleccionado = (int) date('j', strtotime($fechaSeleccionada) ?: time());
$mesesLabel = $isEn ? [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
] : [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

sistema_admin_send_html_security_headers();
include __DIR__ . '/includes/header.php';
?>

<section class="av-section">
    <div class="section-header">
        <h2><i class="fas fa-clipboard-check"></i> <?php echo htmlspecialchars(__('attendance.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="header-actions">
            <a href="attendance_reports.php" class="btn btn-secondary">
                <i class="fas fa-tachometer-alt"></i> <?php echo htmlspecialchars(__('attendance.dashboard'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>

    <?php if (!$moduloAsistenciaDisponible): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($mensajeModuloAsistencia !== '' ? $mensajeModuloAsistencia : ('Modulo no implementado. Contactar a soporte: ' . sistema_admin_support_email()), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php elseif (!$tablaExiste): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('auto.modulo_no_implementado_contactar_a_soporte'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars(sistema_admin_support_email(), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <?php if ($success_message !== ''): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error_message !== ''): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- ====== MOBILE TABS BAR ====== -->
    <div class="av-mobile-tab-bar">
        <button type="button" class="av-mobile-tab-btn active" data-tab="alumnos">
            <i class="fas fa-users"></i>
            <span><?php echo htmlspecialchars(__('auto.alumnos'), ENT_QUOTES, 'UTF-8'); ?></span>
        </button>
        <button type="button" class="av-mobile-tab-btn" data-tab="resumen">
            <i class="fas fa-chart-pie"></i>
            <span><?php echo htmlspecialchars(__('auto.resumen'), ENT_QUOTES, 'UTF-8'); ?></span>
        </button>
        <button type="button" class="av-mobile-tab-btn" data-tab="filtros">
            <i class="fas fa-filter"></i>
            <span><?php echo htmlspecialchars(__('auto.filtros'), ENT_QUOTES, 'UTF-8'); ?></span>
        </button>
    </div>

    <?php if ($tablaExiste && $cursoSeleccionado > 0 && $materiaSeleccionada > 0): ?>
    <!-- ====== STATS ====== -->
    <div class="stats-grid av-stats av-mobile-tab-content" data-mobile-tab="resumen">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
            <div class="stat-content"><h3><?php echo $contadores['presentes']; ?></h3><p><?php echo htmlspecialchars(__('auto.presentes'), ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-content"><h3><?php echo $contadores['tardanzas']; ?></h3><p><?php echo htmlspecialchars(__('auto.tardanzas'), ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-adjust"></i></div>
            <div class="stat-content"><h3><?php echo $contadores['media_falta']; ?></h3><p><?php echo htmlspecialchars(__('auto.media_falta'), ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-file-medical"></i></div>
            <div class="stat-content"><h3><?php echo $contadores['aus_justificados']; ?></h3><p><?php echo htmlspecialchars(__('auto.justificados'), ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-user-times"></i></div>
            <div class="stat-content"><h3><?php echo $contadores['ausentes']; ?></h3><p><?php echo htmlspecialchars(__('auto.ausentes'), ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon neutral"><i class="fas fa-users"></i></div>
            <div class="stat-content"><h3><?php echo count($filasAsistencia); ?></h3><p><?php echo htmlspecialchars(__('auto.total'), ENT_QUOTES, 'UTF-8'); ?></p></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== FILTRO ====== -->
    <div class="card av-filter-card av-mobile-tab-content" data-mobile-tab="filtros">
        <div class="card-header av-filter-toggle-header" id="av-toggle-filtros-btn">
            <h3 class="card-title"><i class="fas fa-filter"></i><?php echo htmlspecialchars(__('auto.filtros_de_b_squeda'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <button type="button" class="btn btn-secondary btn-sm av-btn-toggle-filtros" aria-label="Mostrar/Ocultar filtros">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div class="av-filter-collapsible" id="av-filtros-collapsible">
            <form method="GET" class="form-container" id="av-filtros-form">
                <div class="form-row">
                <div class="form-group">
                    <label for="curso_id"><?php echo htmlspecialchars(__('courses.course'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="curso_id" name="curso_id" required>
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursosDisponibles as $curso):
                            $etiqueta = $curso['anio'] . '° ' . $curso['division'] . ' — ' . $curso['especialidad'];
                        ?>
                        <option value="<?php echo (int) $curso['id']; ?>"
                            <?php echo ((int) $curso['id'] === $cursoSeleccionado) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="materia_id"><?php echo htmlspecialchars(__('subjects.subject'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="materia_id" name="materia_id" required data-selected-value="<?php echo (int) $materiaSeleccionada; ?>">
                        <option value="0"><?php echo htmlspecialchars(__('subjects.select_subject'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php $materiaSelectedImpresa = false; ?>
                        <?php foreach ($materiasFiltroTodas as $mat): ?>
                        <option value="<?php echo (int) ($mat['id'] ?? 0); ?>"
                            data-curso-id="<?php echo (int) ($mat['curso_id'] ?? 0); ?>"
                            data-es-taller="<?php echo (int) ($mat['es_taller'] ?? 0); ?>"
                            <?php
                            $matId = (int) ($mat['id'] ?? 0);
                            $matCursoId = (int) ($mat['curso_id'] ?? 0);
                            $esSeleccionMateria = !$materiaSelectedImpresa
                                && $matId === $materiaSeleccionada
                                && $cursoSeleccionado > 0
                                && $matCursoId === $cursoSeleccionado;
                            if ($esSeleccionMateria) {
                                $materiaSelectedImpresa = true;
                            }
                            echo $esSeleccionMateria ? 'selected' : '';
                            ?>>
                            <?php echo htmlspecialchars((string) ($mat['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="grupo_taller_filter_container" style="display: <?php echo $materiaEsTaller ? '' : 'none'; ?>;">
                    <label for="grupo_taller"><?php echo htmlspecialchars(__('auto.grupo_de_taller'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="grupo_taller" name="grupo_taller" <?php echo $materiaEsTaller ? 'required' : ''; ?>>
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="A" <?php echo $grupoTallerSeleccionado === 'A' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_a'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="B" <?php echo $grupoTallerSeleccionado === 'B' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_b'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="C" <?php echo $grupoTallerSeleccionado === 'C' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_c'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="D" <?php echo $grupoTallerSeleccionado === 'D' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_d'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="E" <?php echo $grupoTallerSeleccionado === 'E' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_e'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estudiante_id"><?php echo htmlspecialchars(__('attendance.search_student'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="estudiante_id" name="estudiante_id">
                        <option value="0"><?php echo htmlspecialchars(__('attendance.all_students'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($alumnosFiltroTodos as $al): ?>
                        <option value="<?php echo (int) ($al['id'] ?? 0); ?>"
                            data-curso-id="<?php echo (int) ($al['curso_id'] ?? 0); ?>"
                            <?php echo ((int) ($al['id'] ?? 0) === $estudianteSeleccionado) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) (($al['apellido'] ?? '') . ', ' . ($al['nombre'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="trimestre"><?php echo htmlspecialchars(__('grades.term'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="trimestre" name="trimestre">
                        <option value="" <?php echo $trimestreSeleccionado === '' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('grades.all_terms'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="1" <?php echo $trimestreSeleccionado === '1' ? 'selected' : ''; ?>>1° <?php echo htmlspecialchars(__('grades.term'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="2" <?php echo $trimestreSeleccionado === '2' ? 'selected' : ''; ?>>2° <?php echo htmlspecialchars(__('grades.term'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo htmlspecialchars(__('attendance.date_taken'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="av-date-nav">
                        <select name="anio" aria-label="Año">
                            <?php for ($y = (int) date('Y') + 1; $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y === $anioSeleccionado ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="mes" aria-label="Mes">
                            <?php foreach ($mesesLabel as $mN => $mLbl): ?>
                            <option value="<?php echo $mN; ?>" <?php echo $mN === $mesSeleccionado ? 'selected' : ''; ?>><?php echo $mLbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="dia" aria-label="Día">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo $d; ?>" <?php echo $d === $diaSeleccionado ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group av-filter-actions">
                    <label>&nbsp;</label>
                    <div class="av-filter-btns">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i><?php echo htmlspecialchars(__('auto.aplicar_filtros'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>

    <?php if ($tablaExiste && $cursoSeleccionado > 0): ?>
    <?php if ($tabActiva === 'toma'): ?>
    <!-- ====== TOMA DE ASISTENCIA ====== -->
    <div class="card av-mobile-tab-content" data-mobile-tab="alumnos">
        <div class="card-header av-header-toma">
            <div>
                <h3 class="card-title"><i class="fas fa-clipboard-list"></i><?php echo htmlspecialchars(__('auto.toma_de_asistencia'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="av-fecha-label"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($fechaLabel, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <form method="get" class="av-day-arrows">
                <input type="hidden" name="curso_id" value="<?php echo (int) $cursoSeleccionado; ?>">
                <input type="hidden" name="materia_id" value="<?php echo (int) $materiaSeleccionada; ?>">
                <input type="hidden" name="estudiante_id" value="<?php echo (int) $estudianteSeleccionado; ?>">
                <input type="hidden" name="trimestre" value="<?php echo htmlspecialchars($trimestreSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="anio" value="<?php echo $anioSeleccionado; ?>">
                <input type="hidden" name="mes" value="<?php echo $mesSeleccionado; ?>">
                <input type="hidden" name="dia" value="<?php echo $diaSeleccionado; ?>">
                <input type="hidden" name="grupo_taller" value="<?php echo htmlspecialchars($grupoTallerSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" name="day_nav" value="prev" class="btn btn-secondary btn-sm" title="Día hábil anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="submit" name="day_nav" value="next" class="btn btn-secondary btn-sm" title="Día hábil siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </form>
            <?php if ($puedeEditar && $filasAsistencia !== []): ?>
            <div class="av-quick-actions">
                <button type="button" id="btn-todos-presentes" class="btn btn-success btn-sm">
                    <i class="fas fa-check-double"></i><?php echo htmlspecialchars(__('auto.marcar_todos_presentes'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($filasAsistencia === []): ?>
        <div class="card-body">
            <p class="text-muted"><i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.no_hay_alumnos_activos_en_este_curso'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <?php else: ?>
        <form method="POST" class="form-container" enctype="multipart/form-data" id="form-asistencia">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="curso_id"   value="<?php echo (int) $cursoSeleccionado; ?>">
            <input type="hidden" name="materia_id" value="<?php echo (int) $materiaSeleccionada; ?>">
            <input type="hidden" name="grupo_taller" value="<?php echo htmlspecialchars($grupoTallerSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="fecha"       value="<?php echo htmlspecialchars($fechaSeleccionada, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="estudiante_id" value="<?php echo (int) $estudianteSeleccionado; ?>">
            <input type="hidden" name="trimestre" value="<?php echo htmlspecialchars($trimestreSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="anio" value="<?php echo $anioSeleccionado; ?>">
            <input type="hidden" name="mes" value="<?php echo $mesSeleccionado; ?>">
            <input type="hidden" name="dia" value="<?php echo $diaSeleccionado; ?>">

            <div class="av-search-bar-container">
                <div class="av-search-wrapper">
                    <i class="fas fa-search av-search-icon"></i>
                    <input type="text" id="av-search-input" class="av-search-input" placeholder="<?php echo htmlspecialchars(__('attendance.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                </div>
            </div>

            <div class="av-students-container">
            <?php foreach ($filasAsistencia as $i => $alumno):
                $eid        = (int) $alumno['id'];
                $pct        = (float) ($alumno['porcentaje'] ?? -1.0);
                $pctLabel   = $pct < 0 ? __('student.no_data') : number_format($pct, 1) . '%';
                $pctClase   = $pct < 0 ? 'av-pct--sin-datos' : ($pct >= 85 ? 'av-pct--ok' : ($pct >= 75 ? 'av-pct--alerta' : 'av-pct--riesgo'));
                $estadoActual = (string) ($alumno['estado'] ?? '');
                $estadoClaseDot = 'av-dot--none';
                if ($estadoActual !== '' && isset($estadosConfig[$estadoActual]['clase'])) {
                    $estadoClaseDot = 'av-dot--' . str_replace('av5--', '', (string) $estadosConfig[$estadoActual]['clase']);
                }
                $obsActual    = htmlspecialchars((string) ($alumno['observacion'] ?? ''), ENT_QUOTES, 'UTF-8');
                $adjActual    = $alumno['adjunto'] ?? null;
                $esSolapado   = ($bloqueosEstudiantes[$eid]['solapado'] ?? false);
                $motivoSolapado = ($bloqueosEstudiantes[$eid]['motivo'] ?? '');
                $esRecursante = ($bloqueosEstudiantes[$eid]['es_recursante'] ?? false);
                
                $statusLower = $estadoActual !== '' ? strtolower(str_replace(' ', '-', $estadoActual)) : 'unmarked';
                if ($statusLower === 'media-falta') $statusLower = 'media';
                if ($statusLower === 'justificado' || $statusLower === 'ausente-justificado') $statusLower = 'justificado';
            ?>
            <div class="av-student-card <?php echo $esSolapado ? 'av-student-card--solapada' : ''; ?><?php echo htmlspecialchars(__('auto.av_card_status'), ENT_QUOTES, 'UTF-8'); ?><?php echo $statusLower; ?>" 
                 data-nombre="<?php echo htmlspecialchars(strtolower($alumno['apellido'] . ' ' . $alumno['nombre']), ENT_QUOTES, 'UTF-8'); ?>"
                 data-dni="<?php echo htmlspecialchars($alumno['dni'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-eid="<?php echo $eid; ?>">
                
                <div class="av-card-accent"></div>
                
                <div class="av-card-header">
                    <div class="av-card-info">
                        <span class="av-card-index"><?php echo $i + 1; ?></span>
                        <?php
                        $iniciales = '';
                        $nombresPartes = explode(' ', trim((string) $alumno['nombre']));
                        $apellidosPartes = explode(' ', trim((string) $alumno['apellido']));
                        $iniciales .= isset($apellidosPartes[0][0]) ? mb_substr($apellidosPartes[0], 0, 1) : '';
                        $iniciales .= isset($nombresPartes[0][0]) ? mb_substr($nombresPartes[0], 0, 1) : '';
                        $iniciales = mb_strtoupper($iniciales);
                        ?>
                        <div class="av-avatar-circle" data-iniciales="<?php echo htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="av-card-name-section">
                            <h4 class="av-card-name"><?php echo htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <span class="av-card-dni"><i class="far fa-id-card"></i> <?php echo htmlspecialchars($alumno['dni'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($esRecursante): ?>
                                <span class="badge badge-warning badge-recursante"><?php echo htmlspecialchars(__('auto.recursante'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <span class="av-mobile-status-badge">
                                <?php echo $estadoActual !== '' ? htmlspecialchars($estadosConfig[$estadoActual]['label'], ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('attendance.unmarked'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="av-card-header-actions">
                        <span class="av-pct <?php echo $pctClase; ?>" data-eid="<?php echo $eid; ?>"><?php echo $pctLabel; ?></span>
                        <a class="btn-ficha" href="student_profile.php?id=<?php echo $eid; ?>" title="Ver ficha">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="av-card-body">
                    <?php if ($esSolapado): ?>
                        <span class="av-badge-solapado">
                            <i class="fas fa-ban"></i> <?php echo htmlspecialchars($motivoSolapado, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php elseif ($puedeEditar): ?>
                        <div class="av5" role="radiogroup" aria-label="Estado de <?php echo htmlspecialchars($alumno['apellido'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($estadosConfig as $val => $cfg): ?>
                            <label class="av5__opt <?php echo $cfg['clase']; ?><?php echo $estadoActual === $val ? ' is-selected' : ''; ?>" title="<?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="radio"
                                       name="estado[<?php echo $eid; ?>]"
                                       value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"
                                       <?php echo $estadoActual === $val ? 'checked' : ''; ?>
                                       autocomplete="off">
                                <span class="av5__face">
                                    <i class="fas <?php echo $cfg['icono']; ?>"></i>
                                    <span class="av5__label"><?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?php $cfg = $estadosConfig[$estadoActual] ?? $estadosConfig['Ausente']; ?>
                        <span class="av-badge av-badge--<?php echo $cfg['clase']; ?>">
                            <i class="fas <?php echo $cfg['icono']; ?>"></i>
                            <?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!$esSolapado): ?>
                <div class="av-card-details" id="details-<?php echo $eid; ?>" style="display: none;">
                    <div class="av-card-details-fields">
                        <div class="av-details-field-obs">
                            <label for="obs-<?php echo $eid; ?>"><i class="far fa-edit"></i><?php echo htmlspecialchars(__('auto.observaci_n'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php if ($puedeEditar): ?>
                            <input type="text"
                                   id="obs-<?php echo $eid; ?>"
                                   name="observacion[<?php echo $eid; ?>]"
                                   value="<?php echo $obsActual; ?>"
                                   class="av-obs-input"
                                   placeholder="<?php echo htmlspecialchars(__('attendance.note_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="200">
                            <?php elseif ($obsActual !== ''): ?>
                            <span class="av-obs-readonly"><?php echo $obsActual; ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="av-details-field-file">
                            <label><i class="fas fa-paperclip"></i><?php echo htmlspecialchars(__('auto.justificativo'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="av-file-uploader-row">
                                <?php if ($puedeEditar): ?>
                                <label class="av-file-label" title="Adjuntar justificativo (JPG, PNG, PDF — máx. 5MB)">
                                    <input type="file"
                                           name="adjunto[<?php echo $eid; ?>]"
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           class="av-file-input">
                                    <span class="av-file-btn"><i class="fas fa-upload"></i><?php echo htmlspecialchars(__('auto.subir'), ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                                <?php endif; ?>
                                
                                <div class="av-file-status-wrapper">
                                    <?php if ($adjActual !== null): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($adjActual, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="av-adj-actual" title="Ver justificativo cargado">
                                        <i class="fas fa-external-link-alt"></i><?php echo htmlspecialchars(__('auto.ver_archivo'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php else: ?>
                                    <span class="av-no-file-text text-muted"><?php echo htmlspecialchars(__('auto.sin_archivo'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="av-card-footer">
                    <button type="button" class="av-toggle-details-btn" data-eid="<?php echo $eid; ?>">
                        <span class="btn-text"><?php echo htmlspecialchars(__('auto.ver_notas_y_justificativo'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>

            <div class="av-floating-status-bar" id="av-status-bar">
                <div class="av-status-progress">
                    <i class="fas fa-tasks"></i>
                    <span><?php echo htmlspecialchars(__('auto.toma'), ENT_QUOTES, 'UTF-8'); ?><strong id="av-progress-count">0/0</strong> (<strong id="av-progress-percent">0%</strong>)</span>
                </div>
                <div class="av-status-saver" id="av-status-saver">
                    <i class="fas fa-check-circle"></i>
                    <span id="av-status-text"><?php echo htmlspecialchars(__('auto.guardado'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>

            <?php if (!$puedeEditar): ?>
            <div class="alert alert-info" style="margin:1rem 1.5rem;">
                <i class="fas fa-eye"></i><?php echo htmlspecialchars(__('auto.vista_de_solo_lectura'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

        </form>
        <?php endif; ?>
    </div>

    <!-- ====== MOBILE BOTTOM SHEET ====== -->
    <div class="av-bottom-sheet" id="av-student-bottom-sheet">
        <div class="av-bottom-sheet-backdrop" id="av-sheet-backdrop"></div>
        <div class="av-bottom-sheet-content">
            <div class="av-bottom-sheet-drag-handle"></div>
            <div class="av-bottom-sheet-header">
                <div class="av-sheet-student-info">
                    <div class="av-sheet-avatar" id="av-sheet-avatar"></div>
                    <div class="av-sheet-text">
                        <h3 id="av-sheet-name"></h3>
                        <span id="av-sheet-dni"></span>
                    </div>
                </div>
                <button type="button" class="av-sheet-close-btn" id="av-sheet-close-btn" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="av-bottom-sheet-body">
                <!-- Selector de Asistencia (Botones Grandes) -->
                <div class="av-sheet-section">
                    <label class="av-sheet-section-title"><?php echo htmlspecialchars(__('auto.registrar_asistencia'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="av-sheet-states-grid">
                        <button type="button" class="av-sheet-state-btn btn-presente" data-state="Presente">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo htmlspecialchars(__('auto.presente'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                        <button type="button" class="av-sheet-state-btn btn-tardanza" data-state="Tardanza">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars(__('auto.tardanza'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                        <button type="button" class="av-sheet-state-btn btn-media" data-state="Media falta">
                            <i class="fas fa-adjust"></i>
                            <span><?php echo htmlspecialchars(__('auto.media_falta'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                        <button type="button" class="av-sheet-state-btn btn-justificado" data-state="Ausente justificado">
                            <i class="fas fa-file-medical"></i>
                            <span><?php echo htmlspecialchars(__('auto.justificado'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                        <button type="button" class="av-sheet-state-btn btn-ausente" data-state="Ausente">
                            <i class="fas fa-times-circle"></i>
                            <span><?php echo htmlspecialchars(__('auto.ausente'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Observación -->
                <div class="av-sheet-section">
                    <label for="av-sheet-obs" class="av-sheet-section-title"><i class="far fa-edit"></i> Observación</label>
                    <textarea id="av-sheet-obs" class="av-sheet-textarea" placeholder="Escribe un motivo o comentario..." maxlength="200"></textarea>
                </div>

                <!-- Justificativo -->
                <div class="av-sheet-section">
                    <label class="av-sheet-section-title"><i class="fas fa-paperclip"></i> Justificativo</label>
                    <div class="av-sheet-file-row">
                        <label class="av-sheet-file-uploader" title="Adjuntar justificativo">
                            <input type="file" id="av-sheet-file-input" accept=".jpg,.jpeg,.png,.pdf" class="av-sheet-file-raw-input">
                            <span class="av-sheet-file-btn"><i class="fas fa-upload"></i><?php echo htmlspecialchars(__('auto.subir_archivo'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                        <div id="av-sheet-file-status" class="av-sheet-file-status-text">Sin archivo</div>
                    </div>
                </div>
            </div>
            <div class="av-bottom-sheet-footer">
                <button type="button" class="btn btn-primary w-100" id="av-sheet-save-btn"><?php echo htmlspecialchars(__('auto.listo'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <?php if ($tablaExiste && $cursoSeleccionado > 0 && $materiaSeleccionada <= 0): ?>
    <div class="card av-mobile-tab-content" data-mobile-tab="alumnos">
        <div class="card-body">
            <p class="text-muted"><i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.seleccion_una_materia_para_registrar_o_visu'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // cursoSeleccionado > 0 ?>
</section>

<?php $asistenciaNonce = htmlspecialchars((string) ($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
<script nonce="<?php echo $asistenciaNonce; ?>">
window.ASISTENCIA_I18N = {
    ver_notas_y_justificativo: <?php echo json_encode(__('auto.ver_notas_y_justificativo'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    ocultar_notas_y_justificativo: <?php echo json_encode(__('auto.ocultar_notas_y_justificativo'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    guardado: <?php echo json_encode(__('auto.guardado'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    guardando: <?php echo json_encode(__('auto.guardando'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    guardando_antes_de_cambiar_de_dia: <?php echo json_encode(__('auto.guardando_antes_de_cambiar_de_d_a'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    error_de_red_al_guardar: <?php echo json_encode(__('auto.error_de_red_al_guardar'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    no_se_guard_revisa: <?php echo json_encode(__('auto.no_se_guard_revis_conexi_n_e_intent_de_nuevo'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    no_se_detectaron_estados: <?php echo json_encode(__('auto.no_se_detectaron_estados_para_guardar'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
</script>
<script src="js/filtros_dependientes.js?v=<?php echo rawurlencode((string) $filtrosDependientesVersion); ?>" defer nonce="<?php echo $asistenciaNonce; ?>"></script>
<script src="js/asistencia_virtual.js?v=<?php echo rawurlencode((string) $asistenciaVirtualVersion); ?>" defer nonce="<?php echo $asistenciaNonce; ?>"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
