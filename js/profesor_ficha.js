/**
 * profesor_ficha.php — modal, materias por curso (AJAX), suplencias
 */
(function () {
    'use strict';

    function readBootstrap() {
        var el = document.getElementById('profesor-ficha-bootstrap');
        if (!el || !el.textContent) {
            return { profesorId: 0, materiasAsignadas: [], abrirModalEditar: false };
        }
        try {
            return JSON.parse(el.textContent.trim());
        } catch (e) {
            return { profesorId: 0, materiasAsignadas: [], abrirModalEditar: false };
        }
    }

    function openEditModal() {
        var modal = document.getElementById('editModal');
        if (!modal) {
            return;
        }
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        var modal = document.getElementById('editModal');
        if (!modal) {
            return;
        }
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function actualizarGrupoTallerVisibilidad() {
        var materiaSelect = document.getElementById('asignar_materia_id');
        var grupoTallerContainer = document.getElementById('grupo_taller_container');
        var grupoTallerSelect = document.getElementById('grupo_taller');
        if (!materiaSelect || !grupoTallerContainer) {
            return;
        }
        var selectedOption = materiaSelect.options[materiaSelect.selectedIndex];
        var esTaller = selectedOption && selectedOption.getAttribute('data-es-taller') === '1';
        if (esTaller) {
            grupoTallerContainer.style.display = 'block';
            if (grupoTallerSelect) {
                grupoTallerSelect.required = true;
            }
        } else {
            grupoTallerContainer.style.display = 'none';
            if (grupoTallerSelect) {
                grupoTallerSelect.required = false;
                grupoTallerSelect.value = '';
            }
        }
    }

    function filtrarMateriasPorCurso(cfg) {
        var cursoSelect = document.getElementById('asignar_curso_id');
        var materiaSelect = document.getElementById('asignar_materia_id');
        if (!cursoSelect || !materiaSelect) {
            return;
        }
        var cursoId = cursoSelect.value;
        if (!cursoId) {
            materiaSelect.value = '';
            materiaSelect.disabled = true;
            while (materiaSelect.children.length > 1) {
                materiaSelect.removeChild(materiaSelect.lastChild);
            }
            actualizarGrupoTallerVisibilidad();
            return;
        }
        materiaSelect.disabled = true;
        materiaSelect.innerHTML = '<option value="">Cargando materias...</option>';
        var url =
            'profesor_ficha.php?id=' +
            encodeURIComponent(String(cfg.profesorId)) +
            '&ajax=get_materias_curso&curso_id=' +
            encodeURIComponent(cursoId);
        fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    var materiasAsignadas = cfg.materiasAsignadas || [];
                    var materiasAsignadasCurso = materiasAsignadas
                        .filter(function (m) {
                            return m.curso_id && String(m.curso_id) === String(cursoId);
                        })
                        .map(function (m) {
                            return parseInt(m.materia_id, 10);
                        });
                    materiaSelect.innerHTML = '<option value="">Seleccionar materia</option>';
                    data.materias.forEach(function (materia) {
                        var alreadyAssigned = materiasAsignadasCurso.indexOf(parseInt(materia.id, 10)) !== -1;
                        var esTaller = String(materia.es_taller) === '1';
                        if (!alreadyAssigned || esTaller) {
                            var option = document.createElement('option');
                            option.value = materia.id;
                            option.textContent = materia.nombre;
                            option.setAttribute('data-es-taller', materia.es_taller);
                            materiaSelect.appendChild(option);
                        }
                    });
                    materiaSelect.disabled = false;
                    if (materiaSelect.children.length === 1) {
                        var opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'No hay materias disponibles para este curso';
                        opt.disabled = true;
                        materiaSelect.appendChild(opt);
                    }
                } else {
                    materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
                }
                actualizarGrupoTallerVisibilidad();
            })
            .catch(function () {
                materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
                actualizarGrupoTallerVisibilidad();
            });
    }

    function matchEspecialidadMateria(especialidad, materia) {
        var clean = function(str) {
            return str.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "") // quitar acentos
                .replace(/[^a-z0-9\s]/g, "")     // quitar puntuación
                .trim();
        };

        var eClean = clean(especialidad);
        var mClean = clean(materia);

        if (!eClean || !mClean) return true;

        if (mClean.indexOf(eClean) !== -1 || eClean.indexOf(mClean) !== -1) {
            return true;
        }

        var eWords = eClean.split(/\s+/).filter(function(w) { return w.length > 3; });
        var mWords = mClean.split(/\s+/).filter(function(w) { return w.length > 3; });

        for (var i = 0; i < eWords.length; i++) {
            for (var j = 0; j < mWords.length; j++) {
                if (mWords[j].indexOf(eWords[i]) !== -1 || eWords[i].indexOf(mWords[j]) !== -1) {
                    return true;
                }
                if (mWords[j].substring(0, 5) === eWords[i].substring(0, 5)) {
                    return true;
                }
            }
        }

        return false;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = readBootstrap();
        if (cfg.abrirModalEditar) {
            openEditModal();
        }
        var cursoSelect = document.getElementById('asignar_curso_id');
        var materiaSelect = document.getElementById('asignar_materia_id');
        if (cursoSelect && materiaSelect) {
            materiaSelect.disabled = true;
            cursoSelect.addEventListener('change', function () {
                filtrarMateriasPorCurso(cfg);
            });
            materiaSelect.addEventListener('change', actualizarGrupoTallerVisibilidad);
            if (cursoSelect.value) {
                filtrarMateriasPorCurso(cfg);
            }
        }
        var fueraServicioCheckbox = document.getElementById('fuera_servicio');
        var suplenteSelect = document.getElementById('suplente_id');
        var suplenciaMateriaSelect = document.getElementById('suplencia_materia_id');
        var mostrarTodosCheckbox = document.getElementById('mostrar_todos_suplentes');

        if (fueraServicioCheckbox && suplenteSelect) {
            fueraServicioCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    suplenteSelect.value = '';
                    suplenteSelect.disabled = true;
                    var warningDiv = document.getElementById('suplentes_warning_message');
                    if (warningDiv) {
                        warningDiv.style.display = 'none';
                    }
                } else {
                    suplenteSelect.disabled = false;
                    filtrarSuplentes();
                }
            });
        }

        function filtrarSuplentes() {
            if (!suplenciaMateriaSelect || !suplenteSelect) {
                return;
            }
            var materiaId = suplenciaMateriaSelect.value;
            var mostrarTodos = mostrarTodosCheckbox && mostrarTodosCheckbox.checked;
            var warningDiv = document.getElementById('suplentes_warning_message');

            if (!materiaId || mostrarTodos) {
                Array.from(suplenteSelect.options).forEach(function (option) {
                    option.style.display = '';
                });
                if (warningDiv) {
                    warningDiv.style.display = 'none';
                }
                return;
            }

            var materiaOption = suplenciaMateriaSelect.options[suplenciaMateriaSelect.selectedIndex];
            var materiaNombre = materiaOption.textContent;

            Array.from(suplenteSelect.options).forEach(function (option) {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                var text = option.textContent;
                var startIdx = text.indexOf('(');
                var endIdx = text.indexOf(')');
                if (startIdx === -1 || endIdx === -1) {
                    option.style.display = ''; // Permitir si no tiene especialidad
                    return;
                }
                var especialidad = text.substring(startIdx + 1, endIdx);
                if (matchEspecialidadMateria(especialidad, materiaNombre)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });

            var suplentesDisponibles = Array.from(suplenteSelect.options).filter(function (option) {
                return option.style.display !== 'none' && option.value !== '';
            });

            if (
                suplentesDisponibles.length === 0 &&
                fueraServicioCheckbox &&
                !fueraServicioCheckbox.checked
            ) {
                if (warningDiv) {
                    warningDiv.style.display = 'block';
                }
            } else {
                if (warningDiv) {
                    warningDiv.style.display = 'none';
                }
            }
        }

        if (suplenciaMateriaSelect && suplenteSelect) {
            suplenciaMateriaSelect.addEventListener('change', filtrarSuplentes);
            if (mostrarTodosCheckbox) {
                mostrarTodosCheckbox.addEventListener('change', filtrarSuplentes);
            }
        }
    });

    window.addEventListener('click', function (event) {
        var modal = document.getElementById('editModal');
        if (modal && event.target === modal) {
            closeEditModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeEditModal();
        }
    });
})();
