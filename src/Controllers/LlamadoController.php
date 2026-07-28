<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioLlamados;
use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;
use DateTime;

/**
 * Controller para manejar las peticiones HTTP relacionadas con llamados de atención
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para llamados de atención.
 */
class LlamadoController extends BaseService
{
    /** @var list<string> */
    private const MOTIVOS_REGISTRO = [
        'Disrespect toward teacher',
        'Inappropriate device use',
        'Verbal aggression toward peer',
        'Physical aggression',
        'Missing school supplies',
        'Incomplete assignments',
        'Inappropriate classroom behavior',
        'Leaving classroom without permission',
        'Vandalism',
        'Other',
    ];

    /** @var list<string> */
    private const SANCIONES_REGISTRO = [
        'Verbal warning',
        'Written warning',
        '1-day suspension',
        '3-day suspension',
        '5-day suspension',
        '10-day suspension',
        'Parent meeting',
        'Counseling referral',
    ];

    private ServicioLlamados $servicioLlamados;
    private ServicioEstudiantes $servicioEstudiantes;

    public function __construct(DatabaseInterface $database, ServicioLlamados $servicioLlamados, ServicioEstudiantes $servicioEstudiantes)
    {
        parent::__construct($database);
        $this->servicioLlamados = $servicioLlamados;
        $this->servicioEstudiantes = $servicioEstudiantes;
    }

    /**
     * Maneja la petición POST para registrar un nuevo llamado
     */
    public function registrar(array $datos, array $cursosPermitidosPreceptor = []): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosRegistro($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Registrar el llamado
            $llamado = $this->servicioLlamados->registrarLlamado(
                $datos['estudiante_id'],
                $datos['motivo'],
                $datos['descripcion'],
                $datos['usuario_id'],
                $datos['sancion'] ?? null,
                $cursosPermitidosPreceptor
            );
            
            return [
                'success' => true,
                'data' => $llamado->toArray(),
                'message' => 'Llamado registrado exitosamente'
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'registrar_llamado',
                'estudiante_id' => $datos['estudiante_id'] ?? null,
                'usuario_id' => $datos['usuario_id'] ?? null
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener llamados de un estudiante
     */
    public function obtenerPorEstudiante(int $estudianteId): array
    {
        try {
            $llamados = $this->servicioLlamados->obtenerPorEstudiante($estudianteId);
            
            return [
                'success' => true,
                'data' => array_map(fn($llamado) => $llamado->toArray(), $llamados),
                'total' => count($llamados)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_llamados_estudiante',
                'estudiante_id' => $estudianteId
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener llamados recientes
     */
    public function recientes(int $dias = 7): array
    {
        try {
            $llamados = $this->servicioLlamados->obtenerRecientes($dias);
            
            return [
                'success' => true,
                'data' => $llamados, // Ya son arrays, no necesitamos convertir
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener llamados graves
     */
    public function graves(): array
    {
        try {
            $llamados = $this->servicioLlamados->obtenerGraves();
            
            return [
                'success' => true,
                'data' => array_map(fn($llamado) => $llamado->toArray(), $llamados),
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener llamados por período
     */
    public function porPeriodo(string $fechaInicio, string $fechaFin): array
    {
        try {
            $fechaInicioObj = new DateTime($fechaInicio);
            $fechaFinObj = new DateTime($fechaFin);
            
            $llamados = $this->servicioLlamados->obtenerPorPeriodo($fechaInicioObj, $fechaFinObj);
            
            return [
                'success' => true,
                'data' => array_map(fn($llamado) => $llamado->toArray(), $llamados),
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición PUT para aplicar una sanción
     */
    public function aplicarSancion(int $llamadoId, array $datos): array
    {
        try {
            if (empty($datos['sancion'])) {
                return [
                    'success' => false,
                    'error' => 'La sanción es requerida'
                ];
            }
            
            $llamado = $this->servicioLlamados->actualizarSancion($llamadoId, $datos['sancion']);
            
            return [
                'success' => true,
                'data' => $llamado->toArray(),
                'message' => 'Sanción aplicada exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un llamado
     */
    public function eliminar(int $llamadoId, array $cursosPermitidosPreceptor = []): array
    {
        try {
            $this->servicioLlamados->eliminarLlamadoConAlcancePreceptor($llamadoId, $cursosPermitidosPreceptor);
            
            return [
                'success' => true,
                'message' => 'Llamado eliminado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estadísticas de llamados
     */
    public function estadisticas(?int $estudianteId = null, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        try {
            $fechaInicioObj = $fechaInicio ? new DateTime($fechaInicio) : null;
            $fechaFinObj = $fechaFin ? new DateTime($fechaFin) : null;
            
            $estadisticas = $this->servicioLlamados->obtenerEstadisticas($estudianteId, $fechaInicioObj, $fechaFinObj);
            
            return [
                'success' => true,
                'data' => $estadisticas
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estudiantes problemáticos
     */
    public function estudiantesProblematicos(): array
    {
        try {
            $problematicos = $this->servicioLlamados->obtenerEstudiantesProblematicos();
            
            return [
                'success' => true,
                'data' => $problematicos,
                'total' => count($problematicos)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener resumen mensual
     */
    public function resumenMensual(int $mes, int $anio): array
    {
        try {
            $resumen = $this->servicioLlamados->obtenerResumenMensual($mes, $anio);
            
            return [
                'success' => true,
                'data' => $resumen
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar si un estudiante tiene llamados recientes
     */
    public function tieneLlamadosRecientes(int $estudianteId, int $dias = 30): array
    {
        try {
            $tieneLlamados = $this->servicioLlamados->tieneLlamadosRecientes($estudianteId, $dias);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'tiene_llamados_recientes' => $tieneLlamados,
                    'dias' => $dias
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar si un estudiante es problemático
     */
    public function esEstudianteProblematico(int $estudianteId): array
    {
        try {
            $esProblematico = $this->servicioLlamados->esEstudianteProblematico($estudianteId);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'es_problematico' => $esProblematico
                ]
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'es_estudiante_problematico',
                'estudiante_id' => $estudianteId
            ]);
        }
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return array<string, string>
     */
    public function formValuesRegistroLlamadoDesdePost(array $post): array
    {
        $sancion = isset($post['sancion']) && (string) $post['sancion'] !== '' ? (string) $post['sancion'] : '';

        return [
            'estudiante_id' => (string) (int) ($post['estudiante_id'] ?? 0),
            'fecha' => (string) ($post['fecha'] ?? ''),
            'motivo' => (string) ($post['motivo'] ?? ''),
            'descripcion' => (string) ($post['descripcion'] ?? ''),
            'sancion' => $sancion,
        ];
    }

    /**
     * Conserva filtros del listado en la URL tras POST (PRG).
     *
     * @param array<string, mixed> $queryGet
     */
    private function construirRedirectLlamadosTrasPrg(array $queryGet, string $success): string
    {
        $preservar = ['fecha_desde', 'fecha_hasta', 'curso', 'estudiante', 'motivo'];
        $params = ['success' => $success];
        foreach ($preservar as $clave) {
            if (!array_key_exists($clave, $queryGet)) {
                continue;
            }
            $v = $queryGet[$clave];
            if ($v === null || $v === '') {
                continue;
            }
            $params[$clave] = is_scalar($v) ? (string) $v : '';
        }

        return 'discipline.php?' . http_build_query($params);
    }

    /**
     * POST registrar / eliminar en discipline.php (PRG si tiene éxito).
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $queryGet query string de la petición POST (p. ej. $_GET) para conservar filtros al redirigir
     * @param list<int> $preceptorCids
     *
     * @return array{
     *   redirect: string|null,
     *   error: string,
     *   action: string|null,
     *   form_values: array<string, string>|null
     * }
     */
    public function procesarPostLlamados(array $post, array $preceptorCids, int $usuarioRegistroId, array $queryGet = []): array
    {
        $sinCambio = ['redirect' => null, 'error' => '', 'action' => null, 'form_values' => null];

        if (isset($post['registrar_llamado'])) {
            $uid = $usuarioRegistroId > 0 ? $usuarioRegistroId : 1;
            $data = [
                'estudiante_id' => (int) ($post['estudiante_id'] ?? 0),
                'fecha' => (string) ($post['fecha'] ?? ''),
                'motivo' => (string) ($post['motivo'] ?? ''),
                'descripcion' => (string) ($post['descripcion'] ?? ''),
                'sancion' => isset($post['sancion']) && (string) $post['sancion'] !== '' ? (string) $post['sancion'] : null,
                'usuario_id' => $uid,
            ];

            $formVals = $this->formValuesRegistroLlamadoDesdePost($post);

            try {
                $resultado = $this->registrar($data, $preceptorCids);
            } catch (\Throwable $e) {
                return [
                    'redirect' => null,
                    'error' => 'Error al registrar el llamado: ' . $e->getMessage(),
                    'action' => 'nuevo',
                    'form_values' => $formVals,
                ];
            }

            if (!empty($resultado['success'])) {
                return [
                    'redirect' => $this->construirRedirectLlamadosTrasPrg($queryGet, 'registrado'),
                    'error' => '',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            if (!empty($resultado['errors']) && is_array($resultado['errors'])) {
                return [
                    'redirect' => null,
                    'error' => implode("\n", $resultado['errors']),
                    'action' => 'nuevo',
                    'form_values' => $formVals,
                ];
            }

            return [
                'redirect' => null,
                'error' => (string) ($resultado['error'] ?? 'Error al registrar el llamado'),
                'action' => 'nuevo',
                'form_values' => $formVals,
            ];
        }

        if (isset($post['eliminar_llamado'])) {
            $lid = (int) ($post['llamado_id'] ?? 0);
            if ($lid <= 0) {
                return [
                    'redirect' => null,
                    'error' => 'Identificador de llamado no válido.',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            try {
                $resultadoEl = $this->eliminar($lid, $preceptorCids);
            } catch (\Throwable $e) {
                return [
                    'redirect' => null,
                    'error' => 'Error al eliminar el llamado: ' . $e->getMessage(),
                    'action' => null,
                    'form_values' => null,
                ];
            }

            if (!empty($resultadoEl['success'])) {
                return [
                    'redirect' => $this->construirRedirectLlamadosTrasPrg($queryGet, 'eliminado'),
                    'error' => '',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            return [
                'redirect' => null,
                'error' => (string) ($resultadoEl['error'] ?? 'No se pudo eliminar el llamado.'),
                'action' => null,
                'form_values' => null,
            ];
        }

        return $sinCambio;
    }

    /**
     * @return list<string>
     */
    public static function motivosRegistroLlamado(): array
    {
        return self::MOTIVOS_REGISTRO;
    }

    /**
     * @return list<string>
     */
    public static function sancionesRegistroLlamado(): array
    {
        return self::SANCIONES_REGISTRO;
    }

    /**
     * Valores por defecto / repoblación del formulario "nuevo llamado".
     *
     * @param array<string, string>|null $formValues
     *
     * @return array{fecha: string, motivo: string, sancion: string, descripcion: string}
     */
    public function prepararFormularioPrefillLlamado(?array $formValues): array
    {
        $f = $formValues ?? [];

        return [
            'fecha' => ($f['fecha'] ?? '') !== '' ? (string) $f['fecha'] : date('Y-m-d'),
            'motivo' => (string) ($f['motivo'] ?? ''),
            'sancion' => (string) ($f['sancion'] ?? ''),
            'descripcion' => (string) ($f['descripcion'] ?? ''),
        ];
    }

    /**
     * @param list<array<string, mixed>> $llamados
     *
     * @return list<array<string, mixed>>
     */
    private function enriquecerFilasLlamadosParaVista(array $llamados): array
    {
        $dias = [
            'Sunday' => 'Domingo',
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
        ];

        $out = [];
        foreach ($llamados as $llamado) {
            $ts = strtotime((string) ($llamado['fecha'] ?? ''));
            $fechaImpresa = $ts ? date('d/m/Y', $ts) : '';
            $diaEs = $ts ? ($dias[date('l', $ts)] ?? '') : '';
            $desc = (string) ($llamado['descripcion'] ?? '');
            $len = strlen($desc);
            $out[] = array_merge($llamado, [
                'vista_fecha_corta' => $fechaImpresa,
                'vista_dia_semana' => $diaEs,
                'vista_descripcion_resumen' => $len > 50 ? substr($desc, 0, 50) . '...' : $desc,
            ]);
        }

        return $out;
    }

    /**
     * Datos completos para la vista discipline.php (selectores, tabla, estadísticas).
     *
     * @param list<int> $preceptorCids
     * @param array<string, mixed> $queryGet
     * @param array<string, string>|null $formValuesRepoblacion tras error de validación en POST
     *
     * @return array<string, mixed>
     */
    public function datosVistaPagina(array $preceptorCids, array $queryGet, ?array $formValuesRepoblacion = null, int $page = 1, int $perPage = 20): array
    {
        $filtros = $this->servicioLlamados->normalizarFiltrosListadoLlamados($preceptorCids, $queryGet);

        $soloCursos = $preceptorCids === [] ? null : $preceptorCids;
        $cursos = $this->servicioLlamados->obtenerCursosParaSelectorLlamados($soloCursos);
        $estudiantes = $this->servicioLlamados->obtenerEstudiantesParaSelectorLlamados($soloCursos);

        $llamadosRaw = $this->servicioLlamados->buscarDetalladosPorFiltros($filtros);
        
        $totalFiltrado = count($llamadosRaw);
        $paginationSvc = new \SistemaAdmin\Services\PaginationService($this->database);
        $meta = $paginationSvc->calculatePagination($totalFiltrado, $page, $perPage);
        $pageNumbers = $paginationSvc->getPageNumbers((int) $meta['total_pages'], (int) $meta['current_page'], 7);
        $pagination = array_merge($meta, ['page_numbers' => $pageNumbers]);

        $slicedRaw = array_slice($llamadosRaw, (int) $meta['offset'], (int) $meta['page_size']);
        $llamados = $this->enriquecerFilasLlamadosParaVista($slicedRaw);

        $agregados = $this->servicioLlamados->agregarAgregadosListadoLlamados($llamadosRaw);

        $estudianteParam = (string) ($filtros['estudiante_id'] ?? '');
        if ($formValuesRepoblacion !== null) {
            $eid = (string) (int) ($formValuesRepoblacion['estudiante_id'] ?? 0);
            if ($eid !== '' && $eid !== '0') {
                $estudianteParam = $eid;
            }
        }

        $cursoPreseleccionado = '';
        if ($estudianteParam !== '') {
            foreach ($estudiantes as $est) {
                if ((string) $est['id'] === $estudianteParam) {
                    $cid = $est['curso_id'] ?? '';
                    $cursoPreseleccionado = ($cid === null || $cid === '') ? 'sin_curso' : (string) $cid;
                    break;
                }
            }
        }

        return array_merge([
            'cursos' => $cursos,
            'estudiantes_filtro' => $estudiantes,
            'estudiantes' => $estudiantes,
            'llamados' => $llamados,
            'fecha_desde' => $filtros['fecha_desde'],
            'fecha_hasta' => $filtros['fecha_hasta'],
            'motivo_filter' => $filtros['motivo'],
            'curso_filter' => $filtros['curso_id'],
            'estudiante_filter' => (string) ($filtros['estudiante_id'] ?? ''),
            'estudiante_id' => $estudianteParam,
            'curso_preseleccionado' => $cursoPreseleccionado,
            'form_prefill_llamado' => $this->prepararFormularioPrefillLlamado($formValuesRepoblacion),
            'motivos_opciones' => self::motivosRegistroLlamado(),
            'sanciones_opciones' => self::sancionesRegistroLlamado(),
            'total_filtrado' => $totalFiltrado,
            'pagination' => $pagination,
        ], $agregados);
    }

    /**
     * Valida los datos para registrar un llamado
     */
    private function validarDatosRegistro(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['estudiante_id'])) {
            $errores[] = 'El ID del estudiante es requerido';
        }
        
        if (empty($datos['motivo'])) {
            $errores[] = 'El motivo es requerido';
        }
        
        if (empty($datos['descripcion'])) {
            $errores[] = 'La descripción es requerida';
        }
        
        if (empty($datos['usuario_id'])) {
            $errores[] = 'El ID del usuario es requerido';
        }
        
        return $errores;
    }
}
