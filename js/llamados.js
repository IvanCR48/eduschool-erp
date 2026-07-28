/**
 * llamados.js — Filtros client-side para llamados.php
 * CSP-safe: archivo externo cargado con nonce via <script src>.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // ── Filtros dependientes por curso (sin auto-submit) ─────────────────
        var filtrosDependientes = window.FiltrosDependientes;
        if (filtrosDependientes && typeof filtrosDependientes.init === 'function') {
            filtrosDependientes.init({
                sourceSelectId: 'curso',
                targets: [
                    { selectId: 'estudiante', emptyValue: '', dataAttr: 'data-curso-id', allowNoSourceShowAll: true }
                ]
            });

            filtrosDependientes.init({
                sourceSelectId: 'curso_form',
                targets: [
                    { selectId: 'estudiante_id', emptyValue: '', dataAttr: 'data-curso-id', allowNoSourceShowAll: true }
                ]
            });

            var selectCursoForm = document.getElementById('curso_form');
            var selectEstudianteForm = document.getElementById('estudiante_id');
            if (selectCursoForm && selectEstudianteForm && !selectCursoForm.value && selectEstudianteForm.value) {
                var opSel = selectEstudianteForm.querySelector('option[value="' + selectEstudianteForm.value + '"]');
                if (opSel) {
                    var cursoSel = opSel.getAttribute('data-curso-id') || '';
                    if (cursoSel !== '') {
                        selectCursoForm.value = cursoSel;
                        selectCursoForm.dispatchEvent(new Event('change'));
                    }
                }
            }
        }

        // ── Confirmar eliminación (CSP-safe, reemplaza data-confirm-message) ─
        document.querySelectorAll('.js-confirm-submit').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var msg = form.getAttribute('data-confirm-message') || '¿Confirmar esta acción?';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                }
            });
        });

    }); // DOMContentLoaded

})();
