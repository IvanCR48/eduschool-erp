document.addEventListener('DOMContentLoaded', function () {
    const cursoSelect = document.getElementById('curso_id_nota');
    const estudianteSelect = document.getElementById('estudiante_id');
    const materiaSelect = document.getElementById('materia_id');
    const tipoSelect = document.getElementById('tipo_registro');
    const notaOnly = document.querySelectorAll('.nota-only');
    const avanceOnly = document.querySelectorAll('.avance-only');
    const cuatrimestreSelect = document.getElementById('nota_cuatrimestre');
    const etapaAvanceSelect = document.getElementById('etapa_avance');
    const valorAvanceSelect = document.getElementById('valor_avance');

    const mostrarElementos = (elementos, visible) => {
        elementos.forEach((el) => {
            if (visible) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    };

    const filtrarMaterias = () => {
        if (!cursoSelect || !materiaSelect) {
            return;
        }
        const cursoSeleccionado = cursoSelect.value;
        Array.from(materiaSelect.options).forEach((option) => {
            if (option.value === '') {
                option.hidden = false;
                return;
            }
            const cursosAsignados = option.dataset.cursos
                ? option.dataset.cursos.split(',').filter(Boolean)
                : [];
            const coincide =
                !cursoSeleccionado ||
                cursosAsignados.length === 0 ||
                cursosAsignados.includes(cursoSeleccionado);
            option.hidden = !coincide;
            if (!coincide && option.selected) {
                option.selected = false;
            }
        });
    };

    const aplicarTipoRegistro = () => {
        if (!tipoSelect) {
            return;
        }
        const esAvance = tipoSelect.value === 'avance';
        mostrarElementos(notaOnly, !esAvance);
        mostrarElementos(avanceOnly, esAvance);
        if (cuatrimestreSelect) {
            cuatrimestreSelect.required = !esAvance;
            if (esAvance) {
                cuatrimestreSelect.value = '';
            }
        }
        if (etapaAvanceSelect) {
            etapaAvanceSelect.required = esAvance;
            if (!esAvance) {
                etapaAvanceSelect.value = '';
            }
        }
        if (valorAvanceSelect) {
            valorAvanceSelect.required = esAvance;
            if (!esAvance) {
                valorAvanceSelect.value = '';
            }
        }
        if (esAvance) {
            const notaInput = document.getElementById('nota');
            if (notaInput) {
                notaInput.value = '';
            }
        }
    };

    const filtrosDependientesDisponibles =
        typeof window.FiltrosDependientes !== 'undefined' &&
        window.FiltrosDependientes &&
        typeof window.FiltrosDependientes.init === 'function';

    if (filtrosDependientesDisponibles) {
        window.FiltrosDependientes.init({
            sourceSelectId: 'curso_id_nota',
            targets: [
                {
                    selectId: 'estudiante_id',
                    emptyValue: '',
                    dataAttr: 'data-curso-id',
                    allowNoSourceShowAll: true
                }
            ]
        });
    }

    if (cursoSelect) {
        cursoSelect.addEventListener('change', filtrarMaterias);
    }

    if (estudianteSelect && cursoSelect) {
        estudianteSelect.addEventListener('change', () => {
            const opcion = estudianteSelect.selectedOptions[0];
            const cursoEstudiante = opcion ? (opcion.dataset.cursoId || '') : '';
            if (cursoEstudiante && cursoEstudiante !== cursoSelect.value) {
                cursoSelect.value = cursoEstudiante;
                if (filtrosDependientesDisponibles) {
                    cursoSelect.dispatchEvent(new Event('change'));
                    return;
                }
                filtrarMaterias();
            }
        });
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', aplicarTipoRegistro);
    }

    filtrarMaterias();
    aplicarTipoRegistro();

    // ── AJAX sin recarga para celdas del boletín ────────────────────────────────
    var boletinArea = document.getElementById('boletin-scroll-area');
    if (!boletinArea) return;

    // Registrar valor de referencia en cada nota-input para detectar cambios al salir
    boletinArea.querySelectorAll('.nota-input').forEach(function (inp) {
        inp.dataset.valorOriginal = inp.value;
    });

    // Bloquear submit nativo (ej: Enter en nota-input) y disparar AJAX desde aquí
    boletinArea.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.classList.contains('nota-form') && !form.classList.contains('avance-form')) return;
        e.preventDefault();
        enviarAjax(form, form.closest('td'));
    });

    // ── Delegación: change → selects de avance ──────────────────────────────────
    boletinArea.addEventListener('change', function (e) {
        var el = e.target;
        if (el.tagName !== 'SELECT') return;
        var form = el.closest('.avance-form');
        if (form) enviarAjax(form, el.closest('td'));
    });

    // ── Delegación: focusout → nota-input (solo si el valor cambió) ─────────────
    boletinArea.addEventListener('focusout', function (e) {
        var el = e.target;
        if (!el.classList.contains('nota-input')) return;
        if (el.value === (el.dataset.valorOriginal || '')) return;
        var form = el.closest('.nota-form');
        if (form) enviarAjax(form, el.closest('td'));
    });

    // Mapa para evitar envíos simultáneos por la misma celda
    var enVuelo = new WeakMap();

    function enviarAjax(form, celda) {
        if (enVuelo.get(form)) return;
        enVuelo.set(form, true);
        setCeldaEstado(celda, 'guardando');

        var data = new FormData(form);
        data.set('ajax_nota', '1');

        // Usar la URL actual de la página (con query params) para que la sesión y filtros se conserven
        var url = window.location.pathname;

        fetch(url, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
        .then(function (r) {
            // Si el servidor redirige (sesión expirada), r.redirected será true
            if (r.redirected) {
                window.location.href = r.url;
                throw new Error('Sesión expirada, redirigiendo...');
            }
            // Verificar Content-Type para asegurar que es JSON
            var ct = r.headers.get('content-type') || '';
            if (ct.indexOf('application/json') === -1) {
                throw new Error('Respuesta inesperada del servidor (no es JSON). ¿Sesión expirada?');
            }
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (res) {
            enVuelo.set(form, false);
            if (res.success) {
                setCeldaEstado(celda, 'ok');

                // Actualizar baseline para que el siguiente focusout no reenvíe
                var notaInp = form.querySelector('.nota-input');
                if (notaInp) notaInp.dataset.valorOriginal = notaInp.value;

                // Primer guardado: pasar de insertar_nota → actualizar_nota
                if (res.nota_id) {
                    var notaIdHidden = form.querySelector('input[name="nota_id"]');
                    if (!notaIdHidden) {
                        notaIdHidden = document.createElement('input');
                        notaIdHidden.type = 'hidden';
                        notaIdHidden.name = 'nota_id';
                        form.appendChild(notaIdHidden);
                    }
                    notaIdHidden.value = res.nota_id;

                    var insertarHidden = form.querySelector('input[name="insertar_nota"]');
                    if (insertarHidden) insertarHidden.name = 'actualizar_nota';
                }
            } else {
                setCeldaEstado(celda, 'error', res.error || 'Error al guardar');
            }
        })
        .catch(function (err) {
            enVuelo.set(form, false);
            var msg = (err && err.message) ? err.message : 'Error de conexión';
            setCeldaEstado(celda, 'error', msg);
            console.error('[Notas AJAX]', msg, err);
        });
    }

    function setCeldaEstado(celda, estado, msg) {
        if (!celda) return;
        celda.classList.remove('nota-cell--guardando', 'nota-cell--ok', 'nota-cell--error');
        if (estado === 'guardando') {
            celda.classList.add('nota-cell--guardando');
        } else if (estado === 'ok') {
            celda.classList.add('nota-cell--ok');
            setTimeout(function () { celda.classList.remove('nota-cell--ok'); }, 1600);
        } else if (estado === 'error') {
            celda.classList.add('nota-cell--error');
            var inp = celda.querySelector('.nota-input, .avance-select');
            if (inp && msg) inp.title = msg;
            setTimeout(function () { celda.classList.remove('nota-cell--error'); }, 3000);
        }
    }
});
