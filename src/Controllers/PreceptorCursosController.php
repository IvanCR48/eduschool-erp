<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Services\ServicioPreceptorCursos;

/**
 * Orquesta POST/PRG y datos de vista para advisors.php (cursos por preceptor).
 */
class PreceptorCursosController extends BaseService
{
    private ServicioPreceptorCursos $servicio;

    public function __construct(DatabaseInterface $database, ServicioPreceptorCursos $servicio)
    {
        parent::__construct($database);
        $this->servicio = $servicio;
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    public function procesarPost(array $post): array
    {
        $pcAction = (string) ($post['preceptor_curso_action'] ?? '');
        if ($pcAction !== 'agregar' && $pcAction !== 'quitar') {
            return ['redirect' => null, 'error' => ''];
        }

        $tablaOk = $this->servicio->esTablaPreceptorCursoDisponible();
        if (($pcAction === 'agregar' || $pcAction === 'quitar') && !$tablaOk) {
            return ['redirect' => null, 'error' => $this->servicio->mensajeMigracionTablaAusente()];
        }

        try {
            $miembroId = isset($post['miembro_id']) ? (int) $post['miembro_id'] : 0;
            $cursoId = isset($post['curso_id']) ? (int) $post['curso_id'] : 0;

            if ($pcAction === 'agregar') {
                $this->servicio->agregarCursoAPreceptor($miembroId, $cursoId);
                $path = 'advisors.php?success=agregar';

                return ['redirect' => function_exists('app_base_path') ? app_base_path($path) : $path, 'error' => ''];
            }

            $this->servicio->quitarCursoDePreceptor($miembroId, $cursoId);
            $path = 'advisors.php?success=quitar';

            return ['redirect' => function_exists('app_base_path') ? app_base_path($path) : $path, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['redirect' => null, 'error' => $e->getMessage()];
        } catch (\Throwable) {
            return [
                'redirect' => null,
                'error' => 'No se pudo actualizar la asignación de cursos. Si el problema continúa, contactá al administrador del sistema.',
            ];
        }
    }

    /**
     * @return array{
     *   tabla_preceptor_curso_ok: bool,
     *   preceptores: list<array<string, mixed>>,
     *   cursos_disponibles: list<array<string, mixed>>,
     *   filas_preceptor: list<array{
     *     p: array<string, mixed>,
     *     eid: int,
     *     lista: list<array<string, mixed>>,
     *     idsAsignados: list<int>,
     *     opciones_libres: int,
     *     cursos_para_agregar: list<array{id: int, etiqueta: string}>,
     *     cursos_quitar: list<array{curso_id: int, etiqueta: string}>,
     *     apellido_display: string,
     *     nombre_display: string
     *   }>,
     *   total_preceptores: int,
     *   preceptores_sin_cursos: int,
     *   total_cursos_activos: int
     * }
     */
    public function datosVista(): array
    {
        $tablaOk = $this->servicio->esTablaPreceptorCursoDisponible();
        $preceptores = $this->servicio->listarPreceptoresActivos();
        $cursosPorId = $this->servicio->mapaCursosPorEquipoDirectivoId();
        $cursosDisponibles = [];
        foreach ($this->servicio->listarCursosActivos() as $c) {
            $c['etiqueta'] = $this->servicio->etiquetaCurso($c);
            $cursosDisponibles[] = $c;
        }
        $filas = $this->servicio->construirFilasPreceptorVista($preceptores, $cursosPorId, $cursosDisponibles);
        $filasVista = $this->servicio->enriquecerFilasPreceptorParaVista($filas, $cursosDisponibles);

        $sinCursos = 0;
        foreach ($filasVista as $fp) {
            if ($fp['cursos_quitar'] === []) {
                ++$sinCursos;
            }
        }

        return [
            'tabla_preceptor_curso_ok' => $tablaOk,
            'preceptores' => $preceptores,
            'cursos_disponibles' => $cursosDisponibles,
            'filas_preceptor' => $filasVista,
            'total_preceptores' => count($filas),
            'preceptores_sin_cursos' => $sinCursos,
            'total_cursos_activos' => count($cursosDisponibles),
        ];
    }
}
