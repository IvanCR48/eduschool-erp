<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/familia_portal.php';

use SistemaAdmin\Services\NotasSubjectGradesPayloadBuilder;
use SistemaAdmin\Services\ServicioBoletinNotas;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Mappers\AsistenciaMapper;
use SistemaAdmin\Services\SchoolYearMilestoneService;
use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioAsistencia;
use SistemaAdmin\Services\ServicioRegistroEstadoMateria;
use SistemaAdmin\Services\SubjectStatusService;
use SistemaAdmin\Controllers\EstudianteController;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$estudianteMapper = new EstudianteMapper($databaseAdapter);
$registroAcademicoEstado = new ServicioRegistroEstadoMateria(
    $databaseAdapter,
    new SubjectStatusService(),
    new SchoolYearMilestoneService($databaseAdapter)
);
$servicioEstudiantes = new ServicioEstudiantes($databaseAdapter, $estudianteMapper, null, null, $registroAcademicoEstado);
$estudianteController = new EstudianteController($databaseAdapter, $servicioEstudiantes);
$asistenciaMapper = new AsistenciaMapper($databaseAdapter);
$servicioAsistencia = new ServicioAsistencia($databaseAdapter, $asistenciaMapper);

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$estudiante_id_int = ($idGet !== false && $idGet > 0) ? $idGet : 0;
if ($estudiante_id_int < 1) {
    header('Location: ' . app_base_path('students.php'));
    exit();
}

$usuario = $servicioAutenticacion->verificarSesion();
$familia_portal_lectura = familia_portal_puede_ver_estudiante($estudiante_id_int);

if (!$usuario && !$familia_portal_lectura) {
    header('Location: ' . app_base_path('public/portal.php') . '#acceso-familias');
    exit();
}

$preceptor_cids = [];
$es_profesor_ficha = false;
if ($familia_portal_lectura && !$usuario) {
    $GLOBALS['familia_portal_vista'] = true;
} elseif ($usuario) {
    require_once __DIR__ . '/includes/preceptor_scope.php';
    require_once __DIR__ . '/includes/profesor_scope.php';
    $preceptor_cids = preceptor_curso_ids();
    $es_profesor_ficha = es_profesor();
    $profesor_cids_ficha = $es_profesor_ficha ? profesor_curso_ids() : [];

    if ($es_profesor_ficha) {
        if ($profesor_cids_ficha === []) {
            header('Location: ' . app_base_path('students.php'));
            exit();
        }
        $chk_curso_pf = $servicioEstudiantes->obtenerCursoIdEstudianteActivo($estudiante_id_int);
        if ($chk_curso_pf === null || !in_array($chk_curso_pf, $profesor_cids_ficha, true)) {
            header('Location: ' . app_base_path('students.php'));
            exit();
        }
    } elseif ((($_SESSION['rol'] ?? '') === 'preceptor') && $preceptor_cids === []) {
        header('Location: ' . app_base_path('students.php'));
        exit();
    } elseif ($preceptor_cids !== []) {
        $chk_curso_id = $servicioEstudiantes->obtenerCursoIdEstudianteActivo($estudiante_id_int);
        if ($chk_curso_id === null || !in_array($chk_curso_id, $preceptor_cids, true)) {
            header('Location: ' . app_base_path('students.php'));
            exit();
        }
    }
}

require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/csrf_functions.php';

$familia_estudiante_ids = [];
if ($familia_portal_lectura && !$usuario) {
    $familia_estudiante_ids = $_SESSION[familia_portal_session_key()]['estudiante_ids'] ?? [];
}
$familia_multihijo = $familia_portal_lectura && !$usuario && is_array($familia_estudiante_ids) && count($familia_estudiante_ids) > 1;

/** Admin, directivo (director/vicedirector), preceptor y secretario pueden cambiar curso; no familias ni profesores. */
$puede_cambiar_curso_ficha = $usuario !== null
    && !$es_profesor_ficha
    && empty($GLOBALS['familia_portal_vista'])
    && hasRole(['admin', 'directivo', 'director', 'vicedirector', 'preceptor', 'secretario']);

/** Misma barra de roles: confirmar aprobación de materia previa desde la ficha (sin mesa de examen). */
$puede_aprobar_materia_previa_ficha = $puede_cambiar_curso_ficha;

$success_message = '';
$warning_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($familia_portal_lectura && !$usuario) {
        $error_message = 'El portal de familias es solo de consulta. Para cambios, contactá a la institución.';
    } elseif ($es_profesor_ficha) {
        $error_message = 'La ficha del estudiante es solo de consulta para docentes.';
    } elseif (!verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
        $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
    } else {
        $postOutcome = $estudianteController->procesarPostFichaEstudiante(
            $estudiante_id_int,
            $_POST,
            [
                'es_preceptor' => (($_SESSION['rol'] ?? '') === 'preceptor'),
                'preceptor_cids' => $preceptor_cids,
                'puede_cambiar_curso_staff' => $puede_cambiar_curso_ficha,
            ]
        );
        if ($postOutcome['redirect'] !== null) {
            header('Location: ' . $postOutcome['redirect']);
            exit();
        }
        $error_message = $postOutcome['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['success'])) {
    $ok = (string) $_GET['success'];
    switch ($ok) {
        case 'actualizado':
            $success_message = 'Información del estudiante actualizada correctamente';
            if (!empty($_GET['aviso'])) {
                $warning_message = (string) $_GET['aviso'];
            }
            break;
        case 'responsable':
            $success_message = 'Responsable agregado correctamente';
            break;
        case 'contacto_emergencia':
            $success_message = 'Contacto de emergencia agregado correctamente';
            break;
        case 'responsable_eliminado':
            $success_message = 'Responsable eliminado correctamente';
            break;
        case 'emergencia_eliminado':
            $success_message = 'Contacto de emergencia eliminado correctamente';
            break;
        case 'previa_aprobada':
            $success_message = 'Materia previa marcada como aprobada.';
            break;
        case 'curso':
            $da = isset($_GET['desde_anio']) ? (string) $_GET['desde_anio'] : '';
            $dd = isset($_GET['desde_div']) ? (string) $_GET['desde_div'] : '';
            $ha = isset($_GET['hasta_anio']) ? (string) $_GET['hasta_anio'] : '';
            $hd = isset($_GET['hasta_div']) ? (string) $_GET['hasta_div'] : '';
            if ($da !== '' && $dd !== '') {
                $success_message = 'Estudiante cambiado exitosamente de ' . $da . '° ' . $dd . ' a ' . $ha . '° ' . $hd;
            } else {
                $success_message = 'Estudiante asignado exitosamente a ' . $ha . '° ' . $hd;
            }
            break;
        case 'recursada_guardada':
            $success_message = 'Materia recursada asignada correctamente.';
            break;
        case 'recursada_eliminada':
            $success_message = 'Asignación de recursada eliminada correctamente.';
            break;
        default:
            break;
    }
}

$fichaVista = $estudianteController->obtenerFichaVista($estudiante_id_int);
if (empty($fichaVista['success']) || empty($fichaVista['data'])) {
    if ($familia_portal_lectura && !$usuario) {
        header('Location: ' . app_base_path('public/portal.php'));
    } else {
        header('Location: ' . app_base_path('students.php'));
    }
    exit();
}
$d = $fichaVista['data'];
$estudiante = $d['estudiante'];
$llamados = $d['llamados'];
$responsables = $d['responsables'];
$contactos_emergencia = $d['contactos_emergencia'];
$turnos_lista = $d['turnos_lista'];
$stats = $d['stats'];
$llamados_amonestacion_verbal = $d['llamados_amonestacion_verbal'];
$cursos_disponibles = $d['cursos_disponibles'];
$notas_estudiante = $d['notas_estudiante'];
$porcentaje_aprobadas = $d['porcentaje_aprobadas'];
$materias_aprobadas = $d['materias_aprobadas'];
$total_materias = $d['total_materias'];

require_once __DIR__ . '/includes/ficha_estudiante/helpers.php';
$notas_boletin = ficha_estudiante_filas_boletin_normalizadas($notas_estudiante);
$materias_previas_ficha = [];
try {
    $materias_previas_ficha = $databaseAdapter->fetchAll(
        'SELECT
            p.id,
            p.anio_previo,
            p.estado,
            p.observaciones,
            p.mes_aprobacion,
            p.anio_aprobacion,
            p.nota,
            m.nombre AS materia_nombre
         FROM materias_previas p
         INNER JOIN materias m ON m.id = p.materia_id
         WHERE p.estudiante_id = ?
         ORDER BY
            CASE p.estado
                WHEN "pendiente" THEN 1
                WHEN "regularizada" THEN 2
                WHEN "aprobada" THEN 3
                ELSE 4
            END,
            p.anio_previo DESC,
            m.nombre ASC',
        [$estudiante_id_int]
    );
} catch (\Throwable $e) {
    $materias_previas_ficha = [];
}

$school_year_intensif_ficha = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
$intensificaciones_ficha = [];
try {
    $intensificaciones_ficha = (new ServicioBoletinNotas($databaseAdapter))
        ->listarIntensificacionesEstudianteParaFicha($estudiante_id_int, $school_year_intensif_ficha);
} catch (\Throwable $e) {
    $intensificaciones_ficha = [];
}

$csrfToken = getCSRFToken();

$recursadas_estudiante = [];
$materias_todas = [];
$curso_materias_map = [];
try {
    $recursadas_estudiante = $servicioEstudiantes->obtenerRecursadasEstudiante($estudiante_id_int, $school_year_intensif_ficha);
    $materias_todas = $databaseAdapter->fetchAll("SELECT id, nombre FROM materias WHERE activa = 1 ORDER BY nombre");
    $rows_mc = $databaseAdapter->fetchAll(
        "SELECT mc.curso_id, mc.materia_id, m.nombre AS materia_nombre
         FROM materia_curso mc
         JOIN materias m ON mc.materia_id = m.id
         WHERE mc.activo = 1 AND m.activa = 1
         ORDER BY m.nombre"
    );
    foreach ($rows_mc as $row) {
        $curso_materias_map[(int)$row['curso_id']][] = [
            'id' => (int)$row['materia_id'],
            'nombre' => $row['materia_nombre']
        ];
    }
} catch (\Throwable $e) {
    // ignorar
}

// Datos de asistencia para la ficha (sólo si hay tabla)
$asistenciaFicha = null;
$asistenciaCuatrimestre = (string) (filter_input(INPUT_GET, 'asistencia_trimestre', FILTER_DEFAULT) ?? '');
if (!in_array($asistenciaCuatrimestre, ['', '1', '2'], true)) {
    $asistenciaCuatrimestre = '';
}
$asistenciaMateriaId = (int) (filter_input(INPUT_GET, 'asistencia_materia_id', FILTER_VALIDATE_INT) ?: 0);
$asistenciaMaterias = [];

$rangoCuatrimestreAsistencia = static function (int $anio, string $cuatrimestre): array {
    return match ($cuatrimestre) {
        '1' => [$anio . '-03-01', $anio . '-07-31'], // 1° cuatrimestre
        '2' => [$anio . '-08-01', $anio . '-12-31'], // 2° cuatrimestre
        default => [$anio . '-03-01', $anio . '-12-31'],
    };
};

try {
    $cursoAsistenciaId = (int) ($estudiante['curso_id'] ?? 0);
    if ($cursoAsistenciaId > 0) {
        $asistenciaMaterias = $databaseAdapter->fetchAll(
            'SELECT m.id, m.nombre
             FROM materias m
             INNER JOIN materia_curso mc ON mc.materia_id = m.id AND mc.activo = 1
             WHERE m.activa = 1 AND mc.curso_id = ?
             ORDER BY m.nombre',
            [$cursoAsistenciaId]
        );
        if ($asistenciaMateriaId > 0) {
            $materiaValida = false;
            foreach ($asistenciaMaterias as $matAs) {
                if ((int) ($matAs['id'] ?? 0) === $asistenciaMateriaId) {
                    $materiaValida = true;
                    break;
                }
            }
            if (!$materiaValida) {
                $asistenciaMateriaId = 0;
            }
        }
    } else {
        $asistenciaMateriaId = 0;
    }

    [$asistenciaDesde, $asistenciaHasta] = $rangoCuatrimestreAsistencia((int) date('Y'), $asistenciaCuatrimestre);
    $asistenciaFicha = $servicioAsistencia->datosAsistenciaFicha(
        $estudiante_id_int,
        $asistenciaDesde,
        $asistenciaHasta,
        20,
        $asistenciaMateriaId > 0 ? $asistenciaMateriaId : null
    );
} catch (\Throwable $e) {
    // Tabla no existe aún — se omite la sección
    $asistenciaFicha = null;
}

$pageTitle = 'Ficha del Estudiante - Sistema Administrativo E.E.S.T N°2';
$fichaCssPath = __DIR__ . '/css/estudiante_ficha.css';
$fichaCssVersion = is_file($fichaCssPath) ? (string) filemtime($fichaCssPath) : '1';
$GLOBALS['extra_css'] = '<link rel="stylesheet" href="' . htmlspecialchars(app_base_path('css/estudiante_ficha.css?v=' . $fichaCssVersion), ENT_QUOTES, 'UTF-8') . '">' . "\n";
sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="ficha-estudiante">
<?php include __DIR__ . '/includes/ficha_estudiante/partials/alertas.php'; ?>

    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('auto.ficha_del_estudiante'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="header-actions">
            <?php if (!empty($familia_portal_lectura) && empty($usuario)): ?>
                <?php if (!empty($familia_multihijo)): ?>
                <a href="<?php echo htmlspecialchars(app_base_path('public/familia_seleccion.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                    <i class="fas fa-users"></i><?php echo htmlspecialchars(__('auto.elegir_otro_estudiante'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars(app_base_path('public/familia_logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i><?php echo htmlspecialchars(__('auto.salir'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
             <?php
             $backUrl = app_base_path('students.php');
             if (!empty($_GET['back_to'])) {
                 $proposedBack = (string) $_GET['back_to'];
                 if (preg_match('/^(notas\.php|estudiantes\.php)/', $proposedBack)) {
                     $backUrl = app_base_path($proposedBack);
                 }
             }
             ?>
             <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                 <i class="fas fa-arrow-left"></i><?php echo htmlspecialchars(__('auto.volver'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php if (!$es_profesor_ficha): ?>
            <button type="button" class="btn btn-warning" data-csp-show-modal="editModal" data-csp-modal-lock-body="1">
                <i class="fas fa-edit"></i><?php echo htmlspecialchars(__('auto.editar_informaci_n'), ENT_QUOTES, 'UTF-8'); ?></button>
            <a href="<?php echo htmlspecialchars(app_base_path('discipline.php?' . http_build_query(['action' => 'nuevo', 'estudiante' => $estudiante['id']])), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('auto.nuevo_llamado'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
            <?php if ($es_profesor_ficha || hasRole('admin') || hasRole('directivo')): ?>
            <a href="<?php echo htmlspecialchars(app_base_path('grades.php?' . http_build_query(['estudiante' => (int) $estudiante['id']])), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                <i class="fas fa-clipboard-check"></i><?php echo htmlspecialchars(__('auto.ver_notas'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información personal -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('auto.informaci_n_personal'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="card-body">
            <div class="student-profile">
                <div class="profile-photo">
                    <?php if (!empty($estudiante['foto'])): ?>
                        <?php
                        $fichaFotoSrc = (string) $estudiante['foto'];
                        if ($fichaFotoSrc !== '' && !str_starts_with($fichaFotoSrc, 'http://')
                            && !str_starts_with($fichaFotoSrc, 'https://')
                            && !str_starts_with($fichaFotoSrc, '//')
                            && !str_starts_with($fichaFotoSrc, '/')) {
                            $fichaFotoSrc = app_base_path(ltrim($fichaFotoSrc, '/'));
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($fichaFotoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto del estudiante">
                    <?php else: ?>
                        <div class="default-photo">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong><?php echo htmlspecialchars(__('auto.dni'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars($estudiante['dni']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong><?php echo htmlspecialchars(__('auto.fecha_de_nacimiento'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>
                                <?php if ($estudiante['fecha_nacimiento']): ?>
                                    <?php echo date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])); ?>
                                    (<?php echo floor((time() - strtotime($estudiante['fecha_nacimiento'])) / (365.25 * 24 * 3600)); ?> <?php echo htmlspecialchars(__('student.years_old'), ENT_QUOTES, 'UTF-8'); ?>)
                                <?php else: ?>
                                    <?php echo htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong><?php echo htmlspecialchars(__('auto.curso'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>
                                <?php if ($estudiante['anio']): ?>
                                    <?php echo $estudiante['anio'] . '° ' . $estudiante['division'] . ($estudiante['especialidad'] ? ' - ' . $estudiante['especialidad'] : ''); ?>
                                    <?php if (!empty($estudiante['turno'])): ?><small>(<?php echo $estudiante['turno']; ?>)</small><?php endif; ?>
                                <?php else: ?>
                                    <span class="status status-warning"><?php echo htmlspecialchars(__('auto.sin_asignar'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong><?php echo htmlspecialchars(__('auto.grupo_sangu_neo'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo $estudiante['grupo_sanguineo'] ? htmlspecialchars($estudiante['grupo_sanguineo'], ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="info-item">
                            <strong><?php echo htmlspecialchars(__('auto.obra_social'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php $obraSocial = $estudiante['obra_social'] ?? ''; ?>
                            <span><?php echo $obraSocial !== '' ? htmlspecialchars((string)$obraSocial, ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="info-item">
                            <strong><?php echo htmlspecialchars(__('auto.fecha_de_ingreso'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo $estudiante['fecha_ingreso'] ? date('d/m/Y', strtotime($estudiante['fecha_ingreso'])) : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-info">
                <h4><?php echo htmlspecialchars(__('auto.informaci_n_de_contacto'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="info-grid">
                    <div class="info-item">
                        <strong><?php echo htmlspecialchars(__('auto.domicilio'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php $domicilio = $estudiante['domicilio'] ?? ''; ?>
                        <span><?php echo $domicilio !== '' ? htmlspecialchars((string)$domicilio, ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><?php echo htmlspecialchars(__('auto.tel_fono_fijo'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php $telefonoFijo = $estudiante['telefono'] ?? ''; ?>
                        <span><?php echo $telefonoFijo !== '' ? htmlspecialchars((string)$telefonoFijo, ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><?php echo htmlspecialchars(__('auto.tel_fono_celular'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php $telefonoCel = $estudiante['telefono_celular'] ?? ($estudiante['telefono'] ?? ''); ?>
                        <span><?php echo $telefonoCel !== '' ? htmlspecialchars((string)$telefonoCel, ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><?php echo htmlspecialchars(__('auto.email'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php $emailVal = $estudiante['email'] ?? ''; ?>
                        <span><?php echo $emailVal !== '' ? htmlspecialchars((string)$emailVal, ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('student.not_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['llamados_total']; ?></h3>
                <p><?php echo htmlspecialchars(__('auto.total_llamados'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['llamados_mes']; ?></h3>
                <p><?php echo htmlspecialchars(__('auto.llamados_este_mes'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <?php if ($estudiante['curso_id'] && !empty($notas_estudiante)): ?>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $materias_aprobadas; ?>/<?php echo $total_materias; ?></h3>
                <p><?php echo htmlspecialchars(__('auto.materias_aprobadas'), ENT_QUOTES, 'UTF-8'); ?></p>
                <small class="ficha-muted"><?php echo htmlspecialchars((string) $porcentaje_aprobadas); ?><?php echo htmlspecialchars(__('auto.del_curso'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($asistenciaFicha !== null): ?>
        <div class="stat-card">
            <div class="stat-icon <?php
                $pctA = (float) ($asistenciaFicha['resumen']['porcentaje'] ?? -1.0);
                echo $pctA < 0 ? 'info' : ($pctA >= 85 ? 'success' : ($pctA >= 75 ? 'warning' : 'danger'));
            ?>">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pctA < 0 ? 'S/D' : number_format($pctA, 1) . '%'; ?></h3>
                <p><?php echo htmlspecialchars(__('auto.asistencia_anual'), ENT_QUOTES, 'UTF-8'); ?></p>
                <small class="ficha-muted"><?php echo (int)($asistenciaFicha['resumen']['total'] ?? 0); ?> registros · <?php echo (int)($asistenciaFicha['resumen']['presente'] ?? 0) + (int)($asistenciaFicha['resumen']['tardanza'] ?? 0); ?><?php echo htmlspecialchars(__('auto.presencias'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="ficha-grid ficha-grid--1">
        <!-- Llamados de atención recientes -->
        <div class="card">
            <div class="card-header ficha-card-header-row">
                <h3 class="card-title"><?php echo htmlspecialchars(__('auto.llamados_de_atenci_n_recientes'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ((empty($familia_portal_lectura) || !empty($usuario)) && !$es_profesor_ficha): ?>
                <a href="<?php echo htmlspecialchars(app_base_path('discipline.php?' . http_build_query(['estudiante' => (int) $estudiante['id']])), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-secondary"><?php echo htmlspecialchars(__('auto.ver_todos'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($llamados)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(__('student.date'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(__('student.reason'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(__('student.sanction'), ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($llamados, 0, 10) as $llamado): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($llamado['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($llamado['motivo'], 0, 40)) . (strlen($llamado['motivo']) > 40 ? '...' : ''); ?></td>
                                <td>
                                    <?php if ($llamado['sancion']): ?>
                                        <span class="status status-warning"><?php echo htmlspecialchars($llamado['sancion']); ?></span>
                                    <?php else: ?>
                                        <span class="status status-success"><?php echo htmlspecialchars(__('auto.sin_sanci_n'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-center-muted"><?php echo htmlspecialchars(__('auto.no_hay_llamados_registrados'), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Responsables y Contactos -->
    <?php if (!empty($responsables) || !empty($contactos_emergencia)): ?>
    <div class="ficha-grid ficha-grid--2">
        <!-- Responsables -->
        <?php if (!empty($responsables)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo htmlspecialchars(__('auto.responsables'), ENT_QUOTES, 'UTF-8'); ?></h3>
            </div>
            <div class="card-body">
                <?php foreach ($responsables as $responsable): ?>
                <div class="ficha-persona-card responsable-item">
                    <div class="ficha-persona-card__actions">
                        <?php if ((empty($familia_portal_lectura) || !empty($usuario)) && !$es_profesor_ficha): ?>
                        <button type="button" class="btn-eliminar js-open-eliminar-responsable" data-responsable-id="<?php echo (int) $responsable['id']; ?>" data-responsable-nombre="<?php echo htmlspecialchars($responsable['apellido'] . ', ' . $responsable['nombre'], ENT_QUOTES, 'UTF-8'); ?>" title="Eliminar responsable">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <h4 class="ficha-persona-card__title">
                        <?php echo htmlspecialchars($responsable['apellido'] . ', ' . $responsable['nombre']); ?>
                        <?php if ($responsable['es_contacto_emergencia']): ?>
                            <span class="status status-success ficha-badge-emergencia"><?php echo htmlspecialchars(__('auto.contacto_de_emergencia'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </h4>
                    <div class="info-grid ficha-persona-card__grid">
                        <div><strong>DNI:</strong> <?php echo htmlspecialchars($responsable['dni'] ?? ''); ?></div>
                        <div><strong><?php echo htmlspecialchars(__('auto.parentesco'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($responsable['parentesco'] ?? ''); ?></div>
                        <div><strong><?php echo htmlspecialchars(__('auto.tel_fono'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($responsable['telefono_celular'] ?? ''); ?></div>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($responsable['email'] ?? ''); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contactos de emergencia -->
        <?php if (!empty($contactos_emergencia)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo htmlspecialchars(__('auto.contactos_de_emergencia'), ENT_QUOTES, 'UTF-8'); ?></h3>
            </div>
            <div class="card-body">
                <?php foreach ($contactos_emergencia as $contacto): ?>
                <div class="ficha-persona-card contacto-item">
                    <div class="ficha-persona-card__actions">
                        <?php if ((empty($familia_portal_lectura) || !empty($usuario)) && !$es_profesor_ficha): ?>
                        <button type="button" class="btn-eliminar js-open-eliminar-contacto" data-contacto-id="<?php echo (int) $contacto['id']; ?>" data-contacto-nombre="<?php echo htmlspecialchars($contacto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" title="Eliminar contacto">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <h4 class="ficha-persona-card__title"><?php echo htmlspecialchars($contacto['nombre']); ?></h4>
                    <div class="info-grid ficha-persona-card__grid">
                        <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($contacto['telefono']); ?></div>
                        <div><strong>Parentesco:</strong> <?php echo htmlspecialchars($contacto['parentesco']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
<?php include __DIR__ . '/includes/ficha_estudiante/partials/boletin.php'; ?>

<?php if ($asistenciaFicha !== null): ?>
<div class="card ficha-asistencia-card" style="margin-top:1.25rem">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
        <h3 class="card-title"><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars(__('student.accumulated_report'), ENT_QUOTES, 'UTF-8'); ?> <?php echo date('Y'); ?></h3>
        <form method="get" class="ficha-asistencia-filtros">
            <input type="hidden" name="id" value="<?php echo $estudiante_id_int; ?>">
            <label for="asistencia_materia_id" class="ficha-asistencia-filtros__label"><?php echo htmlspecialchars(__('auto.materia'), ENT_QUOTES, 'UTF-8'); ?></label>
            <select id="asistencia_materia_id" name="asistencia_materia_id" class="ficha-asistencia-filtros__select">
                <option value="0"><?php echo htmlspecialchars(__('auto.todas'), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php foreach ($asistenciaMaterias as $matAs): ?>
                <option value="<?php echo (int) ($matAs['id'] ?? 0); ?>" <?php echo ((int) ($matAs['id'] ?? 0) === $asistenciaMateriaId) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) ($matAs['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <label for="asistencia_trimestre" class="ficha-asistencia-filtros__label"><?php echo htmlspecialchars(__('auto.cuatrimestre'), ENT_QUOTES, 'UTF-8'); ?></label>
            <select id="asistencia_trimestre" name="asistencia_trimestre" class="ficha-asistencia-filtros__select">
                <option value="" <?php echo $asistenciaCuatrimestre === '' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.ciclo_lectivo'), ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="1" <?php echo $asistenciaCuatrimestre === '1' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.1er_cuatrimestre'), ENT_QUOTES, 'UTF-8'); ?></option>
                <option value="2" <?php echo $asistenciaCuatrimestre === '2' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.2do_cuatrimestre'), ENT_QUOTES, 'UTF-8'); ?></option>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary"><?php echo htmlspecialchars(__('auto.aplicar'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
    </div>
    <div class="card-body">
        <!-- Resumen de estados del año -->
        <?php
        $resA = $asistenciaFicha['resumen'];
        $totalA = (int)($resA['total'] ?? 0);
        $pctLbl = $pctA < 0 ? __('student.no_data') : number_format($pctA, 1) . '%';
        ?>
        <div class="ficha-asistencia-resumen">
            <div class="far-item"><span class="far-value"><?php echo (int)$resA['presente']; ?></span><small class="far-label"><?php echo htmlspecialchars(__('auto.presentes'), ENT_QUOTES, 'UTF-8'); ?></small></div>
            <div class="far-item"><span class="far-value"><?php echo (int)$resA['tardanza']; ?></span><small class="far-label"><?php echo htmlspecialchars(__('auto.tardanzas'), ENT_QUOTES, 'UTF-8'); ?></small></div>
            <div class="far-item"><span class="far-value"><?php echo (int)$resA['media_falta']; ?></span><small class="far-label"><?php echo htmlspecialchars(__('auto.media_falta'), ENT_QUOTES, 'UTF-8'); ?></small></div>
            <div class="far-item"><span class="far-value"><?php echo (int)$resA['ausente_justificado']; ?></span><small class="far-label"><?php echo htmlspecialchars(__('auto.justificados'), ENT_QUOTES, 'UTF-8'); ?></small></div>
            <div class="far-item"><span class="far-value"><?php echo (int)$resA['ausente']; ?></span><small class="far-label"><?php echo htmlspecialchars(__('auto.ausentes'), ENT_QUOTES, 'UTF-8'); ?></small></div>
            <div class="far-item far-item--total">
                <span class="far-rate"><?php echo htmlspecialchars($pctLbl, ENT_QUOTES, 'UTF-8'); ?></span>
                <small class="far-label"><?php echo sprintf(htmlspecialchars(__('student.of_classes'), ENT_QUOTES, 'UTF-8'), $totalA); ?></small>
            </div>
        </div>

        <!-- Historial reciente -->
        <?php if (!empty($asistenciaFicha['historial'])): ?>
        <h4 class="ficha-asistencia-subtitle"><i class="fas fa-history"></i><?php echo htmlspecialchars(__('auto.ltimos_registros'), ENT_QUOTES, 'UTF-8'); ?></h4>
        <div class="table-container">
            <table class="table ficha-asistencia-table" style="font-size:.82rem">
                <thead><tr><th><?php echo htmlspecialchars(__('student.date'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(__('student.subject'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(__('student.status'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(__('student.notes'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(__('student.document'), ENT_QUOTES, 'UTF-8'); ?></th></tr></thead>
                <tbody>
                <?php
                $estadosBadge = [
                    'Presente'            => ['cls' => 'av-cnt--ok',     'ico' => 'fa-check-circle'],
                    'Tardanza'            => ['cls' => 'av-cnt--warn',   'ico' => 'fa-clock'],
                    'Media falta'         => ['cls' => 'av-cnt--info',   'ico' => 'fa-adjust'],
                    'Ausente justificado' => ['cls' => 'av-cnt--prim',   'ico' => 'fa-file-medical'],
                    'Ausente'             => ['cls' => 'av-cnt--danger', 'ico' => 'fa-times-circle'],
                ];
                foreach ($asistenciaFicha['historial'] as $reg):
                    $est   = (string)($reg['estado'] ?? 'Ausente');
                    $badge = $estadosBadge[$est] ?? ['cls' => 'av-cnt--danger', 'ico' => 'fa-times-circle'];
                    $obs   = htmlspecialchars((string)($reg['observacion'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $adj   = $reg['adjunto'] ?? null;
                    $ts    = strtotime((string)($reg['fecha'] ?? ''));
                    $fmtF  = $ts ? date('d/m/Y', $ts) : (string)$reg['fecha'];
                ?>
                <tr>
                    <td><?php echo $fmtF; ?></td>
                    <td><?php echo htmlspecialchars((string) ($reg['materia_nombre'] ?? 'Sin materia'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="av-cnt <?php echo $badge['cls']; ?>"><i class="fas <?php echo $badge['ico']; ?>"></i> <?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo $obs !== '' ? $obs : '<span style="color:#94a3b8">—</span>'; ?></td>
                    <td style="text-align: center;">
                        <?php if ($adj !== null): ?>
                            <a href="<?php echo htmlspecialchars(app_base_path('uploads/' . ltrim((string) $adj, '/')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Ver justificativo" style="color:var(--primary-color);">
                                <i class="fas fa-file-medical-alt"></i><?php echo htmlspecialchars(__('auto.ver'), ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <span style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>
</section>

<?php if (!empty($usuario) && !$es_profesor_ficha): ?>
<?php include __DIR__ . '/includes/ficha_estudiante/partials/modales.php'; ?>
<?php include __DIR__ . '/includes/ficha_estudiante/partials/modal_editar.php'; ?>
<?php $fichaNonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
<script src="<?php echo htmlspecialchars(app_base_path('js/estudiante_ficha.js'), ENT_QUOTES, 'UTF-8'); ?>" defer nonce="<?php echo $fichaNonce; ?>"></script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
