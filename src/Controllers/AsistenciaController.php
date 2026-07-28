<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\ServicioAsistencia;
use SistemaAdmin\Services\BaseService;

/**
 * AsistenciaController — orquesta peticiones HTTP del módulo de asistencia.
 *
 * Responsabilidades:
 *   - Leer y validar los datos del POST (estados, observaciones, archivos).
 *   - Verificar permisos de alcance (preceptor solo puede su curso).
 *   - Delegar al ServicioAsistencia para persistencia.
 *   - Construir los arrays de datos para la vista.
 */
class AsistenciaController extends BaseService
{
    private ServicioAsistencia $servicio;

    public function __construct(DatabaseInterface $database, ServicioAsistencia $servicio)
    {
        parent::__construct($database);
        $this->servicio = $servicio;
    }

    // ------------------------------------------------------------------ //
    // POST — procesar guardado de asistencia
    // ------------------------------------------------------------------ //

    /**
     * Procesa el POST del formulario de asistencia.
     *
     * @param array<string,mixed>  $post        $_POST
     * @param array<string,mixed>  $files       $_FILES
     * @param array<string,string> $adjuntosYa  Adjuntos ya procesados de iteraciones previas (vacío normalmente)
     * @param list<int>            $scopeCursos IDs de cursos que el usuario puede editar (vacío = sin restricción para admin)
     * @return array{error: string, redirect: string|null}
     */
    public function procesarPost(
        array  $post,
        array  $files,
        int    $cursoId,
        int    $materiaId,
        string $fecha,
        int    $usuarioId,
        array  $scopeCursos,
        string $rol
    ): array {
        // Validar rol
        if (!in_array($rol, ['admin', 'preceptor'], true)) {
            return ['error' => 'Sin permisos para registrar asistencia.', 'redirect' => null];
        }

        // Validar alcance de preceptor
        if ($rol === 'preceptor' && $scopeCursos !== [] && !in_array($cursoId, $scopeCursos, true)) {
            return ['error' => 'No tenés permisos para registrar asistencia en ese curso.', 'redirect' => null];
        }

        // Validar fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['error' => 'La fecha no tiene un formato válido.', 'redirect' => null];
        }
        if ($materiaId <= 0) {
            return ['error' => 'Debe seleccionar una materia.', 'redirect' => null];
        }
        if (!$this->servicio->materiaPerteneceACurso($materiaId, $cursoId)) {
            return ['error' => 'La materia seleccionada no corresponde al curso indicado.', 'redirect' => null];
        }

        // Obtener alumnos activos del curso (incluyendo recursantes asignados a esta materia)
        $schoolYear = (int) substr($fecha, 0, 4);
        $grupoTaller = isset($post['grupo_taller']) ? trim((string) $post['grupo_taller']) : '';
        $activos = $this->servicio->idsActivosParaAsistenciaMateria($cursoId, $materiaId, $schoolYear, $grupoTaller);
        
        // Filtrar alumnos con solapamientos
        $servicioEstudiantes = new \SistemaAdmin\Services\ServicioEstudiantes(
            $this->database,
            new \SistemaAdmin\Mappers\EstudianteMapper($this->database)
        );
        $activosFiltrados = [];
        foreach ($activos as $eid) {
            $esSolapado = false;
            $regularRow = $this->database->fetch("SELECT curso_id FROM estudiantes WHERE id = ?", [$eid]);
            if ($regularRow && (int) $regularRow['curso_id'] === $cursoId) {
                $solapados = $servicioEstudiantes->verificarSolapamientosEstudiante($eid, $schoolYear);
                foreach ($solapados as $s) {
                    if ((int) $s['materia_id'] === $materiaId) {
                        $esSolapado = true;
                        break;
                    }
                }
            }
            if (!$esSolapado) {
                $activosFiltrados[] = $eid;
            }
        }
        $activos = $activosFiltrados;

        if ($activos === []) {
            return ['error' => 'No hay alumnos activos en el curso seleccionado.', 'redirect' => null];
        }

        // Leer estados del POST
        $estadosRaw = (is_array($post['estado'] ?? null)) ? $post['estado'] : [];
        /** @var array<int, string> $estados */
        $estados = [];
        foreach ($estadosRaw as $eidRaw => $estadoRaw) {
            $eid = (int) $eidRaw;
            if ($eid > 0) {
                $estados[$eid] = (string) $estadoRaw;
            }
        }

        // Leer observaciones
        $obsRaw = (is_array($post['observacion'] ?? null)) ? $post['observacion'] : [];
        /** @var array<int, string|null> $observaciones */
        $observaciones = [];
        foreach ($obsRaw as $eidRaw => $obsVal) {
            $eid = (int) $eidRaw;
            if ($eid > 0) {
                $observaciones[$eid] = is_string($obsVal) ? $obsVal : null;
            }
        }

        // Procesar archivos adjuntos (uno por alumno)
        /** @var array<int, string|null> $adjuntos */
        $adjuntos = [];
        $adjuntosFiles = (is_array($files['adjunto'] ?? null)) ? $files['adjunto'] : [];

        if ($adjuntosFiles !== []) {
            foreach ($activos as $eid) {
                // PHP pone los files de arrays multidimensionales con esta estructura:
                // $_FILES['adjunto']['name'][eid] = '...'
                $singleFile = $this->extraerArchivoDeArrayFiles($adjuntosFiles, $eid);
                if ($singleFile !== null && ($singleFile['size'] ?? 0) > 0) {
                    try {
                        $adjuntos[$eid] = $this->servicio->procesarAdjunto($singleFile);
                    } catch (\Throwable $e) {
                        return [
                            'error'    => 'Error al subir el adjunto para el alumno ID ' . $eid . ': ' . $e->getMessage(),
                            'redirect' => null,
                        ];
                    }
                }
            }
        }

        // Guardar
        try {
            $guardados = $this->servicio->guardar(
                $cursoId,
                $materiaId,
                $fecha,
                $estados,
                $observaciones,
                $adjuntos,
                $activos,
                $usuarioId,
                $grupoTaller
            );
            if ($guardados <= 0) {
                return ['error' => 'No se detectaron cambios para guardar.', 'redirect' => null];
            }
        } catch (\Throwable $e) {
            $this->logEvent('ERROR', 'Error guardando asistencia', [
                'curso_id' => $cursoId,
                'fecha'    => $fecha,
                'error'    => $e->getMessage(),
            ]);
            return ['error' => 'No se pudo guardar la asistencia. Verificá la base de datos.', 'redirect' => null];
        }

        $redirectParams = ['curso_id' => $cursoId, 'materia_id' => $materiaId, 'fecha' => $fecha, 'ok' => '1'];
        if ($grupoTaller !== '') {
            $redirectParams['grupo_taller'] = $grupoTaller;
        }
        if (isset($post['estudiante_id']) && is_scalar($post['estudiante_id'])) {
            $redirectParams['estudiante_id'] = (string) $post['estudiante_id'];
        }
        if (isset($post['trimestre']) && is_scalar($post['trimestre'])) {
            $redirectParams['trimestre'] = (string) $post['trimestre'];
        }
        if (isset($post['anio']) && is_scalar($post['anio'])) {
            $redirectParams['anio'] = (string) $post['anio'];
        }
        if (isset($post['mes']) && is_scalar($post['mes'])) {
            $redirectParams['mes'] = (string) $post['mes'];
        }
        if (isset($post['dia']) && is_scalar($post['dia'])) {
            $redirectParams['dia'] = (string) $post['dia'];
        }
        $qs = http_build_query($redirectParams);
        return ['error' => '', 'redirect' => 'attendance.php?' . $qs];
    }

    // ------------------------------------------------------------------ //
    // GET — datos para la vista de toma de asistencia
    // ------------------------------------------------------------------ //

    /**
     * Construye el array de filas para la vista, enriqueciendo con el %
     * de asistencia acumulada de cada alumno (año en curso: 1-ene hasta hoy).
     *
     * @return list<array<string, mixed>>
     */
    public function datosVistaToma(
        int $cursoId,
        int $materiaId,
        string $fechaYmd,
        ?string $desde = null,
        ?string $hasta = null,
        ?string $grupoTaller = null
    ): array {
        $filas = $this->servicio->filasPorCursoYFecha($cursoId, $fechaYmd, $materiaId, $grupoTaller);

        // Rango por defecto: desde el 1° de enero del año escolar hasta hoy
        $anioEscolar = (int) substr($fechaYmd, 0, 4);
        $desdeRango  = $desde ?? ($anioEscolar . '-01-01');
        $hastaRango  = $hasta ?? date('Y-m-d');

        foreach ($filas as &$fila) {
            $eid = (int) ($fila['id'] ?? 0);
            $fila['porcentaje'] = $this->servicio->porcentajeAsistencia($eid, $desdeRango, $hastaRango, $materiaId);

            // Normalizar tipos
            $fila['id']          = $eid;
            $fila['apellido']    = (string) ($fila['apellido'] ?? '');
            $fila['nombre']      = (string) ($fila['nombre'] ?? '');
            $fila['dni']         = (string) ($fila['dni'] ?? '');
            $fila['estado']      = (string) ($fila['estado'] ?? '');
            $fila['observacion'] = $fila['observacion'] !== null ? (string) $fila['observacion'] : null;
            $fila['adjunto']     = $fila['adjunto'] !== null ? (string) $fila['adjunto'] : null;
        }
        unset($fila);

        return $filas;
    }

    /**
     * Resumen acumulado por alumno para el panel de reporte del curso.
     *
     * @return list<array<string, mixed>>
     */
    public function datosVistaReporte(
        int $cursoId,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $materiaId = null,
        ?string $grupoTaller = null
    ): array {
        return $this->servicio->resumenCurso($cursoId, $desde, $hasta, $materiaId, $grupoTaller);
    }

    /**
     * @return array{0:string,1:string} [desde, hasta]
     */
    public function rangoCuatrimestreDesdeFecha(string $fechaYmd, string $cuatrimestre): array
    {
        $anio = (int) substr($fechaYmd, 0, 4);
        return match ($cuatrimestre) {
            '1' => [$anio . '-03-01', $anio . '-07-31'],
            '2' => [$anio . '-08-01', $anio . '-12-31'],
            default => [$anio . '-03-01', $anio . '-12-31'],
        };
    }

    /**
     * @param list<array{id:int,nombre:string}> $materiasDisponibles
     */
    public function normalizarMateriaSeleccionada(int $materiaId, array $materiasDisponibles): int
    {
        if ($materiaId <= 0 || $materiasDisponibles === []) {
            return 0;
        }
        foreach ($materiasDisponibles as $matOpt) {
            if ((int) ($matOpt['id'] ?? 0) === $materiaId) {
                return $materiaId;
            }
        }
        return 0;
    }

    /**
     * @param list<array<string,mixed>> $filas
     * @return list<array<string,mixed>>
     */
    public function filtrarFilasPorEstudiante(array $filas, int $estudianteId): array
    {
        if ($estudianteId <= 0) {
            return $filas;
        }
        return array_values(array_filter(
            $filas,
            static fn (array $fila): bool => (int) ($fila['id'] ?? 0) === $estudianteId
        ));
    }

    public function moverDiaHabil(string $fechaBase, int $direccion): string
    {
        $ts = strtotime($fechaBase);
        if ($ts === false) {
            $ts = time();
        }
        $step = $direccion >= 0 ? 1 : -1;
        do {
            $ts = strtotime(($step > 0 ? '+1 day' : '-1 day'), $ts);
            $dow = (int) date('N', $ts);
        } while ($dow >= 6);
        return date('Y-m-d', $ts);
    }

    /**
     * @param list<string> $diasPermitidos
     */
    public function moverDiaConHorario(string $fechaBase, int $direccion, array $diasPermitidos): string
    {
        $ts = strtotime($fechaBase);
        if ($ts === false) {
            $ts = time();
        }
        $step = $direccion >= 0 ? 1 : -1;
        $maxIntentos = 14;
        while ($maxIntentos-- > 0) {
            $ts = strtotime(($step > 0 ? '+1 day' : '-1 day'), $ts);
            $dow = (int) date('N', $ts);
            if ($dow >= 6) {
                continue;
            }
            if ($this->fechaCoincideConDiasPermitidos(date('Y-m-d', $ts), $diasPermitidos)) {
                return date('Y-m-d', $ts);
            }
        }

        return date('Y-m-d', $ts);
    }

    /**
     * @param array<string,mixed> $query
     */
    public function resolverFechaSeleccionada(array $query, array $diasPermitidos = []): string
    {
        $hoyBase = date('Y-m-d');
        if (in_array((int) date('N'), [6, 7], true)) {
            $hoyBase = $this->moverDiaHabil($hoyBase, -1);
        }

        $anioSel = isset($query['anio']) ? (int) $query['anio'] : null;
        $mesSel = isset($query['mes']) ? (int) $query['mes'] : null;
        $diaSel = isset($query['dia']) ? (int) $query['dia'] : null;

        if ($anioSel !== null && $mesSel !== null && $diaSel !== null && checkdate($mesSel, $diaSel, $anioSel)) {
            $fechaSeleccionada = sprintf('%04d-%02d-%02d', $anioSel, $mesSel, $diaSel);
        } else {
            $fechaSeleccionada = isset($query['fecha']) ? trim((string) $query['fecha']) : '';
            if ($fechaSeleccionada === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
                $fechaSeleccionada = $hoyBase;
            }
        }

        $dayNav = isset($query['day_nav']) ? (string) $query['day_nav'] : '';
        if ($dayNav === 'prev') {
            $fechaSeleccionada = $diasPermitidos === []
                ? $this->moverDiaHabil($fechaSeleccionada, -1)
                : $this->moverDiaConHorario($fechaSeleccionada, -1, $diasPermitidos);
        } elseif ($dayNav === 'next') {
            $fechaSeleccionada = $diasPermitidos === []
                ? $this->moverDiaHabil($fechaSeleccionada, 1)
                : $this->moverDiaConHorario($fechaSeleccionada, 1, $diasPermitidos);
        }

        if ($diasPermitidos !== [] && !$this->fechaCoincideConDiasPermitidos($fechaSeleccionada, $diasPermitidos)) {
            $fechaSeleccionada = $this->moverDiaConHorario($fechaSeleccionada, 1, $diasPermitidos);
        }

        return $fechaSeleccionada;
    }

    /**
     * @param list<string> $diasPermitidos
     */
    private function fechaCoincideConDiasPermitidos(string $fechaYmd, array $diasPermitidos): bool
    {
        if ($diasPermitidos === []) {
            return true;
        }
        $ts = strtotime($fechaYmd);
        if ($ts === false) {
            return false;
        }
        $dow = (int) date('N', $ts);
        $mapa = [
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'miércoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'sábado' => 6,
            'domingo' => 7,
        ];
        foreach ($diasPermitidos as $dia) {
            $clave = strtolower(trim((string) $dia));
            if (isset($mapa[$clave]) && $mapa[$clave] === $dow) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contadores rápidos de presentes / tardanzas / ausentes para el día y curso.
     *
     * @param  list<array<string, mixed>> $filas resultado de datosVistaToma()
     * @return array{presentes: int, tardanzas: int, media_falta: int, aus_justificados: int, ausentes: int}
     */
    public function contadoresDia(array $filas): array
    {
        $c = ['presentes' => 0, 'tardanzas' => 0, 'media_falta' => 0, 'aus_justificados' => 0, 'ausentes' => 0];
        foreach ($filas as $f) {
            match ($f['estado'] ?? '') {
                'Presente'            => $c['presentes']++,
                'Tardanza'            => $c['tardanzas']++,
                'Media falta'         => $c['media_falta']++,
                'Ausente justificado' => $c['aus_justificados']++,
                'Ausente'             => $c['ausentes']++,
                default               => null,
            };
        }
        return $c;
    }

    /**
     * Procesa la petición AJAX de guardado de asistencia.
     *
     * @param array $post
     * @param array $files
     * @param int $usuarioId
     * @param array $scopeCursos
     * @param string $rol
     * @return array{success: bool, error?: string, percentages?: array<string, float>}
     */
    public function procesarPostAjax(
        array $post,
        array $files,
        int $usuarioId,
        array $scopeCursos,
        string $rol
    ): array {
        // Validar rol
        if (!in_array($rol, ['admin', 'preceptor'], true)) {
            return ['success' => false, 'error' => 'Sin permisos para registrar asistencia.'];
        }

        $cursoId = isset($post['curso_id']) ? (int) $post['curso_id'] : 0;
        $materiaId = isset($post['materia_id']) ? (int) $post['materia_id'] : 0;
        $fecha = isset($post['fecha']) ? trim((string) $post['fecha']) : '';

        if ($cursoId <= 0 || $materiaId <= 0 || $usuarioId <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos para guardar asistencia.'];
        }

        // Validar alcance de preceptor
        if ($rol === 'preceptor' && $scopeCursos !== [] && !in_array($cursoId, $scopeCursos, true)) {
            return ['success' => false, 'error' => 'No tenés permisos para registrar asistencia en ese curso.'];
        }

        // Validar fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['success' => false, 'error' => 'La fecha no es válida.'];
        }

        if (!$this->servicio->materiaPerteneceACurso($materiaId, $cursoId)) {
            return ['success' => false, 'error' => 'La materia no corresponde al curso.'];
        }

        $estadosRaw = (is_array($post['estado'] ?? null)) ? $post['estado'] : [];
        $obsRaw = (is_array($post['observacion'] ?? null)) ? $post['observacion'] : [];

        if ($estadosRaw === []) {
            return ['success' => false, 'error' => 'No se detectaron estados para guardar.'];
        }

        $schoolYear = (int) substr($fecha, 0, 4);
        $grupoTaller = (string) ($post['grupo_taller'] ?? '');
        $materiaRow = $this->database->fetch("SELECT es_taller FROM materias WHERE id = ?", [$materiaId]);
        $postMateriaEsTaller = $materiaRow && ((int) ($materiaRow['es_taller'] ?? 0) === 1);
        if ($postMateriaEsTaller && $grupoTaller === '') {
            return ['success' => false, 'error' => 'Debe seleccionar un grupo de taller para esta materia.'];
        }

        $activos = $this->servicio->idsActivosParaAsistenciaMateria($cursoId, $materiaId, $schoolYear, $grupoTaller);

        // Filtrar alumnos con solapamientos
        $servicioEstudiantes = new \SistemaAdmin\Services\ServicioEstudiantes(
            $this->database,
            new \SistemaAdmin\Mappers\EstudianteMapper($this->database)
        );
        $activosFiltrados = [];
        foreach ($activos as $eid) {
            $esSolapado = false;
            $regularRow = $this->database->fetch("SELECT curso_id FROM estudiantes WHERE id = ?", [$eid]);
            if ($regularRow && (int) $regularRow['curso_id'] === $cursoId) {
                $solapados = $servicioEstudiantes->verificarSolapamientosEstudiante($eid, $schoolYear);
                foreach ($solapados as $s) {
                    if ((int) $s['materia_id'] === $materiaId) {
                        $esSolapado = true;
                        break;
                    }
                }
            }
            if (!$esSolapado) {
                $activosFiltrados[] = $eid;
            }
        }
        $activos = $activosFiltrados;
        $permitidos = array_fill_keys($activos, true);

        $estadosGuardar = [];
        $obsGuardar = [];
        foreach ($estadosRaw as $eidRaw => $estadoRaw) {
            $eid = (int) $eidRaw;
            if ($eid <= 0 || !isset($permitidos[$eid])) {
                continue;
            }
            $estado = trim((string) $estadoRaw);
            if (!in_array($estado, \SistemaAdmin\Services\ServicioAsistencia::ESTADOS, true)) {
                continue;
            }
            $estadosGuardar[$eid] = $estado;
            if (array_key_exists($eidRaw, $obsRaw)) {
                $obsGuardar[$eid] = trim((string) $obsRaw[$eidRaw]);
            }
        }

        if ($estadosGuardar === []) {
            return ['success' => false, 'error' => 'No se detectaron cambios válidos para guardar.'];
        }

        $adjuntosGuardar = [];
        $adjuntosFiles = (is_array($files['adjunto'] ?? null)) ? $files['adjunto'] : [];
        if ($adjuntosFiles !== []) {
            foreach (array_keys($estadosGuardar) as $eid) {
                $singleFile = $this->extraerArchivoDeArrayFiles($adjuntosFiles, $eid);
                if ($singleFile !== null && ($singleFile['size'] ?? 0) > 0) {
                    try {
                        $adjPath = $this->servicio->procesarAdjunto($singleFile);
                        if ($adjPath) {
                            $adjuntosGuardar[$eid] = $adjPath;
                        }
                    } catch (\Throwable $e) {
                        error_log('[Asistencia][AJAX] Error adjunto EID ' . $eid . ': ' . $e->getMessage());
                    }
                }
            }
        }

        try {
            $this->servicio->guardar(
                $cursoId,
                $materiaId,
                $fecha,
                $estadosGuardar,
                $obsGuardar,
                $adjuntosGuardar,
                array_map('intval', array_keys($estadosGuardar)),
                $usuarioId,
                $grupoTaller
            );

            $porcentajes = [];
            $trimestre = (string) ($post['trimestre'] ?? '');
            [$desdeAjax, $hastaAjax] = $this->rangoCuatrimestreDesdeFecha($fecha, $trimestre);
            foreach (array_keys($estadosGuardar) as $eid) {
                $pct = $this->servicio->porcentajeAsistencia($eid, $desdeAjax, $hastaAjax, $materiaId);
                $porcentajes[(string) $eid] = $pct;
            }

            return ['success' => true, 'porcentajes' => $porcentajes];
        } catch (\Throwable $e) {
            error_log('[Asistencia][AJAX] Error guardando: ' . $e->getMessage());
            return ['success' => false, 'error' => 'No se pudo guardar la asistencia.'];
        }
    }

    // ------------------------------------------------------------------ //
    // Helpers privados
    // ------------------------------------------------------------------ //

    /**
     * Extrae la info de un archivo individual de la estructura multidimensional
     * que PHP genera para inputs type=file con nombre array (adjunto[id]).
     *
     * @param  array<string, mixed> $filesArray $_FILES['adjunto']
     * @return array{name:string,type:string,tmp_name:string,error:int,size:int}|null
     */
    private function extraerArchivoDeArrayFiles(array $filesArray, int $eid): ?array
    {
        $name    = $filesArray['name'][$eid]     ?? null;
        $tmpName = $filesArray['tmp_name'][$eid] ?? null;
        $error   = $filesArray['error'][$eid]    ?? UPLOAD_ERR_NO_FILE;
        $size    = $filesArray['size'][$eid]     ?? 0;
        $type    = $filesArray['type'][$eid]     ?? '';

        if ($name === null || $tmpName === null || (int) $error !== UPLOAD_ERR_OK) {
            return null;
        }

        return [
            'name'     => (string) $name,
            'type'     => (string) $type,
            'tmp_name' => (string) $tmpName,
            'error'    => (int) $error,
            'size'     => (int) $size,
        ];
    }
}
