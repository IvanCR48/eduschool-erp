(function () {
    const modal = document.getElementById('modal-recuperatorio-notas');
    const openBtn = document.getElementById('btn-abrir-modal-recuperatorio');
    const ctxSel = document.getElementById('recuperacion_contexto');
    const alcSel = document.getElementById('recuperacion_alcance');
    const estSelect = document.getElementById('recup_estudiante_id');

    function syncAlcance() {
        if (!ctxSel || !alcSel) {
            return;
        }
        const firstSem = 'intensification_first_semester';
        if (ctxSel.value === firstSem) {
            alcSel.value = 'first_semester';
            alcSel.disabled = true;
        } else {
            alcSel.disabled = false;
        }
    }

    /**
     * @param {number|undefined|null} presetEstudianteId Si viene definido (>0), preselecciona ese estudiante en el &lt;select&gt;.
     */
    function openModal(presetEstudianteId) {
        if (!modal) {
            return;
        }

        if (estSelect) {
            if (typeof presetEstudianteId === 'number' && presetEstudianteId > 0) {
                const idStr = String(presetEstudianteId);
                estSelect.value = idStr;
                if (estSelect.value !== idStr) {
                    estSelect.value = '';
                }
            } else {
                estSelect.value = '';
            }
        }

        modal.classList.add('is-open');
        document.body.classList.add('notas-modal-open');
        syncAlcance();
    }

    function closeModal() {
        if (!modal) {
            return;
        }
        modal.classList.remove('is-open');
        document.body.classList.remove('notas-modal-open');
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            openModal(null);
        });
    }

    document.addEventListener('click', function (ev) {
        const trigger = ev.target.closest('.js-open-recup-modal');
        if (!trigger || !modal) {
            return;
        }
        const raw = trigger.getAttribute('data-estudiante-id');
        const id = raw ? parseInt(raw, 10) : 0;
        if (id > 0) {
            ev.preventDefault();
            openModal(id);
        }
    });

    document.querySelectorAll('[data-close-recup-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', function (ev) {
            if (ev.target === modal) {
                closeModal();
            }
        });
    }

    if (ctxSel) {
        ctxSel.addEventListener('change', syncAlcance);
    }

    syncAlcance();
})();
