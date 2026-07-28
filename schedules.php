<?php

declare(strict_types=1);

$horas_disponibles_default = [
    '07:30',
    '09:30',
    '10:40',
    '11:50',
    '12:10',
    '13:10',
    '15:10',
    '16:20',
    '17:30',
    '18:25',
    '21:00'
];
require_once __DIR__ . '/includes/sistema_admin_session.php';
require_once __DIR__ . '/includes/sistema_admin_http.php';
require_once __DIR__ . '/includes/csrf_functions.php';

use SistemaAdmin\Controllers\HorariosController;
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
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/profesor_scope.php';

$preceptor_cids = preceptor_curso_ids();
$es_profesor_hor = es_profesor();
$profesor_cids_hor = $es_profesor_hor ? profesor_curso_ids() : [];
$alcance_cursos_horarios = null;
if ($es_profesor_hor) {
    $alcance_cursos_horarios = $profesor_cids_hor;
} elseif (($usuario['rol'] ?? '') === 'preceptor') {
    $alcance_cursos_horarios = $preceptor_cids;
}

$servicioHorarios = new ServicioHorarios($databaseAdapter);
$horariosController = new HorariosController($databaseAdapter, $servicioAutenticacion, $servicioHorarios);

$normalizarHora = static function (?string $hora): ?string {
    $hora = trim((string) $hora);
    if ($hora === '') {
        return null;
    }
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', $hora, $m)) {
        return null;
    }
    $h = (int) $m[1];
    $i = (int) $m[2];
    if ($h < 0 || $h > 23 || $i < 0 || $i > 59) {
        return null;
    }
    return sprintf('%02d:%02d', $h, $i);
};

$ordenarYLimpiarHoras = static function (array $horas) use ($normalizarHora): array {
    $limpias = [];
    foreach ($horas as $h) {
        $n = $normalizarHora((string) $h);
        if ($n !== null) {
            $limpias[$n] = true;
        }
    }
    $resultado = array_keys($limpias);
    usort($resultado, static fn (string $a, string $b): int => strcmp($a, $b));
    return $resultado;
};

$guardarHorasDisponiblesConfig = static function (array $horas) use ($databaseAdapter, $usuario): bool {
    $valor = implode(',', $horas);
    $clave = 'horarios.horas_disponibles';
    $uid = (int) ($usuario['id'] ?? 0);
    $existente = $databaseAdapter->fetch(
        'SELECT id FROM configuracion_sistema WHERE clave = ? LIMIT 1',
        [$clave]
    );
    if ($existente) {
        $databaseAdapter->query(
            'UPDATE configuracion_sistema
             SET valor = ?, tipo = "string", categoria = "academico", descripcion = ?, modificado_por = ?, modificado_en = NOW()
             WHERE id = ?',
            [$valor, 'Horas disponibles para inicio/fin de módulos en horarios', $uid > 0 ? $uid : null, (int) $existente['id']]
        );
    } else {
        $databaseAdapter->query(
            'INSERT INTO configuracion_sistema (clave, valor, tipo, categoria, descripcion, modificado_por, modificado_en, creado_en)
             VALUES (?, ?, "string", "academico", ?, ?, NOW(), NOW())',
            [$clave, $valor, 'Horas disponibles para inicio/fin de módulos en horarios', $uid > 0 ? $uid : null]
        );
    }
    return true;
};

$csrfToken = getCSRFToken();
$horas_disponibles = $horas_disponibles_default;
try {
    $cfgHoras = $databaseAdapter->fetch(
        'SELECT valor FROM configuracion_sistema WHERE clave = ? LIMIT 1',
        ['horarios.horas_disponibles']
    );
    if (!empty($cfgHoras['valor'])) {
        $horasCfg = array_map('trim', explode(',', (string) $cfgHoras['valor']));
        $horasParseadas = $ordenarYLimpiarHoras($horasCfg);
        if ($horasParseadas !== []) {
            $horas_disponibles = $horasParseadas;
        }
    }
} catch (\Throwable $e) {
    // Si falla la config, se mantiene el set por defecto.
}

if (filter_input(INPUT_GET, 'ajax', FILTER_DEFAULT) === 'get_materias_por_curso') {
    header('Content-Type: application/json; charset=utf-8');
    $ajaxCsrf = (string) (filter_input(INPUT_GET, 'csrf_token', FILTER_DEFAULT) ?? '');
    if (!verifyCSRFToken($ajaxCsrf)) {
        echo json_encode([
            'success' => false,
            'message' => 'Token de seguridad inválido o expirado.',
        ]);
        exit();
    }
    try {
        $cursoIdGet = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT);
        $cursoId = ($cursoIdGet !== false && $cursoIdGet > 0) ? $cursoIdGet : 0;
        $rolUsuario = (string) ($usuario['rol'] ?? '');
        $alcanceAjax = null;
        if ($rolUsuario === 'profesor') {
            $alcanceAjax = profesor_curso_ids();
        } elseif ($rolUsuario === 'preceptor') {
            $alcanceAjax = $preceptor_cids;
        }
        $resultado = $horariosController->materiasPorCursoParaAjax($cursoId, $rolUsuario, $alcanceAjax);
        if ($resultado['success']) {
            echo json_encode([
                'success' => true,
                'data' => $resultado['data'] ?? [],
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error',
            ]);
        }
    } catch (\Throwable $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener materias: ' . $e->getMessage(),
        ]);
    }
    exit();
}

$pageTitle = __('schedules.title') . ' - ' . \SistemaAdmin\Bootstrap\AppRequestInit::systemName();

$action = trim((string) (filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?? ''));
$profesor_filter = trim((string) (filter_input(INPUT_GET, 'profesor', FILTER_DEFAULT) ?? ''));
$cursoGetInt = filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT);
$cursoFromGetStr = ($cursoGetInt !== false && $cursoGetInt > 0) ? (string) $cursoGetInt : '';

$curso_filter = '';
if ($alcance_cursos_horarios === null) {
    $curso_filter = $cursoFromGetStr;
} elseif ($alcance_cursos_horarios === []) {
    $curso_filter = '';
} elseif (count($alcance_cursos_horarios) === 1) {
    $curso_filter = (string) $alcance_cursos_horarios[0];
} else {
    $curso_filter = ($cursoFromGetStr !== '' && in_array((int) $cursoFromGetStr, $alcance_cursos_horarios, true))
        ? $cursoFromGetStr
        : '';
}

if ($es_profesor_hor) {
    $pidHor = profesor_id_sesion();
    $profesor_filter = $pidHor !== null ? (string) $pidHor : '';
}

$success_message = '';
$error_message = '';
$accionBloquesMensaje = false;

if ($es_profesor_hor && ($action === 'editar' || $action === 'nuevo')) {
    header('Location: schedules.php');
    exit();
}

// Solo admin y directivo pueden crear horarios
if ($action === 'nuevo' && !$servicioAutenticacion->tienePermiso('gestionar_horarios')) {
    header('Location: schedules.php?error=unauthorized');
    exit();
}

$esPostHorario = $_SERVER['REQUEST_METHOD'] === 'POST' && (
    isset($_POST['guardar_horario']) || isset($_POST['actualizar_horario']) || isset($_POST['eliminar_horario'])
);
$esPostBloques = $_SERVER['REQUEST_METHOD'] === 'POST' && (
    isset($_POST['agregar_hora_disponible']) || isset($_POST['actualizar_hora_disponible']) || isset($_POST['eliminar_hora_disponible'])
);

if ($esPostHorario && $es_profesor_hor) {
    $error_message = 'Los docentes solo pueden consultar horarios.';
} elseif ($esPostHorario && !verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
    $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
} elseif ($esPostBloques && !$servicioAutenticacion->tienePermiso('gestionar_horarios')) {
    $error_message = 'No tiene permisos para gestionar bloques horarios.';
} elseif ($esPostBloques && !verifyCSRFToken((string) ($_POST['csrf_token'] ?? ''))) {
    $error_message = 'La solicitud no pudo validarse. Actualice la página e intente nuevamente.';
} elseif (isset($_POST['agregar_hora_disponible'])) {
    $accionBloquesMensaje = true;
    try {
        $nuevaHora = $normalizarHora((string) (filter_input(INPUT_POST, 'nueva_hora', FILTER_DEFAULT) ?? ''));
        if ($nuevaHora === null) {
            $error_message = 'Hora inválida. Use formato HH:MM.';
        } elseif (in_array($nuevaHora, $horas_disponibles, true)) {
            $error_message = 'Esa hora ya está registrada.';
        } else {
            $horas_disponibles[] = $nuevaHora;
            $horas_disponibles = $ordenarYLimpiarHoras($horas_disponibles);
            $guardarHorasDisponiblesConfig($horas_disponibles);
            $success_message = 'Hora agregada correctamente.';
        }
    } catch (\Throwable $e) {
        $error_message = 'No se pudo agregar la hora. Intente nuevamente.';
        error_log('Error agregando hora disponible: ' . $e->getMessage());
    }
} elseif (isset($_POST['actualizar_hora_disponible'])) {
    $accionBloquesMensaje = true;
    try {
        $horaOriginal = $normalizarHora((string) (filter_input(INPUT_POST, 'hora_original', FILTER_DEFAULT) ?? ''));
        $horaNueva = $normalizarHora((string) (filter_input(INPUT_POST, 'hora_nueva', FILTER_DEFAULT) ?? ''));
        if ($horaOriginal === null || $horaNueva === null) {
            $error_message = 'Hora inválida. Use formato HH:MM.';
        } elseif (!in_array($horaOriginal, $horas_disponibles, true)) {
            $error_message = 'La hora original no existe.';
        } elseif ($horaOriginal !== $horaNueva && in_array($horaNueva, $horas_disponibles, true)) {
            $error_message = 'La nueva hora ya existe.';
        } else {
            $horas_disponibles = array_values(array_filter(
                $horas_disponibles,
                static fn (string $h): bool => $h !== $horaOriginal
            ));
            $horas_disponibles[] = $horaNueva;
            $horas_disponibles = $ordenarYLimpiarHoras($horas_disponibles);
            $guardarHorasDisponiblesConfig($horas_disponibles);
            $success_message = 'Hora actualizada correctamente.';
        }
    } catch (\Throwable $e) {
        $error_message = 'No se pudo actualizar la hora. Intente nuevamente.';
        error_log('Error actualizando hora disponible: ' . $e->getMessage());
    }
} elseif (isset($_POST['eliminar_hora_disponible'])) {
    $accionBloquesMensaje = true;
    try {
        $horaEliminar = $normalizarHora((string) (filter_input(INPUT_POST, 'hora_eliminar', FILTER_DEFAULT) ?? ''));
        if ($horaEliminar === null || !in_array($horaEliminar, $horas_disponibles, true)) {
            $error_message = 'La hora seleccionada no existe.';
        } elseif (count($horas_disponibles) <= 2) {
            $error_message = 'Debe quedar al menos 2 horas disponibles.';
        } else {
            $horas_disponibles = array_values(array_filter(
                $horas_disponibles,
                static fn (string $h): bool => $h !== $horaEliminar
            ));
            $horas_disponibles = $ordenarYLimpiarHoras($horas_disponibles);
            $guardarHorasDisponiblesConfig($horas_disponibles);
            $success_message = 'Hora eliminada correctamente.';
        }
    } catch (\Throwable $e) {
        $error_message = 'No se pudo eliminar la hora. Intente nuevamente.';
        error_log('Error eliminando hora disponible: ' . $e->getMessage());
    }
} elseif (isset($_POST['guardar_horario'])) {
    try {
        $aulaCre = filter_input(INPUT_POST, 'aula', FILTER_DEFAULT);
        $profCre = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
        $resultado = $horariosController->crear([
            'curso_id' => (string) (filter_input(INPUT_POST, 'curso_id', FILTER_DEFAULT) ?? ''),
            'materia_id' => (string) (filter_input(INPUT_POST, 'materia_id', FILTER_DEFAULT) ?? ''),
            'profesor_id' => ($profCre !== false && $profCre > 0) ? (string) $profCre : null,
            'grupo_taller' => (string) (filter_input(INPUT_POST, 'grupo_taller', FILTER_DEFAULT) ?? ''),
            'dia_semana' => (string) (filter_input(INPUT_POST, 'dia_semana', FILTER_DEFAULT) ?? ''),
            'hora_inicio' => (string) (filter_input(INPUT_POST, 'hora_inicio', FILTER_DEFAULT) ?? ''),
            'hora_fin' => (string) (filter_input(INPUT_POST, 'hora_fin', FILTER_DEFAULT) ?? ''),
            'aula' => ($aulaCre !== null && trim((string) $aulaCre) !== '') ? trim((string) $aulaCre) : null,
        ]);

        if ($resultado['success']) {
            $success_message = $resultado['message'] ?? 'Horario creado exitosamente';
            $action = '';
        } else {
            if (isset($resultado['errors']) && is_array($resultado['errors'])) {
                $lineas = array_map(static function ($x): string {
                    return str_replace(["\r\n", "\r", "\n"], ' ', (string) $x);
                }, $resultado['errors']);
                $error_message = "Por favor corrija los siguientes errores:\n• " . implode("\n• ", $lineas);
            } else {
                $error_message = $resultado['error'] ?? 'Error al crear horario. Por favor, intente nuevamente.';
            }
        }
    } catch (\Throwable $e) {
        error_log('Error al crear horario: ' . $e->getMessage());
        $error_message = 'Error al crear horario: ' . $e->getMessage();
    }
} elseif (isset($_POST['actualizar_horario'])) {
    try {
        $hid = filter_input(INPUT_POST, 'horario_id', FILTER_VALIDATE_INT);
        $horarioIdPost = ($hid !== false && $hid > 0) ? $hid : 0;
        $aulaAct = filter_input(INPUT_POST, 'aula', FILTER_DEFAULT);
        $profAct = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
        $resultado = $horariosController->actualizar($horarioIdPost, [
            'curso_id' => (string) (filter_input(INPUT_POST, 'curso_id', FILTER_DEFAULT) ?? ''),
            'materia_id' => (string) (filter_input(INPUT_POST, 'materia_id', FILTER_DEFAULT) ?? ''),
            'profesor_id' => ($profAct !== false && $profAct > 0) ? (string) $profAct : null,
            'grupo_taller' => (string) (filter_input(INPUT_POST, 'grupo_taller', FILTER_DEFAULT) ?? ''),
            'dia_semana' => (string) (filter_input(INPUT_POST, 'dia_semana', FILTER_DEFAULT) ?? ''),
            'hora_inicio' => (string) (filter_input(INPUT_POST, 'hora_inicio', FILTER_DEFAULT) ?? ''),
            'hora_fin' => (string) (filter_input(INPUT_POST, 'hora_fin', FILTER_DEFAULT) ?? ''),
            'aula' => ($aulaAct !== null && trim((string) $aulaAct) !== '') ? trim((string) $aulaAct) : null,
        ]);

        if ($resultado['success']) {
            $success_message = $resultado['message'] ?? 'Horario actualizado exitosamente';
            $action = '';
        } else {
            if (isset($resultado['errors']) && is_array($resultado['errors'])) {
                $lineas = array_map(static function ($x): string {
                    return str_replace(["\r\n", "\r", "\n"], ' ', (string) $x);
                }, $resultado['errors']);
                $error_message = "Por favor corrija los siguientes errores:\n• " . implode("\n• ", $lineas);
            } else {
                $error_message = $resultado['error'] ?? 'Error al actualizar horario. Por favor, intente nuevamente.';
            }
        }
    } catch (\Throwable $e) {
        error_log('Error al actualizar horario: ' . $e->getMessage());
        $error_message = 'Error al actualizar horario: ' . $e->getMessage();
    }
} elseif (isset($_POST['eliminar_horario'])) {
    try {
        $eid = filter_input(INPUT_POST, 'horario_id', FILTER_VALIDATE_INT);
        $resultado = $horariosController->eliminar(($eid !== false && $eid > 0) ? $eid : 0);

        if ($resultado['success']) {
            $success_message = $resultado['message'] ?? 'Horario eliminado';
        } else {
            $error_message = $resultado['error'] ?? 'No se pudo eliminar el horario.';
        }
    } catch (\Throwable $e) {
        error_log('Error al eliminar horario: ' . $e->getMessage());
        $error_message = 'Error al eliminar el horario: ' . $e->getMessage();
    }
}

$horario_editar = null;
$idEditar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($action === 'editar' && $idEditar !== false && $idEditar > 0) {
    $resultado = $horariosController->obtener($idEditar);
    if ($resultado['success']) {
        $horario_editar = $resultado['data'];
        if ($es_profesor_hor) {
            $pidEd = profesor_id_sesion();
            if ($pidEd === null
                || (int) ($horario_editar['profesor_id'] ?? 0) !== $pidEd
                || !profesor_puede_ver_curso((int) ($horario_editar['curso_id'] ?? 0))) {
                $horario_editar = null;
                $error_message = 'No tiene acceso a ese horario.';
                $action = '';
            }
        } elseif ($preceptor_cids !== [] && !preceptor_permitido_curso((int) ($horario_editar['curso_id'] ?? 0))) {
            $horario_editar = null;
            $error_message = 'No tiene acceso a ese horario.';
            $action = '';
        }
    } else {
        $error_message = $resultado['error'];
        $action = '';
    }
}

$vistaHorarios = $horariosController->datosVistaGestion($curso_filter, $profesor_filter, $alcance_cursos_horarios);
extract($vistaHorarios, EXTR_SKIP);

$GLOBALS['extra_css'] = '<link rel="stylesheet" href="css/horarios.css">' . "\n";

sistema_admin_send_html_security_headers();
include 'includes/header.php';
?>

<section class="horarios-section">
    <?php if ($es_profesor_hor): ?>
    <div class="alert alert-info horarios-alert-preceptor">
        <i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.vista_docente_solo_sus_horarios_en_los_curso'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php elseif ($preceptor_cids !== []): ?>
    <div class="alert alert-info horarios-alert-preceptor">
        <i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.vista_de_preceptor_solo_horarios_de_sus_curs'), ENT_QUOTES, 'UTF-8'); ?><strong><?php echo htmlspecialchars(preceptor_curso_etiqueta()); ?></strong>).
    </div>
    <?php endif; ?>
    <div class="section-header">
        <h2><?php echo htmlspecialchars(__('schedules.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
        <div class="header-actions">
            <button
                type="button"
                id="btn-toggle-bloques-horarios"
                class="btn btn-secondary"
                aria-controls="horarios-bloques-card"
                aria-expanded="<?php echo $accionBloquesMensaje ? 'true' : 'false'; ?>"
            >
                <i class="fas fa-sliders-h"></i> <?php echo htmlspecialchars(__('schedules.configure_blocks'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <a href="schedules.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('schedules.new_schedule'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo nl2br(htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8')); ?>
        </div>
    <?php endif; ?>

    <?php if (!$formularios_ok): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('auto.formulario_de_datos_no_disponible'), ENT_QUOTES, 'UTF-8'); ?><?php if ($debug_error_formulario): ?>
                <br><strong style="color: #dc2626;"><?php echo htmlspecialchars(__('auto.error'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars($debug_error_formulario); ?></strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
    <div class="card horarios-bloques-card<?php echo $accionBloquesMensaje ? '' : ' is-collapsed'; ?>" id="horarios-bloques-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock"></i> <?php echo htmlspecialchars(__('schedules.configure_blocks_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="card-body horarios-bloques-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(__('schedules.th_current_time'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('schedules.th_modify'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(__('schedules.th_remove'), ENT_QUOTES, 'UTF-8'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas_disponibles as $horaDisponible): ?>
                        <tr>
                            <td><strong class="horarios-bloques-hora-pill"><?php echo htmlspecialchars($horaDisponible, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td>
                                <form method="POST" class="horarios-inline-form horarios-bloques-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="hora_original" value="<?php echo htmlspecialchars($horaDisponible, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="time" name="hora_nueva" value="<?php echo htmlspecialchars($horaDisponible, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <button type="submit" name="actualizar_hora_disponible" class="btn btn-sm btn-warning">
                                        <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" class="horarios-inline-form js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars('¿Eliminar esta hora de la configuración?', ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="hora_eliminar" value="<?php echo htmlspecialchars($horaDisponible, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" name="eliminar_hora_disponible" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> <?php echo htmlspecialchars(__('action.delete'), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form method="POST" class="form-container horarios-bloques-add-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nueva_hora"><?php echo htmlspecialchars(__('schedules.add_time_block'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="time" id="nueva_hora" name="nueva_hora" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="agregar_hora_disponible" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('schedules.add_time_block'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (filter_input(INPUT_GET, 'error', FILTER_DEFAULT) === 'unauthorized'): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars(__('auto.no_tienes_permisos_para_crear_horarios'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_horarios, 0, ',', '.'); ?></h3>
                <p><?php echo htmlspecialchars(__('schedules.total_schedules'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        
        
    </div>

    <!-- Formulario nuevo horario -->
    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('schedules.register_new'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="POST" class="form-container">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="curso_id"><?php echo htmlspecialchars(__('courses.course'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="curso_id" id="curso_id" required>
                        <option value=""><?php echo htmlspecialchars(__('courses.select_course'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo (int) $curso['id']; ?>">
                            <?php echo HorariosController::etiquetaCursoFormularioLargo($curso); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="materia_id"><?php echo htmlspecialchars(__('subjects.subject'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="materia_id" id="materia_id" required>
                        <option value=""><?php echo htmlspecialchars(__('subjects.select_subject'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($materias as $materia): ?>
                        <option value="<?php echo $materia['id']; ?>">
                            <?php echo htmlspecialchars($materia['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="grupo_taller_container" style="display: none;">
                    <label for="grupo_taller"><?php echo htmlspecialchars(__('auto.grupo_de_taller'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="grupo_taller" id="grupo_taller">
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="A"><?php echo htmlspecialchars(__('auto.grupo_a'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="B"><?php echo htmlspecialchars(__('auto.grupo_b'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="C"><?php echo htmlspecialchars(__('auto.grupo_c'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="D"><?php echo htmlspecialchars(__('auto.grupo_d'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="E"><?php echo htmlspecialchars(__('auto.grupo_e'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dia_semana"><?php echo htmlspecialchars(__('schedules.select_day'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="dia_semana" id="dia_semana" required>
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_day'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($dias_semana as $valor => $nombre): ?>
                        <option value="<?php echo $valor; ?>"><?php echo $nombre; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="hora_inicio"><?php echo htmlspecialchars(__('schedules.start_time'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="hora_inicio" id="hora_inicio" required>
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_time'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($horas_disponibles as $hora): ?>
                        <option value="<?php echo $hora; ?>"><?php echo $hora; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="hora_fin"><?php echo htmlspecialchars(__('schedules.end_time'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="hora_fin" id="hora_fin" required>
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_time'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($horas_disponibles as $hora): ?>
                        <option value="<?php echo $hora; ?>"><?php echo $hora; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="aula"><?php echo htmlspecialchars(__('schedules.classroom'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" name="aula" id="aula" maxlength="50" placeholder="<?php echo htmlspecialchars(__('schedules.classroom_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="profesor_id"><?php echo htmlspecialchars(__('teachers.teacher'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="profesor_id" id="profesor_id">
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_teacher_hint'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="guardar_horario" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('schedules.save_schedule'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="schedules.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Formulario editar horario -->
    <?php if ($action === 'editar' && $horario_editar): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('auto.editar_horario'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="POST" class="form-container">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="horario_id" value="<?php echo (int) $horario_editar['id']; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="curso_id_edit"><?php echo htmlspecialchars(__('courses.course'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="curso_id" id="curso_id_edit" required>
                        <option value=""><?php echo htmlspecialchars(__('courses.select_course'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo (int) $curso['id']; ?>"
                                <?php echo (int) ($horario_editar['curso_id'] ?? 0) === (int) $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo HorariosController::etiquetaCursoFormularioLargo($curso); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="materia_id_edit"><?php echo htmlspecialchars(__('subjects.subject'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="materia_id" id="materia_id_edit" required>
                        <option value=""><?php echo htmlspecialchars(__('subjects.select_subject'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($materias as $materia): ?>
                        <option value="<?php echo $materia['id']; ?>" 
                                <?php echo $horario_editar['materia_id'] == $materia['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($materia['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="grupo_taller_container_edit" style="display: none;">
                    <label for="grupo_taller_edit"><?php echo htmlspecialchars(__('auto.grupo_de_taller'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="grupo_taller" id="grupo_taller_edit">
                        <option value=""><?php echo htmlspecialchars(__('students.select_option'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="A" <?php echo ($horario_editar['grupo_taller'] ?? '') === 'A' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_a'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="B" <?php echo ($horario_editar['grupo_taller'] ?? '') === 'B' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_b'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="C" <?php echo ($horario_editar['grupo_taller'] ?? '') === 'C' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_c'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="D" <?php echo ($horario_editar['grupo_taller'] ?? '') === 'D' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_d'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="E" <?php echo ($horario_editar['grupo_taller'] ?? '') === 'E' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.grupo_e'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dia_semana_edit"><?php echo htmlspecialchars(__('schedules.select_day'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="dia_semana" id="dia_semana_edit" required>
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_day'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($dias_semana as $valor => $nombre): ?>
                        <option value="<?php echo $valor; ?>" 
                                <?php echo $horario_editar['dia_semana'] == $valor ? 'selected' : ''; ?>>
                            <?php echo $nombre; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="hora_inicio_edit"><?php echo htmlspecialchars(__('schedules.start_time'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="hora_inicio" id="hora_inicio_edit" required>
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_time'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php
                        $hora_inicio_edit_norm = HorariosController::normalizarHoraOpcion(isset($horario_editar['hora_inicio']) ? (string) $horario_editar['hora_inicio'] : null);
                        foreach ($horas_disponibles as $hora): ?>
                        <option value="<?php echo $hora; ?>" <?php echo $hora_inicio_edit_norm === $hora ? 'selected' : ''; ?>>
                            <?php echo $hora; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="hora_fin_edit"><?php echo htmlspecialchars(__('schedules.end_time'), ENT_QUOTES, 'UTF-8'); ?>: *</label>
                    <select name="hora_fin" id="hora_fin_edit" required>
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_time'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php
                        $hora_fin_edit_norm = HorariosController::normalizarHoraOpcion(isset($horario_editar['hora_fin']) ? (string) $horario_editar['hora_fin'] : null);
                        foreach ($horas_disponibles as $hora): ?>
                        <option value="<?php echo $hora; ?>" <?php echo $hora_fin_edit_norm === $hora ? 'selected' : ''; ?>>
                            <?php echo $hora; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="aula_edit"><?php echo htmlspecialchars(__('schedules.classroom'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <input type="text" name="aula" id="aula_edit" maxlength="50" 
                           value="<?php echo htmlspecialchars($horario_editar['aula'] ?? ''); ?>" 
                           placeholder="<?php echo htmlspecialchars(__('schedules.classroom_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="profesor_id_edit"><?php echo htmlspecialchars(__('teachers.teacher'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="profesor_id" id="profesor_id_edit">
                        <option value=""><?php echo htmlspecialchars(__('schedules.select_teacher_hint'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php 
                        if ($horario_editar['materia_id'] && $horario_editar['curso_id']) {
                            $mid = (int) $horario_editar['materia_id'];
                            $cid = (int) $horario_editar['curso_id'];
                            $profesores_materia = $servicioHorarios->listarDocentesParaHorario($mid, $cid);
                            
                            foreach ($profesores_materia as $profesor) {
                                $selected = ($horario_editar['profesor_id'] == $profesor['id']) ? 'selected' : '';
                                echo '<option value="' . $profesor['id'] . '" ' . $selected . '>';
                                echo htmlspecialchars($profesor['apellido'] . ' ' . $profesor['nombre']);
                                echo '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="actualizar_horario" class="btn btn-primary">
                    <i class="fas fa-save"></i><?php echo htmlspecialchars(__('auto.actualizar_horario'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="schedules.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('students.search_filters'), ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <form method="GET" class="form-container" id="horarios-filtros-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="curso"><?php echo htmlspecialchars(__('courses.course'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="curso" id="curso">
                        <option value=""><?php echo htmlspecialchars(__('courses.all_courses'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo (int) $curso['id']; ?>"
                                <?php echo $curso_filter !== '' && $curso_filter === (string) (int) $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo HorariosController::etiquetaCursoFiltro($curso); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="profesor"><?php echo htmlspecialchars(__('teachers.teacher'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                    <select name="profesor" id="profesor">
                        <option value=""><?php echo htmlspecialchars(__('teachers.all_teachers'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($profesores_filtro as $profesor): ?>
                        <?php
                            $profesorIdFiltro = (string) ((int) ($profesor['id'] ?? 0));
                            $docenteFiltro = trim((string) (($profesor['apellido'] ?? '') . ' ' . ($profesor['nombre'] ?? '')));
                            if ($profesorIdFiltro === '0' || $docenteFiltro === '') {
                                continue;
                            } 
                        ?>
                        <option value="<?php echo htmlspecialchars($profesorIdFiltro, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $profesor_filter === $profesorIdFiltro ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($docenteFiltro, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo htmlspecialchars(__('students.btn_search'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="schedules.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('students.btn_clear'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>

    <!-- Vista de horarios -->
    <?php if (!empty($horarios)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars(__('auto.horarios_registrados'), ENT_QUOTES, 'UTF-8'); ?><?php echo number_format($total_horarios, 0, ',', '.'); ?>)</h3>
        </div>

        <div class="card-body">
            <?php $cursoIndex = 0; ?>
            <?php foreach ($vista_semanal_por_curso as $bloque): ?>
            <details class="curso-horarios-container" <?php echo ($curso_filter !== '') ? 'open' : ''; ?>>
                <summary class="curso-header">
                    <h4>
                        <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($bloque['titulo_curso'], ENT_QUOTES, 'UTF-8'); ?>
                        <span class="status status-primary">
                            <?php echo htmlspecialchars($bloque['turno_etiqueta'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </h4>
                </summary>

                <div class="horarios-grid-layout">
                    <!-- Sección 1: Horario General (Materias Comunes) -->
                    <div class="horarios-sub-section">
                        <h5 class="horarios-sub-title">
                            <i class="fas fa-calendar-alt"></i><?php echo htmlspecialchars(__('auto.horario_general'), ENT_QUOTES, 'UTF-8'); ?></h5>

                        <?php if (empty($bloque['filas'])): ?>
                            <div class="horarios-no-data">
                                <i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('auto.no_hay_horarios_de_materias_comunes_registrad'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php else: ?>
                            <div class="horario-semanal">
                                <table class="table horario-semanal-table">
                                    <thead>
                                        <tr>
                                            <th class="horario-semanal-th-hora"><?php echo htmlspecialchars(__('auto.horario'), ENT_QUOTES, 'UTF-8'); ?></th>
                                            <?php foreach ($dias_semana as $dia_etiqueta): ?>
                                            <th><?php echo htmlspecialchars($dia_etiqueta, ENT_QUOTES, 'UTF-8'); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bloque['filas'] as $fila): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fila['hora_inicio_corta'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            </td>
                                            <?php foreach ($dias_semana as $dia_num => $dia_nombre): ?>
                                            <td>
                                                <?php
                                                $h = $fila['por_dia'][$dia_num] ?? null;
                                                if ($h === null) {
                                                    echo '';
                                                } else {
                                                ?>
                                                    <div class="materia-cell">
                                                        <div class="horario-bloque-tramo">
                                                            <div class="horario-tramo-inicio">
                                                                <div class="horario-tramo-linea">
                                                                    <span class="horario-tramo-hora"><?php echo date('H:i', strtotime((string) ($h['hora_inicio'] ?? ''))); ?></span>
                                                                    <strong class="horario-tramo-materia"><?php echo htmlspecialchars((string) ($h['materia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                                </div>
                                                                <?php if (!empty($h['es_contraturno'])): ?>
                                                                    <span class="status status-danger status-chip-sm"><?php echo htmlspecialchars(__('auto.contraturno'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                <?php endif; ?>
                                                                <?php if (!empty($h['aula'])): ?>
                                                                    <div class="horario-tramo-meta"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars((string) $h['aula'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($h['docente'])): ?>
                                                                    <div class="horario-tramo-meta"><i class="fas fa-user"></i> <?php echo htmlspecialchars((string) $h['docente'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                    <?php if (($h['suplencia_estado'] ?? '') === 'activa'): ?>
                                                                        <?php if (!empty($h['fuera_servicio'])): ?>
                                                                            <div class="horario-tramo-meta"><span class="status status-danger status-chip-xs"><i class="fas fa-ban"></i><?php echo htmlspecialchars(__('auto.fuera_de_servicio'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                                        <?php else: ?>
                                                                            <div class="horario-tramo-meta"><span class="status status-warning status-chip-xs"><i class="fas fa-user-clock"></i><?php echo htmlspecialchars(__('auto.suplente'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars(trim((string) (($h['suplente_apellido'] ?? '') . ' ' . ($h['suplente_nombre'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <div class="horario-tramo-meta"><span class="status status-danger status-chip-xs"><?php echo htmlspecialchars(__('auto.sin_profesor'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="horario-tramo-sep" aria-hidden="true"></div>
                                                            <div class="horario-tramo-fin">
                                                                <div class="horario-tramo-linea">
                                                                    <span class="horario-tramo-hora horario-tramo-hora-fin"><?php echo date('H:i', strtotime((string) ($h['hora_fin'] ?? ''))); ?></span>
                                                                    <span class="horario-tramo-materia-fin"><?php echo htmlspecialchars((string) ($h['materia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                </div>
                                                                <span class="horario-tramo-fin-label"><?php echo htmlspecialchars(__('auto.fin_del_m_dulo'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                            </div>
                                                        </div>

                                                        <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
                                                        <div class="horario-tramo-acciones">
                                                            <a href="schedules.php?action=editar&amp;id=<?php echo (int) $h['id']; ?>"
                                                               class="btn btn-xs btn-warning horario-btn-accion" title="Editar horario">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="horarios-inline-form js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars('¿Está seguro de que desea eliminar este horario?', ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="horario_id" value="<?php echo (int) $h['id']; ?>">
                                                                <button type="submit" name="eliminar_horario" class="btn btn-xs btn-danger horario-btn-accion" title="Eliminar horario">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php } ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sección 2: Horarios de Taller por Grupo -->
                    <?php if (!empty($bloque['grupos_taller'])): ?>
                    <div class="horarios-sub-section horarios-taller-section">
                        <h5 class="horarios-sub-title">
                            <i class="fas fa-tools"></i><?php echo htmlspecialchars(__('auto.horario_de_taller'), ENT_QUOTES, 'UTF-8'); ?></h5>

                        <div class="horarios-tabs-nav">
                            <?php $isFirstTab = true; ?>
                            <?php foreach ($bloque['grupos_taller'] as $grupo => $filasTaller): ?>
                                <button type="button"
                                        class="tab-nav-btn <?php echo $isFirstTab ? 'active' : ''; ?>"
                                        data-tab-target="taller-<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?>-<?php echo $cursoIndex; ?>"><?php echo htmlspecialchars(__('auto.grupo'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?>
                                </button>
                            <?php $isFirstTab = false; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="horarios-tabs-panels">
                            <?php $isFirstPanel = true; ?>
                            <?php foreach ($bloque['grupos_taller'] as $grupo => $filasTaller): ?>
                            <div class="tab-panel <?php echo $isFirstPanel ? 'active' : ''; ?>"
                                 id="taller-<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?>-<?php echo $cursoIndex; ?>">
                                <div class="horario-semanal">
                                    <table class="table horario-semanal-table">
                                        <thead>
                                            <tr>
                                                <th class="horario-semanal-th-hora">Horario</th>
                                                <?php foreach ($dias_semana as $dia_etiqueta): ?>
                                                <th><?php echo htmlspecialchars($dia_etiqueta, ENT_QUOTES, 'UTF-8'); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($filasTaller as $fila): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($fila['hora_inicio_corta'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                </td>
                                                <?php foreach ($dias_semana as $dia_num => $dia_nombre): ?>
                                                <td>
                                                    <?php
                                                    $h = $fila['por_dia'][$dia_num] ?? null;
                                                    if ($h === null) {
                                                        echo '';
                                                    } else {
                                                    ?>
                                                        <div class="materia-cell">
                                                            <div class="horario-bloque-tramo">
                                                                <div class="horario-tramo-inicio">
                                                                    <div class="horario-tramo-linea">
                                                                        <span class="horario-tramo-hora"><?php echo date('H:i', strtotime((string) ($h['hora_inicio'] ?? ''))); ?></span>
                                                                        <strong class="horario-tramo-materia"><?php echo htmlspecialchars((string) ($h['materia'] ?? ''), ENT_QUOTES, 'UTF-8') . ' - Grupo ' . htmlspecialchars((string) ($h['grupo_taller'] ?? $grupo), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                                    </div>
                                                                    <?php if (!empty($h['es_contraturno'])): ?>
                                                                        <span class="status status-danger status-chip-sm">Contraturno</span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($h['aula'])): ?>
                                                                        <div class="horario-tramo-meta"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars((string) $h['aula'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($h['docente'])): ?>
                                                                        <div class="horario-tramo-meta"><i class="fas fa-user"></i> <?php echo htmlspecialchars((string) $h['docente'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                                        <?php if (($h['suplencia_estado'] ?? '') === 'activa'): ?>
                                                                            <?php if (!empty($h['fuera_servicio'])): ?>
                                                                                <div class="horario-tramo-meta"><span class="status status-danger status-chip-xs"><i class="fas fa-ban"></i><?php echo htmlspecialchars(__('auto.fuera_de_servicio'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                                            <?php else: ?>
                                                                                <div class="horario-tramo-meta"><span class="status status-warning status-chip-xs"><i class="fas fa-user-clock"></i><?php echo htmlspecialchars(__('auto.suplente'), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars(trim((string) (($h['suplente_apellido'] ?? '') . ' ' . ($h['suplente_nombre'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <div class="horario-tramo-meta"><span class="status status-danger status-chip-xs"><?php echo htmlspecialchars(__('auto.sin_profesor'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="horario-tramo-sep" aria-hidden="true"></div>
                                                                <div class="horario-tramo-fin">
                                                                    <div class="horario-tramo-linea">
                                                                        <span class="horario-tramo-hora horario-tramo-hora-fin"><?php echo date('H:i', strtotime((string) ($h['hora_fin'] ?? ''))); ?></span>
                                                                        <span class="horario-tramo-materia-fin"><?php echo htmlspecialchars((string) ($h['materia'] ?? ''), ENT_QUOTES, 'UTF-8') . ' - Grupo ' . htmlspecialchars((string) ($h['grupo_taller'] ?? $grupo), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                    </div>
                                                                    <span class="horario-tramo-fin-label"><?php echo htmlspecialchars(__('auto.fin_del_m_dulo'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                </div>
                                                            </div>

                                                            <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
                                                            <div class="horario-tramo-acciones">
                                                                <a href="schedules.php?action=editar&amp;id=<?php echo (int) $h['id']; ?>"
                                                                   class="btn btn-xs btn-warning horario-btn-accion" title="Editar horario">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form method="POST" class="horarios-inline-form js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars('¿Está seguro de que desea eliminar este horario?', ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <input type="hidden" name="horario_id" value="<?php echo (int) $h['id']; ?>">
                                                                    <button type="submit" name="eliminar_horario" class="btn btn-xs btn-danger horario-btn-accion" title="Eliminar horario">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php } ?>
                                                </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php $isFirstPanel = false; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </details>
            <?php $cursoIndex++; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Estado vacío -->
    <div class="card">
        <div class="card-body text-center horarios-empty-body">
            <i class="fas fa-clock horarios-empty-icon"></i>
            <h3 class="horarios-empty-title"><?php echo htmlspecialchars(__('schedules.no_schedules_registered'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="horarios-empty-text">
                <?php if ($curso_filter || $profesor_filter): ?><?php echo htmlspecialchars(__('courses.no_courses_found'), ENT_QUOTES, 'UTF-8'); ?><?php else: ?><?php echo htmlspecialchars(__('schedules.start_registering'), ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </p>
            <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
            <a href="schedules.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i><?php echo htmlspecialchars(__('auto.registrar_primer_horario'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php
$horariosNonce = htmlspecialchars((string) ($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8');
$horariosPageConfig = [
    'csrfToken' => $csrfToken,
    'materiaEditarId' => isset($horario_editar['materia_id']) ? (string) $horario_editar['materia_id'] : '',
    'profesorEditarId' => isset($horario_editar['profesor_id']) ? (string) $horario_editar['profesor_id'] : '',
    'allTeachersLabel' => __('teachers.all_teachers'),
    'selectTeacherHint' => __('schedules.select_teacher_hint'),
    'selectSubjectLabel' => __('subjects.select_subject'),
    'profesoresPorCurso' => $profesores_por_curso,
    'profesoresFiltroOpciones' => array_map(static function (array $p): array {
        return [
            'id' => (int) ($p['id'] ?? 0),
            'label' => trim((string) (($p['apellido'] ?? '') . ' ' . ($p['nombre'] ?? ''))),
        ];
    }, $profesores_filtro),
];
$horariosPageJson = json_encode($horariosPageConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($horariosPageJson === false) {
    $horariosPageJson = '{}';
}
$horariosJsVersion = @filemtime(__DIR__ . '/js/horarios.js') ?: time();
?>
<script nonce="<?php echo $horariosNonce; ?>">
window.__HORARIOS_PAGE__ = <?php echo $horariosPageJson; ?>;
window.HORARIOS_I18N = {
    no_teacher_available: <?php echo json_encode(__('schedules.no_teacher_available'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
</script>
<script src="js/horarios.js?v=<?php echo rawurlencode((string) $horariosJsVersion); ?>" defer nonce="<?php echo $horariosNonce; ?>"></script>

<?php include 'includes/footer.php'; ?>
