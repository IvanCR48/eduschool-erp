(function () {
    'use strict';

    function parseJsonAttr(el, name) {
        var raw = el.getAttribute(name);
        if (!raw) {
            return [];
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return [];
        }
    }

    function initCharts() {
        var root = document.getElementById('sa-dashboard-charts');
        if (!root || typeof Chart === 'undefined') {
            return;
        }

        var labelsAnio = parseJsonAttr(root, 'data-labels-anio');
        var datosAnio = parseJsonAttr(root, 'data-datos-anio');
        var conCurso = parseInt(root.getAttribute('data-est-con-curso') || '0', 10);
        var sinCurso = parseInt(root.getAttribute('data-est-sin-curso') || '0', 10);

        var i18nStudents = root.getAttribute('data-i18n-students') || 'Students';
        var i18nEnrolled = root.getAttribute('data-i18n-enrolled') || 'Enrolled in Class';
        var i18nUnassigned = root.getAttribute('data-i18n-unassigned') || 'Unassigned';

        var canvasAnio = document.getElementById('distribucionAnio');
        if (canvasAnio && labelsAnio.length > 0) {
            new Chart(canvasAnio.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labelsAnio,
                    datasets: [{
                        label: i18nStudents,
                        data: datosAnio,
                        backgroundColor: 'rgba(5, 150, 105, 0.8)',
                        borderColor: 'rgba(4, 120, 87, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        var canvasEstado = document.getElementById('estadoEstudiantes');
        if (canvasEstado) {
            new Chart(canvasEstado.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [i18nEnrolled, i18nUnassigned],
                    datasets: [{
                        data: [conCurso, sinCurso],
                        backgroundColor: [
                            'rgba(37, 99, 235, 0.85)',
                            'rgba(79, 70, 229, 0.65)'
                        ],
                        borderColor: [
                            'rgba(29, 78, 216, 1)',
                            'rgba(67, 56, 202, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }
})();
