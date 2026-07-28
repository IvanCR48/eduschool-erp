<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Services\ServicioMaterias;

/**
 * Orquesta HTTP/vista de materias (subjects.php).
 */
class MateriaController extends BaseService
{
    private ServicioMaterias $servicioMaterias;

    public function __construct(DatabaseInterface $database, ServicioMaterias $servicioMaterias)
    {
        parent::__construct($database);
        $this->servicioMaterias = $servicioMaterias;
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string}
     */
    public function procesarGuardarDesdePost(array $post): array
    {
        try {
            $nombre = isset($post['nombre']) ? (string) $post['nombre'] : '';
            $especialidadId = !empty($post['especialidad_id']) ? (int) $post['especialidad_id'] : null;
            $cursos = isset($post['cursos']) && is_array($post['cursos']) ? $post['cursos'] : [];
            $esTaller = isset($post['es_taller']) && (string) $post['es_taller'] !== '' ? 1 : 0;

            $this->servicioMaterias->crearMateriaConCursos($nombre, $especialidadId, $cursos, $esTaller);

            return ['success' => true, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Error al crear materia: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al crear materia: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string}
     */
    public function procesarDesactivarDesdePost(array $post): array
    {
        try {
            $id = isset($post['materia_id']) ? (int) $post['materia_id'] : 0;
            $this->servicioMaterias->desactivarMateria($id);

            return ['success' => true, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Error al desactivar: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al desactivar: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string}
     */
    public function procesarGestionarCursosDesdePost(array $post): array
    {
        try {
            $materiaId = isset($post['materia_id']) ? (int) $post['materia_id'] : 0;
            $cursosSeleccionados = isset($post['cursos']) && is_array($post['cursos'])
                ? $post['cursos']
                : [];

            $this->servicioMaterias->sincronizarCursosDeMateria($materiaId, $cursosSeleccionados);

            return ['success' => true, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Error al gestionar cursos: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al gestionar cursos: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{
     *   anio_min_especialidad: int,
     *   especialidades: list<array<string, mixed>>,
     *   cursos: list<array<string, mixed>>,
     *   materias: list<array<string, mixed>>,
     *   filtro_especialidad: string,
     *   filtro_especialidad_label: string,
     *   materia_gestionar: array<string, mixed>|null,
     *   cursos_disponibles: list<array<string, mixed>>,
     *   cursos_asignados_ids: list<int>
     * }
     */
    public function datosVista(string $filtroEspecialidad, ?array $materiaGestionar, int $page = 1, int $perPage = 20): array
    {
        $cursosDisponibles = [];
        $cursosAsignadosIds = [];

        $especialidades = $this->servicioMaterias->listarEspecialidadesActivas();

        if ($materiaGestionar !== null) {
            $mid = (int) $materiaGestionar['id'];
            $espId = !empty($materiaGestionar['especialidad_id']) ? (int) $materiaGestionar['especialidad_id'] : null;
            $cursosDisponibles = $this->servicioMaterias->listarCursosDisponiblesParaGestion($mid, $espId);
            $cursosAsignadosIds = $this->servicioMaterias->listarIdsCursosAsignadosAMateria($mid);
        }

        $todasMaterias = $this->servicioMaterias->listarMateriasParaAdministracion($filtroEspecialidad);
        $totalFiltrado = count($todasMaterias);

        $paginationSvc = new \SistemaAdmin\Services\PaginationService($this->database);
        $meta = $paginationSvc->calculatePagination($totalFiltrado, $page, $perPage);
        $pageNumbers = $paginationSvc->getPageNumbers((int) $meta['total_pages'], (int) $meta['current_page'], 7);
        $pagination = array_merge($meta, ['page_numbers' => $pageNumbers]);

        $materiasPaginadas = array_slice($todasMaterias, (int) $meta['offset'], (int) $meta['page_size']);

        return [
            'anio_min_especialidad' => ServicioMaterias::ANIO_MIN_ESPECIALIDAD,
            'especialidades' => $especialidades,
            'cursos' => $this->servicioMaterias->listarCursosActivosParaFormularioAlta(),
            'materias' => $materiasPaginadas,
            'total_filtrado' => $totalFiltrado,
            'pagination' => $pagination,
            'filtro_especialidad' => $filtroEspecialidad,
            'filtro_especialidad_label' => $this->etiquetaFiltroEspecialidad($filtroEspecialidad, $especialidades),
            'materia_gestionar' => $materiaGestionar,
            'cursos_disponibles' => $cursosDisponibles,
            'cursos_asignados_ids' => $cursosAsignadosIds,
        ];
    }

    /**
     * @param list<array<string, mixed>> $especialidades
     */
    private function etiquetaFiltroEspecialidad(string $filtro, array $especialidades): string
    {
        if ($filtro === '') {
            return '';
        }
        if ($filtro === 'sin_especialidad') {
            return 'Sin especialidad';
        }
        foreach ($especialidades as $esp) {
            if ((string) ($esp['id'] ?? '') === $filtro) {
                return (string) ($esp['nombre'] ?? '');
            }
        }

        return '';
    }
}
