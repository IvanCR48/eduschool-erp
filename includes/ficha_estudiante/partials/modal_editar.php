<?php
/** Variables: $csrfToken, $estudiante, $turnos_lista */
?>
<div id="editModal" class="modal modal--ficha modal--edit">
    <div class="modal-content modal-content--edit">
        <div class="modal-header">
            <h3><?php echo htmlspecialchars(__('student.edit_info'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="close" role="button" tabindex="0" data-csp-hide-modal="editModal" data-csp-modal-lock-body="1">&times;</span>
        </div>
        <div class="modal-body">
            <div class="ficha-edit-tabs" role="tablist" aria-label="Secciones de edición">
                <button type="button" class="ficha-edit-tabs__btn is-active" role="tab" aria-selected="true" aria-controls="ficha-panel-contacto" id="ficha-tab-btn-contacto" data-ficha-tab="contacto"><?php echo htmlspecialchars(__('student.contact_and_shift'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="ficha-edit-tabs__btn" role="tab" aria-selected="false" aria-controls="ficha-panel-responsable" id="ficha-tab-btn-responsable" data-ficha-tab="responsable"><?php echo htmlspecialchars(__('student.guardian'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="ficha-edit-tabs__btn" role="tab" aria-selected="false" aria-controls="ficha-panel-emergencia" id="ficha-tab-btn-emergencia" data-ficha-tab="emergencia"><?php echo htmlspecialchars(__('student.emergency'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>

            <div class="ficha-edit-panels">
                <div class="ficha-edit-panel is-active" id="ficha-panel-contacto" role="tabpanel" aria-labelledby="ficha-tab-btn-contacto">
                    <form method="POST" class="form-container" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <h4><?php echo htmlspecialchars(__('student.contact_info'), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="domicilio"><?php echo htmlspecialchars(__('student.address'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <textarea name="domicilio" id="domicilio" placeholder="Dirección completa"><?php echo htmlspecialchars((string) ($estudiante['domicilio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="telefono_fijo"><?php echo htmlspecialchars(__('student.landline_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="tel" name="telefono_fijo" id="telefono_fijo" value="<?php echo htmlspecialchars((string) ($estudiante['telefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telefono_celular"><?php echo htmlspecialchars(__('student.mobile_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="tel" name="telefono_celular" id="telefono_celular" value="<?php echo htmlspecialchars((string) ($estudiante['telefono_celular'] ?? ($estudiante['telefono'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars((string) ($estudiante['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="100">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="foto"><?php echo htmlspecialchars(__('student.profile_picture'), ENT_QUOTES, 'UTF-8'); ?> (JPG/PNG, máx. 5MB):</label>
                                <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/gif">
                                <?php if (!empty($estudiante['foto'])): ?>
                                    <div style="margin-top: 5px; display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="eliminar_foto" id="eliminar_foto" value="1">
                                        <label for="eliminar_foto" style="display: inline; font-size: 0.9em; color: #dc2626; font-weight: normal; cursor: pointer;"><?php echo htmlspecialchars(__('student.delete_current_photo'), ENT_QUOTES, 'UTF-8'); ?></label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="grupo_sanguineo"><?php echo htmlspecialchars(__('student.blood_group'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <?php $grupoSanguineoActual = (string) ($estudiante['grupo_sanguineo'] ?? ''); ?>
                                <select name="grupo_sanguineo" id="grupo_sanguineo">
                                    <option value=""><?php echo htmlspecialchars(__('action.select'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $grupoSanguineoActual === $opt ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="obra_social"><?php echo htmlspecialchars(__('student.health_insurance'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" name="obra_social" id="obra_social" value="<?php echo htmlspecialchars((string) ($estudiante['obra_social'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="100">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dni_responsable_portal"><?php echo htmlspecialchars(__('student.guardian_id'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" name="dni_responsable_portal" id="dni_responsable_portal" maxlength="20"
                                       pattern="[0-9A-Za-z\.\-]{5,20}"
                                       placeholder="<?php echo htmlspecialchars(__('student.guardian_id_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                                       value="<?php echo htmlspecialchars((string) ($estudiante['dni_responsable'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <small class="ficha-help"><?php echo htmlspecialchars(__('student.guardian_id_help'), ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha_nacimiento"><?php echo htmlspecialchars(__('student.birthdate'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" 
                                       value="<?php echo htmlspecialchars((string) ($estudiante['fecha_nacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="fecha_ingreso"><?php echo htmlspecialchars(__('student.enrollment_date'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="date" name="fecha_ingreso" id="fecha_ingreso" 
                                       value="<?php echo htmlspecialchars((string) ($estudiante['fecha_ingreso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <?php if (!empty($estudiante['anio']) && !empty($estudiante['division'])): ?>
                        <h4><?php echo htmlspecialchars(__('student.course_and_shift'), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo htmlspecialchars(__('student.current_course'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <div class="ficha-muted">
                                    <?php echo (int) $estudiante['anio'] . '° ' . htmlspecialchars((string) $estudiante['division'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($estudiante['especialidad'])): ?> — <?php echo htmlspecialchars((string) $estudiante['especialidad'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="nuevo_turno_id"><?php echo htmlspecialchars(__('student.shift'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <select name="nuevo_turno_id" id="nuevo_turno_id">
                                    <option value=""><?php echo htmlspecialchars(__('student.keep_current_shift'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string) ($estudiante['turno'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>)</option>
                                    <?php foreach ($turnos_lista as $t): ?>
                                    <option value="<?php echo (int) $t['id']; ?>" <?php echo (!empty($estudiante['turno_id']) && (int) $estudiante['turno_id'] === (int) $t['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) $t['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="ficha-help"><?php echo htmlspecialchars(__('student.shift_change_help'), ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="form-actions">
                            <button type="submit" name="actualizar_estudiante" class="btn btn-primary">
                                <i class="fas fa-save"></i><?php echo htmlspecialchars(__('action.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                            <button type="button" class="btn btn-secondary" data-csp-hide-modal="editModal" data-csp-modal-lock-body="1">
                                <i class="fas fa-times"></i><?php echo htmlspecialchars(__('action.cancel'), ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                </div>

                <div class="ficha-edit-panel" id="ficha-panel-responsable" role="tabpanel" aria-labelledby="ficha-tab-btn-responsable">
                    <form method="POST" class="form-container">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <h4><?php echo htmlspecialchars(__('student.add_guardian'), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_resp"><?php echo htmlspecialchars(__('students.first_name'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" name="nombre" id="nombre_resp" required maxlength="50">
                            </div>
                            <div class="form-group">
                                <label for="apellido_resp"><?php echo htmlspecialchars(__('students.last_name'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" name="apellido" id="apellido_resp" required maxlength="50">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dni_resp"><?php echo htmlspecialchars(__('students.th_dni'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" name="dni" id="dni_resp" maxlength="20">
                            </div>
                            <div class="form-group">
                                <label for="telefono_resp"><?php echo htmlspecialchars(__('students.th_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="tel" name="telefono" id="telefono_resp" required maxlength="20">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email_resp">Email:</label>
                                <input type="email" name="email" id="email_resp" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="parentesco_resp"><?php echo htmlspecialchars(__('student.relationship'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <select name="parentesco" id="parentesco_resp" required>
                                    <option value=""><?php echo htmlspecialchars(__('action.select'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Padre"><?php echo htmlspecialchars(__('student.relationship_father'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Madre"><?php echo htmlspecialchars(__('student.relationship_mother'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Tutor"><?php echo htmlspecialchars(__('student.relationship_guardian'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Abuelo/a"><?php echo htmlspecialchars(__('student.relationship_grandparent'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Hermano/a"><?php echo htmlspecialchars(__('student.relationship_sibling'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Tío/a"><?php echo htmlspecialchars(__('student.relationship_uncle_aunt'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Otro"><?php echo htmlspecialchars(__('student.relationship_other'), ENT_QUOTES, 'UTF-8'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="es_contacto_emergencia" value="1"><?php echo htmlspecialchars(__('student.is_emergency_contact'), ENT_QUOTES, 'UTF-8'); ?></label>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="guardar_responsable" class="btn btn-success">
                                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(__('student.add_guardian'), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="ficha-edit-panel" id="ficha-panel-emergencia" role="tabpanel" aria-labelledby="ficha-tab-btn-emergencia">
                    <form method="POST" class="form-container">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <h4><?php echo htmlspecialchars(__('student.add_emergency_contact'), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_contacto"><?php echo htmlspecialchars(__('students.first_name'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" name="nombre" id="nombre_contacto" required maxlength="50">
                            </div>
                            <div class="form-group">
                                <label for="telefono_contacto"><?php echo htmlspecialchars(__('students.th_phone'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="tel" name="telefono" id="telefono_contacto" required maxlength="20">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="parentesco_contacto"><?php echo htmlspecialchars(__('student.relationship'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <select name="parentesco" id="parentesco_contacto" required>
                                    <option value=""><?php echo htmlspecialchars(__('action.select'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Padre"><?php echo htmlspecialchars(__('student.relationship_father'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Madre"><?php echo htmlspecialchars(__('student.relationship_mother'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Tutor"><?php echo htmlspecialchars(__('student.relationship_guardian'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Abuelo/a"><?php echo htmlspecialchars(__('student.relationship_grandparent'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Hermano/a"><?php echo htmlspecialchars(__('student.relationship_sibling'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Tío/a"><?php echo htmlspecialchars(__('student.relationship_uncle_aunt'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Vecino/a"><?php echo htmlspecialchars(__('student.relationship_neighbor'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="Otro"><?php echo htmlspecialchars(__('student.relationship_other'), ENT_QUOTES, 'UTF-8'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="guardar_contacto" class="btn btn-danger">
                                <i class="fas fa-plus"></i><?php echo htmlspecialchars(__('student.add_emergency_contact'), ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
