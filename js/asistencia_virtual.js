/**
 * asistencia_virtual.js — lógica client-side del módulo de asistencia.
 * Cargado como script externo (src) para cumplir con la CSP del sistema.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ── Marcar todos presentes ──────────────────────────────────────
        var btnTodos = document.getElementById('btn-todos-presentes');
        if (btnTodos) {
            btnTodos.addEventListener('click', function () {
                var radios = document.querySelectorAll('#form-asistencia .av-students-container input[type="radio"]');
                var huboCambios = false;
                radios.forEach(function (radio) {
                    if (radio.value === 'Presente') {
                        var estaba = radio.checked;
                        radio.checked = true;
                        if (!estaba) {
                            huboCambios = true;
                        }
                    }
                });

                // Feedback visual breve en el botón
                var original = btnTodos.innerHTML;
                btnTodos.innerHTML = '<i class="fas fa-check"></i> ¡Listo!';
                btnTodos.style.opacity = '.75';
                setTimeout(function () {
                    btnTodos.innerHTML = original;
                    btnTodos.style.opacity = '';
                }, 1200);

                // Si hubo cambios, forzamos guardado automático.
                if (huboCambios) {
                    var formAsistencia = document.getElementById('form-asistencia');
                    if (formAsistencia) {
                        formAsistencia.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                }
            });
        }

        // ── Mostrar nombre de archivo adjunto seleccionado ──────────────
        var fileInputs = document.querySelectorAll('.av-file-input');
        fileInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var btn = this.closest('.av-file-label').querySelector('.av-file-btn');
                if (!btn) return;
                if (this.files && this.files.length > 0) {
                    btn.innerHTML = '<i class="fas fa-check" style="color:#10b981"></i>';
                    btn.title = this.files[0].name;
                    btn.style.borderColor = '#6ee7b7';
                    btn.style.background  = '#d1fae5';
                } else {
                    btn.innerHTML = '<i class="fas fa-paperclip"></i>';
                    btn.title = '';
                    btn.style.borderColor = '';
                    btn.style.background  = '';
                }
            });
        });

        // ── Guardado automático ─────────────────────────────────────────────
        var formAsistencia = document.getElementById('form-asistencia');
        if (formAsistencia) {
            var autoSaveTimeout = null;
            var saveInFlight = false;
            var savePending = false;
            var currentSavePromise = null;
            var pendingPayloadBuilder = null;
            var statusBadge = null;
            var refrescarContadoresUI = function () {
                var resumen = {
                    presentes: 0,
                    tardanzas: 0,
                    media_falta: 0,
                    aus_justificados: 0,
                    ausentes: 0,
                    total: 0
                };
                formAsistencia.querySelectorAll('.av5').forEach(function (grupo) {
                    var selected = grupo.querySelector('input[type="radio"]:checked');
                    if (!selected) {
                        return;
                    }
                    resumen.total += 1;
                    var valor = selected.value;
                    if (valor === 'Presente') resumen.presentes += 1;
                    else if (valor === 'Tardanza') resumen.tardanzas += 1;
                    else if (valor === 'Media falta') resumen.media_falta += 1;
                    else if (valor === 'Ausente justificado') resumen.aus_justificados += 1;
                    else if (valor === 'Ausente') resumen.ausentes += 1;
                });

                var cards = document.querySelectorAll('.av-stats .stat-card .stat-content h3');
                if (cards.length >= 6) {
                    cards[0].textContent = String(resumen.presentes);
                    cards[1].textContent = String(resumen.tardanzas);
                    cards[2].textContent = String(resumen.media_falta);
                    cards[3].textContent = String(resumen.aus_justificados);
                    cards[4].textContent = String(resumen.ausentes);
                    cards[5].textContent = String(resumen.total);
                }
            };
            var updateProgressBar = function () {
                var total = 0;
                var checked = 0;
                formAsistencia.querySelectorAll('.av-students-container .av-student-card').forEach(function (card) {
                    if (card.classList.contains('av-student-card--solapada')) {
                        return;
                    }
                    total++;
                    var hasChecked = card.querySelector('input[type="radio"]:checked');
                    if (hasChecked) {
                        checked++;
                    }
                });

                var countEl = document.getElementById('av-progress-count');
                var percentEl = document.getElementById('av-progress-percent');
                if (countEl && percentEl) {
                    countEl.textContent = checked + '/' + total;
                    var pct = total > 0 ? Math.round((checked / total) * 100) : 0;
                    percentEl.textContent = pct + '%';
                }
            };
            var setStatus = function (text, tone) {
                var statusTextEl = document.getElementById('av-status-text');
                var statusSaverEl = document.getElementById('av-status-saver');
                if (statusTextEl && statusSaverEl) {
                    statusTextEl.textContent = text;
                    statusSaverEl.classList.remove('is-saving', 'is-error');
                    
                    var iconEl = statusSaverEl.querySelector('i');
                    if (iconEl) {
                        iconEl.className = 'fas fa-check-circle';
                    }

                    if (tone === 'error') {
                        statusSaverEl.classList.add('is-error');
                        if (iconEl) iconEl.className = 'fas fa-exclamation-circle';
                    } else if (tone === 'muted') {
                        statusSaverEl.classList.add('is-saving');
                        if (iconEl) iconEl.className = 'fas fa-spinner';
                    }
                }
            };
            var syncSeleccionVisual = function () {
                formAsistencia.querySelectorAll('.av5').forEach(function (grupo) {
                    grupo.querySelectorAll('.av5__opt').forEach(function (opt) {
                        opt.classList.remove('is-selected');
                    });
                    var selected = grupo.querySelector('input[type="radio"]:checked');
                    if (selected) {
                        var label = selected.closest('.av5__opt');
                        if (label) {
                            label.classList.add('is-selected');
                        }
                    }

                    var card = grupo.closest('.av-student-card');
                    if (card) {
                        card.classList.remove('av-card-status-presente', 'av-card-status-tardanza', 'av-card-status-media', 'av-card-status-justificado', 'av-card-status-ausente', 'av-card-status-unmarked');
                        var statusLower = 'unmarked';
                        if (selected) {
                            var val = selected.value;
                            if (val === 'Presente') statusLower = 'presente';
                            else if (val === 'Tardanza') statusLower = 'tardanza';
                            else if (val === 'Media falta') statusLower = 'media';
                            else if (val === 'Ausente justificado') statusLower = 'justificado';
                            else if (val === 'Ausente') statusLower = 'ausente';
                        }
                        card.classList.add('av-card-status-' + statusLower);
                    }
                });
                refrescarContadoresUI();
                updateProgressBar();
            };
            var construirPayloadFila = function (eid, estadoForzado) {
                var payload = new FormData();
                payload.set('ajax_guardar', '1');
                ['csrf_token', 'curso_id', 'materia_id', 'grupo_taller', 'fecha', 'estudiante_id', 'trimestre', 'anio', 'mes', 'dia'].forEach(function (name) {
                    var field = formAsistencia.querySelector('[name="' + name + '"]');
                    if (field && typeof field.value !== 'undefined') {
                        payload.set(name, field.value);
                    }
                });

                var estadoActual = typeof estadoForzado === 'string' && estadoForzado !== ''
                    ? estadoForzado
                    : (function () {
                        var radioChecked = formAsistencia.querySelector('input[type="radio"][name="estado[' + eid + ']"]:checked');
                        return radioChecked ? radioChecked.value : '';
                    })();
                if (estadoActual !== '') {
                    payload.set('estado[' + eid + ']', estadoActual);
                }

                var obsInput = formAsistencia.querySelector('input[name="observacion[' + eid + ']"]');
                if (obsInput) {
                    payload.set('observacion[' + eid + ']', obsInput.value || '');
                }

                var fileInput = formAsistencia.querySelector('input[type="file"][name="adjunto[' + eid + ']"]');
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    payload.set('adjunto[' + eid + ']', fileInput.files[0]);
                }

                return payload;
            };
            var construirPayloadCompleto = function () {
                var payload = new FormData(formAsistencia);
                payload.set('ajax_guardar', '1');
                return payload;
            };
            var enviarGuardadoAjax = function (payloadBuilder) {
                if (saveInFlight) {
                    savePending = true;
                    pendingPayloadBuilder = payloadBuilder || pendingPayloadBuilder;
                    return currentSavePromise || Promise.resolve();
                }
                saveInFlight = true;
                setStatus((window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.guardando) || 'Guardando...', 'muted');
                var builder = payloadBuilder || pendingPayloadBuilder || construirPayloadCompleto;
                pendingPayloadBuilder = null;
                var formData = builder();

                currentSavePromise = fetch('asistencia_virtual.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        if (payload && payload.success) {
                            setStatus((window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.guardado) || 'Guardado', 'ok');
                            if (payload.porcentajes && typeof payload.porcentajes === 'object') {
                                Object.keys(payload.porcentajes).forEach(function (eid) {
                                    var pct = Number(payload.porcentajes[eid]);
                                    var pctEl = formAsistencia.querySelector('.av-pct[data-eid="' + eid + '"]');
                                    if (!pctEl) {
                                        return;
                                    }
                                    pctEl.classList.remove('av-pct--ok', 'av-pct--alerta', 'av-pct--riesgo', 'av-pct--sin-datos');
                                    if (!Number.isFinite(pct) || pct < 0) {
                                        pctEl.textContent = 'Sin datos';
                                        pctEl.classList.add('av-pct--sin-datos');
                                    } else {
                                        pctEl.textContent = pct.toFixed(1) + '%';
                                        if (pct >= 85) {
                                            pctEl.classList.add('av-pct--ok');
                                        } else if (pct >= 75) {
                                            pctEl.classList.add('av-pct--alerta');
                                        } else {
                                            pctEl.classList.add('av-pct--riesgo');
                                        }
                                    }
                                });
                            }
                            return true;
                        } else {
                            var msg = payload && payload.error ? payload.error : ((window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.no_se_detectaron_estados) || 'No se pudo guardar');
                            setStatus(msg, 'error');
                            return false;
                        }
                    })
                    .catch(function () {
                        setStatus((window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.error_de_red_al_guardar) || 'Error de red al guardar', 'error');
                        return false;
                    })
                    .finally(function () {
                        saveInFlight = false;
                        if (savePending) {
                            savePending = false;
                            enviarGuardadoAjax(pendingPayloadBuilder || construirPayloadCompleto);
                        }
                    });
                return currentSavePromise;
            };
            var forzarGuardadoInmediato = function (payloadBuilder) {
                if (autoSaveTimeout) {
                    window.clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = null;
                }
                return enviarGuardadoAjax(payloadBuilder || pendingPayloadBuilder || construirPayloadCompleto);
            };
            var submitAuto = function (payloadBuilder) {
                if (autoSaveTimeout) {
                    window.clearTimeout(autoSaveTimeout);
                }
                if (payloadBuilder) {
                    pendingPayloadBuilder = payloadBuilder;
                }
                autoSaveTimeout = window.setTimeout(function () {
                    enviarGuardadoAjax(pendingPayloadBuilder || construirPayloadCompleto);
                }, 250);
            };

            formAsistencia.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    syncSeleccionVisual();
                    var m = (radio.name || '').match(/^estado\[(\d+)\]$/);
                    if (m) {
                        var eid = m[1];
                        var estadoSeleccionado = String(radio.value || '');
                        submitAuto(function () { return construirPayloadFila(eid, estadoSeleccionado); });
                    } else {
                        submitAuto();
                    }
                });
            });
            formAsistencia.querySelectorAll('.av5__opt').forEach(function (optLabel) {
                optLabel.addEventListener('click', function () {
                    // Forzar selección explícita del radio dentro de la tarjeta antes de guardar.
                    var radio = optLabel.querySelector('input[type="radio"]');
                    if (radio) {
                        // Si ya estaba seleccionado, no reencolar guardado innecesariamente.
                        if (radio.checked) {
                            return;
                        }
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
            formAsistencia.querySelectorAll('.av-obs-input').forEach(function (input) {
                input.addEventListener('blur', function () {
                    var m = (input.name || '').match(/^observacion\[(\d+)\]$/);
                    if (m) {
                        var eid = m[1];
                        submitAuto(function () { return construirPayloadFila(eid); });
                    } else {
                        submitAuto();
                    }
                });
            });
            formAsistencia.querySelectorAll('.av-file-input').forEach(function (input) {
                input.addEventListener('change', function () {
                    var m = (input.name || '').match(/^adjunto\[(\d+)\]$/);
                    if (m) {
                        var eid = m[1];
                        submitAuto(function () { return construirPayloadFila(eid); });
                    } else {
                        submitAuto();
                    }
                });
            });

            syncSeleccionVisual();

            formAsistencia.addEventListener('submit', function (e) {
                // El formulario se guarda por AJAX para evitar recargas constantes.
                e.preventDefault();
                enviarGuardadoAjax(construirPayloadCompleto);
            });

            // Si navegan al día anterior/siguiente, primero forzamos guardado.
            var dayNavForm = document.querySelector('.av-day-arrows');
            if (dayNavForm) {
                dayNavForm.addEventListener('submit', function (e) {
                    if (!saveInFlight && !autoSaveTimeout) {
                        return;
                    }
                    e.preventDefault();
                    var submitter = e.submitter || null;
                    setStatus((window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.guardando_antes_de_cambiar_de_dia) || 'Guardando antes de cambiar de día...', 'muted');
                    forzarGuardadoInmediato().then(function (ok) {
                        if (ok) {
                            if (submitter && typeof dayNavForm.requestSubmit === 'function') {
                                dayNavForm.requestSubmit(submitter);
                            } else {
                                if (submitter && submitter.name && !dayNavForm.querySelector('input[name="' + submitter.name + '"]')) {
                                    var hidden = document.createElement('input');
                                    hidden.type = 'hidden';
                                    hidden.name = submitter.name;
                                    hidden.value = submitter.value;
                                    dayNavForm.appendChild(hidden);
                                }
                                dayNavForm.submit();
                            }
                            return;
                        }
                        setStatus((window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.no_se_guard_revisa) || 'No se guardó. Revisá conexión e intentá de nuevo.', 'error');
                    });
                });
            }
        }

        // ── Botón imprimir reporte (CSP-safe) ───────────────────────────────
        var btnImprimir = document.getElementById('btn-imprimir-reporte');
        if (btnImprimir) {
            btnImprimir.addEventListener('click', function () {
                window.print();
            });
        }

        // ── Filtro visual dependiente por curso (sin auto-submit) ───────────
        var cursoFiltro = document.getElementById('curso_id');
        var materiaFiltro = document.getElementById('materia_id');
        var estudianteFiltro = document.getElementById('estudiante_id');

        function filtrarOpcionesPorCurso(selectEl, cursoId, emptyValue) {
            if (!selectEl) return;
            var valorActual = String(selectEl.value || emptyValue);
            var hayValorActualVisible = false;
            var opcionVisibleConValorActual = null;
            var opciones = selectEl.querySelectorAll('option');
            opciones.forEach(function (op) {
                if (op.value === emptyValue) {
                    op.style.display = '';
                    op.disabled = false;
                    return;
                }
                var cursoOp = op.getAttribute('data-curso-id') || '';
                var visible = !!cursoId && cursoOp === String(cursoId);
                op.style.display = visible ? '' : 'none';
                op.disabled = !visible;
                if (visible && String(op.value) === valorActual) {
                    hayValorActualVisible = true;
                    if (!opcionVisibleConValorActual) {
                        opcionVisibleConValorActual = op;
                    }
                }
            });

            // Evita "saltos" visuales cuando hay materias repetidas por curso:
            // si existe al menos una opción visible con el mismo valor, conservarla.
            if (valorActual !== String(emptyValue)) {
                if (hayValorActualVisible) {
                    // Seleccionar explícitamente la opción visible correcta (no solo por value),
                    // porque puede haber opciones duplicadas con el mismo value en otros cursos.
                    opciones.forEach(function (op) { op.selected = false; });
                    if (opcionVisibleConValorActual) {
                        opcionVisibleConValorActual.selected = true;
                    } else {
                        selectEl.value = valorActual;
                    }
                } else {
                    selectEl.value = emptyValue;
                }
            }
        }

        var grupoTallerFiltro = document.getElementById('grupo_taller');
        var grupoTallerContainer = document.getElementById('grupo_taller_filter_container');

        function refrescarGrupoTallerVisibility() {
            if (!materiaFiltro || !grupoTallerContainer) return;
            var selectedOption = materiaFiltro.options[materiaFiltro.selectedIndex];
            if (selectedOption) {
                var esTaller = selectedOption.getAttribute('data-es-taller') === '1';
                if (esTaller) {
                    grupoTallerContainer.style.display = '';
                    if (grupoTallerFiltro) {
                        grupoTallerFiltro.required = true;
                    }
                } else {
                    grupoTallerContainer.style.display = 'none';
                    if (grupoTallerFiltro) {
                        grupoTallerFiltro.required = false;
                        grupoTallerFiltro.value = '';
                    }
                }
            } else {
                grupoTallerContainer.style.display = 'none';
                if (grupoTallerFiltro) {
                    grupoTallerFiltro.required = false;
                    grupoTallerFiltro.value = '';
                }
            }
        }

        function refrescarDependientesCurso() {
            if (!cursoFiltro) return;
            var cursoId = cursoFiltro.value;
            filtrarOpcionesPorCurso(materiaFiltro, cursoId, '0');
            filtrarOpcionesPorCurso(estudianteFiltro, cursoId, '0');
            refrescarGrupoTallerVisibility();
        }

        // Importante: en esta vista usamos SOLO el filtro local de abajo.
        // Evita doble aplicación (helper global + local) que producía reseteos visuales
        // del selector de materia al reenviar filtros.

        if (cursoFiltro) {
            cursoFiltro.addEventListener('change', refrescarDependientesCurso);
            refrescarDependientesCurso();
        }

        if (materiaFiltro) {
            materiaFiltro.addEventListener('change', refrescarGrupoTallerVisibility);
        }

        // ── Restaurar selección de materia luego del filtrado inicial ────────
        if (materiaFiltro && cursoFiltro) {
            var materiaDeseada = String(materiaFiltro.getAttribute('data-selected-value') || '0');
            if (materiaDeseada !== '0') {
                var opcionDeseada = materiaFiltro.querySelector('option[value="' + materiaDeseada + '"]');
                if (opcionDeseada) {
                    var cursoOp = String(opcionDeseada.getAttribute('data-curso-id') || '');
                    var cursoActual = String(cursoFiltro.value || '');
                    var visible = opcionDeseada.style.display !== 'none' && !opcionDeseada.disabled;
                    if (cursoOp === cursoActual && visible) {
                        materiaFiltro.value = materiaDeseada;
                        refrescarGrupoTallerVisibility();
                    }
                }
            }
        }

        // ── Buscador en tiempo real ──────────────────────────────────────────
        var searchInput = document.getElementById('av-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = searchInput.value.toLowerCase().trim();
                var cards = document.querySelectorAll('.av-students-container .av-student-card');
                cards.forEach(function (card) {
                    var nombre = card.getAttribute('data-nombre') || '';
                    var dni = card.getAttribute('data-dni') || '';
                    if (nombre.indexOf(q) !== -1 || dni.indexOf(q) !== -1) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // ── Acordeón de detalles de tarjeta ──────────────────────────────────
        var toggleButtons = document.querySelectorAll('.av-toggle-details-btn');
        toggleButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var eid = btn.getAttribute('data-eid');
                var details = document.getElementById('details-' + eid);
                if (details) {
                    var isOpen = details.style.display !== 'none';
                    if (isOpen) {
                        details.style.display = 'none';
                        btn.classList.remove('is-open');
                        btn.querySelector('.btn-text').textContent = (window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.ver_notas_y_justificativo) || 'Ver notas y justificativo';
                        btn.querySelector('i').className = 'fas fa-chevron-down';
                    } else {
                        details.style.display = 'block';
                        btn.classList.add('is-open');
                        btn.querySelector('.btn-text').textContent = (window.ASISTENCIA_I18N && window.ASISTENCIA_I18N.ocultar_notas_y_justificativo) || 'Ocultar notas y justificativo';
                        btn.querySelector('i').className = 'fas fa-chevron-up';
                    }
                }
            });
        });

        // ── Filtros colapsables ──────────────────────────────────────────────
        var toggleFiltrosHeader = document.getElementById('av-toggle-filtros-btn');
        var collapsibleFiltros = document.getElementById('av-filtros-collapsible');
        var btnToggleChevron = document.querySelector('.av-btn-toggle-filtros');
        if (toggleFiltrosHeader && collapsibleFiltros) {
            // Cargar preferencia guardada
            var pref = localStorage.getItem('av-filters-collapsed');
            if (pref === 'true') {
                collapsibleFiltros.classList.add('is-collapsed');
                if (btnToggleChevron) btnToggleChevron.classList.add('is-collapsed');
            }

            toggleFiltrosHeader.addEventListener('click', function (e) {
                // Si hizo clic en select o inputs, no contraer
                if (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT' || e.target.tagName === 'A' || e.target.tagName === 'BUTTON' && !e.target.classList.contains('av-btn-toggle-filtros')) {
                    return;
                }
                var collapsed = collapsibleFiltros.classList.toggle('is-collapsed');
                if (btnToggleChevron) btnToggleChevron.classList.toggle('is-collapsed', collapsed);
                localStorage.setItem('av-filters-collapsed', collapsed ? 'true' : 'false');
            });
        }

        // ── LÓGICA DE REDISEÑO MÓVIL PREMIUM (Fase 2) ───────────────────────────────
        var mobileTabBtns = document.querySelectorAll('.av-mobile-tab-btn');
        var mobileTabContents = document.querySelectorAll('.av-mobile-tab-content');

        function switchMobileTab(tabId) {
            mobileTabBtns.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
            });
            mobileTabContents.forEach(function (content) {
                content.classList.toggle('active-mobile-tab', content.getAttribute('data-mobile-tab') === tabId);
            });
        }

        if (mobileTabBtns.length > 0) {
            mobileTabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    switchMobileTab(this.getAttribute('data-tab'));
                });
            });

            // Si es móvil y no hay alumnos o no se seleccionó materia, ir a filtros
            var hasStudents = document.querySelectorAll('.av-students-container .av-student-card').length > 0;
            if (window.innerWidth <= 768) {
                if (!hasStudents) {
                    switchMobileTab('filtros');
                } else {
                    switchMobileTab('alumnos');
                }
            }
        }

        // CONTROL DEL BOTTOM SHEET
        var bottomSheet = document.getElementById('av-student-bottom-sheet');
        var sheetBackdrop = document.getElementById('av-sheet-backdrop');
        var sheetCloseBtn = document.getElementById('av-sheet-close-btn');
        var sheetSaveBtn = document.getElementById('av-sheet-save-btn');
        var studentCards = document.querySelectorAll('.av-students-container .av-student-card');

        function openBottomSheet(card) {
            if (card.classList.contains('av-student-card--solapada')) {
                return; // Ignorar solapadas
            }
            var eid = card.getAttribute('data-eid');
            bottomSheet.dataset.activeEid = eid;

            // Rellenar información básica
            var name = card.querySelector('.av-card-name').textContent;
            var dni = card.querySelector('.av-card-dni').innerHTML;
            var avatarCircle = card.querySelector('.av-avatar-circle');
            var initials = avatarCircle ? avatarCircle.getAttribute('data-iniciales') : '';

            document.getElementById('av-sheet-name').textContent = name;
            document.getElementById('av-sheet-dni').innerHTML = dni;
            document.getElementById('av-sheet-avatar').textContent = initials;

            // Rellenar estado actual (Radio buttons del Bottom Sheet)
            var cardCheckedRadio = card.querySelector('input[type="radio"]:checked');
            var activeState = cardCheckedRadio ? cardCheckedRadio.value : '';
            
            var stateButtons = bottomSheet.querySelectorAll('.av-sheet-state-btn');
            stateButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-state') === activeState);
            });

            // Rellenar Observación
            var cardObsInput = card.querySelector('input[name="observacion[' + eid + ']"]');
            var obsVal = cardObsInput ? cardObsInput.value : '';
            document.getElementById('av-sheet-obs').value = obsVal;

            // Rellenar Archivo Justificativo
            var fileStatusText = document.getElementById('av-sheet-file-status');
            var cardAdjAnchor = card.querySelector('.av-adj-actual');
            if (cardAdjAnchor) {
                fileStatusText.innerHTML = cardAdjAnchor.outerHTML;
            } else {
                fileStatusText.textContent = 'Sin archivo';
            }
            
            // Limpiar input del file por si acaso
            document.getElementById('av-sheet-file-input').value = '';

            // Mostrar Sheet
            bottomSheet.classList.add('is-open');
            document.body.style.overflow = 'hidden'; // Evita scroll de fondo
        }

        function closeBottomSheet() {
            if (bottomSheet) {
                bottomSheet.classList.remove('is-open');
                document.body.style.overflow = '';
                bottomSheet.dataset.activeEid = '';
            }
        }

        if (bottomSheet && studentCards.length > 0) {
            studentCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    // Si el clic fue en el botón de ficha de estudiante, no abrir Bottom Sheet
                    if (e.target.closest('.btn-ficha')) {
                        return;
                    }
                    if (window.innerWidth <= 768) {
                        openBottomSheet(card);
                    }
                });
            });

            if (sheetCloseBtn) {
                sheetCloseBtn.addEventListener('click', closeBottomSheet);
            }
            if (sheetBackdrop) {
                sheetBackdrop.addEventListener('click', closeBottomSheet);
            }
            if (sheetSaveBtn) {
                sheetSaveBtn.addEventListener('click', closeBottomSheet);
            }

            // Cambios de estado en el Bottom Sheet
            var stateButtons = bottomSheet.querySelectorAll('.av-sheet-state-btn');
            stateButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var activeEid = bottomSheet.dataset.activeEid;
                    if (!activeEid) return;

                    var stateVal = this.getAttribute('data-state');
                    
                    // Actualizar botones del Bottom Sheet
                    stateButtons.forEach(function (b) {
                        b.classList.toggle('active', b === btn);
                    });

                    // Modificar radio input correspondiente en el formulario
                    var cardRadio = document.querySelector('input[type="radio"][name="estado[' + activeEid + 'Detailed]"][value="' + stateVal + '"]');
                    if (!cardRadio) {
                        // Intentar buscar normal
                        cardRadio = document.querySelector('input[type="radio"][name="estado[' + activeEid + ']"][value="' + stateVal + '"]');
                    }
                    if (cardRadio) {
                        cardRadio.checked = true;
                        cardRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Actualizar badge móvil del estudiante
                    var card = document.querySelector('.av-student-card[data-eid="' + activeEid + '"]');
                    if (card) {
                        var statusBadge = card.querySelector('.av-mobile-status-badge');
                        if (statusBadge) {
                            var labelText = stateVal === 'Ausente justificado' ? 'Justificado' : stateVal;
                            statusBadge.textContent = labelText;
                        }
                    }
                });
            });

            // Cambios de observación en el Bottom Sheet
            var sheetObsTextarea = document.getElementById('av-sheet-obs');
            if (sheetObsTextarea) {
                sheetObsTextarea.addEventListener('blur', function () {
                    var activeEid = bottomSheet.dataset.activeEid;
                    if (!activeEid) return;

                    var obsVal = this.value;
                    var cardObsInput = document.querySelector('input[name="observacion[' + activeEid + ']"]');
                    if (cardObsInput) {
                        cardObsInput.value = obsVal;
                        cardObsInput.dispatchEvent(new Event('blur', { bubbles: true }));
                    }
                });
            }

            // Cambios de archivo en el Bottom Sheet
            var sheetFileInput = document.getElementById('av-sheet-file-input');
            if (sheetFileInput) {
                sheetFileInput.addEventListener('change', function () {
                    var activeEid = bottomSheet.dataset.activeEid;
                    if (!activeEid) return;

                    var cardFileInput = document.querySelector('input[type="file"][name="adjunto[' + activeEid + ']"]');
                    if (cardFileInput && this.files.length > 0) {
                        cardFileInput.files = this.files;
                        cardFileInput.dispatchEvent(new Event('change', { bubbles: true }));

                        // Actualizar texto del file status
                        var fileStatusText = document.getElementById('av-sheet-file-status');
                        fileStatusText.textContent = this.files[0].name;
                        fileStatusText.style.color = '#10b981';
                        fileStatusText.style.fontWeight = '700';
                    }
                });
            }
        }

    }); // DOMContentLoaded

})();
