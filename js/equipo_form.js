(function () {
    'use strict';

    function syncPreceptorCurso() {
        var cargo = document.getElementById('cargo');
        var grupo = document.getElementById('grupo_curso_preceptor');
        var selCurso = document.getElementById('curso_id_preceptor');
        if (!cargo || !grupo) {
            return;
        }
        function sync() {
            var esPreceptor = cargo.value === 'preceptor';
            grupo.classList.toggle('grupo-curso-preceptor--oculto', !esPreceptor);
            if (selCurso) {
                selCurso.required = esPreceptor;
                if (!esPreceptor) {
                    selCurso.value = '';
                }
            }
        }
        cargo.addEventListener('change', sync);
        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncPreceptorCurso);
    } else {
        syncPreceptorCurso();
    }
})();
