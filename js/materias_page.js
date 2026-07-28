(function () {
    'use strict';

    function initFiltroCursosPorEspecialidad() {
        var sel = document.getElementById('especialidad_id');
        var box = document.getElementById('cursos-asignar-container');
        if (!sel || !box) {
            return;
        }
        var minAnio = parseInt(box.getAttribute('data-anio-min-especialidad') || '4', 10);
        if (isNaN(minAnio)) {
            minAnio = 4;
        }

        function actualizar() {
            var espVal = sel.value;
            box.querySelectorAll('.curso-asignar-item').forEach(function (lab) {
                var anio = parseInt(lab.getAttribute('data-anio'), 10) || 0;
                var espCurso = lab.getAttribute('data-curso-especialidad-id');
                var cb = lab.querySelector('input[type="checkbox"]');
                if (!espVal) {
                    lab.style.display = '';
                    return;
                }
                var ok = espCurso === espVal && anio >= minAnio;
                lab.style.display = ok ? '' : 'none';
                if (!ok && cb) {
                    cb.checked = false;
                }
            });
        }

        sel.addEventListener('change', actualizar);
        actualizar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFiltroCursosPorEspecialidad);
    } else {
        initFiltroCursosPorEspecialidad();
    }
})();
