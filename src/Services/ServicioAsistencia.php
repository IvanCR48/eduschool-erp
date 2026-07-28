<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Mappers\AsistenciaMapper;

/**
 * ServicioAsistencia — lógica de negocio para el módulo de asistencia virtual.
 *
 * Responsabilidades:
 *   - Validar que los datos sean coherentes (alumnos del curso, estado válido).
 *   - Orquestar el mapper para persistencia.
 *   - Calcular métricas (% asistencia).
 *   - Manejar adjuntos de justificativos vía FileUploadService.
 */
class ServicioAsistencia extends BaseService
{
    /** Estados válidos en el sistema. */
    public const ESTADOS = [
        'Presente',
        'Tardanza',
        'Media falta',
        'Ausente justificado',
        'Ausente',
    ];

    /** Estados que cuentan como "asistió" para el cálculo de porcentaje. */
    public const ESTADOS_PRESENTES = ['Presente', 'Tardanza'];

    private AsistenciaMapper $mapper;

    public function __construct(DatabaseInterface $database, AsistenciaMapper $mapper)
    {
        parent::__construct($database);
        $this->mapper = $mapper;
    }

    // ------------------------------------------------------------------ //
    // Guardado
    // ------------------------------------------------------------------ //

    /**
     * Guarda la asistencia completa de un curso para una fecha dada.
     *
     * @param array<int, string>      $estados       [estudiante_id => estado]
     * @param array<int, string|null> $observaciones [estudiante_id => texto|null]
     * @param array<int, string|null> $adjuntos      [estudiante_id => path|null] (ya procesados)
     * @param list<int>               $activos       IDs válidos del curso (ya validados por el controller)
     */
    public function guardar(
        int    $cursoId,
        int    $materiaId,
        string $fechaYmd,
        array  $estados,
        array  $observaciones,
        array  $adjuntos,
        array  $activos,
        int    $usuarioId,
        ?string $grupoTaller = null
    ): int {
        if (!$this->mapper->materiaPerteneceACurso($materiaId, $cursoId)) {
            throw new \InvalidArgumentException('La materia seleccionada no pertenece al curso indicado.');
        }

        if ($activos === []) {
            return 0;
        }

        $permitido = array_fill_keys($activos, true);
        $registros = [];

        foreach ($activos as $eid) {
            if (!isset($estados[$eid])) {
                continue;
            }
            $estadoRaw = trim((string) $estados[$eid]);
            if (!in_array($estadoRaw, self::ESTADOS, true)) {
                continue;
            }
            $estado = $estadoRaw;

            $obs = isset($observaciones[$eid]) ? mb_substr(trim((string) $observaciones[$eid]), 0, 500) : null;
            if ($obs === '') {
                $obs = null;
            }

            $adj = isset($adjuntos[$eid]) ? (string) $adjuntos[$eid] : null;
            if ($adj === '') {
                $adj = null;
            }

            $registros[] = [
                'estudiante_id' => $eid,
                'estado'        => $estado,
                'observacion'   => $obs,
                'adjunto'       => $adj,
            ];
        }

        $estadosAntes = [];
        foreach ($this->mapper->filasPorCursoYFecha($cursoId, $fechaYmd, $materiaId, $grupoTaller) as $fila) {
            $eidF = (int) ($fila['id'] ?? 0);
            if ($eidF > 0 && isset($permitido[$eidF])) {
                $estadosAntes[$eidF] = [
                    'estado' => $fila['estado'] ?? null,
                    'observacion' => $fila['observacion'] ?? null,
                ];
            }
        }

        $this->mapper->upsertRegistros($registros, $cursoId, $materiaId, $fechaYmd, $usuarioId, $grupoTaller);

        $cambios = [];
        foreach ($registros as $r) {
            $eidC = (int) $r['estudiante_id'];
            $antes = $estadosAntes[$eidC] ?? ['estado' => null, 'observacion' => null];
            $despues = [
                'estado' => $r['estado'],
                'observacion' => $r['observacion'] ?? null,
            ];
            $mismoEstado = (string) ($antes['estado'] ?? '') === (string) ($despues['estado'] ?? '');
            $mismaObs = (string) ($antes['observacion'] ?? '') === (string) ($despues['observacion'] ?? '');
            if (!$mismoEstado || !$mismaObs) {
                $cambios[] = [
                    'estudiante_id' => $eidC,
                    'before' => $antes,
                    'after' => $despues,
                ];
            }
        }

        $this->registrarAuditoria('GUARDAR_ASISTENCIA', 'asistencia_virtual', $cursoId, [
            'fecha' => $fechaYmd,
            'curso_id' => $cursoId,
            'materia_id' => $materiaId,
            'registros_guardados' => count($registros),
            'cambios' => $cambios,
            'cambios_count' => count($cambios),
        ]);

        return count($registros);
    }

    // ------------------------------------------------------------------ //
    // Métricas
    // ------------------------------------------------------------------ //

    /**
     * Calcula el porcentaje de asistencia de un alumno.
     * Considera "asistió" a Presente y Tardanza.
     * Devuelve -1.0 si no hay registros.
     */
    public function porcentajeAsistencia(
        int     $estudianteId,
        ?string $desde = null,
        ?string $hasta = null,
        ?int    $materiaId = null
    ): float {
        $r = $this->mapper->resumenPorEstudiante($estudianteId, $desde, $hasta, $materiaId);
        if ($r['total'] === 0) {
            return -1.0;
        }
        $asistio = (float) $r['presente'] + ((float) $r['tardanza'] * 0.75) + ((float) $r['media_falta'] * 0.5);
        return round($asistio / $r['total'] * 100, 1);
    }

    /**
     * Resumen de asistencia de todos los alumnos de un curso.
     * Agrega el porcentaje calculado a cada fila.
     *
     * @return list<array<string, mixed>>
     */
    public function resumenCurso(
        int $cursoId,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $materiaId = null,
        ?string $grupoTaller = null
    ): array {
        $filas = $this->mapper->resumenPorCurso($cursoId, $desde, $hasta, $materiaId, $grupoTaller);
        foreach ($filas as &$fila) {
            $total = (int) ($fila['total'] ?? 0);
            if ($total === 0) {
                $fila['porcentaje'] = -1.0;
            } else {
                $asistio = (float) ($fila['presente'] ?? 0) + ((float) ($fila['tardanza'] ?? 0) * 0.75) + ((float) ($fila['media_falta'] ?? 0) * 0.5);
                $fila['porcentaje'] = round($asistio / $total * 100, 1);
            }
        }
        unset($fila);
        return $filas;
    }

    // ------------------------------------------------------------------ //
    // Adjuntos
    // ------------------------------------------------------------------ //

    /**
     * Procesa la subida de un archivo de justificativo para un alumno.
     * Devuelve el path relativo guardado, o null si no había archivo.
     */
    public function procesarAdjunto(array $fileData): ?string
    {
        if (empty($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            return null;
        }

        $uploadService = new FileUploadService($this->database);
        $resultado = $uploadService->uploadFile($fileData, 'justificativos');

        if (!$resultado['success']) {
            throw new \RuntimeException(
                'No se pudo subir el archivo adjunto: ' . ($resultado['error'] ?? 'Error desconocido')
            );
        }

        // Retornamos solo el nombre de archivo seguro (relativo a uploads/justificativos/)
        return 'justificativos/' . $resultado['filename'];
    }

    // ------------------------------------------------------------------ //
    // Helpers de acceso al mapper (para el controller)
    // ------------------------------------------------------------------ //

    /** @return list<int> */
    public function idsActivosPorCurso(int $cursoId): array
    {
        return $this->mapper->idsActivosPorCurso($cursoId);
    }

    public function idsActivosParaAsistenciaMateria(int $cursoId, int $materiaId, int $schoolYear, ?string $grupoTaller = null): array
    {
        return $this->mapper->idsActivosParaAsistenciaMateria($cursoId, $materiaId, $schoolYear, $grupoTaller);
    }

    public function tablaAsistenciaExiste(): bool
    {
        return $this->mapper->tablaAsistenciaExiste();
    }

    /**
     * @param list<int>|null $scopeCursos
     * @return list<array{id:int,anio:int,division:string,especialidad:string}>
     */
    public function cursosActivosParaAsistencia(?array $scopeCursos = null): array
    {
        return $this->mapper->cursosActivosParaAsistencia($scopeCursos);
    }

    /**
     * @return list<array{id:int,nombre:string}>
     */
    public function materiasActivasPorCurso(int $cursoId): array
    {
        return $this->mapper->materiasActivasPorCurso($cursoId);
    }

    /**
     * @return list<array{id:int,apellido:string,nombre:string}>
     */
    public function alumnosActivosPorCurso(int $cursoId): array
    {
        return $this->mapper->alumnosActivosPorCurso($cursoId);
    }

    public function materiaPerteneceACurso(int $materiaId, int $cursoId): bool
    {
        return $this->mapper->materiaPerteneceACurso($materiaId, $cursoId);
    }

    /**
     * @return list<string>
     */
    public function diasConHorarioMateria(int $cursoId, int $materiaId): array
    {
        return $this->mapper->diasConHorarioMateria($cursoId, $materiaId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filasPorCursoYFecha(int $cursoId, string $fechaYmd, int $materiaId, ?string $grupoTaller = null): array
    {
        return $this->mapper->filasPorCursoYFecha($cursoId, $fechaYmd, $materiaId, $grupoTaller);
    }

    /**
     * Umbral porcentual por debajo del cual un alumno está en riesgo.
     */
    public const UMBRAL_RIESGO = 75.0;

    // ------------------------------------------------------------------ //
    // Riesgo / dashboard
    // ------------------------------------------------------------------ //

    /**
     * @return list<array<string, mixed>>
     */
    public function alumnosEnRiesgo(int $cursoId, ?string $desde = null, ?string $hasta = null): array
    {
        $anio  = (int) date('Y');
        $desde = $desde ?? ($anio . '-01-01');
        $hasta = $hasta ?? date('Y-m-d');
        return $this->mapper->alumnosEnRiesgo($cursoId, self::UMBRAL_RIESGO, $desde, $hasta);
    }

    /**
     * @param  list<int>|null $cursosIds
     * @return list<array<string, mixed>>
     */
    public function alumnosEnRiesgoGlobal(?array $cursosIds = null, ?string $desde = null, ?string $hasta = null): array
    {
        $anio  = (int) date('Y');
        $desde = $desde ?? ($anio . '-01-01');
        $hasta = $hasta ?? ($anio . '-12-31');
        return $this->mapper->alumnosEnRiesgoGlobal($cursosIds, self::UMBRAL_RIESGO, $desde, $hasta);
    }

    /**
     * @param  list<int>|null $cursosIds
     * @return list<array<string, mixed>>
     */
    public function resumenDivisionesHoy(string $fecha, ?array $cursosIds = null): array
    {
        return $this->mapper->resumenDivisionesHoy($fecha, $cursosIds);
    }

    /**
     * @param  list<int>|null $cursosIds
     * @return list<array<string, mixed>>
     */
    public function justificadosDelDia(string $fecha, ?array $cursosIds = null): array
    {
        return $this->mapper->justificadosDelDia($fecha, $cursosIds);
    }

    // ------------------------------------------------------------------ //
    // Historial para la ficha del estudiante
    // ------------------------------------------------------------------ //

    /**
     * @return array{resumen: array<string,int|float>, historial: list<array<string,mixed>>}
     */
    public function datosAsistenciaFicha(
        int $estudianteId,
        ?string $desde = null,
        ?string $hasta = null,
        int $limiteHistorial = 20,
        ?int $materiaId = null
    ): array
    {
        $anio  = (int) date('Y');
        $desdeRango = $desde ?? ($anio . '-01-01');
        $hastaRango = $hasta ?? ($anio . '-12-31');

        $resumen   = $this->mapper->resumenPorEstudiante($estudianteId, $desdeRango, $hastaRango, $materiaId);
        $historial = $this->mapper->historialRecienteEstudiante($estudianteId, $limiteHistorial, $desdeRango, $hastaRango, $materiaId);

        $total   = $resumen['total'];
        $asistio = (float) $resumen['presente'] + ((float) $resumen['tardanza'] * 0.75) + ((float) $resumen['media_falta'] * 0.5);
        $resumen['porcentaje'] = $total > 0 ? round($asistio / $total * 100, 1) : -1.0;

        return ['resumen' => $resumen, 'historial' => $historial];
    }

    // ------------------------------------------------------------------ //
    // Métricas para Directivos
    // ------------------------------------------------------------------ //

    public function obtenerMetricasDirectivos(array $filtros): array
    {
        $fechaDiaria = $filtros['hasta'] ?? date('Y-m-d');
        
        $diariaGeneral   = $this->mapper->obtenerAsistenciaDiariaGeneral($fechaDiaria, $filtros);
        $diariaGrado     = $this->mapper->obtenerAsistenciaDiariaPorGrado($fechaDiaria, $filtros);
        $diariaDivision  = $this->mapper->obtenerAsistenciaDiariaPorDivision($fechaDiaria, $filtros);
        $diariaTurno     = $this->mapper->obtenerAsistenciaDiariaPorTurno($fechaDiaria, $filtros);
        
        $semanal         = $this->mapper->obtenerAsistenciaHistoricaSemanal($filtros);
        $mensual         = $this->mapper->obtenerAsistenciaHistoricaMensual($filtros);
        $anual           = $this->mapper->obtenerAsistenciaHistoricaAnual($filtros);
        
        $serieDiaria     = $this->mapper->obtenerTendenciaInasistenciasDiaria($filtros);
        $turnoAsistencia = $this->mapper->obtenerAsistenciaPorTurno($filtros);
        $rankingCursos   = $this->mapper->obtenerRankingAusentismoCursos($filtros);
        
        $tendencia       = $this->calcularTendenciaPorcentaje($serieDiaria);
        
        return [
            'diaria_general'      => $diariaGeneral,
            'diaria_grado'        => $diariaGrado,
            'diaria_division'     => $diariaDivision,
            'diaria_turno'        => $diariaTurno,
            'comparativa_semanal' => $semanal,
            'comparativa_mensual' => $mensual,
            'comparativa_anual'   => $anual,
            'serie_diaria'        => $serieDiaria,
            'asistencia_turno'    => $turnoAsistencia,
            'ranking_cursos'      => $rankingCursos,
            'tendencia'           => $tendencia,
            'fecha_diaria'        => $fechaDiaria
        ];
    }

    public function calcularTendenciaPorcentaje(array $serieDiaria): array
    {
        if (count($serieDiaria) < 2) {
            return ['direccion' => 'estable', 'valor' => 0.0, 'texto' => 'Datos insuficientes'];
        }

        $rates = [];
        foreach ($serieDiaria as $dia) {
            $total = (int) $dia['total'];
            $presentes = (int) $dia['presentes'];
            $rates[] = $total > 0 ? (($total - $presentes) / $total) * 100 : 0.0;
        }

        $totalDays = count($rates);
        $half = (int) floor($totalDays / 2);
        
        $chunkSize = min(7, $half);
        if ($chunkSize === 0) {
            $chunkSize = 1;
        }

        $lastPeriod = array_slice($rates, -$chunkSize);
        $prevPeriod = array_slice($rates, -2 * $chunkSize, $chunkSize);

        $avgLast = array_sum($lastPeriod) / count($lastPeriod);
        $avgPrev = array_sum($prevPeriod) / count($prevPeriod);

        $diff = $avgLast - $avgPrev;

        if (round($diff, 1) > 0.0) {
            return [
                'direccion' => 'sube',
                'valor'     => round($diff, 1),
                'texto'     => 'Subiendo ↑ ' . number_format(abs($diff), 1) . '% respecto al período anterior'
            ];
        } elseif (round($diff, 1) < 0.0) {
            return [
                'direccion' => 'baja',
                'valor'     => round(abs($diff), 1),
                'texto'     => 'Bajando ↓ ' . number_format(abs($diff), 1) . '% respecto al período anterior'
            ];
        } else {
            return [
                'direccion' => 'estable',
                'valor'     => 0.0,
                'texto'     => 'Estable (0.0% de cambio)'
            ];
        }
    }

}
