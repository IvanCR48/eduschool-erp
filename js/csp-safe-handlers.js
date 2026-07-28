/**
 * Sustituye onclick / onsubmit / onchange inline para cumplir CSP (script-src con nonce).
 * Archivo servido desde 'self'; no usa nonce en la etiqueta.
 */
(function () {
    'use strict';

    function nativeSubmit(form) {
        HTMLFormElement.prototype.submit.call(form);
    }

    /**
     * HTMLFormElement.submit() no incluye el botón pulsado. Si tras confirm() usamos submit()
     * sin reinyectar el name del botón, PHP no recibe p. ej. desactivar_materia (isset false).
     * Además e.submitter a veces es null (navegadores antiguos o casos límite): antes se hacía
     * submit() “vacío” y hacía falta pulsar de nuevo.
     */
    function ensureSubmitterFieldsThenNativeSubmit(form, subBtn) {
        var btn = subBtn;
        if (!btn || !btn.name) {
            var nodes = form.querySelectorAll('button, input[type="submit"]');
            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                if (!el.name) {
                    continue;
                }
                if (el.tagName === 'BUTTON' && el.type === 'button') {
                    continue;
                }
                btn = el;
                break;
            }
        }
        if (btn && btn.name) {
            var old = form.querySelector('input[type="hidden"][data-sa-confirm-submit]');
            if (old) {
                old.remove();
            }
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = btn.name;
            hid.value = btn.value || '';
            hid.setAttribute('data-sa-confirm-submit', '1');
            form.appendChild(hid);
        }
        nativeSubmit(form);
    }

    document.addEventListener(
        'submit',
        function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.classList.contains('js-confirm-submit')) {
                var formMsg = form.getAttribute('data-confirm-message');
                if (formMsg) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.confirm(formMsg)) {
                        ensureSubmitterFieldsThenNativeSubmit(form, e.submitter);
                    }
                    return;
                }
            }

            var sub = e.submitter;
            if (sub && sub.hasAttribute('data-confirm-message')) {
                var subMsg = sub.getAttribute('data-confirm-message');
                if (subMsg) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.confirm(subMsg)) {
                        ensureSubmitterFieldsThenNativeSubmit(form, sub);
                    }
                }
            }
        },
        true
    );

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.classList && t.classList.contains('js-submit-on-change') && t.form) {
            nativeSubmit(t.form);
        }
    });

    function cspToggleFormDisplay(formId) {
        var form = document.getElementById(formId);
        if (!form) {
            return;
        }
        var current = window.getComputedStyle(form).display;
        form.style.display = current === 'none' ? 'block' : 'none';
    }

    function cspShowModal(modalId, lockBody) {
        var el = document.getElementById(modalId);
        if (!el) {
            return;
        }
        el.style.display = 'block';
        if (lockBody) {
            document.body.style.overflow = 'hidden';
        }
    }

    function cspHideModal(modalId, lockBody) {
        var el = document.getElementById(modalId);
        if (!el) {
            return;
        }
        el.style.display = 'none';
        if (lockBody) {
            document.body.style.overflow = 'auto';
        }
    }

    document.addEventListener('click', function (e) {
        var cred = e.target.closest('[data-csp-toggle-credenciales]');
        if (cred) {
            e.preventDefault();
            var cid = cred.getAttribute('data-csp-toggle-credenciales');
            ['credenciales-' + cid, 'credenciales-tabla-' + cid].forEach(function (pid) {
                var panel = document.getElementById(pid);
                if (!panel) {
                    return;
                }
                var vis = panel.style.display === 'block';
                panel.style.display = vis ? 'none' : 'block';
            });
            return;
        }

        var tf = e.target.closest('[data-csp-toggle-form]');
        if (tf) {
            e.preventDefault();
            cspToggleFormDisplay(tf.getAttribute('data-csp-toggle-form'));
            return;
        }

        var sm = e.target.closest('[data-csp-show-modal]');
        if (sm) {
            e.preventDefault();
            cspShowModal(
                sm.getAttribute('data-csp-show-modal'),
                sm.getAttribute('data-csp-modal-lock-body') === '1'
            );
            return;
        }

        var hm = e.target.closest('[data-csp-hide-modal]');
        if (hm) {
            e.preventDefault();
            cspHideModal(
                hm.getAttribute('data-csp-hide-modal'),
                hm.getAttribute('data-csp-modal-lock-body') === '1'
            );
            return;
        }

        var rd = e.target.closest('[data-csp-reload]');
        if (rd) {
            e.preventDefault();
            window.location.reload();
        }

        var bo = e.target.closest('[data-csp-open-boletin]');
        if (bo) {
            e.preventDefault();
            var url = bo.getAttribute('data-csp-open-boletin');
            if (url) {
                window.open(url, '_blank', 'width=800,height=600');
            }
            return;
        }

        var delR = e.target.closest('.js-open-eliminar-responsable');
        if (delR) {
            e.preventDefault();
            var rid = delR.getAttribute('data-responsable-id');
            var rnom = delR.getAttribute('data-responsable-nombre') || '';
            var ri = document.getElementById('responsableId');
            var rn = document.getElementById('nombreResponsable');
            var rm = document.getElementById('modalEliminarResponsable');
            if (ri) {
                ri.value = rid;
            }
            if (rn) {
                rn.textContent = rnom;
            }
            if (rm) {
                rm.style.display = 'block';
            }
            return;
        }

        var delC = e.target.closest('.js-open-eliminar-contacto');
        if (delC) {
            e.preventDefault();
            var cid = delC.getAttribute('data-contacto-id');
            var cnom = delC.getAttribute('data-contacto-nombre') || '';
            var ci = document.getElementById('contactoId');
            var cn = document.getElementById('nombreContacto');
            var cm = document.getElementById('modalEliminarContacto');
            if (ci) {
                ci.value = cid;
            }
            if (cn) {
                cn.textContent = cnom;
            }
            if (cm) {
                cm.style.display = 'block';
            }
        }
    });
})();
