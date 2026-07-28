document.addEventListener('DOMContentLoaded', function () {
    const anioSelect = document.getElementById('anio');
    const especialidadSelect = document.getElementById('especialidad_id');

    if (!anioSelect || !especialidadSelect) {
        return;
    }

    const toggleEspecialidad = () => {
        const anio = parseInt(anioSelect.value, 10);
        const esSuperior = !isNaN(anio) && anio >= 4;

        especialidadSelect.disabled = !esSuperior;

        if (!esSuperior) {
            especialidadSelect.value = '';
        }
    };

    anioSelect.addEventListener('change', toggleEspecialidad);
    toggleEspecialidad();
});
