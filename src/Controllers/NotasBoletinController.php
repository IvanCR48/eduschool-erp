<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Services\NotasSubjectGradesPayloadBuilder;
use SistemaAdmin\Services\ServicioBoletinNotas;
use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioNotas;

/**
 * Orquesta POST y vista del boletín (grades.php).
 * Compone NotaController y ServicioBoletinNotas para no acoplar la página a esas clases.
 */
class NotasBoletinController extends BaseService
{
    private NotaController $notaController;

    private ServicioBoletinNotas $servicioBoletinNotas;

    public function __construct(
        DatabaseInterface $database,
        private ServicioEstudiantes $servicioEstudiantes,
        private ServicioNotas $servicioNotas
    ) {
        parent::__construct($database);
        $this->notaController = new NotaController($database, $servicioNotas, $servicioEstudiantes);
        $this->servicioBoletinNotas = new ServicioBoletinNotas($database);
    }

    /**
     * @param array<string, string> $formValuesInicial
     * @return array{success_message: string, error_message: string, form_values: array<string, string>}
     */
    public function procesarPost(bool $canManage, bool $esPost, array $post, array $formValuesInicial): array
    {
        $successMessage = '';
        $errorMessage = '';
        $formValues = $formValuesInicial;

        if (!$canManage || !$esPost) {
            return [
                'success_message' => $successMessage,
                'error_message' => $errorMessage,
                'form_values' => $formValues,
            ];
        }

        try {
            if (isset($post['actualizar_nota'])) {
                [$successMessage, $errorMessage] = $this->procesarActualizarNota($post);
            } elseif (isset($post['insertar_nota'])) {
                [$successMessage, $errorMessage, $formValues] = $this->procesarInsertarNota($post);
            } elseif (isset($post['guardar_avance'])) {
                [$successMessage, $errorMessage] = $this->procesarGuardarAvanceDesdeBoletin($post);
            } elseif (isset($post['eliminar_nota'])) {
                [$successMessage, $errorMessage] = $this->procesarEliminarNota($post);
            } elseif (isset($post['guardar_recuperatorio'])) {
                [$successMessage, $errorMessage] = $this->procesarGuardarRecuperatorio($post);
            }
        } catch (\Throwable $e) {
            $errorMessage = 'Error: ' . $e->getMessage();
        }

        return [
            'success_message' => $successMessage,
            'error_message' => $errorMessage,
            'form_values' => $formValues,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosVista(string $cursoFilter, string $estudianteFilter, string $trimestreFilter): array
    {
        return $this->servicioBoletinNotas->datosVistaBoletin(
            $cursoFilter,
            $estudianteFilter,
            $trimestreFilter,
            $this->servicioEstudiantes,
            $this->servicioNotas
        );
    }

    /**
     * @return array{curso_filter: string, cuatrimestre_filter: string, estudiante_info: array<string, mixed>|null}
     */
    public function aplicarFiltrosDesdeEstudiante(
        string $estudianteFilter,
        string $cursoFilter,
        string $trimestreFilter
    ): array {
        return $this->servicioBoletinNotas->aplicarFiltrosDesdeEstudiante(
            $estudianteFilter,
            $cursoFilter,
            $trimestreFilter
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: string, 1: string}
     */
    private function procesarActualizarNota(array $post): array
    {
        $notaId = (int) ($post['nota_id'] ?? 0);
        $nuevoValor = isset($post['nota']) && $post['nota'] !== '' ? (float) $post['nota'] : null;
        $observaciones = !empty($post['observaciones']) ? (string) $post['observaciones'] : null;

        if ($nuevoValor === null) {
            return ['', 'El valor de la nota es requerido'];
        }

        $resultado = $this->notaController->actualizar($notaId, [
            'calificacion' => $nuevoValor,
            'observaciones' => $observaciones,
        ]);

        if ($resultado['success']) {
            return ['Nota actualizada correctamente', ''];
        }

        if (!empty($resultado['errors']) && is_array($resultado['errors'])) {
            return ['', implode('; ', $resultado['errors'])];
        }

        return ['', $resultado['error'] ?? 'Error al actualizar nota'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: string, 1: string, 2: array<string, string>}
     */
    private function procesarInsertarNota(array $post): array
    {
        $tipoRegistro = (string) ($post['tipo_registro'] ?? 'numerica');
        $cursoSeleccionado = (string) ($post['curso_id'] ?? '');
        $formValues = [
            'curso_id' => $cursoSeleccionado,
            'estudiante_id' => (string) ($post['estudiante_id'] ?? ''),
            'materia_id' => (string) ($post['materia_id'] ?? ''),
            'trimestre' => (string) ($post['trimestre'] ?? ''),
            'nota' => (string) ($post['nota'] ?? ''),
            'observaciones' => (string) ($post['observaciones'] ?? ''),
            'tipo_registro' => $tipoRegistro,
            'valor_avance' => (string) ($post['valor_avance'] ?? ''),
            'etapa_avance' => (string) ($post['etapa_avance'] ?? ''),
        ];

        if ($tipoRegistro === 'avance') {
            $estudianteId = (int) ($post['estudiante_id'] ?? 0);
            $materiaId = (int) ($post['materia_id'] ?? 0);
            $etapa = (string) ($post['etapa_avance'] ?? '');
            $valor = (string) ($post['valor_avance'] ?? '');

            if (!$estudianteId || !$materiaId || $etapa === '') {
                return ['', 'Complete los datos obligatorios del avance.', $formValues];
            }

            $resultadoAvance = $this->servicioBoletinNotas->gestionarAvance($estudianteId, $materiaId, $etapa, $valor);

            if ($resultadoAvance['success']) {
                $formValues['estudiante_id'] = '';
                $formValues['materia_id'] = '';
                $formValues['valor_avance'] = '';
                $formValues['etapa_avance'] = '';
                $formValues['tipo_registro'] = 'avance';

                return [$resultadoAvance['message'] ?? 'Avance registrado correctamente', '', $formValues];
            }

            return ['', $resultadoAvance['error'] ?? 'No se pudo registrar el avance.', $formValues];
        }

        $data = [
            'estudiante_id' => (int) $post['estudiante_id'],
            'materia_id' => (int) $post['materia_id'],
            'bimestre' => (string) $post['trimestre'],
            'calificacion' => isset($post['nota']) && $post['nota'] !== '' ? (float) $post['nota'] : null,
            'observaciones' => !empty($post['observaciones']) ? (string) $post['observaciones'] : null,
        ];

        $resultado = $this->notaController->cargar($data);

        if ($resultado['success']) {
            $formValues = [
                'curso_id' => $cursoSeleccionado,
                'estudiante_id' => '',
                'materia_id' => '',
                'trimestre' => '',
                'nota' => '',
                'observaciones' => '',
                'tipo_registro' => 'numerica',
                'valor_avance' => '',
                'etapa_avance' => '',
            ];

            return ['Nota registrada correctamente', '', $formValues];
        }

        $error = $resultado['error'] ?? 'Error al registrar nota';
        if (!empty($resultado['errors']) && is_array($resultado['errors'])) {
            $error = implode('; ', $resultado['errors']);
        }

        return ['', $error, $formValues];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: string, 1: string}
     */
    private function procesarGuardarAvanceDesdeBoletin(array $post): array
    {
        $estudianteId = (int) ($post['estudiante_id'] ?? 0);
        $materiaId = (int) ($post['materia_id'] ?? 0);
        $etapa = (string) ($post['etapa'] ?? '');
        $valor = (string) ($post['valor'] ?? '');

        $resultadoAvance = $this->servicioBoletinNotas->gestionarAvance($estudianteId, $materiaId, $etapa, $valor);

        if ($resultadoAvance['success']) {
            return [$resultadoAvance['message'] ?? 'Avance actualizado.', ''];
        }

        return ['', $resultadoAvance['error'] ?? 'No se pudo actualizar el avance.'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: string, 1: string}
     */
    private function procesarEliminarNota(array $post): array
    {
        $notaId = (int) ($post['nota_id'] ?? 0);
        $resultado = $this->notaController->eliminar($notaId);

        if ($resultado['success']) {
            return ['Nota eliminada', ''];
        }

        return ['', $resultado['error'] ?? 'Error al eliminar nota'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: string, 1: string}
     */
    private function procesarGuardarRecuperatorio(array $post): array
    {
        $cursoId = (int) ($post['curso_id'] ?? 0);
        $estudianteId = (int) ($post['estudiante_id'] ?? 0);
        $materiaId = (int) ($post['materia_id'] ?? 0);
        $ctx = (string) ($post['recuperacion_contexto'] ?? '');
        $alcance = (string) ($post['recuperacion_alcance'] ?? '');
        $calif = isset($post['calificacion_recup']) && $post['calificacion_recup'] !== ''
            ? (float) $post['calificacion_recup']
            : null;
        $obs = isset($post['observaciones_recup']) ? trim((string) $post['observaciones_recup']) : '';
        $observaciones = $obs !== '' ? $obs : null;

        if ($cursoId < 1 || $estudianteId < 1 || $materiaId < 1 || $calif === null) {
            return ['', 'Completá curso, estudiante, materia y calificación.'];
        }

        $contextosValidos = [
            'intensification_first_semester',
            'intensification_december',
            'intensification_february_march',
        ];
        if (!in_array($ctx, $contextosValidos, true)) {
            return ['', 'Contexto de evaluación inválido.'];
        }

        if ($ctx === 'intensification_first_semester') {
            $alcance = 'first_semester';
        }

        $alcancesValidos = ['first_semester', 'second_semester', 'both'];
        if (!in_array($alcance, $alcancesValidos, true)) {
            return ['', 'Alcance inválido.'];
        }

        $cursoEst = $this->servicioEstudiantes->obtenerCursoIdEstudianteActivo($estudianteId);
        if ($cursoEst === null || $cursoEst !== $cursoId) {
            return ['', 'El estudiante no pertenece al curso indicado.'];
        }

        if (!$this->servicioBoletinNotas->materiaPerteneceACursoActiva($cursoId, $materiaId)) {
            return ['', 'La materia no corresponde a ese curso.'];
        }

        $schoolYear = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());

        try {
            $this->servicioNotas->cargarNotaIntensificacion(
                $estudianteId,
                $materiaId,
                $calif,
                $ctx,
                $alcance,
                $schoolYear,
                $observaciones
            );
        } catch (\Throwable $e) {
            return ['', $e->getMessage()];
        }

        return ['Recuperatorio / intensificación registrado correctamente.', ''];
    }

    /**
     * Segmentos de columnas del boletín por materia según filtro de cuatrimestre (vista grades.php).
     *
     * @return list<array{tipo: string, clave: int|string}>
     */
    public static function boletinSegmentosPorCuatrimestre(string $trimestreFilter): array
    {
        $termsCount = 2;
        try {
            if (function_exists('sistema_admin_pdo')) {
                $termsCount = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount(sistema_admin_pdo());
            }
        } catch (\Throwable $e) {
            $termsCount = 2;
        }

        if ($termsCount === 3) {
            if ($trimestreFilter === '1') {
                return [['tipo' => 'cuatrimestre', 'clave' => 1]];
            }
            if ($trimestreFilter === '2') {
                return [['tipo' => 'cuatrimestre', 'clave' => 2]];
            }
            if ($trimestreFilter === '3') {
                return [['tipo' => 'cuatrimestre', 'clave' => 3]];
            }
            if ($trimestreFilter === 'final') {
                return [
                    ['tipo' => 'cuatrimestre', 'clave' => 1],
                    ['tipo' => 'cuatrimestre', 'clave' => 2],
                    ['tipo' => 'cuatrimestre', 'clave' => 3],
                    ['tipo' => 'final', 'clave' => 'final'],
                ];
            }

            return [
                ['tipo' => 'cuatrimestre', 'clave' => 1],
                ['tipo' => 'cuatrimestre', 'clave' => 2],
                ['tipo' => 'cuatrimestre', 'clave' => 3],
            ];
        }

        if ($termsCount === 4) {
            if ($trimestreFilter === '1') {
                return [['tipo' => 'cuatrimestre', 'clave' => 1]];
            }
            if ($trimestreFilter === '2') {
                return [['tipo' => 'cuatrimestre', 'clave' => 2]];
            }
            if ($trimestreFilter === '3') {
                return [['tipo' => 'cuatrimestre', 'clave' => 3]];
            }
            if ($trimestreFilter === '4') {
                return [['tipo' => 'cuatrimestre', 'clave' => 4]];
            }
            if ($trimestreFilter === 'final') {
                return [
                    ['tipo' => 'cuatrimestre', 'clave' => 1],
                    ['tipo' => 'cuatrimestre', 'clave' => 2],
                    ['tipo' => 'cuatrimestre', 'clave' => 3],
                    ['tipo' => 'cuatrimestre', 'clave' => 4],
                    ['tipo' => 'final', 'clave' => 'final'],
                ];
            }

            return [
                ['tipo' => 'cuatrimestre', 'clave' => 1],
                ['tipo' => 'cuatrimestre', 'clave' => 2],
                ['tipo' => 'cuatrimestre', 'clave' => 3],
                ['tipo' => 'cuatrimestre', 'clave' => 4],
            ];
        }

        if ($trimestreFilter === 'final') {
            return [
                ['tipo' => 'cuatrimestre', 'clave' => 1],
                ['tipo' => 'cuatrimestre', 'clave' => 2],
                ['tipo' => 'final', 'clave' => 'final'],
            ];
        }
        if ($trimestreFilter === 'avance1') {
            return [
                ['tipo' => 'avance', 'clave' => 'avance1'],
            ];
        }
        if ($trimestreFilter === '1') {
            return [
                ['tipo' => 'avance', 'clave' => 'avance1'],
                ['tipo' => 'cuatrimestre', 'clave' => 1],
            ];
        }
        if ($trimestreFilter === 'avance2') {
            return [
                ['tipo' => 'avance', 'clave' => 'avance2'],
            ];
        }
        if ($trimestreFilter === '2') {
            return [
                ['tipo' => 'avance', 'clave' => 'avance2'],
                ['tipo' => 'cuatrimestre', 'clave' => 2],
            ];
        }

        return [
            ['tipo' => 'avance', 'clave' => 'avance1'],
            ['tipo' => 'cuatrimestre', 'clave' => 1],
            ['tipo' => 'avance', 'clave' => 'avance2'],
            ['tipo' => 'cuatrimestre', 'clave' => 2],
        ];
    }

    public static function boletinColspanPorMateria(string $trimestreFilter): int
    {
        return count(self::boletinSegmentosPorCuatrimestre($trimestreFilter));
    }

    /**
     * @param array{tipo: string, clave: int|string} $seg
     */
    public static function boletinEtiquetaSubcolumna(array $seg): string
    {
        if ($seg['tipo'] === 'final') {
            return __('student.average');
        }
        if ($seg['tipo'] === 'cuatrimestre') {
            $c = (int) $seg['clave'];
            $termsCount = 2;
            if (function_exists('sistema_admin_pdo')) {
                $termsCount = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount(sistema_admin_pdo());
            }
            if ($termsCount === 3 || $termsCount === 4) {
                return match ($c) {
                    1 => __('student.term1_generic'),
                    2 => __('student.term2_generic'),
                    3 => __('student.term3'),
                    4 => __('student.term4'),
                    default => $c . '° Term',
                };
            }
            return match ($c) {
                1 => __('student.term1'),
                2 => __('student.term2'),
                default => $c . '° Term',
            };
        }
        if ($seg['clave'] === 'avance1') {
            return __('student.term1_preview');
        }
        if ($seg['clave'] === 'avance2') {
            return __('student.term2_preview');
        }

        return (string) $seg['clave'];
    }

    /**
     * @param array{tipo: string, clave: int|string} $seg
     */
    public static function boletinClaseThSubcolumna(array $seg): string
    {
        if ($seg['tipo'] === 'final') {
            return 'final-col';
        }
        if ($seg['tipo'] === 'cuatrimestre') {
            return 'cuatrimestre-col';
        }

        return 'avance-col';
    }

    /**
     * @param array<string, mixed> $notaData clave cuatrimestre/avance del boletín (nota, observaciones, nota_id, …)
     * @param array{tipo: string, clave: int|string} $segmento
     *
     * @return array<string, mixed> descriptor de celda para la vista (tipo discrimina el render)
     */
    private static function celdaBoletinDescriptor(
        int $estudianteId,
        int $materiaId,
        array $segmento,
        array $notaData,
        bool $puedeGestionar
    ): array {
        $tipoSeg = $segmento['tipo'];
        $clave = $segmento['clave'];
        $tdExtraClass = $tipoSeg === 'final' ? 'final' : '';

        $nota = $notaData['nota'] ?? null;
        $obs = $notaData['observaciones'] ?? null;
        $notaId = $notaData['nota_id'] ?? null;

        if ($tipoSeg === 'cuatrimestre' && $puedeGestionar) {
            return [
                'td_class_extra' => $tdExtraClass,
                'tipo' => 'form_cuatrimestre',
                'estudiante_id' => $estudianteId,
                'materia_id' => $materiaId,
                'cuatrimestre' => $clave,
                'nota_id' => $notaId ? (int) $notaId : 0,
                'nota_valor' => (string) ($nota ?? ''),
                'observaciones' => (string) ($obs ?? ''),
                'usa_actualizar' => (bool) $notaId,
            ];
        }

        if ($tipoSeg === 'avance' && $puedeGestionar) {
            return [
                'td_class_extra' => $tdExtraClass,
                'tipo' => 'form_avance',
                'estudiante_id' => $estudianteId,
                'materia_id' => $materiaId,
                'etapa' => (string) $clave,
                'valor_actual' => (string) ($nota ?? ''),
            ];
        }

        if ($tipoSeg === 'avance') {
            $texto = ($nota !== null && $nota !== '') ? (string) $nota : '-';

            return [
                'td_class_extra' => $tdExtraClass,
                'tipo' => 'vista_avance',
                'texto' => $texto,
            ];
        }

        $esFinal = $tipoSeg === 'final';
        $tieneNota = $nota !== null && $nota !== '';
        if ($esFinal && $tieneNota) {
            $linea1 = number_format((float) $nota, 2);
        } else {
            $linea1 = $tieneNota ? (string) $nota : '-';
        }

        $obsStr = ($obs !== null && $obs !== '') ? (string) $obs : null;

        return [
            'td_class_extra' => $tdExtraClass,
            'tipo' => 'vista_calificacion',
            'linea1' => $linea1,
            'clase_span_extra' => ($esFinal && $tieneNota) ? 'nota-final-value' : '',
            'observaciones' => $obsStr,
        ];
    }

    /**
     * Tabla del boletín lista para iterar en la vista (sin lógica de segmentos en grades.php).
     *
     * @param array<int, array<string, mixed>> $boletinOrganizado
     * @param list<array<string, mixed>>       $materias
     *
     * @return array{
     *   colspan_por_materia: int,
     *   subcolumnas: list<array{clase_th: string, etiqueta: string}>,
     *   filas: list<array{
     *     estudiante_id: int,
     *     estudiante: array<string, mixed>,
     *     celdas: list<array<string, mixed>>
     *   }>
     * }
     */
    public function prepararVistaTablaBoletin(
        array $boletinOrganizado,
        array $materias,
        string $trimestreFilter,
        bool $puedeGestionar,
        array $restricciones = []
    ): array {
        $segmentos = self::boletinSegmentosPorCuatrimestre($trimestreFilter);
        $subcolumnas = [];
        foreach ($segmentos as $seg) {
            $subcolumnas[] = [
                'clase_th' => self::boletinClaseThSubcolumna($seg),
                'etiqueta' => self::boletinEtiquetaSubcolumna($seg),
            ];
        }

        $filas = [];
        foreach ($boletinOrganizado as $eid => $datos) {
            $estudianteId = (int) $eid;
            $celdas = [];
            foreach ($materias as $materia) {
                $mid = (int) $materia['id'];
                
                // Resolver si esta materia está restringida para este alumno
                $restr = $restricciones[$estudianteId] ?? null;
                $disabledReason = null;
                $bloqueada = false;
                if ($restr) {
                    if ($restr['es_recursante']) {
                        if (!in_array($mid, $restr['materias_permitidas'], true)) {
                            $disabledReason = 'No cursa';
                            $bloqueada = true;
                        }
                    } else {
                        if (isset($restr['materias_solapadas'][$mid])) {
                            $disabledReason = 'Solapado con ' . $restr['materias_solapadas'][$mid];
                            $bloqueada = true;
                        }
                    }
                }

                $notasM = $datos['notas'][$mid] ?? null;
                $cuatrimestres = (is_array($notasM) && isset($notasM['cuatrimestres']) && is_array($notasM['cuatrimestres']))
                    ? $notasM['cuatrimestres']
                    : [];
                foreach ($segmentos as $segmento) {
                    $clave = $segmento['clave'];
                    $rawCelda = $cuatrimestres[$clave] ?? null;
                    $notaData = is_array($rawCelda)
                        ? $rawCelda
                        : ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                    
                    if ($bloqueada) {
                        $celdas[] = [
                            'td_class_extra' => ($segmento['tipo'] === 'final' ? 'final' : '') . ' cell-disabled-recursada',
                            'tipo' => 'vista_bloqueada',
                            'texto' => $disabledReason ?: '-',
                            'disabled_reason' => $disabledReason,
                        ];
                    } else {
                        $celdas[] = self::celdaBoletinDescriptor(
                            $estudianteId,
                            $mid,
                            $segmento,
                            $notaData,
                            $puedeGestionar
                        );
                    }
                }
            }
            $filas[] = [
                'estudiante_id' => $estudianteId,
                'estudiante' => $datos['estudiante'],
                'celdas' => $celdas,
            ];
        }

        return [
            'colspan_por_materia' => self::boletinColspanPorMateria($trimestreFilter),
            'subcolumnas' => $subcolumnas,
            'filas' => $filas,
        ];
    }
}
