<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Services\ServicioCursos;

/**
 * Orquesta HTTP/vista de gestión de cursos (courses.php).
 */
class CursoController extends BaseService
{
    private ServicioCursos $servicioCursos;
    private ?\SistemaAdmin\Services\ServicioEstudiantes $servicioEstudiantes;

    public function __construct(
        DatabaseInterface $database,
        ServicioCursos $servicioCursos,
        ?\SistemaAdmin\Services\ServicioEstudiantes $servicioEstudiantes = null
    ) {
        parent::__construct($database);
        $this->servicioCursos = $servicioCursos;
        $this->servicioEstudiantes = $servicioEstudiantes;
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string}
     */
    public function procesarGuardarCursoDesdePost(array $post): array
    {
        try {
            $this->servicioCursos->crearDesdeDatosFormulario($post);

            return ['success' => true, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Error al crear curso: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al crear curso: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string, message: string}
     */
    public function procesarEliminarCursoDesdePost(array $post, bool $puedeEliminarCursos): array
    {
        if (!$puedeEliminarCursos) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para eliminar cursos',
                'message' => '',
            ];
        }

        $cursoId = isset($post['curso_id']) ? (int) $post['curso_id'] : 0;
        $resultado = $this->servicioCursos->bajaCursoAdministracion($cursoId);

        if (!empty($resultado['success'])) {
            return [
                'success' => true,
                'error' => '',
                'message' => (string) ($resultado['message'] ?? 'Curso eliminado correctamente'),
            ];
        }

        return [
            'success' => false,
            'error' => (string) ($resultado['error'] ?? 'Error al eliminar curso'),
            'message' => '',
        ];
    }

    /**
     * @param list<int>|null $alcanceCids null = todos los cursos activos; [] o lista = filtro explícito
     *
     * @return array{
     *   cursos: list<array<string, mixed>>,
     *   cursos_por_grupo: list<array<string, mixed>>,
     *   anios: list<int>,
     *   divisiones: list<string>,
     *   especialidades: list<array<string, mixed>>,
     *   turnos: list<array<string, mixed>>,
     *   anio_filter: string,
     *   division_filter: string,
     *   especialidad_filter: string,
     *   total_cursos: int,
     *   total_estudiantes: int,
     *   cursos_sin_estudiantes: int
     * }
     */
    public function datosVistaGestion(?array $alcanceCids, string $anioFilter, string $divisionFilter, string $especialidadFilter): array
    {
        $anioFilter = trim($anioFilter);
        if ($anioFilter !== '' && !ctype_digit($anioFilter)) {
            $anioFilter = '';
        }
        $divisionFilter = trim($divisionFilter);
        $especialidadFilter = trim($especialidadFilter);
        if ($especialidadFilter !== '' && !ctype_digit($especialidadFilter)) {
            $especialidadFilter = '';
        }

        $cursos = $this->servicioCursos->listarParaVistaGestion($alcanceCids, $anioFilter, $divisionFilter, $especialidadFilter);
        $especialidades = $this->servicioCursos->listarEspecialidadesActivasOrdenadas();
        $turnos = $this->servicioCursos->listarTurnosOrdenados();

        $anios = $this->servicioCursos->listarAniosDisponibles();
        $divisiones = ['1', '2', '3', '4', '5', '6'];

        $totalCursos = count($cursos);
        $totalEstudiantes = (int) array_sum(array_column($cursos, 'cantidad_estudiantes'));
        $cursosSinEstudiantes = count(array_filter(
            $cursos,
            static fn (array $row): bool => (int) ($row['cantidad_estudiantes'] ?? 0) === 0
        ));

        // Get active groups for the selected course if both anio and division filters are active
        $cursosPorGrupo = [];
        if ($anioFilter !== '' && $divisionFilter !== '') {
            $cursoActivo = $this->servicioCursos->buscarCursoActivoPorAnioDivision((int)$anioFilter, $divisionFilter);
            if ($cursoActivo) {
                $servEst = $this->servicioEstudiantes ?? new \SistemaAdmin\Services\ServicioEstudiantes(
                    $this->database,
                    new \SistemaAdmin\Mappers\EstudianteMapper($this->database)
                );
                $cursosPorGrupo = $servEst->obtenerGruposActivosPorCurso((int)$cursoActivo['id']);
            }
        }

        return [
            'cursos' => $cursos,
            'cursos_por_grupo' => $cursosPorGrupo,
            'anios' => $anios,
            'divisiones' => $divisiones,
            'especialidades' => $especialidades,
            'turnos' => $turnos,
            'anio_filter' => $anioFilter,
            'division_filter' => $divisionFilter,
            'especialidad_filter' => $especialidadFilter,
            'total_cursos' => $totalCursos,
            'total_estudiantes' => $totalEstudiantes,
            'cursos_sin_estudiantes' => $cursosSinEstudiantes,
        ];
    }
}
