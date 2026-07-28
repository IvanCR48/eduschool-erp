<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';
require_once __DIR__ . '/../includes/csrf_functions.php';

use SistemaAdmin\Controllers\SchoolYearMilestoneController;
use SistemaAdmin\Services\NotasSubjectGradesPayloadBuilder;
use SistemaAdmin\Services\ServicioAutenticacion;

$databaseAdapter = sistema_admin_db_adapter();
$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$usuario = $servicioAutenticacion->verificarSesion();

if (!$usuario) {
    header('Location: ' . sistema_admin_login_redirect_url());
    exit();
}

require_once __DIR__ . '/../includes/auth_helpers.php';
if (!(hasRole('admin') || hasRole('directivo') || hasRole('preceptor'))) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

$disableFields = !(hasRole('admin') || hasRole('directivo') || hasRole('preceptor'));
$controller = new SchoolYearMilestoneController($databaseAdapter);
$csrfToken = getCSRFToken();
$success_message = '';
$error_message = '';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST'
    && filter_input(INPUT_POST, 'guardar_calendario_escolar', FILTER_DEFAULT) !== null) {
    if ($disableFields) {
        $error_message = 'No tenés permisos para modificar el calendario escolar.';
    } else {
        $tokenPost = (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? '');
        if (!verifyCSRFToken($tokenPost)) {
            $error_message = 'La solicitud no pudo validarse. Actualizá la página e intentá de nuevo.';
        } else {
            $res = $controller->procesarGuardado($_POST);
            if ($res['ok']) {
                $success_message = $res['message'];
            } else {
                $error_message = $res['message'];
            }
        }
    }
}

$milestones = $controller->listarMilestones();
$anioLectivoInferido = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());

$pageTitle = __('calendar.title') . ' — ' . \SistemaAdmin\Bootstrap\AppRequestInit::systemName();
$currentPage = 'calendario_escolar.php';
$GLOBALS['css_path'] = '../css/style.css';
$calendarioEscolarCssVersion = @filemtime(__DIR__ . '/../css/calendario_escolar.css') ?: time();
$GLOBALS['extra_css'] = '<link rel="stylesheet" href="../css/calendario_escolar.css?v='
    . htmlspecialchars((string) $calendarioEscolarCssVersion, ENT_QUOTES, 'UTF-8') . '">' . "\n";

sistema_admin_send_html_security_headers();
include __DIR__ . '/../includes/header.php';
?>

<section class="calendario-escolar">
    <header class="calendario-escolar__hero">
        <h2><i class="fas fa-calendar-alt" aria-hidden="true"></i> <?php echo htmlspecialchars(__('calendar.title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="calendario-escolar__lead">
            <?php echo htmlspecialchars(__('calendar.closure_notice'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </header>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($disableFields): ?>
        <div class="alert alert-info" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('calendar.view_only_notice'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="calendario-escolar__body-layout">
        <!-- Columna Izquierda: Información de ciclo y formulario de guardado -->
        <div class="calendario-escolar__left-col">
            <div class="card calendario-escolar__card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo htmlspecialchars(__('calendar.current_school_year'), ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
                <div class="card-body">
                    <p class="calendario-escolar__callout">
                        <span><?php echo htmlspecialchars(__('calendar.rule_explanation'), ENT_QUOTES, 'UTF-8'); ?> <strong>marzo–febrero</strong></span>
                        <span class="calendario-escolar__callout-year"><?php echo (int) $anioLectivoInferido; ?></span>
                    </p>
                </div>
            </div>

            <div class="card calendario-escolar__card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit" aria-hidden="true"></i> <?php echo htmlspecialchars(__('calendar.save_milestone'), ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
                <div class="card-body">
                    <form method="post" class="form-container calendario-escolar__form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="school_year"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars(__('calendar.school_year'), ENT_QUOTES, 'UTF-8'); ?> *</label>
                                <input type="number" name="school_year" id="school_year" required min="2000" max="2100"
                                       value="<?php echo (int) $anioLectivoInferido; ?>"
                                       title="Año de inicio del ciclo"
                                       <?php echo $disableFields ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label for="february_march_closure_date"><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars(__('calendar.closure_date'), ENT_QUOTES, 'UTF-8'); ?> *</label>
                                <input type="date" name="february_march_closure_date" id="february_march_closure_date" required
                                       <?php echo $disableFields ? 'disabled' : ''; ?>
                                       value="<?php
                                       $defaultCierre = '';
                                       foreach ($milestones as $m) {
                                           if ((int) ($m['school_year'] ?? 0) === (int) $anioLectivoInferido) {
                                               $defaultCierre = (string) ($m['february_march_closure_date'] ?? '');
                                               break;
                                           }
                                       }
                                       echo htmlspecialchars($defaultCierre, ENT_QUOTES, 'UTF-8');
                                       ?>">
                            </div>
                        </div>

                        <!-- Panel de Corrección de Notas -->
                        <div class="grade-correction-panel">
                            <h4><i class="fas fa-user-lock"></i> <?php echo htmlspecialchars(__('calendar.grade_correction_period'), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p><?php echo htmlspecialchars(__('calendar.grade_correction_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                            
                            <div class="form-row" style="grid-template-columns: 1fr; margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label for="grade_correction_enabled"><?php echo htmlspecialchars(__('calendar.manual_modifications'), ENT_QUOTES, 'UTF-8'); ?></label>
                                    <select name="grade_correction_enabled" id="grade_correction_enabled" <?php echo $disableFields ? 'disabled' : ''; ?>>
                                        <?php
                                        $defaultEnabled = 0;
                                        foreach ($milestones as $m) {
                                            if ((int) ($m['school_year'] ?? 0) === (int) $anioLectivoInferido) {
                                                $defaultEnabled = (int) ($m['grade_correction_enabled'] ?? 0);
                                                break;
                                            }
                                        }
                                        ?>
                                        <option value="0" <?php echo $defaultEnabled === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('calendar.follow_scheduled_period'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <option value="1" <?php echo $defaultEnabled === 1 ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('calendar.force_open'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="grade_correction_start_date"><?php echo htmlspecialchars(__('calendar.start_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                                    <input type="date" name="grade_correction_start_date" id="grade_correction_start_date"
                                           <?php echo $disableFields ? 'disabled' : ''; ?>
                                           value="<?php
                                           $defaultStart = '';
                                           foreach ($milestones as $m) {
                                               if ((int) ($m['school_year'] ?? 0) === (int) $anioLectivoInferido) {
                                                   $defaultStart = (string) ($m['grade_correction_start_date'] ?? '');
                                                   break;
                                               }
                                           }
                                           echo htmlspecialchars($defaultStart, ENT_QUOTES, 'UTF-8');
                                           ?>">
                                </div>
                                <div class="form-group">
                                    <label for="grade_correction_end_date"><?php echo htmlspecialchars(__('calendar.end_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                                    <input type="date" name="grade_correction_end_date" id="grade_correction_end_date"
                                           <?php echo $disableFields ? 'disabled' : ''; ?>
                                           value="<?php
                                           $defaultEnd = '';
                                           foreach ($milestones as $m) {
                                               if ((int) ($m['school_year'] ?? 0) === (int) $anioLectivoInferido) {
                                                   $defaultEnd = (string) ($m['grade_correction_end_date'] ?? '');
                                                   break;
                                               }
                                           }
                                           echo htmlspecialchars($defaultEnd, ENT_QUOTES, 'UTF-8');
                                           ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-row" style="grid-template-columns: 1fr;">
                            <div class="form-group">
                                <label for="notes"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars(__('calendar.internal_notes'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="text" name="notes" id="notes" maxlength="255" placeholder="<?php echo htmlspecialchars(__('calendar.notes_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                                       <?php echo $disableFields ? 'disabled' : ''; ?>
                                       value="<?php
                                       $defaultNotes = '';
                                       foreach ($milestones as $m) {
                                           if ((int) ($m['school_year'] ?? 0) === (int) $anioLectivoInferido) {
                                               $defaultNotes = (string) ($m['notes'] ?? '');
                                               break;
                                           }
                                       }
                                       echo htmlspecialchars($defaultNotes, ENT_QUOTES, 'UTF-8');
                                       ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <?php if (!$disableFields): ?>
                            <button type="submit" name="guardar_calendario_escolar" value="1" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php endif; ?>
                            <a href="../index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(__('action.back'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Tarjetas de Años Recientes y Tabla de Cierres -->
        <div class="calendario-escolar__right-col">
            
            <!-- Tarjetas rápidas (Años configurados) -->
            <?php if ($milestones !== []): 
                $recentMilestones = array_slice($milestones, 0, 4);
            ?>
            <div class="school-year-cards-grid">
                <?php foreach ($recentMilestones as $rm):
                    $isActive = ((int) ($rm['school_year'] ?? 0) === (int) $anioLectivoInferido);
                    $isOpen = ((int) ($rm['grade_correction_enabled'] ?? 0) === 1);
                ?>
                <div class="school-year-card <?php echo $isActive ? 'is-active' : ''; ?>" data-year="<?php echo (int) $rm['school_year']; ?>">
                    <div>
                        <span class="school-year-card__title"><?php echo htmlspecialchars(__('calendar.school_year'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="school-year-card__value"><?php echo (int) $rm['school_year']; ?></span>
                    </div>
                    <span class="school-year-card__status <?php echo $isOpen ? 'school-year-card__status--open' : 'school-year-card__status--closed'; ?>">
                        <?php echo $isOpen ? htmlspecialchars(__('calendar.open'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('calendar.scheduled'), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card calendario-escolar__card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list" aria-hidden="true"></i><?php echo htmlspecialchars(__('calendar.configured_closures'), ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
                <div class="card-body card-body--flush">
                    <?php if ($milestones === []): ?>
                        <p class="calendario-escolar__empty"><?php echo htmlspecialchars(__('calendar.no_closures_found'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else: ?>
                        <div class="calendario-escolar__table-wrap">
                            <table class="calendario-escolar__table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.school_year'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.closure_feb_mar'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.status'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.start_corr'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.end_corr'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.notes'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars(__('calendar.updated'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($milestones as $m): 
                                        $isOpen = ((int) ($m['grade_correction_enabled'] ?? 0) === 1);
                                    ?>
                                    <tr>
                                        <td class="col-year"><?php echo (int) ($m['school_year'] ?? 0); ?></td>
                                        <td class="col-date"><?php echo htmlspecialchars((string) ($m['february_march_closure_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-status">
                                            <?php if ($isOpen): ?>
                                                <span class="badge-status badge-status--open"><i class="fas fa-lock-open"></i><?php echo htmlspecialchars(__('calendar.open'), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php else: ?>
                                                <span class="badge-status badge-status--closed"><i class="fas fa-lock"></i><?php echo htmlspecialchars(__('calendar.scheduled'), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-start-date"><?php echo $m['grade_correction_start_date'] ? htmlspecialchars((string) $m['grade_correction_start_date'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td class="col-end-date"><?php echo $m['grade_correction_end_date'] ? htmlspecialchars((string) $m['grade_correction_end_date'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td class="col-notes"><?php echo htmlspecialchars((string) ($m['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-updated"><?php echo htmlspecialchars((string) ($m['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <?php if (hasRole('admin')): ?>
    <p class="calendario-escolar__footer-note">
        <i class="fas fa-tools" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars(__('calendar.admin_tools_link_notice'), ENT_QUOTES, 'UTF-8'); ?><a href="admin_tools.php"><?php echo htmlspecialchars(__('admin_tools.title'), ENT_QUOTES, 'UTF-8'); ?></a>.</span>
    </p>
    <?php endif; ?>
</section>

<?php
$milestonesJson = json_encode($milestones);
$milestonesNonce = htmlspecialchars((string) ($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<script nonce="<?php echo $milestonesNonce; ?>">
document.addEventListener('DOMContentLoaded', function() {
    var milestones = <?php echo $milestonesJson; ?>;
    var schoolYearInput = document.getElementById('school_year');
    var closureDateInput = document.getElementById('february_march_closure_date');
    var correctionEnabledInput = document.getElementById('grade_correction_enabled');
    var correctionStartInput = document.getElementById('grade_correction_start_date');
    var correctionEndInput = document.getElementById('grade_correction_end_date');
    var notesInput = document.getElementById('notes');

    schoolYearInput.addEventListener('change', function() {
        var year = parseInt(schoolYearInput.value, 10);
        var found = milestones.find(function(m) {
            return parseInt(m.school_year, 10) === year;
        });

        // Update active class on year cards
        document.querySelectorAll('.school-year-card').forEach(function(card) {
            var cardYear = parseInt(card.getAttribute('data-year'), 10);
            card.classList.toggle('is-active', cardYear === year);
        });

        if (found) {
            closureDateInput.value = found.february_march_closure_date || '';
            correctionEnabledInput.value = found.grade_correction_enabled || '0';
            correctionStartInput.value = found.grade_correction_start_date || '';
            correctionEndInput.value = found.grade_correction_end_date || '';
            notesInput.value = found.notes || '';
        } else {
            closureDateInput.value = '';
            correctionEnabledInput.value = '0';
            correctionStartInput.value = '';
            correctionEndInput.value = '';
            notesInput.value = '';
        }
    });

    // Add click listeners to quick year cards
    document.querySelectorAll('.school-year-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var selectedYear = card.getAttribute('data-year');
            if (selectedYear && schoolYearInput) {
                schoolYearInput.value = selectedYear;
                schoolYearInput.dispatchEvent(new Event('change'));
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
