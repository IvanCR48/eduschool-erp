<?php

use SistemaAdmin\Services\ServicioBoletinNotas;

/**
 * Boletín de notas (tabla o estado vacío). Estructura HTML corregida (un solo .card).
 *
 * Variables: $estudiante, $notas_boletin, $estudiante_id_int
 * Opcional: $puede_cambiar_curso_ficha (bool) — si true, muestra asignar/cambiar curso (staff, no familias ni docentes).
 * Opcional: $intensificaciones_ficha, $school_year_intensif_ficha — calificaciones de intensificación/recuperación (ciclo actual).
 * Opcional: $puede_aprobar_materia_previa_ficha (bool) — staff puede confirmar aprobación de previa sin mesa de examen.
 */
$puedeBoletinCambioCurso = !empty($puede_cambiar_curso_ficha);
$puedeAprobarPreviaStaff = !empty($puede_aprobar_materia_previa_ficha);
$espBoletin = !empty($estudiante['especialidad']) ? (string) $estudiante['especialidad'] : '';
if (!empty($estudiante['anio']) && !empty($estudiante['division'])) {
    $tituloCurso = (int) $estudiante['anio'] . '° ' . htmlspecialchars((string) $estudiante['division'], ENT_QUOTES, 'UTF-8')
        . ($espBoletin !== '' ? ' - ' . htmlspecialchars($espBoletin, ENT_QUOTES, 'UTF-8') : '');
} else {
    $tituloCurso = htmlspecialchars(__('auto.sin_curso_asignado'), ENT_QUOTES, 'UTF-8');
}
$hayNotas = $notas_boletin !== [];
$mostrarImprimirBoletin = empty($GLOBALS['familia_portal_vista']);
$materiasPreviasFicha = [];
$intensificacionesFicha = $intensificaciones_ficha ?? [];
$schoolYearIntensifFicha = isset($school_year_intensif_ficha) ? (int) $school_year_intensif_ficha : null;
?>
    <div class="card ficha-boletin-card">
        <div class="card-header ficha-boletin-card__header">
            <div class="ficha-boletin-card__header-inner">
                <h3 class="card-title">📊 <?php echo htmlspecialchars(__('student.report_card_header'), ENT_QUOTES, 'UTF-8'); ?> — <?php echo $tituloCurso; ?></h3>
                <div class="ficha-boletin-card__actions">
                <?php if ($hayNotas && $mostrarImprimirBoletin): ?>
                <button type="button" class="btn btn-success ficha-boletin-card__print" data-csp-open-boletin="<?php echo htmlspecialchars(app_base_path('print_report_card.php?' . http_build_query(['id' => $estudiante_id_int, 'csrf_token' => $csrfToken ?? ''])), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-print"></i><?php echo htmlspecialchars(__('student.print_report_card'), ENT_QUOTES, 'UTF-8'); ?></button>
                <?php endif; ?>
                <?php if ($puedeBoletinCambioCurso && !empty($estudiante['curso_id'])): ?>
                <button type="button" class="btn btn-warning" data-csp-show-modal="modalCambioCurso">
                    <i class="fas fa-exchange-alt"></i><?php echo htmlspecialchars(__('auto.cambiar_de_curso'), ENT_QUOTES, 'UTF-8'); ?></button>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($hayNotas): ?>
        <?php $termsCountFicha = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount(sistema_admin_pdo()); ?>
        <div class="boletin-estudiante-container">
            <table class="boletin-estudiante-table">
                <thead>
                    <tr>
                        <th class="materia-header"><?php echo htmlspecialchars(__('student.subject'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php if ($termsCountFicha === 3 || $termsCountFicha === 4): ?>
                            <?php for ($t = 1; $t <= $termsCountFicha; $t++): ?>
                                <th class="cuatrimestre-header"><?php echo htmlspecialchars(__('student.term' . $t), ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php endfor; ?>
                        <?php else: ?>
                            <th class="cuatrimestre-header"><?php echo htmlspecialchars(__('student.term1_preview'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th class="cuatrimestre-header"><?php echo htmlspecialchars(__('student.term1'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th class="cuatrimestre-header"><?php echo htmlspecialchars(__('student.term2_preview'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th class="cuatrimestre-header"><?php echo htmlspecialchars(__('student.term2'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <?php endif; ?>
                        <th class="promedio-header"><?php echo htmlspecialchars(__('student.average'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th class="estado-header"><?php echo htmlspecialchars(__('student.status'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notas_boletin as $datos): ?>
                    <?php
                    $registroEstado = $datos['registro_academico_estado'] ?? null;
                    $promOk = !empty($datos['promedio_calculado']);
                    $prom = $datos['promedio'];
                    ?>
                    <tr>
                        <td class="materia-cell">
                            <strong><?php echo htmlspecialchars((string) $datos['materia']['nombre']); ?></strong>
                        </td>
                        <?php if ($termsCountFicha === 3 || $termsCountFicha === 4): ?>
                            <?php for ($t = 1; $t <= $termsCountFicha; $t++): ?>
                            <td class="nota-cell">
                                <span class="nota-value"><?php
                                    $tRaw = $datos['cuatrimestres'][$t] ?? null;
                                    echo $tRaw !== null && $tRaw !== '' ? htmlspecialchars((string) $tRaw) : '-';
                                ?></span>
                            </td>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php
                            $avance1 = $datos['avances']['avance1'] ?? null;
                            $avance2 = $datos['avances']['avance2'] ?? null;
                            $v1 = is_array($avance1) && !empty($avance1['valor']) ? (string) $avance1['valor'] : '';
                            $v2 = is_array($avance2) && !empty($avance2['valor']) ? (string) $avance2['valor'] : '';
                            $t1Raw = $datos['cuatrimestres'][1] ?? null;
                            $t2Raw = $datos['cuatrimestres'][2] ?? null;
                            $t1 = $t1Raw !== null ? $t1Raw : '-';
                            $t2 = $t2Raw !== null ? $t2Raw : '-';
                            ?>
                            <td class="nota-cell">
                                <span class="nota-value"><?php echo $v1 !== '' ? htmlspecialchars($v1) : '-'; ?></span>
                            </td>
                            <td class="nota-cell">
                                <span class="nota-value"><?php echo htmlspecialchars((string) $t1); ?></span>
                            </td>
                            <td class="nota-cell">
                                <span class="nota-value"><?php echo $v2 !== '' ? htmlspecialchars($v2) : '-'; ?></span>
                            </td>
                            <td class="nota-cell">
                                <span class="nota-value"><?php echo htmlspecialchars((string) $t2); ?></span>
                            </td>
                        <?php endif; ?>
                        <td class="promedio-cell">
                            <?php if ($promOk || $prom !== null): ?>
                                <span class="promedio-value"><?php echo htmlspecialchars((string) $prom); ?></span>
                            <?php else: ?>
                                <span class="promedio-pendiente"><?php echo htmlspecialchars(__('grades.pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="estado-cell">
                            <?php
                            if ($registroEstado instanceof \SistemaAdmin\DTO\SubjectStatusResult):
                                $st = $registroEstado->status->value;
                                $s1 = $registroEstado->effectiveSemester1;
                                $s2 = $registroEstado->effectiveSemester2;
                                $title = sprintf(
                                    'Sem1: %s | Sem2: %s',
                                    $s1 !== null ? (string) $s1 : '—',
                                    $s2 !== null ? (string) $s2 : '—'
                                );
                                if ($st === 'Passed'): ?>
                                    <span class="estado aprobado" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(__('student.course_passed'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php elseif ($st === 'Intensification'): ?>
                                    <span class="estado pendiente" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(__('student.in_intensification'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span class="estado reprobado" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(__('student.pending_subject'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif;
                            elseif ($promOk): ?>
                                <?php if ($prom >= 7): ?>
                                    <span class="estado aprobado"><?php echo htmlspecialchars(__('student.passed'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span class="estado reprobado"><?php echo htmlspecialchars(__('student.failed'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="estado pendiente"><?php echo htmlspecialchars(__('grades.pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card-body">
            <div class="ficha-boletin-empty">
                <i class="fas fa-clipboard-list ficha-boletin-empty__icon" aria-hidden="true"></i>
                <h4 class="ficha-boletin-empty__title"><?php echo htmlspecialchars(__('student.no_grades_registered'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <p class="ficha-boletin-empty__text">
                    <?php if (empty($estudiante['curso_id'])): ?>
                        <strong><?php echo htmlspecialchars(__('student.no_course_assigned'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <small><?php echo htmlspecialchars(__('student.assign_course_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php else: ?>
                        <strong><?php echo htmlspecialchars(__('student.no_grades_registered'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <small>Las notas aparecerán aquí una vez que sean cargadas por los profesores.</small>
                    <?php endif; ?>
                </p>
                <?php if ($puedeBoletinCambioCurso && empty($estudiante['curso_id'])): ?>
                <button type="button" class="btn btn-primary" data-csp-show-modal="modalCambioCurso">
                    <i class="fas fa-graduation-cap"></i><?php echo htmlspecialchars(__('student.assign_course_btn'), ENT_QUOTES, 'UTF-8'); ?></button>
                <?php elseif ($puedeBoletinCambioCurso && !empty($estudiante['curso_id'])): ?>
                <button type="button" class="btn btn-warning" data-csp-show-modal="modalCambioCurso">
                    <i class="fas fa-exchange-alt"></i> <?php echo htmlspecialchars(__('auto.cambiar_de_curso'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($intensificacionesFicha !== []): ?>
        <div class="ficha-boletin-subsection ficha-intensif-section">
            <div class="ficha-intensif-section__head">
                <h4 class="ficha-intensif-section__title">Intensificaciones y recuperatorios</h4>
                <?php if ($schoolYearIntensifFicha !== null): ?>
                <p class="ficha-intensif-section__meta">>Ciclo lectivo<?php echo (int) $schoolYearIntensifFicha; ?>
                    · Se muestra la última calificación por materia, instancia y alcance.</p>
                <?php endif; ?>
            </div>
            <div class="ficha-intensif-table-wrap">
                <table class="ficha-intensif-table">
                    <thead>
                        <tr>
                            <th scope="col">Materia</th>
                            <th scope="col">Instancia</th>
                            <th scope="col">Alcance</th>
                            <th scope="col">Nota:</th>
                            <th scope="col">&fecha=</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($intensificacionesFicha as $intRow): ?>
                        <?php
                        $ctx = (string) ($intRow['evaluation_context'] ?? '');
                        $fechaRaw = (string) ($intRow['fecha'] ?? '');
                        $fechaFmt = $fechaRaw;
                        if ($fechaRaw !== '' && preg_match('/^(\d{4}-\d{2}-\d{2})/', $fechaRaw, $m)) {
                            $di = \DateTimeImmutable::createFromFormat('Y-m-d', $m[1]);
                            $fechaFmt = $di ? $di->format('d/m/Y') : $fechaRaw;
                        }
                        ?>
                        <tr>
                            <td class="ficha-intensif-table__mat"><?php echo htmlspecialchars((string) ($intRow['materia_nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ServicioBoletinNotas::etiquetaContextoEvaluacionHumano($ctx), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ServicioBoletinNotas::etiquetaAlcanceRecuperacionHumano($intRow['recovery_scope'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="ficha-intensif-table__nota"><?php echo htmlspecialchars((string) ($intRow['calificacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="ficha-intensif-table__fecha"><?php echo htmlspecialchars($fechaFmt, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($materiasPreviasFicha)): ?>
        <div class="ficha-previas-panel">
            <h4 class="ficha-previas-panel__title">Materias previas</h4>
            <p class="ficha-previas-panel__hint">Registro administrativo. Las calificaciones de diciembre / febrero–marzo del ciclo actual se gestionan en <strong>Notas</strong> como intensificaciones; aquí solo se documenta el estado de la previa.</p>
            <ul class="ficha-previas-list">
                <?php foreach ($materiasPreviasFicha as $previa): ?>
                <?php
                $estadoPrevia = strtolower((string) ($previa['estado'] ?? 'pendiente'));
                $estadoLabel = match ($estadoPrevia) {
                    'aprobada' => 'Aprobada',
                    'reprobada' => 'Reprobada',
                    'regularizada' => 'Regularizada',
                    default => 'Pendiente',
                };
                $estadoClase = match ($estadoPrevia) {
                    'aprobada' => 'ficha-previas-pill--ok',
                    'reprobada' => 'ficha-previas-pill--rep',
                    'regularizada' => 'ficha-previas-pill--reg',
                    default => 'ficha-previas-pill--pend',
                };
                $previaId = (int) ($previa['id'] ?? 0);
                $puedeConfirmarAqui = $puedeAprobarPreviaStaff && $previaId > 0
                    && in_array($estadoPrevia, ['pendiente', 'reprobada'], true);
                ?>
                <li class="ficha-previas-item">
                    <div class="ficha-previas-item__row">
                        <span class="ficha-previas-item__materia"><?php echo htmlspecialchars((string) ($previa['materia_nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="ficha-previas-pill <?php echo htmlspecialchars($estadoClase, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($estadoLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <dl class="ficha-previas-item__meta ficha-previas-item__meta--compact">
                        <div><dt>Año de la previa</dt><dd><?php echo (int) ($previa['anio_previo'] ?? 0); ?>°</dd></div>
                        <?php if ($estadoPrevia === 'aprobada' && !empty($previa['anio_aprobacion'])): ?>
                        <div><dt>Alta</dt><dd>Año calendario <?php echo (int) $previa['anio_aprobacion']; ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <?php if ($puedeConfirmarAqui): ?>
                    <form method="post" class="ficha-previas-approve-form js-confirm-submit" data-confirm-message="¿Confirmar la aprobación de esta materia previa? No se registran mesas de examen en el sistema.">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="previa_id" value="<?php echo $previaId; ?>">
                        <button type="submit" name="aprobar_materia_previa" value="1" class="btn btn-primary btn-sm ficha-previas-approve-btn">Confirmar aprobación</button>
                    </form>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

    </div>
