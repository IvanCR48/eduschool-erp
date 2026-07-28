<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\DTOs\SubjectStatusResult;
use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Models\Nota;

/**
 * Consultas y armado del boletín / avances para grades.php (sin SQL en la vista).
 */
class ServicioBoletinNotas extends BaseService
{
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    public function inferirCuatrimestrePorMesActual(): string
    {
        $mes = (int) date('n');
        if ($mes >= 3 && $mes<= 8) {
            return '1';
        }
        if ($mes >= 9 && $mes<= 11) {
            return '2';
        }

        return '3';
    }

    /**
     * @return array{curso_filter: string, cuatrimestre_filter: string, estudiante_info: array<string, mixed>|null}
     */
    public function aplicarFiltrosDesdeEstudiante(
        string $estudianteFilter,
        string $cursoFilter,
        string $trimestreFilter
    ): array {
        $estudianteInfo = null;
        if ($estudianteFilter !== '') {
            $estudianteInfo = $this->obtenerInfoEstudianteActivo((int) $estudianteFilter);
        }

        if ($estudianteFilter !== '' && $cursoFilter === '' && $estudianteInfo !== null) {
            $cid = $estudianteInfo['curso_id'] ?? null;
            if ($cid !== null && $cid !== '') {
                $cursoFilter = (string) $cid;
                if ($trimestreFilter === '') {
                    $trimestreFilter = $this->inferirCuatrimestrePorMesActual();
                }
            }
        }

        return [
            'curso_filter' => $cursoFilter,
            'cuatrimestre_filter' => $trimestreFilter,
            'estudiante_info' => $estudianteInfo,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerInfoEstudianteActivo(int $estudianteId): ?array
    {
        if ($estudianteId < 1) {
            return null;
        }

        return $this->database->fetch(
            'SELECT id, curso_id, apellido, nombre FROM estudiantes WHERE id = ? AND activo = 1',
            [$estudianteId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarCursosActivosResumidos(): array
    {
        $sql = <<<'SQL'
            SELECT c.id, c.anio, c.division, esp.nombre AS especialidad
            FROM cursos c
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            WHERE c.activo = 1
            ORDER BY c.anio, c.division
            SQL;

        return $this->database->fetchAll($sql, []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarEstudiantesActivosConCurso(): array
    {
        $sql = <<<'SQL'
            SELECT e.id, e.apellido, e.nombre, e.curso_id, c.anio, c.division, esp.nombre AS especialidad
            FROM estudiantes e
            LEFT JOIN cursos c ON e.curso_id = c.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            WHERE e.activo = 1
            ORDER BY e.apellido, e.nombre
            SQL;

        return $this->database->fetchAll($sql, []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarMateriasPorCursoOFallbackTodas(?string $cursoFilter): array
    {
        if ($cursoFilter !== null && $cursoFilter !== '') {
            return $this->database->fetchAll(<<<'SQL'
                SELECT m.* FROM materias m
                INNER JOIN materia_curso mc ON m.id = mc.materia_id
                WHERE mc.curso_id = ? AND m.activa = 1
                ORDER BY m.nombre
                SQL,
                [$cursoFilter]
            );
        }

        return $this->database->fetchAll(
            'SELECT * FROM materias WHERE activa = 1 ORDER BY nombre',
            []
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarMateriasCatalogoConCursosCsv(): array
    {
        return $this->database->fetchAll(
            <<<'SQL'
            SELECT
                m.id,
                m.nombre,
                COALESCE(GROUP_CONCAT(DISTINCT mc.curso_id ORDER BY mc.curso_id SEPARATOR ','), '') AS cursos_asignados
            FROM materias m
            LEFT JOIN materia_curso mc ON mc.materia_id = m.id AND mc.activo = 1
            WHERE m.activa = 1
            GROUP BY m.id, m.nombre
            ORDER BY m.nombre
            SQL,
            []
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerDetalleCurso(int $cursoId): ?array
    {
        if ($cursoId < 1) {
            return null;
        }

        return $this->database->fetch(<<<'SQL'
            SELECT c.anio, c.division, esp.nombre AS especialidad
            FROM cursos c
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            WHERE c.id = ?
            SQL,
            [$cursoId]
        );
    }

    /**
     * @return array{success: bool, message: string|null, error: string|null}
     */
    public function gestionarAvance(
        int $estudianteId,
        int $materiaId,
        string $etapa,
        string $valor
    ): array {
        $resultado = ['success' => false, 'message' => null, 'error' => null];

        if (!in_array($etapa, ['avance1', 'avance2'], true)) {
            $resultado['error'] = 'Etapa de avance inválida.';

            return $resultado;
        }

        if ($valor !== '' && !in_array($valor, ['TEA', 'TEP', 'TED'], true)) {
            $resultado['error'] = 'Valor de avance inválido.';

            return $resultado;
        }

        $estudiante = $this->database->fetch(
            'SELECT id, curso_id FROM estudiantes WHERE id = ? AND activo = 1',
            [$estudianteId]
        );

        if ($estudiante === null || empty($estudiante['curso_id'])) {
            $resultado['error'] = 'El estudiante debe tener un curso asignado para registrar avances.';

            return $resultado;
        }

        $materiaAsignada = $this->database->fetch(
            'SELECT COUNT(*) AS total FROM materia_curso WHERE materia_id = ? AND curso_id = ? AND activo = 1',
            [$materiaId, $estudiante['curso_id']]
        );

        if (!$materiaAsignada || !(int) $materiaAsignada['total']) {
            $resultado['error'] = 'La materia seleccionada no está asignada al curso del estudiante.';

            return $resultado;
        }

        $existe = $this->database->fetch(
            'SELECT id FROM notas_avance WHERE estudiante_id = ? AND materia_id = ? AND etapa = ?',
            [$estudianteId, $materiaId, $etapa]
        );

        if ($valor === '') {
            if ($existe) {
                $this->database->query(
                    'DELETE FROM notas_avance WHERE estudiante_id = ? AND materia_id = ? AND etapa = ?',
                    [$estudianteId, $materiaId, $etapa]
                );
                $resultado['success'] = true;
                $resultado['message'] = 'Avance eliminado correctamente.';
            } else {
                $resultado['success'] = true;
                $resultado['message'] = 'No había avance registrado.';
            }

            return $resultado;
        }

        if ($existe) {
            $this->database->query(<<<'SQL'
                UPDATE notas_avance
                SET valor = ?, observaciones = NULL, fecha = CURRENT_DATE
                WHERE estudiante_id = ? AND materia_id = ? AND etapa = ?
                SQL,
                [$valor, $estudianteId, $materiaId, $etapa]
            );
            $resultado['success'] = true;
            $resultado['message'] = 'Avance actualizado correctamente.';
        } else {
            $this->database->query(
                <<<'SQL'
                INSERT INTO notas_avance (estudiante_id, materia_id, etapa, valor, observaciones)
                VALUES (?, ?, ?, ?, NULL)
                SQL,
                [$estudianteId, $materiaId, $etapa, $valor]
            );
            $resultado['success'] = true;
            $resultado['message'] = 'Avance registrado correctamente.';
        }

        return $resultado;
    }

    /**
     * @param list<int> $estudianteIds
     * @return list<array<string, mixed>>
     */
    public function listarAvancesPorEstudianteIds(array $estudianteIds): array
    {
        if ($estudianteIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($estudianteIds), '?'));

        return $this->database->fetchAll(
            "SELECT na.* FROM notas_avance na WHERE na.estudiante_id IN ($placeholders)",
            $estudianteIds
        );
    }

    /**
     * @param list<array<string, mixed>> $materias
     * @param list<Estudiante> $estudiantesCurso
     * @return array{
     *   notas_boletin: list<array<string, mixed>>,
     *   boletin_organizado: array<int, array<string, mixed>>,
     *   hay_notas_cargadas: bool,
     *   hay_avances_cargados: bool,
     *   total_estudiantes: int
     * }
     */
    public function construirBoletin(
        string $cursoFilter,
        string $estudianteFilter,
        string $trimestreFilter,
        array $materias,
        array $estudiantesCurso,
        ServicioNotas $servicioNotas
    ): array {
        $materiasPorId = [];
        foreach ($materias as $m) {
            $materiasPorId[(int) $m['id']] = (string) $m['nombre'];
        }

        $notasBoletin = [];
        if ($cursoFilter !== '') {
            foreach ($estudiantesCurso as $estudiante) {
                $notasEstudiante = $servicioNotas->obtenerNotasEstudiante($estudiante->getId());
                foreach ($notasEstudiante as $nota) {
                    if ($nota->getBimestre() === Nota::BIMESTRE_INTENSIFICACION) {
                        continue;
                    }
                    if ($trimestreFilter !== '' && $trimestreFilter !== 'final' && $nota->getBimestre() != $trimestreFilter) {
                        continue;
                    }
                    $mid = $nota->getMateriaId();
                    $notasBoletin[] = [
                        'id' => $nota->getId(),
                        'estudiante_id' => $estudiante->getId(),
                        'apellido' => $estudiante->getApellido(),
                        'nombre' => $estudiante->getNombre(),
                        'materia_id' => $mid,
                        'materia' => $materiasPorId[$mid] ?? ('Materia ID: ' . $mid),
                        'bimestre' => $nota->getBimestre(),
                        'nota' => $nota->getValor(),
                        'observaciones' => $nota->getObservaciones(),
                    ];
                }
            }
        }

        $boletinOrganizado = [];
        $termsCount = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount($this->database->getPdo());
        if ($estudiantesCurso !== []) {
            foreach ($estudiantesCurso as $estudiante) {
                $boletinOrganizado[$estudiante->getId()] = [
                    'estudiante' => [
                        'id' => $estudiante->getId(),
                        'apellido' => $estudiante->getApellido(),
                        'nombre' => $estudiante->getNombre(),
                        'dni' => $estudiante->getDni(),
                    ],
                    'notas' => [],
                ];

                foreach ($materias as $materia) {
                    $cuatrimestresArray = [];

                    if ($termsCount === 3 || $termsCount === 4) {
                        if (is_numeric($trimestreFilter) && (int)$trimestreFilter >= 1 && (int)$trimestreFilter <= $termsCount) {
                            $cuatrimestresArray[(int)$trimestreFilter] = ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                        } elseif ($trimestreFilter === 'final') {
                            for ($t = 1; $t <= $termsCount; $t++) {
                                $cuatrimestresArray[$t] = ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                            }
                            $cuatrimestresArray['final'] = ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                        } else {
                            for ($t = 1; $t <= $termsCount; $t++) {
                                $cuatrimestresArray[$t] = ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                            }
                        }
                    } else {
                        // 2 terms default
                        if ($trimestreFilter !== '' && $trimestreFilter !== 'final') {
                            if ($trimestreFilter === 'avance1') {
                                $cuatrimestresArray = [
                                    'avance1' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                ];
                            } elseif ($trimestreFilter === '1') {
                                $cuatrimestresArray = [
                                    'avance1' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                    1 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                                ];
                            } elseif ($trimestreFilter === 'avance2') {
                                $cuatrimestresArray = [
                                    'avance2' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                ];
                            } elseif ($trimestreFilter === '2') {
                                $cuatrimestresArray = [
                                    'avance2' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                    2 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                                ];
                            } else {
                                $cuatrimestresArray[$trimestreFilter] = ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                            }
                        } elseif ($trimestreFilter === 'final') {
                            $cuatrimestresArray = [
                                'avance1' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                1 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                                'avance2' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                2 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                                'final' => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                            ];
                        } else {
                            $cuatrimestresArray = [
                                'avance1' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                1 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                                'avance2' => ['nota' => null, 'observaciones' => null, 'avance_id' => null],
                                2 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                            ];
                        }
                    }

                    if (!isset($cuatrimestresArray['final'])) {
                        $cuatrimestresArray['final'] = ['nota' => null, 'observaciones' => null, 'nota_id' => null];
                    }

                    $boletinOrganizado[$estudiante->getId()]['notas'][$materia['id']] = [
                        'materia' => $materia,
                        'cuatrimestres' => $cuatrimestresArray,
                    ];
                }
            }
        }

        // $notasBoletin proviene de findByEstudiante (ORDER creado_en DESC: la más reciente va primero).
        // Solo se asigna cada celda la primera vez que se encuentra, garantizando que siempre
        // prevalece la nota más reciente cuando existen filas duplicadas para el mismo slot.
        $hayNotasCargadas = false;
        foreach ($notasBoletin as $nota) {
            $estudianteId = $nota['estudiante_id'];
            $materiaId = $nota['materia_id'];
            $bimestre = $nota['bimestre'];
            if (!isset($boletinOrganizado[$estudianteId]['notas'][$materiaId]['cuatrimestres'][$bimestre])) {
                continue;
            }
            // Si la celda ya tiene una nota asignada (nota_id !== null), la fila actual es más
            // antigua; se descarta para no pisar la más reciente.
            if ($boletinOrganizado[$estudianteId]['notas'][$materiaId]['cuatrimestres'][$bimestre]['nota_id'] !== null) {
                continue;
            }
            $boletinOrganizado[$estudianteId]['notas'][$materiaId]['cuatrimestres'][$bimestre] = [
                'nota' => $nota['nota'],
                'observaciones' => $nota['observaciones'],
                'nota_id' => $nota['id'],
            ];
            if ($nota['nota'] !== null && $nota['nota'] !== '') {
                $hayNotasCargadas = true;
            }
        }

        $estudiantesIdsBoletin = array_keys($boletinOrganizado);
        $hayAvancesCargados = false;
        if ($estudiantesIdsBoletin !== []) {
            $avancesRaw = $this->listarAvancesPorEstudianteIds($estudiantesIdsBoletin);
            foreach ($avancesRaw as $avance) {
                $eid = (int) $avance['estudiante_id'];
                $mid = (int) $avance['materia_id'];
                $etapa = (string) $avance['etapa'];
                if (!isset($boletinOrganizado[$eid]['notas'][$mid]['cuatrimestres'][$etapa])) {
                    continue;
                }
                $boletinOrganizado[$eid]['notas'][$mid]['cuatrimestres'][$etapa] = [
                    'nota' => $avance['valor'],
                    'observaciones' => $avance['observaciones'],
                    'avance_id' => $avance['id'],
                ];
                if (!empty($avance['valor'])) {
                    $hayAvancesCargados = true;
                }
            }
        }

        foreach ($boletinOrganizado as &$datosEstudiante) {
            foreach ($datosEstudiante['notas'] as &$datosMateria) {
                $valores = [];
                for ($tNum = 1; $tNum <= $termsCount; $tNum++) {
                    $tVal = $datosMateria['cuatrimestres'][$tNum]['nota'] ?? null;
                    if ($tVal !== null && $tVal !== '') {
                        $valores[] = (float) $tVal;
                    }
                }
                if ($valores !== []) {
                    $notaFinal = round(array_sum($valores) / count($valores), 2);
                    $datosMateria['cuatrimestres']['final']['nota'] = $notaFinal;
                    $datosMateria['cuatrimestres']['final']['observaciones'] = count($valores) === $termsCount
                        ? 'Promedio completo'
                        : 'Promedio parcial';
                } else {
                    $datosMateria['cuatrimestres']['final']['nota'] = null;
                    $datosMateria['cuatrimestres']['final']['observaciones'] = null;
                }
            }
            unset($datosMateria);
        }
        unset($datosEstudiante);

        /*
         * Estado de materia (Passed / Intensification / Prerequisite) con intensificaciones:
         * por cada par estudiante+materia conviene llamar a
         * {@see self::resolverEstadoRegistroAcademicoMateria()} con el mismo `school_year`
         * que usás en las filas de `notas` (p. ej. {@see NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina()}).
         *
         * Ejemplo (un estudiante, una materia, tras armar el boletín):
         *   $schoolYear = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
         *   $estado = $this->resolverEstadoRegistroAcademicoMateria($estudianteId, (int) $materia['id'], $schoolYear);
         *   // $estado?->status, $estado?->effectiveSemester1, $estado?->effectiveSemester2
         */

        return [
            'notas_boletin' => $notasBoletin,
            'boletin_organizado' => $boletinOrganizado,
            'hay_notas_cargadas' => $hayNotasCargadas,
            'hay_avances_cargados' => $hayAvancesCargados,
            'total_estudiantes' => count($estudiantesCurso),
        ];
    }

    /**
     * Integración lectura-only con {@see SubjectStatusService} (notas + `school_year_milestones`).
     * Devuelve null si falta configuración de cierre o hay error transitorio.
     */
    public function resolverEstadoRegistroAcademicoMateria(int $estudianteId, int $materiaId, int $schoolYear): ?SubjectStatusResult
    {
        $registro = new ServicioRegistroEstadoMateria(
            $this->database,
            new SubjectStatusService(),
            new SchoolYearMilestoneService($this->database),
        );

        return $registro->calcularEstadoMateriaONull($estudianteId, $materiaId, $schoolYear);
    }

    /**
     * @return array{
     *   cursos: list<array<string, mixed>>,
     *   estudiantes: list<array<string, mixed>>,
     *   materias: list<array<string, mixed>>,
     *   materias_catalogo: list<array<string, mixed>>,
     *   estudiantes_curso: list<Estudiante>,
     *   boletin_organizado: array<int, array<string, mixed>>,
     *   hay_notas_cargadas: bool,
     *   hay_avances_cargados: bool,
     *   total_estudiantes: int,
     *   curso_info_boletin: array<string, mixed>|null
     * }
     */
    public function datosVistaBoletin(
        string $cursoFilter,
        string $estudianteFilter,
        string $trimestreFilter,
        ServicioEstudiantes $servicioEstudiantes,
        ServicioNotas $servicioNotas
    ): array {
        $cursos = $this->listarCursosActivosResumidos();
        $estudiantes = $this->listarEstudiantesActivosConCurso();
        $materias = $this->listarMateriasPorCursoOFallbackTodas($cursoFilter !== '' ? $cursoFilter : null);
        $materiasCatalogo = $this->listarMateriasCatalogoConCursosCsv();

        $estudiantesCurso = [];
        if ($cursoFilter !== '') {
            $schoolYear = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
            $estudiantesCurso = $servicioEstudiantes->obtenerPorCursoConRecursantes((int) $cursoFilter, $schoolYear);
            if ($estudianteFilter !== '') {
                $estudiantesCurso = array_values(array_filter(
                    $estudiantesCurso,
                    static fn (Estudiante $e): bool => (string) $e->getId() === $estudianteFilter
                ));
            }
        }

        $boletin = $this->construirBoletin(
            $cursoFilter,
            $estudianteFilter,
            $trimestreFilter,
            $materias,
            $estudiantesCurso,
            $servicioNotas
        );

        $cursoInfoBoletin = null;
        if ($cursoFilter !== '') {
            $cursoInfoBoletin = $this->obtenerDetalleCurso((int) $cursoFilter);
        }

        $restriccionesEstudiantes = [];
        $schoolYearRestr = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
        foreach ($estudiantesCurso as $e) {
            $eid = $e->getId();
            $regularCursoId = $e->getCursoId();
            $restriccionesEstudiantes[$eid] = [
                'es_recursante' => ($regularCursoId !== (int) $cursoFilter),
                'materias_permitidas' => [],
                'materias_solapadas' => [],
            ];

            if ($regularCursoId !== (int) $cursoFilter) {
                $recursadas = $servicioEstudiantes->obtenerRecursadasEstudiante($eid, $schoolYearRestr);
                foreach ($recursadas as $r) {
                    if ((int) $r['curso_id'] === (int) $cursoFilter) {
                        $restriccionesEstudiantes[$eid]['materias_permitidas'][] = (int) $r['materia_id'];
                    }
                }
            } else {
                $solapados = $servicioEstudiantes->verificarSolapamientosEstudiante($eid, $schoolYearRestr);
                foreach ($solapados as $s) {
                    $restriccionesEstudiantes[$eid]['materias_solapadas'][(int)$s['materia_id']] = $s['recursada_materia_nombre'];
                }
            }
        }

        return [
            'cursos' => $cursos,
            'estudiantes' => $estudiantes,
            'materias' => $materias,
            'materias_catalogo' => $materiasCatalogo,
            'estudiantes_curso' => $estudiantesCurso,
            'boletin_organizado' => $boletin['boletin_organizado'],
            'hay_notas_cargadas' => $boletin['hay_notas_cargadas'],
            'hay_avances_cargados' => $boletin['hay_avances_cargados'],
            'total_estudiantes' => $boletin['total_estudiantes'],
            'curso_info_boletin' => $cursoInfoBoletin,
            'restricciones_estudiantes' => $restriccionesEstudiantes,
        ];
    }

    /**
     * Estudiante activo con datos de curso para la vista de impresión de boletín.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerEstudianteParaImpresionBoletin(int $estudianteId): ?array
    {
        if ($estudianteId < 1) {
            return null;
        }

        return $this->database->fetch(
            <<<'SQL'
            SELECT e.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
            FROM estudiantes e
            LEFT JOIN cursos c ON e.curso_id = c.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            WHERE e.id = ? AND e.activo = 1
            SQL,
            [$estudianteId]
        );
    }

    /**
     * Tabla de notas/avances por materia para print_report_card.php (bimestres 1 y 2).
     *
     * @return array<int, array<string, mixed>>
     */
    public function construirNotasParaImpresionBoletin(int $estudianteId, ?int $cursoId): array
    {
        if ($cursoId === null || $cursoId < 1) {
            return [];
        }

        $materias = $this->database->fetchAll(
            <<<'SQL'
            SELECT m.*
            FROM materias m
            INNER JOIN materia_curso mc ON m.id = mc.materia_id
            WHERE mc.curso_id = ? AND m.activa = 1
            ORDER BY m.nombre
            SQL,
            [$cursoId]
        );

        $termsCount = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount($this->database->getPdo());
        $initialCuatrimestres = [];
        for ($t = 1; $t <= $termsCount; $t++) {
            $initialCuatrimestres[$t] = null;
        }

        // ORDER BY n.id DESC: la fila más reciente por (materia, bimestre) queda primera.
        // El bucle de llenado solo asigna el slot la primera vez, descartando duplicados más antiguos.
        $notasRaw = $this->database->fetchAll(
            <<<'SQL'
            SELECT n.*, m.nombre as materia_nombre
            FROM notas n
            LEFT JOIN materias m ON n.materia_id = m.id
            WHERE n.estudiante_id = ? AND n.bimestre IN (1, 2, 3, 4)
            ORDER BY n.bimestre, m.nombre, n.id DESC
            SQL,
            [$estudianteId]
        );

        $notasOrganizadas = [];
        foreach ($materias as $materia) {
            $mid = (int) $materia['id'];
            $notasOrganizadas[$mid] = [
                'materia' => $materia,
                'cuatrimestres' => $initialCuatrimestres,
                'avances' => ['avance1' => null, 'avance2' => null],
                'promedio' => null,
                'promedio_calculado' => false,
                'promedio_completo' => false,
            ];
        }

        foreach ($notasRaw as $nota) {
            $materiaId = (int) $nota['materia_id'];
            $bimestre = (int) $nota['bimestre'];
            if (isset($notasOrganizadas[$materiaId])
                && array_key_exists($bimestre, $notasOrganizadas[$materiaId]['cuatrimestres'])
                && $notasOrganizadas[$materiaId]['cuatrimestres'][$bimestre] === null
            ) {
                $notasOrganizadas[$materiaId]['cuatrimestres'][$bimestre] = $nota['calificacion'];
            }
        }

        $avancesRaw = $this->database->fetchAll(
            <<<'SQL'
            SELECT na.*, m.nombre as materia_nombre
            FROM notas_avance na
            LEFT JOIN materias m ON na.materia_id = m.id
            WHERE na.estudiante_id = ?
            SQL,
            [$estudianteId]
        );

        foreach ($avancesRaw as $avance) {
            $materiaId = (int) $avance['materia_id'];
            $etapa = (string) $avance['etapa'];
            if (isset($notasOrganizadas[$materiaId]) && array_key_exists($etapa, $notasOrganizadas[$materiaId]['avances'])) {
                $notasOrganizadas[$materiaId]['avances'][$etapa] = $avance['valor'];
            }
        }

        // Aplicar intensificaciones: si existe una nota de recuperación para un cuatrimestre,
        // reemplaza la nota base de ese cuatrimestre en el boletín impreso.
        $schoolYear = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
        $intensifRaw = $this->database->fetchAll(
            <<<'SQL'
            SELECT n.materia_id, n.calificacion, n.evaluation_context, n.recovery_scope
            FROM notas n
            INNER JOIN (
                SELECT materia_id, evaluation_context, recovery_scope, MAX(id) AS max_id
                FROM notas
                WHERE estudiante_id = ?
                  AND school_year = ?
                  AND evaluation_context <> 'regular'
                  AND evaluation_context IS NOT NULL
                GROUP BY materia_id, evaluation_context, recovery_scope
            ) u ON n.id = u.max_id
            SQL,
            [$estudianteId, $schoolYear]
        );

        // Para cada fila de intensificación, aplicar el reemplazo según recovery_scope.
        // Se sigue el mismo orden de prioridad que SubjectStatusService (context_order: 10/20/30).
        $ctxPriority = [
            'intensification_first_semester' => 10,
            'intensification_december'        => 20,
            'intensification_february_march'  => 30,
        ];
        usort($intensifRaw, static function (array $a, array $b) use ($ctxPriority): int {
            return ($ctxPriority[$a['evaluation_context']] ?? 99) <=> ($ctxPriority[$b['evaluation_context']] ?? 99);
        });

        foreach ($intensifRaw as $row) {
            $mid   = (int) $row['materia_id'];
            $scope = (string) ($row['recovery_scope'] ?? '');
            $grade = (float) $row['calificacion'];
            if (!isset($notasOrganizadas[$mid])) {
                continue;
            }
            if ($scope === 'first_semester') {
                $notasOrganizadas[$mid]['cuatrimestres'][1] = $grade;
            } elseif ($scope === 'second_semester') {
                $notasOrganizadas[$mid]['cuatrimestres'][2] = $grade;
            } elseif ($scope === 'both') {
                $notasOrganizadas[$mid]['cuatrimestres'][1] = $grade;
                $notasOrganizadas[$mid]['cuatrimestres'][2] = $grade;
            }
        }

        foreach ($notasOrganizadas as &$datos) {
            $notasValidas = array_filter($datos['cuatrimestres'], static function ($nota) {
                return $nota !== null && $nota !== '';
            });
            if ($notasValidas !== []) {
                $datos['promedio'] = round(array_sum($notasValidas) / count($notasValidas), 2);
                $datos['promedio_calculado'] = true;
                $datos['promedio_completo'] = (count($notasValidas) === $termsCount);
            } else {
                $datos['promedio'] = null;
                $datos['promedio_calculado'] = false;
                $datos['promedio_completo'] = false;
            }
        }
        unset($datos);

        return $notasOrganizadas;
    }

    /**
     * Datos listos para la vista de impresión del boletín (estudiante, filas por materia y estadísticas).
     *
     * @return array{
     *     encontrado: bool,
     *     estudiante: array<string, mixed>|null,
     *     notas_por_materia: array<int, array<string, mixed>>,
     *     estadisticas: array{
     *         materias_aprobadas: int,
     *         materias_reprobadas: int,
     *         materias_pendientes: int,
     *         materias_con_promedio: int,
     *         promedio_general: float|null,
     *         total_materias: int
     *     }
     * }
     */
    public function obtenerBoletinParaImpresion(int $estudianteId): array
    {
        $statsVacias = [
            'materias_aprobadas' => 0,
            'materias_reprobadas' => 0,
            'materias_pendientes' => 0,
            'materias_con_promedio' => 0,
            'promedio_general' => null,
            'total_materias' => 0,
        ];

        if ($estudianteId < 1) {
            return [
                'encontrado' => false,
                'estudiante' => null,
                'notas_por_materia' => [],
                'materias_previas' => [],
                'estadisticas' => $statsVacias,
            ];
        }

        $estudiante = $this->obtenerEstudianteParaImpresionBoletin($estudianteId);
        if ($estudiante === null) {
            return [
                'encontrado' => false,
                'estudiante' => null,
                'notas_por_materia' => [],
                'materias_previas' => [],
                'estadisticas' => $statsVacias,
            ];
        }

        $cursoId = (int) ($estudiante['curso_id'] ?? 0);
        $notasPorMateria = $this->construirNotasParaImpresionBoletin(
            $estudianteId,
            $cursoId > 0 ? $cursoId : null
        );

        $materiasAprobadas = 0;
        $materiasReprobadas = 0;
        $materiasPendientes = 0;
        $totalPromedio = 0.0;
        $materiasConPromedio = 0;

        foreach ($notasPorMateria as $datos) {
            if ($datos['promedio'] !== null && $datos['promedio'] !== '') {
                $materiasConPromedio++;
                $totalPromedio += (float) $datos['promedio'];
            }

            if (!empty($datos['promedio_completo'])) {
                $p = (float) ($datos['promedio'] ?? 0);
                if ($p >= 7.0) {
                    $materiasAprobadas++;
                } else {
                    $materiasReprobadas++;
                }
            } else {
                $materiasPendientes++;
            }
        }

        $promedioGeneral = $materiasConPromedio > 0
            ? round($totalPromedio / $materiasConPromedio, 2)
            : null;
        $materiasPrevias = $this->obtenerMateriasPreviasEstudiante($estudianteId);

        return [
            'encontrado' => true,
            'estudiante' => $estudiante,
            'notas_por_materia' => $notasPorMateria,
            'materias_previas' => $materiasPrevias,
            'estadisticas' => [
                'materias_aprobadas' => $materiasAprobadas,
                'materias_reprobadas' => $materiasReprobadas,
                'materias_pendientes' => $materiasPendientes,
                'materias_con_promedio' => $materiasConPromedio,
                'promedio_general' => $promedioGeneral,
                'total_materias' => count($notasPorMateria),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function obtenerMateriasPreviasEstudiante(int $estudianteId): array
    {
        return [];
    }

    /**
     * Etiqueta legible del contexto (cuatrimestral / períodos escolares), sin mencionar «semestre» en la UI.
     */
    public static function etiquetaContextoEvaluacionHumano(string $evaluationContext): string
    {
        return match ($evaluationContext) {
            'intensification_first_semester' => 'Intensif. 1° cuatrimestre',
            'intensification_december' => 'Diciembre',
            'intensification_february_march' => 'Febrero / marzo',
            'regular' => 'Regular',
            default => $evaluationContext,
        };
    }

    /**
     * Alcance de recuperación para mostrar en pantallas.
     */
    public static function etiquetaAlcanceRecuperacionHumano(?string $recoveryScope): string
    {
        return match ((string) ($recoveryScope ?? '')) {
            'first_semester' => '1° cuatrimestre',
            'second_semester' => '2° cuatrimestre',
            'both' => 'Ambos cuatrimestres',
            default => '—',
        };
    }

    /**
     * Intensificaciones del estudiante en un ciclo, una fila por combinación materia + contexto + alcance (última nota).
     *
     * @return list<array<string, mixed>>
     */
    public function listarIntensificacionesEstudianteParaFicha(int $estudianteId, int $schoolYear): array
    {
        if ($estudianteId < 1 || $schoolYear < 1) {
            return [];
        }

        return $this->database->fetchAll(
            <<<'SQL'
            SELECT
                n.id,
                n.estudiante_id,
                n.materia_id,
                n.calificacion,
                n.evaluation_context,
                n.recovery_scope,
                n.school_year,
                n.fecha,
                m.nombre AS materia_nombre
            FROM notas n
            INNER JOIN materias m ON m.id = n.materia_id
            INNER JOIN (
                SELECT materia_id, evaluation_context, recovery_scope, MAX(id) AS max_id
                FROM notas
                WHERE estudiante_id = ?
                  AND school_year = ?
                  AND evaluation_context <> 'regular'
                GROUP BY materia_id, evaluation_context, recovery_scope
            ) u ON n.id = u.max_id
            ORDER BY m.nombre ASC, n.evaluation_context ASC, n.id ASC
            SQL,
            [$estudianteId, $schoolYear]
        );
    }

    public function materiaPerteneceACursoActiva(int $cursoId, int $materiaId): bool
    {
        if ($cursoId < 1 || $materiaId < 1) {
            return false;
        }

        $r = $this->database->fetch(
            <<<'SQL'
            SELECT 1 AS ok
            FROM materia_curso mc
            WHERE mc.curso_id = ? AND mc.materia_id = ? AND mc.activo = 1
            LIMIT 1
            SQL,
            [$cursoId, $materiaId]
        );

        return $r !== null;
    }
}
