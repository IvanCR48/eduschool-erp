(function () {
    'use strict';

    const cfg = window.__HORARIOS_PAGE__ || {};
    const csrfToken = typeof cfg.csrfToken === 'string' ? cfg.csrfToken : '';
    const allTeachersLabel = typeof cfg.allTeachersLabel === 'string' ? cfg.allTeachersLabel : 'Todos los profesores';
    const selectTeacherHint = typeof cfg.selectTeacherHint === 'string' ? cfg.selectTeacherHint : 'Seleccionar profesor (primero selecciona curso y materia)';
    const selectSubjectLabel = typeof cfg.selectSubjectLabel === 'string' ? cfg.selectSubjectLabel : 'Seleccionar materia';

    function cargarProfesoresPorMateria(materiaId, cursoId, selectDocenteId, selectedProfesorId) {
        selectedProfesorId = selectedProfesorId || '';
        const selectDocente = document.getElementById(selectDocenteId);

        selectDocente.innerHTML = '<option value="">' + selectTeacherHint + '</option>';

        if (!materiaId || !cursoId) {
            return;
        }

        const url = 'profesores.php?ajax=get_profesores_por_materia&materia_id=' + encodeURIComponent(materiaId) + '&curso_id=' + encodeURIComponent(cursoId);
        fetch(url)
            .then(function (response) {
                const ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    return response.text().then(function (t) {
                        throw new Error('Respuesta no JSON (¿sesión cerrada?): ' + t.substring(0, 120));
                    });
                }
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    const optErr = document.createElement('option');
                    optErr.value = '';
                    optErr.disabled = true;
                    optErr.textContent = data.message || 'No se pudieron cargar docentes';
                    selectDocente.appendChild(optErr);
                    return;
                }
                if (data.profesores && data.profesores.length > 0) {
                    data.profesores.forEach(function (profesor) {
                        const option = document.createElement('option');
                        option.value = profesor.id;
                        option.textContent = profesor.apellido + ' ' + profesor.nombre + (profesor.grupo_taller ? ' (Grupo ' + profesor.grupo_taller + ')' : '');
                        if (selectedProfesorId && String(profesor.id) === String(selectedProfesorId)) {
                            option.selected = true;
                        }
                        selectDocente.appendChild(option);
                    });
                } else {
                    const optVac = document.createElement('option');
                    optVac.value = '';
                    optVac.disabled = true;
                    optVac.textContent = (window.HORARIOS_I18N && window.HORARIOS_I18N.no_teacher_available) || 'Ningún docente disponible: asigne el curso y la materia en la ficha del profesor (Profesor → cursos/materias). Si el curso no tiene especialidad, igual debe figurar en profesor_curso y profesor_materia para el año vigente.';
                    selectDocente.appendChild(optVac);
                }
            })
            .catch(function (error) {
                console.error('Error al cargar profesores:', error);
                const opt = document.createElement('option');
                opt.value = '';
                opt.disabled = true;
                opt.textContent = 'Error al cargar docentes. Recargue la página o verifique la sesión.';
                selectDocente.appendChild(opt);
            });
    }

    function actualizarGrupoTallerVisibilidad(materiaSelect) {
        if (!materiaSelect) {
            return;
        }
        const isEdit = materiaSelect.id.includes('edit');
        const containerId = isEdit ? 'grupo_taller_container_edit' : 'grupo_taller_container';
        const selectId = isEdit ? 'grupo_taller_edit' : 'grupo_taller';
        
        const container = document.getElementById(containerId);
        const select = document.getElementById(selectId);
        
        if (!container || !select) {
            return;
        }
        
        const selectedOption = materiaSelect.options[materiaSelect.selectedIndex];
        const esTaller = selectedOption && selectedOption.getAttribute('data-es-taller') === '1';
        
        if (esTaller) {
            container.style.display = 'block';
            select.required = true;
        } else {
            container.style.display = 'none';
            select.required = false;
            select.value = '';
        }
    }

    function cargarMateriasPorCurso(cursoSelect, materiaSelect, profesorSelect, selectedMateriaId, selectedProfesorId) {
        if (!cursoSelect || !materiaSelect) {
            return;
        }
        selectedMateriaId = selectedMateriaId || '';
        selectedProfesorId = selectedProfesorId || '';

        const cursoId = cursoSelect.value;
        materiaSelect.innerHTML = '<option value="">' + selectSubjectLabel + '</option>';
        materiaSelect.disabled = true;

        if (profesorSelect) {
            profesorSelect.innerHTML = '<option value="">' + selectTeacherHint + '</option>';
        }

        if (!cursoId) {
            actualizarGrupoTallerVisibilidad(materiaSelect);
            return;
        }

        const params = new URLSearchParams();
        params.set('ajax', 'get_materias_por_curso');
        params.set('curso_id', cursoId);
        params.set('csrf_token', csrfToken);

        fetch('horarios.php?' + params.toString())
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(function (materia) {
                        const option = document.createElement('option');
                        option.value = materia.id;
                        option.textContent = materia.nombre;
                        option.setAttribute('data-es-taller', materia.es_taller);
                        materiaSelect.appendChild(option);
                    });

                    materiaSelect.disabled = false;

                    if (selectedMateriaId) {
                        materiaSelect.value = selectedMateriaId;
                        if (profesorSelect) {
                            cargarProfesoresPorMateria(selectedMateriaId, cursoId, profesorSelect.id, selectedProfesorId);
                        }
                    }
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'El curso no tiene materias asignadas';
                    option.disabled = true;
                    option.selected = true;
                    materiaSelect.appendChild(option);
                }
                actualizarGrupoTallerVisibilidad(materiaSelect);
            })
            .catch(function (error) {
                console.error('Error al obtener materias del curso:', error);
                actualizarGrupoTallerVisibilidad(materiaSelect);
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const materiaActualEditar = cfg.materiaEditarId || '';
        const profesorActualEditar = cfg.profesorEditarId || '';
        const profesoresPorCurso = cfg.profesoresPorCurso && typeof cfg.profesoresPorCurso === 'object' ? cfg.profesoresPorCurso : {};
        const profesoresFiltroOpciones = Array.isArray(cfg.profesoresFiltroOpciones) ? cfg.profesoresFiltroOpciones : [];

        const cursoSelect = document.getElementById('curso_id');
        const materiaSelect = document.getElementById('materia_id');
        const profesorSelect = document.getElementById('profesor_id');

        if (cursoSelect && materiaSelect && profesorSelect) {
            materiaSelect.disabled = !cursoSelect.value;

            cursoSelect.addEventListener('change', function () {
                cargarMateriasPorCurso(cursoSelect, materiaSelect, profesorSelect);
            });

            materiaSelect.addEventListener('change', function () {
                cargarProfesoresPorMateria(this.value, cursoSelect.value, 'profesor_id');
                actualizarGrupoTallerVisibilidad(this);
            });

            if (cursoSelect.value) {
                cargarMateriasPorCurso(cursoSelect, materiaSelect, profesorSelect);
            }
        }

        const cursoEditSelect = document.getElementById('curso_id_edit');
        const materiaEditSelect = document.getElementById('materia_id_edit');
        const profesorEditSelect = document.getElementById('profesor_id_edit');

        if (cursoEditSelect && materiaEditSelect && profesorEditSelect) {
            materiaEditSelect.disabled = !cursoEditSelect.value;

            cursoEditSelect.addEventListener('change', function () {
                cargarMateriasPorCurso(cursoEditSelect, materiaEditSelect, profesorEditSelect);
            });

            materiaEditSelect.addEventListener('change', function () {
                cargarProfesoresPorMateria(this.value, cursoEditSelect.value, 'profesor_id_edit');
                actualizarGrupoTallerVisibilidad(this);
            });

            if (cursoEditSelect.value) {
                cargarMateriasPorCurso(
                    cursoEditSelect,
                    materiaEditSelect,
                    profesorEditSelect,
                    materiaActualEditar,
                    profesorActualEditar
                );
            }
        }

        const cursoFiltroSelect = document.getElementById('curso');
        const profesorFiltroSelect = document.getElementById('profesor');

        if (cursoFiltroSelect && profesorFiltroSelect) {
            const reconstruirFiltroProfesores = function () {
                const cursoId = cursoFiltroSelect.value;
                const profesorPrevio = profesorFiltroSelect.value;
                const profesorSel = document.getElementById('profesor');

                profesorSel.innerHTML = '<option value="">' + allTeachersLabel + '</option>';

                if (cursoId && profesoresPorCurso[cursoId]) {
                    profesoresPorCurso[cursoId].forEach(function (profesor) {
                        const option = document.createElement('option');
                        option.value = String(profesor.id);
                        option.textContent = profesor.apellido + ' ' + profesor.nombre;
                        profesorSel.appendChild(option);
                    });
                } else {
                    profesoresFiltroOpciones.forEach(function (p) {
                        const option = document.createElement('option');
                        option.value = String(p.id);
                        option.textContent = p.label;
                        profesorSel.appendChild(option);
                    });
                }

                if (profesorPrevio && profesorSel.querySelector('option[value="' + profesorPrevio + '"]')) {
                    profesorSel.value = profesorPrevio;
                } else {
                    profesorSel.value = '';
                }
            };

            cursoFiltroSelect.addEventListener('change', reconstruirFiltroProfesores);
            reconstruirFiltroProfesores();
        }

        const btnToggleBloques = document.getElementById('btn-toggle-bloques-horarios');
        const cardBloques = document.getElementById('horarios-bloques-card');
        if (btnToggleBloques && cardBloques) {
            btnToggleBloques.addEventListener('click', function () {
                const estabaColapsado = cardBloques.classList.contains('is-collapsed');
                cardBloques.classList.toggle('is-collapsed');
                const expandido = estabaColapsado;
                btnToggleBloques.setAttribute('aria-expanded', expandido ? 'true' : 'false');
            });
        }

        // Tab switching for workshop group schedules
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.tab-nav-btn');
            if (!btn) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();

            var targetId = btn.getAttribute('data-tab-target');
            if (!targetId) {
                return;
            }

            var nav = btn.closest('.horarios-tabs-nav');
            if (!nav) {
                return;
            }

            var allBtns = nav.querySelectorAll('.tab-nav-btn');
            for (var i = 0; i < allBtns.length; i++) {
                allBtns[i].classList.remove('active');
            }
            btn.classList.add('active');

            var section = nav.closest('.horarios-taller-section');
            if (!section) {
                return;
            }

            var panels = section.querySelectorAll('.tab-panel');
            for (var j = 0; j < panels.length; j++) {
                if (panels[j].id === targetId) {
                    panels[j].classList.add('active');
                } else {
                    panels[j].classList.remove('active');
                }
            }
        });
    });
}());
