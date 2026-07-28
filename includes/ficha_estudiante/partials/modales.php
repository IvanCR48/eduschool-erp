<?php
/** Variables: $csrfToken, $llamados_amonestacion_verbal, $cursos_disponibles, $puede_cambiar_curso_ficha (opcional) */
?>
<!-- Modal de confirmación para eliminar responsable -->
<div id="modalEliminarResponsable" class="modal modal--ficha">
    <div class="modal-content modal-content--narrow">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('student.confirm_deletion'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="close" role="button" tabindex="0" data-csp-hide-modal="modalEliminarResponsable">&times;</span>
        </div>
        <div class="modal-body">
            <p><?php echo htmlspecialchars(__('student.confirm_delete_guardian'), ENT_QUOTES, 'UTF-8'); ?> <strong id="nombreResponsable"></strong>?</p>
            <p class="modal-warn"><i class="fas fa-info-circle"></i><?php echo htmlspecialchars(__('student.action_cannot_be_undone'), ENT_QUOTES, 'UTF-8'); ?></p>
            <form method="POST" class="modal-form-actions">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="responsable_id" id="responsableId">
                <div class="modal-form-actions__buttons">
                    <button type="button" class="btn btn-secondary" data-csp-hide-modal="modalEliminarResponsable">
                        <i class="fas fa-times"></i><?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="submit" name="eliminar_responsable" class="btn btn-danger">
                        <i class="fas fa-trash"></i><?php echo htmlspecialchars(__('action.delete'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalEliminarContacto" class="modal modal--ficha">
    <div class="modal-content modal-content--narrow">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars(__('student.confirm_deletion'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="close" role="button" tabindex="0" data-csp-hide-modal="modalEliminarContacto">&times;</span>
        </div>
        <div class="modal-body">
            <p><?php echo htmlspecialchars(__('student.confirm_delete_contact'), ENT_QUOTES, 'UTF-8'); ?> <strong id="nombreContacto"></strong>?</p>
            <p class="modal-warn"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars(__('student.action_cannot_be_undone'), ENT_QUOTES, 'UTF-8'); ?></p>
            <form method="POST" class="modal-form-actions">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="contacto_id" id="contactoId">
                <div class="modal-form-actions__buttons">
                    <button type="button" class="btn btn-secondary" data-csp-hide-modal="modalEliminarContacto">
                        <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="submit" name="eliminar_contacto" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo htmlspecialchars(__('action.delete'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($puede_cambiar_curso_ficha)): ?>
<div id="modalCambioCurso" class="modal modal--ficha">
    <div class="modal-content modal-content--curso">
        <div class="modal-header">
            <h3><i class="fas fa-exchange-alt"></i><?php echo htmlspecialchars(__('student.course_change'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="close" role="button" tabindex="0" data-csp-hide-modal="modalCambioCurso">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-curso-info">
                <h4><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars(__('student.important_info'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <ul>
                    <li>El estudiante tiene <strong><?php echo (int) $llamados_amonestacion_verbal; ?> <?php echo htmlspecialchars(__('student.verbal_warnings_notice'), ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><?php echo htmlspecialchars(__('student.keep_grades_notice'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><?php echo htmlspecialchars(__('student.delete_pending_notice'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><?php echo htmlspecialchars(__('student.warn_log_notice'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li>El estudiante conservará su progreso académico</li>
                </ul>
            </div>
            <form method="POST" class="modal-form-actions">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="nuevo_curso_id"><?php echo htmlspecialchars(__('student.select_new_course'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="nuevo_curso_id" id="nuevo_curso_id" required class="form-control">
                        <option value=""><?php echo htmlspecialchars(__('student.select_new_course'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($cursos_disponibles as $curso): ?>
                        <option value="<?php echo (int) $curso['id']; ?>">
                            <?php echo htmlspecialchars((string) $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'] . ' (' . $curso['turno'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-form-actions__buttons">
                    <button type="button" class="btn btn-secondary" data-csp-hide-modal="modalCambioCurso">
                        <i class="fas fa-times"></i> <?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="submit" name="cambiar_curso" class="btn btn-danger">
                        <i class="fas fa-exchange-alt"></i><?php echo htmlspecialchars(__('student.confirm_course_change'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
