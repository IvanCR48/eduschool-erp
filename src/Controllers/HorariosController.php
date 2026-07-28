<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioHorarios;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Controller para manejar las peticiones HTTP relacionadas con horarios
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para horarios.
 */
class HorariosController extends BaseService
{
    private ServicioAutenticacion $servicioAutenticacion;
    private ServicioHorarios $servicioHorarios;

    public function __construct(
        DatabaseInterface $database,
        ServicioAutenticacion $servicioAutenticacion,
        ServicioHorarios $servicioHorarios
    ) {
        parent::__construct($database);
        $this->servicioAutenticacion = $servicioAutenticacion;
        $this->servicioHorarios = $servicioHorarios;
    }

    /**
     * Maneja la petición GET para listar horarios
     */
    public function listar(array $filtros = []): array
    {
        try {
            $horarios = $this->servicioHorarios->listarHorarios($filtros);

            return [
                'success' => true,
                'data' => $horarios,
                'total' => count($horarios),
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_horarios',
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener un horario por ID
     */
    public function obtener(int $horarioId): array
    {
        try {
            $horario = $this->servicioHorarios->obtenerHorarioPorId($horarioId);
            if ($horario === null) {
                return [
                    'success' => false,
                    'error' => 'Horario no encontrado',
                ];
            }

            return [
                'success' => true,
                'data' => $horario,
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_horario',
            ]);
        }
    }

    /**
     * Maneja la petición POST para crear un nuevo horario
     */
    public function crear(array $datos): array
    {
        try {
            if (!$this->servicioAutenticacion->tienePermiso('gestionar_horarios')) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para crear horarios',
                ];
            }

            $resultado = $this->servicioHorarios->crearHorario($datos);
            if (!$resultado['ok']) {
                return [
                    'success' => false,
                    'errors' => $resultado['errors'],
                ];
            }

            return [
                'success' => true,
                'data' => ['id' => $resultado['id']],
                'message' => 'Horario creado exitosamente',
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'crear_horario',
            ]);
        }
    }

    /**
     * Maneja la petición PUT para actualizar un horario
     */
    public function actualizar(int $horarioId, array $datos): array
    {
        try {
            if (!$this->servicioAutenticacion->tienePermiso('gestionar_horarios')) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para modificar horarios',
                ];
            }

            $resultado = $this->servicioHorarios->actualizarHorario($horarioId, $datos);
            if (!$resultado['ok']) {
                if (!empty($resultado['not_found'])) {
                    return [
                        'success' => false,
                        'error' => 'Horario no encontrado',
                    ];
                }

                return [
                    'success' => false,
                    'errors' => $resultado['errors'],
                ];
            }

            return [
                'success' => true,
                'data' => ['id' => $horarioId],
                'message' => 'Horario actualizado exitosamente',
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'actualizar_horario',
            ]);
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un horario
     */
    public function eliminar(int $horarioId): array
    {
        try {
            if (!$this->servicioAutenticacion->tienePermiso('gestionar_horarios')) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para eliminar horarios',
                ];
            }

            $resultado = $this->servicioHorarios->eliminarHorario($horarioId);
            if (!$resultado['ok']) {
                return [
                    'success' => false,
                    'error' => 'Horario no encontrado',
                ];
            }

            return [
                'success' => true,
                'message' => 'Horario eliminado exitosamente',
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'eliminar_horario',
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener datos para formularios
     */
    public function obtenerDatosFormularios(): array
    {
        try {
            $data = $this->servicioHorarios->obtenerDatosFormularios();

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener datos de formularios: ' . $e->getMessage(),
                'debug_trace' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Listado enriquecido, catálogos de formulario (con alcance preceptor/docente), diagnóstico y mapas para schedules.php.
     *
     * @param list<int>|null $alcanceCids null = sin filtro de curso; [] = sin cursos en alcance; lista = restricción
     *
     * @return array<string, mixed>
     */
    public function datosVistaGestion(string $cursoFilter, string $profesorFilter, ?array $alcanceCids): array
    {
        $filtros = [];
        if ($alcanceCids !== null && $alcanceCids === []) {
            $filtros['forzar_sin_filas'] = true;
        } else {
            if ($cursoFilter !== '') {
                $filtros['curso'] = $cursoFilter;
            } elseif ($alcanceCids !== null && count($alcanceCids) > 1) {
                $filtros['cursos'] = $alcanceCids;
            }
        }
        if ($profesorFilter !== '') {
            $filtros['profesor'] = $profesorFilter;
        }

        $resListar = $this->listar($filtros);
        $horariosRaw = $resListar['success'] ? ($resListar['data'] ?? []) : [];
        $horarios = $this->servicioHorarios->enriquecerFilasListadoHorariosParaVista($horariosRaw);

        $resForm = $this->obtenerDatosFormularios();
        $cursos = $resForm['success'] ? ($resForm['data']['cursos'] ?? []) : [];
        $cursos = $this->servicioHorarios->filtrarCursosPorAlcancePreceptor($cursos, $alcanceCids);
        $materias = $resForm['success'] ? ($resForm['data']['materias'] ?? []) : [];
        $profesoresFiltro = $resForm['success'] ? ($resForm['data']['profesores'] ?? []) : [];

        $debugErrorFormulario = '';
        if (!$resForm['success']) {
            $debugErrorFormulario = $resForm['error'] ?? 'Error desconocido en obtenerDatosFormularios';
        }

        $panel = $this->datosPanelDiagnosticoYFiltrosProfesores($cursos);

        $diasSemana = [
            'lunes' => __('day.monday'),
            'martes' => __('day.tuesday'),
            'miercoles' => __('day.wednesday'),
            'jueves' => __('day.thursday'),
            'viernes' => __('day.friday'),
            'sabado' => __('day.saturday'),
        ];

        $vistaSemanalPorCurso = $this->construirVistaSemanalHorariosPorCurso($horarios, $diasSemana);

        return array_merge([
            'horarios' => $horarios,
            'cursos' => $cursos,
            'materias' => $materias,
            'profesores_filtro' => $profesoresFiltro,
            'formularios_ok' => $resForm['success'],
            'debug_error_formulario' => $debugErrorFormulario,
            'total_horarios' => count($horarios),
            'dias_semana' => $diasSemana,
            'vista_semanal_por_curso' => $vistaSemanalPorCurso,
        ], $panel);
    }

    /**
     * Normaliza TIME de MySQL a HH:MM para comparar con opciones del formulario.
     */
    public static function normalizarHoraOpcion(?string $t): string
    {
        if ($t === null || $t === '') {
            return '';
        }
        $t = trim((string) $t);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return $t;
    }

    /**
     * Etiqueta de curso para selects del formulario (alta/edición).
     *
     * @param array<string, mixed> $curso
     */
    public static function etiquetaCursoFormularioLargo(array $curso): string
    {
        $anio = (int) ($curso['anio'] ?? 0);
        $div = htmlspecialchars((string) ($curso['division'] ?? ''), ENT_QUOTES, 'UTF-8');
        $turno = htmlspecialchars((string) ($curso['turno'] ?? ''), ENT_QUOTES, 'UTF-8');
        $turnoPart = $turno !== '' ? ' (' . $turno . ')' : '';
        if ($anio <= 3) {
            return $anio . '° ' . $div . $turnoPart;
        }
        $esp = htmlspecialchars((string) ($curso['especialidad'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ($esp === '') {
            $esp = __('student.no_specialty');
        }

        return $anio . '° ' . $div . ' - ' . $esp . $turnoPart;
    }

    /**
     * Etiqueta corta de curso para el filtro GET.
     *
     * @param array<string, mixed> $curso
     */
    public static function etiquetaCursoFiltro(array $curso): string
    {
        $anio = (int) ($curso['anio'] ?? 0);
        $div = htmlspecialchars((string) ($curso['division'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ($anio <= 3) {
            return $anio . '° ' . $div;
        }
        $esp = htmlspecialchars((string) ($curso['especialidad'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ($esp === '') {
            $esp = __('student.no_specialty');
        }

        return $anio . '° ' . $div . ' - ' . $esp;
    }

    /**
     * Agrupa y ordena horarios para la grilla semanal (evita lógica en la vista).
     *
     * Separa materias comunes (filas) de materias de taller agrupadas por grupo (grupos_taller).
     *
     * @param list<array<string, mixed>> $horarios
     * @param array<string, string>      $diasSemana clave día (lunes…) => etiqueta
     *
     * @return list<array{titulo_curso: string, turno_etiqueta: string, filas: list<array<string, mixed>>, grupos_taller: array<string, list<array<string, mixed>>>}>
     */
    public function construirVistaSemanalHorariosPorCurso(array $horarios, array $diasSemana): array
    {
        $horariosPorCurso = [];
        foreach ($horarios as $horario) {
            $espRaw = trim((string) ($horario['especialidad'] ?? ''));
            $especialidad = $espRaw !== '' ? $espRaw : __('student.no_specialty');
            $cursoKey = (string) ($horario['anio'] ?? '') . '° ' . (string) ($horario['division'] ?? '') . ' - ' . $especialidad;
            $horariosPorCurso[$cursoKey][] = $horario;
        }

        ksort($horariosPorCurso, SORT_NATURAL);

        $ordenDias = [
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
        ];

        foreach ($horariosPorCurso as &$horariosCurso) {
            usort($horariosCurso, static function (array $a, array $b) use ($ordenDias): int {
                $diaA = $ordenDias[$a['dia_semana'] ?? ''] ?? 99;
                $diaB = $ordenDias[$b['dia_semana'] ?? ''] ?? 99;
                if ($diaA === $diaB) {
                    return strcmp((string) ($a['hora_inicio'] ?? ''), (string) ($b['hora_inicio'] ?? ''));
                }

                return $diaA <=> $diaB;
            });
        }
        unset($horariosCurso);

        $bloques = [];
        foreach ($horariosPorCurso as $cursoNombre => $horariosCurso) {
            $turno = (string) ($horariosCurso[0]['turno'] ?? '');
            
            $horariosGeneral = [];
            $horariosTallerPorGrupo = [];

            foreach ($horariosCurso as $h) {
                $esTaller = (int) ($h['es_taller'] ?? 0);
                if ($esTaller === 1) {
                    $g = !empty($h['grupo_taller']) ? (string) $h['grupo_taller'] : 'A';
                    $horariosTallerPorGrupo[$g][] = $h;
                } else {
                    $horariosGeneral[] = $h;
                }
            }

            $filasGeneral = $this->generarGridFilas($horariosGeneral, $diasSemana);

            $gruposTaller = [];
            ksort($horariosTallerPorGrupo);
            foreach ($horariosTallerPorGrupo as $grupo => $subset) {
                $gruposTaller[$grupo] = $this->generarGridFilas($subset, $diasSemana);
            }

            $bloques[] = [
                'titulo_curso' => $cursoNombre,
                'turno_etiqueta' => $turno,
                'filas' => $filasGeneral,
                'grupos_taller' => $gruposTaller,
            ];
        }

        return $bloques;
    }

    /**
     * Genera la grilla de filas horarias para un conjunto de horarios dado.
     */
    private function generarGridFilas(array $horariosSubset, array $diasSemana): array
    {
        $porInicio = [];
        $horariosPorDia = [];
        $aMinutos = static function (string $hora): ?int {
            if (!preg_match('/^(\d{1,2}):(\d{2})/', $hora, $m)) {
                return null;
            }
            $h = (int) $m[1];
            $i = (int) $m[2];
            if ($h < 0 || $h >23 || $i< 0 || $i > 59) {
                return null;
            }
            return ($h * 60) + $i;
        };

        foreach ($horariosSubset as $h) {
            $inicio = (string) ($h['hora_inicio'] ?? '');
            if (!isset($porInicio[$inicio])) {
                $porInicio[$inicio] = [];
            }
            $diaSemana = (string) ($h['dia_semana'] ?? '');
            $porInicio[$inicio][$diaSemana] = $h;
            if (!isset($horariosPorDia[$diaSemana])) {
                $horariosPorDia[$diaSemana] = [];
            }
            $horariosPorDia[$diaSemana][] = $h;
        }

        $horaKeys = array_keys($porInicio);
        usort($horaKeys, static function (string $a, string $b): int {
            $ta = strtotime($a);
            $tb = strtotime($b);
            if ($ta === false) {
                return $tb === false ? 0 : 1;
            }
            if ($tb === false) {
                return -1;
            }

            return $ta <=> $tb;
        });

        $filas = [];
        foreach ($horaKeys as $horaInicio) {
            $porDia = [];
            $horaFilaMin = $aMinutos($horaInicio);
            foreach (array_keys($diasSemana) as $diaNum) {
                $bloqueDia = $porInicio[$horaInicio][$diaNum] ?? null;
                if ($bloqueDia === null && $horaFilaMin !== null && !empty($horariosPorDia[$diaNum])) {
                    foreach ($horariosPorDia[$diaNum] as $candidato) {
                        $minInicio = $aMinutos((string) ($candidato['hora_inicio'] ?? ''));
                        $minFin = $aMinutos((string) ($candidato['hora_fin'] ?? ''));
                        if ($minInicio === null || $minFin === null) {
                            continue;
                        }
                        if ($minInicio < $horaFilaMin && $horaFilaMin < $minFin) {
                            $bloqueDia = $candidato;
                            break;
                        }
                    }
                }
                $porDia[$diaNum] = $bloqueDia;
            }
            $ts = strtotime($horaInicio);
            $filas[] = [
                'hora_inicio_raw' => $horaInicio,
                'hora_inicio_corta' => $ts !== false ? date('H:i', $ts) : $horaInicio,
                'por_dia' => $porDia,
            ];
        }

        return $filas;
    }

    /**
     * Diagnóstico de catálogos + mapa profesores por curso (misma lógica que schedules.php).
     *
     * @param list<array<string, mixed>> $cursosVisibles cursos ya filtrados (p. ej. alcance preceptor)
     * @return array{
     *   db_nombre: string,
     *   cursos_total: int,
     *   cursos_activos: int,
     *   materias_activas: int,
     *   profesores_activos: int,
     *   profesores_por_curso: array<int, list<array<string, mixed>>>
     * }
     */
    public function datosPanelDiagnosticoYFiltrosProfesores(array $cursosVisibles): array
    {
        $diag = $this->servicioHorarios->obtenerDiagnosticoCatalogosHorarios();
        $diag['profesores_por_curso'] = $this->servicioHorarios->construirMapaProfesoresPorCursoDesdeHorarios($cursosVisibles);

        return $diag;
    }

    /**
     * Materias de un curso para el endpoint AJAX (autorización por rol preceptor).
     *
     * @param list<int> $preceptorCids Cursos del preceptor; vacío si el usuario no es preceptor
     * @return array{success: bool, data?: list<array<string, mixed>>, message?: string}
     */
    public function materiasPorCursoParaAjax(int $cursoId, string $rolUsuario, ?array $alcanceCids): array
    {
        if ($cursoId <= 0) {
            return [
                'success' => false,
                'message' => 'ID de curso inválido',
            ];
        }

        $puedeGestionar = $this->servicioAutenticacion->tienePermiso('gestionar_horarios');
        $esProfesorLectura = ($rolUsuario === 'profesor' && $this->servicioAutenticacion->tienePermiso('ver_horarios'));
        if (!$puedeGestionar && !$esProfesorLectura) {
            return [
                'success' => false,
                'message' => 'No autorizado',
            ];
        }

        if ($rolUsuario === 'preceptor') {
            if ($alcanceCids === null || !in_array($cursoId, $alcanceCids, true)) {
                return [
                    'success' => false,
                    'message' => 'No autorizado',
                ];
            }
        }

        if ($rolUsuario === 'profesor') {
            if ($alcanceCids === null || $alcanceCids === [] || !in_array($cursoId, $alcanceCids, true)) {
                return [
                    'success' => false,
                    'message' => 'No autorizado',
                ];
            }
        }

        try {
            $data = $this->servicioHorarios->listarMateriasActivasPorCurso($cursoId);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener materias: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Maneja la petición GET para obtener profesores por materia y curso
     */
    public function obtenerProfesoresPorMateria(int $materiaId, int $cursoId): array
    {
        try {
            $profesores = $this->servicioHorarios->listarDocentesParaHorario($materiaId, $cursoId);

            return [
                'success' => true,
                'data' => $profesores
            ];
            
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_horarios'
            ]);
        }
    }
}
