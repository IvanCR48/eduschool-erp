<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Models\LlamadoAtencion;
use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Mappers\LlamadoMapper;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use SistemaAdmin\Contracts\DatabaseInterface;
use DateTime;

/**
 * Implementación concreta del ServicioLlamados
 * 
 * Contiene la lógica de negocio para la gestión de llamados de atención.
 * Implementa la interfaz IServicioLlamados.
 */
class ServicioLlamados extends BaseService
{
    private LlamadoMapper $llamadoMapper;
    private EstudianteMapper $estudianteMapper;

    public function __construct(DatabaseInterface $database, LlamadoMapper $llamadoMapper, EstudianteMapper $estudianteMapper)
    {
        parent::__construct($database);
        $this->llamadoMapper = $llamadoMapper;
        $this->estudianteMapper = $estudianteMapper;
    }

    public function registrarLlamado(
        int $estudianteId,
        string $motivo,
        string $descripcion,
        int $usuarioId,
        ?string $sancion = null,
        array $cursosPermitidosPreceptor = []
    ): LlamadoAtencion {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }

        $this->assertEstudianteEnAlcancePreceptor(
            $estudiante,
            $cursosPermitidosPreceptor,
            true,
            'No puede registrar llamados para estudiantes fuera de sus cursos asignados.'
        );
        
        // Validar que el motivo no esté vacío
        if (empty(trim($motivo))) {
            throw new \InvalidArgumentException("El motivo del llamado no puede estar vacío");
        }
        
        // Validar que la descripción no esté vacía
        if (empty(trim($descripcion))) {
            throw new \InvalidArgumentException("La descripción del llamado no puede estar vacía");
        }
        
        // Crear el llamado
        $llamado = new LlamadoAtencion($estudianteId, $motivo, $descripcion, $usuarioId, $sancion);

        // Guardar en la base de datos
        $guardado = $this->llamadoMapper->save($llamado);
        $lid = $guardado->getId();
        if ($lid !== null) {
            $this->registrarAuditoria('CREAR', 'llamado_atencion', $lid, [
                'after' => $guardado->toArray(),
            ]);
        }

        return $guardado;
    }

    public function obtenerPorEstudiante(int $estudianteId): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        return $this->llamadoMapper->findByEstudiante($estudianteId);
    }

    public function obtenerRecientes(int $dias = 7): array
    {
        return $this->llamadoMapper->findRecientes($dias);
    }

    /**
     * @param list<int>|null $soloIdsPreceptor null = todos los cursos (admin/directivo)
     * @return list<array<string, mixed>>
     */
    public function obtenerCursosParaSelectorLlamados(?array $soloIdsPreceptor): array
    {
        return $this->llamadoMapper->findCursosParaSelectorLlamados($soloIdsPreceptor);
    }

    /**
     * @param list<int>|null $soloCursosPreceptor null = todos los estudiantes activos
     * @return list<array<string, mixed>>
     */
    public function obtenerEstudiantesParaSelectorLlamados(?array $soloCursosPreceptor): array
    {
        return $this->llamadoMapper->findEstudiantesParaSelectorLlamados($soloCursosPreceptor);
    }

    /**
     * Filtros para findDetalladosParaVista (página, export, reportes).
     * Claves: fecha_desde, fecha_hasta, motivo, curso_id, estudiante_id, preceptor_curso_ids; opcional tiene_sancion (bool).
     *
     * @param array<string, mixed> $filtros
     * @return list<array<string, mixed>>
     */
    public function buscarDetalladosPorFiltros(array $filtros): array
    {
        $base = [
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'motivo' => '',
            'curso_id' => '',
            'estudiante_id' => '',
            'preceptor_curso_ids' => [],
        ];
        $merged = array_merge($base, $filtros);
        $merged['preceptor_curso_ids'] = array_values(array_map(
            static fn ($id): int => (int) $id,
            $merged['preceptor_curso_ids'] ?? []
        ));

        return $this->llamadoMapper->findDetalladosParaVista($merged);
    }

    /**
     * Ajusta curso_id según alcance preceptor (un curso fijo vs varios).
     *
     * @param array<string, mixed> $filtros debe incluir curso_id y preceptor_curso_ids
     * @return array<string, mixed>
     */
    public function aplicarAlcancePreceptorAFiltrosListado(array $filtros): array
    {
        $preceptorIds = $filtros['preceptor_curso_ids'] ?? [];
        if ($preceptorIds === []) {
            return $filtros;
        }
        if (count($preceptorIds) === 1) {
            $filtros['curso_id'] = (string) $preceptorIds[0];
        } else {
            $cf = (string) ($filtros['curso_id'] ?? '');
            if ($cf !== '' && in_array((int) $cf, $preceptorIds, true)) {
                $filtros['curso_id'] = (string) (int) $cf;
            } else {
                $filtros['curso_id'] = '';
            }
        }

        return $filtros;
    }

    /**
     * Misma semántica que la página discipline.php a partir de $_GET.
     *
     * @param list<int> $preceptorCids
     * @param array<string, mixed> $queryGet
     * @return array<string, mixed>
     */
    public function normalizarFiltrosListadoLlamados(array $preceptorCids, array $queryGet): array
    {
        $fechaDesde = isset($queryGet['fecha_desde']) ? trim((string) $queryGet['fecha_desde']) : date('Y-m-01');
        $fechaHasta = isset($queryGet['fecha_hasta']) ? trim((string) $queryGet['fecha_hasta']) : date('Y-m-d');
        if ($fechaDesde === '') {
            $fechaDesde = date('Y-m-01');
        }
        if ($fechaHasta === '') {
            $fechaHasta = date('Y-m-d');
        }

        $motivo = isset($queryGet['motivo']) ? trim((string) $queryGet['motivo']) : '';
        $estudianteParam = isset($queryGet['estudiante']) ? (string) $queryGet['estudiante'] : '';
        $cursoFilter = isset($queryGet['curso']) ? (string) $queryGet['curso'] : '';

        $filtros = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'motivo' => $motivo,
            'curso_id' => $cursoFilter,
            'estudiante_id' => $estudianteParam,
            'preceptor_curso_ids' => $preceptorCids,
        ];

        return $this->aplicarAlcancePreceptorAFiltrosListado($filtros);
    }

    /**
     * @param list<int> $preceptorCursoIds vacío si no es preceptor
     * @return list<array<string, mixed>>
     */
    public function buscarDetalladosParaVista(
        string $fechaDesde,
        string $fechaHasta,
        string $motivo,
        string $cursoId,
        string $estudianteId,
        array $preceptorCursoIds
    ): array {
        return $this->buscarDetalladosPorFiltros([
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'motivo' => $motivo,
            'curso_id' => $cursoId,
            'estudiante_id' => $estudianteId,
            'preceptor_curso_ids' => $preceptorCursoIds,
        ]);
    }

    /**
     * Totales y rankings a partir del listado ya filtrado (misma semántica que la vista histórica).
     *
     * @param list<array<string, mixed>> $llamados
     * @return array{
     *   total_llamados: int,
     *   llamados_hoy: int,
     *   llamados_con_sancion: int,
     *   llamados_sin_sancion: int,
     *   estudiantes_con_llamados: int,
     *   motivos_frecuentes: list<array{motivo: string, cantidad: int, pct_barra: float}>,
     *   sanciones_frecuentes: list<array{sancion: string, cantidad: int, pct_barra: float}>
     * }
     */
    public function agregarAgregadosListadoLlamados(array $llamados): array
    {
        $totalLlamados = count($llamados);
        $hoy = date('Y-m-d');
        $llamadosHoy = 0;
        $llamadosConSancion = 0;
        $llamadosSinSancion = 0;

        foreach ($llamados as $llamado) {
            if (($llamado['fecha'] ?? '') === $hoy) {
                $llamadosHoy++;
            }
            if (!empty($llamado['sancion'])) {
                $llamadosConSancion++;
            } else {
                $llamadosSinSancion++;
            }
        }

        $estudiantesConLlamados = count(array_unique(array_column($llamados, 'estudiante_id')));

        $motivosConteo = [];
        foreach ($llamados as $llamado) {
            $m = (string) ($llamado['motivo'] ?? '');
            $motivosConteo[$m] = ($motivosConteo[$m] ?? 0) + 1;
        }
        arsort($motivosConteo);
        $motivosFrecuentes = array_slice(array_map(
            static fn (string $motivo, int $cantidad): array => ['motivo' => $motivo, 'cantidad' => $cantidad],
            array_keys($motivosConteo),
            array_values($motivosConteo)
        ), 0, 10);

        $totalMotivosRank = 0;
        foreach ($motivosFrecuentes as $row) {
            $totalMotivosRank += (int) ($row['cantidad'] ?? 0);
        }
        foreach ($motivosFrecuentes as $i => $row) {
            $c = (int) ($row['cantidad'] ?? 0);
            $motivosFrecuentes[$i]['pct_barra'] = $totalMotivosRank > 0
                ? round(($c / $totalMotivosRank) * 100, 4)
                : 0.0;
        }

        $sancionesConteo = [];
        foreach ($llamados as $llamado) {
            if (!empty($llamado['sancion'])) {
                $s = (string) $llamado['sancion'];
                $sancionesConteo[$s] = ($sancionesConteo[$s] ?? 0) + 1;
            }
        }
        arsort($sancionesConteo);
        $sancionesFrecuentes = array_slice(array_map(
            static fn (string $sancion, int $cantidad): array => ['sancion' => $sancion, 'cantidad' => $cantidad],
            array_keys($sancionesConteo),
            array_values($sancionesConteo)
        ), 0, 10);

        $totalSancionesRank = 0;
        foreach ($sancionesFrecuentes as $row) {
            $totalSancionesRank += (int) ($row['cantidad'] ?? 0);
        }
        foreach ($sancionesFrecuentes as $i => $row) {
            $c = (int) ($row['cantidad'] ?? 0);
            $sancionesFrecuentes[$i]['pct_barra'] = $totalSancionesRank > 0
                ? round(($c / $totalSancionesRank) * 100, 4)
                : 0.0;
        }

        return [
            'total_llamados' => $totalLlamados,
            'llamados_hoy' => $llamadosHoy,
            'llamados_con_sancion' => $llamadosConSancion,
            'llamados_sin_sancion' => $llamadosSinSancion,
            'estudiantes_con_llamados' => $estudiantesConLlamados,
            'motivos_frecuentes' => $motivosFrecuentes,
            'sanciones_frecuentes' => $sancionesFrecuentes,
        ];
    }

    public function obtenerGraves(): array
    {
        return $this->llamadoMapper->findGraves();
    }

    public function obtenerPorPeriodo(DateTime $fechaInicio, DateTime $fechaFin): array
    {
        return $this->llamadoMapper->findByPeriodo($fechaInicio, $fechaFin);
    }

    public function actualizarSancion(int $llamadoId, string $sancion): LlamadoAtencion
    {
        // Buscar el llamado
        $llamado = $this->llamadoMapper->findById($llamadoId);
        if ($llamado === null) {
            throw new \InvalidArgumentException("No se encontró el llamado con ID: $llamadoId");
        }
        
        $antes = $llamado->toArray();

        // Aplicar la sanción
        $llamado->setSancion($sancion);
        $this->llamadoMapper->update($llamado);

        $this->registrarAuditoria('ACTUALIZAR', 'llamado_atencion', $llamadoId, [
            'before' => $antes,
            'after' => $llamado->toArray(),
        ]);

        return $llamado;
    }

    public function eliminarLlamado(int $llamadoId): bool
    {
        // Verificar que el llamado existe
        $llamado = $this->llamadoMapper->findById($llamadoId);
        if ($llamado === null) {
            throw new \InvalidArgumentException("No se encontró el llamado con ID: $llamadoId");
        }

        $antes = $llamado->toArray();
        $ok = $this->llamadoMapper->delete($llamadoId);
        if ($ok) {
            $this->registrarAuditoria('ELIMINAR', 'llamado_atencion', $llamadoId, ['before' => $antes]);
        }

        return $ok;
    }

    public function eliminarLlamadoConAlcancePreceptor(int $llamadoId, array $cursosPermitidosPreceptor): bool
    {
        $llamado = $this->llamadoMapper->findById($llamadoId);
        if ($llamado === null) {
            throw new \InvalidArgumentException("No se encontró el llamado con ID: $llamadoId");
        }

        $estudiante = $this->estudianteMapper->findById($llamado->getEstudianteId());
        if ($estudiante === null) {
            throw new \InvalidArgumentException('No se encontró el estudiante asociado al llamado.');
        }

        $this->assertEstudianteEnAlcancePreceptor(
            $estudiante,
            $cursosPermitidosPreceptor,
            false,
            'No puede eliminar llamados de estudiantes fuera de sus cursos asignados.'
        );

        $antes = $llamado->toArray();
        $ok = $this->llamadoMapper->delete($llamadoId);
        if ($ok) {
            $this->registrarAuditoria('ELIMINAR', 'llamado_atencion', $llamadoId, ['before' => $antes]);
        }

        return $ok;
    }

    /**
     * @param array<int> $cursosPermitidos
     */
    private function assertEstudianteEnAlcancePreceptor(
        Estudiante $estudiante,
        array $cursosPermitidos,
        bool $requiereEstudianteActivo,
        string $mensajeFueraDeAlcance
    ): void {
        if ($cursosPermitidos === []) {
            return;
        }

        if ($requiereEstudianteActivo && !$estudiante->esActivo()) {
            throw new \InvalidArgumentException($mensajeFueraDeAlcance);
        }

        $cid = (int) ($estudiante->getCursoId() ?? 0);
        if (!in_array($cid, $cursosPermitidos, true)) {
            throw new \InvalidArgumentException($mensajeFueraDeAlcance);
        }
    }

    public function obtenerEstadisticas(
        ?int $estudianteId = null,
        ?DateTime $fechaInicio = null,
        ?DateTime $fechaFin = null
    ): array {
        return $this->llamadoMapper->getEstadisticas($estudianteId, $fechaInicio, $fechaFin);
    }

    public function obtenerHistorialDisciplinario(int $estudianteId): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        // Obtener todos los llamados del estudiante
        $llamados = $this->obtenerPorEstudiante($estudianteId);
        
        // Calcular estadísticas
        $totalLlamados = count($llamados);
        $llamadosGraves = count(array_filter($llamados, fn($l) => $l->esGrave()));
        $conSancion = count(array_filter($llamados, fn($l) => !empty($l->getSancion())));
        
        // Agrupar por motivo
        $porMotivo = [];
        foreach ($llamados as $llamado) {
            $motivo = $llamado->getMotivo();
            if (!isset($porMotivo[$motivo])) {
                $porMotivo[$motivo] = 0;
            }
            $porMotivo[$motivo]++;
        }
        
        // Calcular tendencia (últimos 3 meses vs anteriores)
        $fechaActual = new DateTime();
        $fecha3Meses = new DateTime('-3 months');
        
        $llamadosRecientes = array_filter($llamados, function($l) use ($fecha3Meses) {
            return $l->getFecha() >= $fecha3Meses;
        });
        
        $llamadosAnteriores = array_filter($llamados, function($l) use ($fecha3Meses) {
            return $l->getFecha()< $fecha3Meses;
        });
        
        $tendencia = count($llamadosRecientes) > count($llamadosAnteriores) ? 'creciente' : 
                    (count($llamadosRecientes) < count($llamadosAnteriores) ? 'decreciente' : 'estable');
        
        return [
            'estudiante' => $estudiante,
            'total_llamados' => $totalLlamados,
            'llamados_graves' => $llamadosGraves,
            'con_sancion' => $conSancion,
            'por_motivo' => $porMotivo,
            'tendencia' => $tendencia,
            'llamados_recientes' => count($llamadosRecientes),
            'llamados_anteriores' => count($llamadosAnteriores),
            'ultimo_llamado' => !empty($llamados) ? $llamados[0]->getFecha() : null
        ];
    }

    public function tieneLlamadosRecientes(int $estudianteId, int $dias = 30): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        return $this->llamadoMapper->tieneLlamadosRecientes($estudianteId, $dias);
    }

    public function esEstudianteProblematico(int $estudianteId): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        // Un estudiante es problemático si tiene más de 3 llamados en los últimos 6 meses
        $fechaInicio = new DateTime('-6 months');
        $fechaFin = new DateTime();
        
        $estadisticas = $this->obtenerEstadisticas($estudianteId, $fechaInicio, $fechaFin);
        
        return $estadisticas['total_llamados'] > 3;
    }

    public function obtenerEstudiantesProblematicos(): array
    {
        $estudiantes = $this->estudianteMapper->findActive();
        $problematicos = [];
        
        foreach ($estudiantes as $estudiante) {
            if ($this->esEstudianteProblematico($estudiante->getId())) {
                $llamados = $this->obtenerPorEstudiante($estudiante->getId());
                $problematicos[] = [
                    'estudiante' => $estudiante,
                    'total_llamados' => count($llamados),
                    'llamados_graves' => count(array_filter($llamados, fn($l) => $l->esGrave())),
                    'ultimo_llamado' => !empty($llamados) ? $llamados[0]->getFecha() : null
                ];
            }
        }
        
        // Ordenar por total de llamados (descendente)
        usort($problematicos, fn($a, $b) => $b['total_llamados'] <=> $a['total_llamados']);
        
        return $problematicos;
    }

    public function obtenerResumenMensual(int $mes, int $anio): array
    {
        $fechaInicio = new DateTime("$anio-$mes-01");
        $fechaFin = new DateTime("$anio-$mes-" . $fechaInicio->format('t'));
        
        $llamados = $this->obtenerPorPeriodo($fechaInicio, $fechaFin);
        $estadisticas = $this->obtenerEstadisticas(null, $fechaInicio, $fechaFin);
        
        // Agrupar por día
        $porDia = [];
        foreach ($llamados as $llamado) {
            $dia = $llamado->getFecha()->format('d');
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = 0;
            }
            $porDia[$dia]++;
        }
        
        return [
            'mes' => $mes,
            'anio' => $anio,
            'total_llamados' => $estadisticas['total_llamados'],
            'llamados_graves' => $estadisticas['graves'],
            'con_sancion' => $estadisticas['con_sancion'],
            'por_dia' => $porDia,
            'promedio_diario' => count($llamados) / $fechaInicio->format('t')
        ];
    }

    /**
     * Método adicional para obtener llamados por tipo de motivo
     */
    public function obtenerLlamadosPorMotivo(string $motivo): array
    {
        $llamados = $this->llamadoMapper->findAll();
        
        return array_filter($llamados, function($llamado) use ($motivo) {
            return stripos($llamado->getMotivo(), $motivo) !== false;
        });
    }

    /**
     * Método adicional para validar si se puede registrar un llamado
     */
    public function puedeRegistrarLlamado(int $estudianteId): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            return false;
        }
        
        // Verificar que el estudiante esté activo
        if (!$estudiante->esActivo()) {
            return false;
        }
        
        return true;
    }
}
