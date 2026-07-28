/**
 * Ficha estudiante: pestañas del modal de edición, cierre unificado (backdrop + Escape).
 */
(function () {
    'use strict';

    function closeEditModal() {
        var m = document.getElementById('editModal');
        if (m) {
            m.style.display = 'none';
        }
        document.body.style.overflow = 'auto';
    }

    function closeModalEl(modal) {
        if (!modal) {
            return;
        }
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    /* Pestañas del modal de edición */
    document.addEventListener('click', function (e) {
        var tabBtn = e.target.closest('[data-ficha-tab]');
        if (!tabBtn || !tabBtn.closest('.ficha-edit-tabs')) {
            return;
        }
        e.preventDefault();
        var tab = tabBtn.getAttribute('data-ficha-tab');
        var root = tabBtn.closest('.modal-body');
        if (!root) {
            return;
        }
        root.querySelectorAll('.ficha-edit-tabs__btn').forEach(function (b) {
            b.classList.toggle('is-active', b === tabBtn);
            b.setAttribute('aria-selected', b === tabBtn ? 'true' : 'false');
        });
        root.querySelectorAll('.ficha-edit-panel').forEach(function (p) {
            var show = p.id === 'ficha-panel-' + tab;
            p.classList.toggle('is-active', show);
        });
    });

    window.addEventListener('click', function (event) {
        var t = event.target;
        if (t && t.classList && t.classList.contains('modal')) {
            if (t.id === 'editModal') {
                closeEditModal();
            } else {
                closeModalEl(t);
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        document.querySelectorAll('.modal').forEach(function (modal) {
            if (modal.style.display !== 'block') {
                return;
            }
            if (modal.id === 'editModal') {
                closeEditModal();
            } else {
                closeModalEl(modal);
            }
        });
    });

    // Filtro dinámico de materias por curso en la asignación de recursadas
    var cursoSelect = document.querySelector('.js-recursada-curso-select');
    var materiaSelect = document.querySelector('.js-recursada-materia-select');
    if (cursoSelect && materiaSelect) {
        var materiasData = {};
        try {
            var raw = cursoSelect.getAttribute('data-curso-materias');
            if (raw) {
                materiasData = JSON.parse(raw);
            }
        } catch (e) {
            console.error('Error parsing materias map:', e);
        }

        cursoSelect.addEventListener('change', function () {
            var cursoId = cursoSelect.value;
            materiaSelect.innerHTML = '';
            
            if (!cursoId) {
                materiaSelect.disabled = true;
                var opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Seleccionar curso primero...';
                materiaSelect.appendChild(opt);
                return;
            }

            var list = materiasData[cursoId] || [];
            if (list.length === 0) {
                materiaSelect.disabled = true;
                var optEmpty = document.createElement('option');
                optEmpty.value = '';
                optEmpty.textContent = 'No hay materias registradas en este curso';
                materiaSelect.appendChild(optEmpty);
            } else {
                materiaSelect.disabled = false;
                var optPlaceholder = document.createElement('option');
                optPlaceholder.value = '';
                optPlaceholder.textContent = 'Seleccionar materia...';
                materiaSelect.appendChild(optPlaceholder);

                list.forEach(function (mat) {
                    var optMat = document.createElement('option');
                    optMat.value = mat.id;
                    optMat.textContent = mat.nombre;
                    materiaSelect.appendChild(optMat);
                });
            }
        });
    }
})();
