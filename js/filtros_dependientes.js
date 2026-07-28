/**
 * filtros_dependientes.js
 *
 * Helper comun para filtros visuales dependientes por curso.
 * No realiza submit del formulario; solo muestra/oculta opciones.
 *
 * Uso basico:
 * window.FiltrosDependientes.init({
 *   sourceSelectId: 'curso',
 *   targets: [
 *     { selectId: 'estudiante', emptyValue: '', dataAttr: 'data-curso-id' },
 *     { selectId: 'materia', emptyValue: '', dataAttr: 'data-curso-id' }
 *   ]
 * });
 */
(function (global) {
    'use strict';

    /**
     * @param {HTMLSelectElement|null} sourceSelect
     * @param {{selectEl: HTMLSelectElement, emptyValue: string, dataAttr: string, allowNoSourceShowAll: boolean}[]} targets
     */
    function applyFilter(sourceSelect, targets) {
        if (!sourceSelect || !targets || targets.length === 0) return;

        var sourceValue = sourceSelect.value;

        targets.forEach(function (target) {
            var selectEl = target.selectEl;
            if (!selectEl) return;

            var currentValue = String(selectEl.value || target.emptyValue);
            var hasVisibleCurrentValue = false;
            var options = selectEl.querySelectorAll('option');
            options.forEach(function (op) {
                if (op.value === target.emptyValue) {
                    op.style.display = '';
                    op.disabled = false;
                    return;
                }

                var optionSourceValue = op.getAttribute(target.dataAttr) || '';
                var shouldShow;

                if (!sourceValue) {
                    shouldShow = !!target.allowNoSourceShowAll;
                } else if (sourceValue === 'sin_curso') {
                    shouldShow = optionSourceValue === 'sin_curso' || optionSourceValue === '';
                } else {
                    shouldShow = optionSourceValue === String(sourceValue);
                }

                op.style.display = shouldShow ? '' : 'none';
                op.disabled = !shouldShow;
                if (shouldShow && String(op.value) === currentValue) {
                    hasVisibleCurrentValue = true;
                }
            });

            // Evita reseteos incorrectos cuando hay opciones duplicadas por value:
            // conservar el valor actual si al menos una opción visible coincide.
            if (currentValue !== String(target.emptyValue)) {
                if (hasVisibleCurrentValue) {
                    selectEl.value = currentValue;
                } else {
                    selectEl.value = target.emptyValue;
                }
            }
        });
    }

    /**
     * @param {{
     *   sourceSelectId: string,
     *   targets: Array<{
     *     selectId: string,
     *     emptyValue?: string,
     *     dataAttr?: string,
     *     allowNoSourceShowAll?: boolean
     *   }>,
     *   applyOnInit?: boolean
     * }} config
     */
    function init(config) {
        if (!config || !config.sourceSelectId || !config.targets || config.targets.length === 0) {
            return false;
        }

        var sourceSelect = document.getElementById(config.sourceSelectId);
        if (!sourceSelect) return false;

        var targets = config.targets
            .map(function (t) {
                var selectEl = document.getElementById(t.selectId);
                if (!selectEl) return null;
                return {
                    selectEl: selectEl,
                    emptyValue: typeof t.emptyValue === 'string' ? t.emptyValue : '',
                    dataAttr: t.dataAttr || 'data-curso-id',
                    allowNoSourceShowAll: t.allowNoSourceShowAll !== false
                };
            })
            .filter(function (t) { return t !== null; });

        if (targets.length === 0) return false;

        sourceSelect.addEventListener('change', function () {
            applyFilter(sourceSelect, targets);
        });

        if (config.applyOnInit !== false) {
            applyFilter(sourceSelect, targets);
        }

        return true;
    }

    global.FiltrosDependientes = {
        init: init
    };
})(window);
