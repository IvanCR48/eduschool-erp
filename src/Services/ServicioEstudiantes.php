<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use SistemaAdmin\Contracts\DatabaseInterface;
use DateTime;

/**
 * Implementación concreta del ServicioEstudiantes
 * 
 * Contiene la lógica de negocio para la gestión de estudiantes.
 * Implementa la interfaz IServicioEstudiantes.
 */
class ServicioEstudiantes extends BaseService
{
    private EstudianteMapper $estudianteMapper;
    private CacheService $cacheService;
    private PaginationService $paginationService;
    private ?ServicioRegistroEstadoMateria $registroEstadoMateria;

    public function __construct(
        DatabaseInterface $database,
        EstudianteMapper $estudianteMapper,
        CacheService $cacheService = null,
        PaginationService $paginationService = null,
        ?ServicioRegistroEstadoMateria $registroEstadoMateria = null
    ) {
        parent::__construct($database);
        $this->estudianteMapper = $estudianteMapper;
        $this->cacheService = $cacheService ?? new CacheService($database);
        $this->paginationService = $paginationService ?? new PaginationService($database);
        $this->registroEstadoMateria = $registroEstadoMateria;
    }

    public function buscarPorId(int $id): ?Estudiante
    {
        $estudiante = $this->estudianteMapper->findById($id);
        
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($id);
        }
        
        return $estudiante;
    }

    public function buscarPorDni(string $dni): ?Estudiante
    {
        return $this->estudianteMapper->findByDni($dni);
    }

    public function obtenerTodos(): array
    {
        return $this->estudianteMapper->findActive();
    }

    public function obtenerPorCurso(int $cursoId): array
    {
        return $this->estudianteMapper->findBy(['curso_id' => $cursoId, 'activo' => 1]);
    }

    public function obtenerGruposActivosPorCurso(int $cursoId): array
    {
        $estudiantes = $this->obtenerPorCurso($cursoId);
        $grupos = [];
        
        foreach ($estudiantes as $estudiante) {
            $grupo = $estudiante->getGrupoTaller();
            if ($grupo !== null && $grupo !== '') {
                if (!isset($grupos[$grupo])) {
                    $grupos[$grupo] = [
                        'grupo' => $grupo,
                        'cantidad' => 0,
                        'estudiantes' => []
                    ];
                }
                $grupos[$grupo]['cantidad']++;
                $grupos[$grupo]['estudiantes'][] = $estudiante->getApellido() . ', ' . $estudiante->getNombre();
            }
        }
        
        ksort($grupos);
        return array_values($grupos);
    }

    public function buscarPorNombre(string $termino): array
    {
        // Búsqueda más sofisticada que incluye nombre y apellido
        $estudiantes = $this->estudianteMapper->findActive();
        
        $termino = strtolower(trim($termino));
        $resultados = [];
        
        foreach ($estudiantes as $estudiante) {
            $nombreCompleto = strtolower($estudiante->getNombreCompleto());
            $nombre = strtolower($estudiante->getNombre());
            $apellido = strtolower($estudiante->getApellido());
            
            if (strpos($nombreCompleto, $termino) !== false || 
                strpos($nombre, $termino) !== false || 
                strpos($apellido, $termino) !== false) {
                $resultados[] = $estudiante;
            }
        }
        
        return $resultados;
    }

    public function crear(Estudiante $estudiante): Estudiante
    {
        // Validar que el DNI no exista
        if ($this->dniExiste($estudiante->getDni())) {
            throw new \InvalidArgumentException("Ya existe un estudiante con el DNI: " . $estudiante->getDni());
        }
        
        // Validar que el estudiante sea válido (las validaciones están en el modelo)
        $guardado = $this->estudianteMapper->save($estudiante);
        $this->registrarAuditoria('CREAR', 'estudiante', $guardado->getId(), [
            'after' => $guardado->toArray(),
        ]);

        return $guardado;
    }

    public function actualizar(Estudiante $estudiante): Estudiante
    {
        // Verificar que el estudiante existe
        $estudianteExistente = $this->estudianteMapper->findById($estudiante->getId());
        if ($estudianteExistente === null) {
            throw new EstudianteNoEncontradoException($estudiante->getId());
        }
        
        // Validar que el DNI no esté en uso por otro estudiante
        if ($this->dniExiste($estudiante->getDni(), $estudiante->getId())) {
            throw new \InvalidArgumentException("Ya existe otro estudiante con el DNI: " . $estudiante->getDni());
        }

        $antes = $estudianteExistente->toArray();

        // Actualizar
        $this->estudianteMapper->update($estudiante);
        $this->registrarAuditoria('ACTUALIZAR', 'estudiante', $estudiante->getId(), [
            'before' => $antes,
            'after' => $estudiante->toArray(),
        ]);

        return $estudiante;
    }

    public function eliminar(int $id): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($id);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($id);
        }

        $antes = $estudiante->toArray();
        $resultado = $this->estudianteMapper->delete($id);
        if ($resultado) {
            $this->registrarAuditoria('ELIMINAR', 'estudiante', $id, ['before' => $antes]);
            $this->invalidarCache();
        }

        return $resultado;
    }

    public function obtenerEstadisticas(): array
    {
        $totalEstudiantes = $this->estudianteMapper->countBy(['activo' => 1]);
        $estudiantes = $this->estudianteMapper->findActive();
        
        // Estadísticas por curso
        $porCurso = [];
        $mayoresDeEdad = 0;
        $conContacto = 0;
        
        foreach ($estudiantes as $estudiante) {
            $cursoId = $estudiante->getCursoId();
            if ($cursoId) {
                if (!isset($porCurso[$cursoId])) {
                    $porCurso[$cursoId] = 0;
                }
                $porCurso[$cursoId]++;
            }
            
            if ($estudiante->esMayorDeEdad()) {
                $mayoresDeEdad++;
            }
            
            if ($estudiante->tieneContacto()) {
                $conContacto++;
            }
        }
        
        return [
            'total_estudiantes' => $totalEstudiantes,
            'mayores_de_edad' => $mayoresDeEdad,
            'menores_de_edad' => $totalEstudiantes - $mayoresDeEdad,
            'con_contacto' => $conContacto,
            'sin_contacto' => $totalEstudiantes - $conContacto,
            'por_curso' => $porCurso
        ];
    }

    public function obtenerCumpleaneros(DateTime $fecha): array
    {
        $estudiantes = $this->estudianteMapper->findActive();
        $cumpleaneros = [];
        
        $mes = $fecha->format('m');
        $dia = $fecha->format('d');
        
        foreach ($estudiantes as $estudiante) {
            $fechaNacimiento = $estudiante->getFechaNacimiento();
            if ($fechaNacimiento && 
                $fechaNacimiento->format('m') === $mes && 
                $fechaNacimiento->format('d') === $dia) {
                $cumpleaneros[] = $estudiante;
            }
        }
        
        return $cumpleaneros;
    }

    public function dniExiste(string $dni, ?int $excluirId = null): bool
    {
        return $this->estudianteMapper->existsByDni($dni, $excluirId);
    }

    /**
     * Cursos activos con especialidad y turno (formulario y filtros de estudiantes).
     *
     * @return list<array{id:int|string, anio:int|string, division:string, especialidad:?string, turno:?string}>
     */
    public function obtenerCursosActivosParaFormularioEstudiante(): array
    {
        return $this->database->fetchAll(
            'SELECT c.id, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
            FROM cursos c
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            WHERE c.activo = 1
            ORDER BY c.anio, c.division'
        );
    }

    /**
     * Curso asignado del estudiante activo, o null si no existe / inactivo.
     */
    public function obtenerCursoIdEstudianteActivo(int $estudianteId): ?int
    {
        $fila = $this->database->fetch(
            'SELECT curso_id FROM estudiantes WHERE id = ? AND activo = 1',
            [$estudianteId]
        );
        if ($fila === null || $fila === []) {
            return null;
        }

        $cid = $fila['curso_id'] ?? null;
        if ($cid === null || $cid === '') {
            return null;
        }

        return (int) $cid;
    }

    /**
     * Método adicional para obtener estudiantes con información de curso
     */
    public function obtenerConInformacionCurso(): array
    {
        // Este método podría expandirse para incluir información del curso
        // usando joins o consultas adicionales
        return $this->estudianteMapper->findActive();
    }

    /**
     * Total de estudiantes activos que cumplen los filtros de la vista de listado.
     *
     * @param list<int>|null $alcanceCursoIds
     */
    public function contarListadoVista(string $search, ?int $cursoIdFiltro, ?array $alcanceCursoIds, ?string $grupoTaller = null): int
    {
        return $this->estudianteMapper->countListadoVista($search, $cursoIdFiltro, $alcanceCursoIds, $grupoTaller);
    }

    /**
     * @param list<int>|null $alcanceCursoIds
     *
     * @return list<array<string, mixed>>
     */
    public function listarVistaPaginado(string $search, ?int $cursoIdFiltro, ?array $alcanceCursoIds, int $limit, int $offset, ?string $grupoTaller = null): array
    {
        return $this->estudianteMapper->findListadoVistaPaginado($search, $cursoIdFiltro, $alcanceCursoIds, $limit, $offset, $grupoTaller);
    }

    /**
     * Método adicional para validar datos antes de guardar
     */
    public function validarDatosEstudiante(Estudiante $estudiante): array
    {
        $errores = [];
        
        // Validar DNI
        if ($this->dniExiste($estudiante->getDni(), $estudiante->getId())) {
            $errores[] = "El DNI ya está en uso";
        }
        
        // Validar email si existe
        if ($estudiante->getEmail() && !filter_var($estudiante->getEmail(), FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
            $errores[] = "El email no es válido";
        }
        
        // Validar fecha de nacimiento
        if ($estudiante->getFechaNacimiento()) {
            $edad = $estudiante->getEdad();
            if ($edad < 0 || $edad > 100) {
                $errores[] = "La edad debe estar entre 0 y 100 años";
            }
        }
        
        return $errores;
    }

    /**
     * Obtener estudiantes con paginación
     */
    public function obtenerConPaginacion(int $page = 1, int $pageSize = 20, array $filtros = []): array
    {
        $cacheKey = "estudiantes:page:{$page}:size:{$pageSize}:" . md5(serialize($filtros));
        
        return $this->cacheService->remember($cacheKey, function() use ($page, $pageSize, $filtros) {
            // Contar total de estudiantes
            $totalItems = $this->estudianteMapper->countBy(array_merge(['activo' => 1], $filtros));
            
            // Calcular paginación
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            
            // Obtener estudiantes paginados
            $estudiantes = $this->estudianteMapper->findWithPagination($pagination['offset'], $pagination['page_size'], $filtros);
            
            return [
                'estudiantes' => $estudiantes,
                'pagination' => $pagination
            ];
        }, 300); // Cache por 5 minutos
    }

    /**
     * Buscar estudiantes con paginación
     */
    public function buscarConPaginacion(string $termino, int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = "estudiantes:search:{$termino}:page:{$page}:size:{$pageSize}";
        
        return $this->cacheService->remember($cacheKey, function() use ($termino, $page, $pageSize) {
            // Obtener todos los estudiantes que coinciden
            $estudiantes = $this->buscarPorNombre($termino);
            $totalItems = count($estudiantes);
            
            // Calcular paginación
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            
            // Aplicar paginación manualmente
            $estudiantesPaginados = array_slice($estudiantes, $pagination['offset'], $pagination['page_size']);
            
            return [
                'estudiantes' => $estudiantesPaginados,
                'pagination' => $pagination
            ];
        }, 180); // Cache por 3 minutos
    }

    /**
     * Obtener estudiantes por curso con paginación
     */
    public function obtenerPorCursoConPaginacion(int $cursoId, int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = "estudiantes:curso:{$cursoId}:page:{$page}:size:{$pageSize}";
        
        return $this->cacheService->remember($cacheKey, function() use ($cursoId, $page, $pageSize) {
            // Contar total de estudiantes en el curso
            $totalItems = $this->estudianteMapper->countBy(['curso_id' => $cursoId, 'activo' => 1]);
            
            // Calcular paginación
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            
            // Obtener estudiantes paginados
            $estudiantes = $this->estudianteMapper->findWithPagination(
                $pagination['offset'], 
                $pagination['page_size'], 
                ['curso_id' => $cursoId, 'activo' => 1]
            );
            
            return [
                'estudiantes' => $estudiantes,
                'pagination' => $pagination
            ];
        }, 300); // Cache por 5 minutos
    }

    /**
     * Invalidar cache de estudiantes
     */
    public function invalidarCache(): void
    {
        $this->cacheService->invalidatePattern("estudiantes:*");
    }

    /**
     * Obtener estadísticas con cache
     */
    public function obtenerEstadisticasConCache(): array
    {
        $cacheKey = "estadisticas:estudiantes";
        
        return $this->cacheService->remember($cacheKey, function() {
            return $this->obtenerEstadisticas();
        }, 600); // Cache por 10 minutos
    }

    public function actualizarContactoFicha(
        int $estudianteId,
        ?string $telefono,
        ?string $email,
        ?string $domicilio,
        ?string $grupoSanguineo = null,
        ?string $obraSocial = null,
        ?int $nuevoTurnoId = null,
        ?string $dniResponsable = null,
        ?string $grupoTaller = null,
        ?string $fechaNacimiento = null,
        ?string $fechaIngreso = null
    ): array {
        $this->buscarPorId($estudianteId);
        $this->estudianteMapper->actualizarContactoFicha(
            $estudianteId,
            $telefono,
            $email,
            $domicilio,
            $grupoSanguineo,
            $obraSocial,
            $dniResponsable,
            $grupoTaller,
            $fechaNacimiento,
            $fechaIngreso
        );

        $warning = null;
        if ($nuevoTurnoId !== null && $nuevoTurnoId > 0) {
            $infoActual = $this->estudianteMapper->obtenerAnioDivisionPorEstudiante($estudianteId);
            if (!empty($infoActual['anio']) && !empty($infoActual['division'])) {
                $cursoMatch = $this->estudianteMapper->buscarCursoPorAnioDivisionTurno(
                    (int)$infoActual['anio'],
                    (string)$infoActual['division'],
                    $nuevoTurnoId
                );
                if ($cursoMatch) {
                    $this->estudianteMapper->actualizarCursoEstudiante($estudianteId, (int)$cursoMatch['id']);
                } else {
                    $warning = "No existe un curso con el turno seleccionado para el mismo año y división.";
                }
            }
        }

        return ['success' => true, 'warning' => $warning];
    }

    public function guardarResponsableFicha(
        int $estudianteId,
        string $nombre,
        string $apellido,
        ?string $dni,
        string $telefono,
        ?string $email,
        string $parentesco,
        int $esContactoEmergencia
    ): bool {
        $this->buscarPorId($estudianteId);
        return $this->estudianteMapper->insertarResponsable(
            $estudianteId, $nombre, $apellido, $dni, $telefono, $email, $parentesco, $esContactoEmergencia
        );
    }

    public function guardarContactoEmergenciaFicha(int $estudianteId, string $nombre, string $telefono, string $parentesco): bool
    {
        $this->buscarPorId($estudianteId);
        return $this->estudianteMapper->insertarContactoEmergencia($estudianteId, $nombre, $telefono, $parentesco);
    }

    public function eliminarResponsableFicha(int $estudianteId, int $responsableId): bool
    {
        $this->buscarPorId($estudianteId);
        return $this->estudianteMapper->eliminarResponsable($estudianteId, $responsableId);
    }

    public function eliminarContactoEmergenciaFicha(int $estudianteId, int $contactoId): bool
    {
        $this->buscarPorId($estudianteId);
        return $this->estudianteMapper->eliminarContactoEmergencia($estudianteId, $contactoId);
    }

    public function cambiarCursoFicha(int $estudianteId, int $nuevoCursoId): array
    {
        $this->buscarPorId($estudianteId);
        $nuevoCurso = $this->estudianteMapper->obtenerCursoActivoPorId($nuevoCursoId);
        if (!$nuevoCurso) {
            throw new \Exception('El curso seleccionado no existe o no está activo');
        }

        $cursoActual = $this->estudianteMapper->obtenerCursoActualEstudiante($estudianteId);
        if (!$cursoActual) {
            throw new \Exception('No se pudo obtener la información del estudiante actual');
        }

        $pdo = $this->estudianteMapper->getDatabase()->getPdo();
        $pdo->beginTransaction();
        try {
            $this->estudianteMapper->eliminarMateriasPreviasEstudiante($estudianteId);
            $this->estudianteMapper->actualizarCursoEstudiante($estudianteId, $nuevoCursoId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['nuevo_curso' => $nuevoCurso, 'curso_actual' => $cursoActual];
    }

    /**
     * Arma el DTO de vista para estudiante_ficha (boletín y métricas incluidos).
     */
    public function obtenerVistaFichaEstudiante(int $estudianteId): ?array
    {
        $raw = $this->estudianteMapper->obtenerDatosRawVistaFichaEstudiante($estudianteId);
        if ($raw === null) {
            return null;
        }

        $stats = [
            'llamados_total' => $raw['stats_llamados_total'],
            'llamados_mes' => $raw['stats_llamados_mes'],
        ];

        $notasEstudiante = [];
        $porcentajeAprobadas = 0;
        $materiasAprobadas = 0;
        $totalMaterias = 0;

        $materias = $raw['materias'];
        if (!empty($raw['estudiante']['curso_id']) && count($materias) > 0) {
            $termsCountFicha = \SistemaAdmin\Services\SchoolConfigService::getAcademicTermsCount($this->database->getPdo());
            $initialCuatrimestres = [];
            for ($t = 1; $t <= $termsCountFicha; $t++) {
                $initialCuatrimestres[$t] = null;
            }

            $notasOrganizadas = [];
            foreach ($materias as $materia) {
                $notasOrganizadas[$materia['id']] = [
                    'materia' => $materia,
                    'cuatrimestres' => $initialCuatrimestres,
                    'avances' => [
                        'avance1' => ['valor' => null],
                        'avance2' => ['valor' => null],
                    ],
                    'promedio' => null,
                ];
            }

            foreach ($raw['notas_raw'] as $nota) {
                if (isset($notasOrganizadas[$nota['materia_id']])) {
                    $notasOrganizadas[$nota['materia_id']]['cuatrimestres'][(int) $nota['bimestre']] = $nota['calificacion'];
                }
            }

            foreach ($raw['avances_raw'] as $avance) {
                $mid = $avance['materia_id'] ?? null;
                $etapa = $avance['etapa'] ?? null;
                if ($mid === null || $etapa === null) {
                    continue;
                }
                if (!isset($notasOrganizadas[$mid]['avances'][$etapa])) {
                    continue;
                }
                $notasOrganizadas[$mid]['avances'][$etapa] = [
                    'valor' => $avance['valor'],
                ];
            }

            foreach ($notasOrganizadas as $materiaId => &$datos) {
                $notasValidas = array_filter($datos['cuatrimestres'], fn ($n) => $n !== null);
                if (count($notasValidas) > 0) {
                    $datos['promedio'] = round(array_sum($notasValidas) / count($notasValidas), 2);
                    $datos['promedio_calculado'] = (count($notasValidas) === $termsCountFicha);
                } else {
                    $datos['promedio'] = null;
                    $datos['promedio_calculado'] = false;
                }
            }
            unset($datos);

            if ($this->registroEstadoMateria !== null && $notasOrganizadas !== []) {
                try {
                    $schoolYear = NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable());
                    $materiaIds = array_map(static fn ($id): int => (int) $id, array_keys($notasOrganizadas));
                    $estadosPorMateria = $this->registroEstadoMateria->calcularEstadosPorEstudiante(
                        $estudianteId,
                        $schoolYear,
                        $materiaIds
                    );
                    foreach ($notasOrganizadas as $mid => &$datosConEstado) {
                        $midInt = (int) $mid;
                        if (!isset($estadosPorMateria[$midInt])) {
                            continue;
                        }
                        $resultado = $estadosPorMateria[$midInt];
                        $datosConEstado['registro_academico_estado'] = $resultado;

                        // Sobrescribir cuatrimestres con las calificaciones efectivas (post-intensificación)
                        // para que el boletín de la ficha y el promedio reflejen la nota final real.
                        if ($resultado->effectiveSemester1 !== null) {
                            $datosConEstado['cuatrimestres'][1] = $resultado->effectiveSemester1;
                        }
                        if ($resultado->effectiveSemester2 !== null) {
                            $datosConEstado['cuatrimestres'][2] = $resultado->effectiveSemester2;
                        }

                        // Recalcular promedio con los valores efectivos
                        $notasEfectivas = array_filter(
                            $datosConEstado['cuatrimestres'],
                            static fn ($n): bool => $n !== null
                        );
                        if (count($notasEfectivas) > 0) {
                            $datosConEstado['promedio'] = round(array_sum($notasEfectivas) / count($notasEfectivas), 2);
                            $datosConEstado['promedio_calculado'] = (count($notasEfectivas) === $termsCountFicha);
                        }
                    }
                    unset($datosConEstado);
                } catch (\Throwable) {
                    // Sin fila en school_year_milestones u otro error: la ficha sigue sin el bloque de estado
                }
            }

            $totalMaterias = count($materias);
            $materiasConNotas = 0;
            foreach ($notasOrganizadas as $datos) {
                if (!empty($datos['promedio_calculado'])) {
                    $materiasConNotas++;
                    if ($datos['promedio'] >= 7) {
                        $materiasAprobadas++;
                    }
                }
            }

            if ($materiasConNotas > 0) {
                $porcentajeAprobadas = round(($materiasAprobadas / $totalMaterias) * 100, 1);
            }
            $notasEstudiante = $notasOrganizadas;
        }

        return [
            'estudiante' => $raw['estudiante'],
            'llamados' => $raw['llamados'],
            'responsables' => $raw['responsables'],
            'contactos_emergencia' => $raw['contactos_emergencia'],
            'turnos_lista' => $raw['turnos_lista'],
            'stats' => $stats,
            'llamados_amonestacion_verbal' => $raw['llamados_amonestacion_verbal'],
            'cursos_disponibles' => $raw['cursos_disponibles'],
            'notas_estudiante' => $notasEstudiante,
            'porcentaje_aprobadas' => $porcentajeAprobadas,
            'materias_aprobadas' => $materiasAprobadas,
            'total_materias' => $totalMaterias,
        ];
    }

    public function guardarRecursada(int $estudianteId, int $materiaId, int $cursoId, int $schoolYear): array
    {
        try {
            $existente = $this->estudianteMapper->obtenerRecursadasPorEstudiante($estudianteId, $schoolYear);
            foreach ($existente as $r) {
                if ((int)$r['materia_id'] === $materiaId) {
                    return ['success' => false, 'error' => 'El estudiante ya está recursando esta materia en este ciclo lectivo.'];
                }
            }
            $id = $this->estudianteMapper->crearRecursada($estudianteId, $materiaId, $cursoId, $schoolYear);
            return ['success' => true, 'id' => $id];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error al guardar la materia recursada: ' . $e->getMessage()];
        }
    }

    public function eliminarRecursada(int $recursadaId): array
    {
        try {
            $this->estudianteMapper->eliminarRecursada($recursadaId);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error al eliminar la materia recursada: ' . $e->getMessage()];
        }
    }

    public function obtenerRecursadasEstudiante(int $estudianteId, int $schoolYear): array
    {
        return $this->estudianteMapper->obtenerRecursadasPorEstudiante($estudianteId, $schoolYear);
    }

    public function verificarSolapamientosEstudiante(int $estudianteId, int $schoolYear): array
    {
        $cursoId = null;
        $raw = $this->estudianteMapper->obtenerDatosRawVistaFichaEstudiante($estudianteId);
        if ($raw && isset($raw['estudiante']['curso_id'])) {
            $cursoId = (int)$raw['estudiante']['curso_id'];
        }
        if (!$cursoId) {
            return [];
        }

        $recursadaHorarios = $this->estudianteMapper->obtenerHorariosRecursadas($estudianteId, $schoolYear);
        $regularHorarios = $this->estudianteMapper->obtenerHorariosCurso($cursoId);

        $overlaps = [];
        foreach ($regularHorarios as $reg) {
            foreach ($recursadaHorarios as $rec) {
                if (strtolower($reg['dia_semana']) === strtolower($rec['dia_semana'])) {
                    $regStart = $reg['hora_inicio'];
                    $regEnd = $reg['hora_fin'];
                    $recStart = $rec['hora_inicio'];
                    $recEnd = $rec['hora_fin'];
                    if ($regStart < $recEnd && $regEnd > $recStart) {
                        $overlaps[(int)$reg['materia_id']] = [
                            'materia_id' => (int)$reg['materia_id'],
                            'materia_nombre' => $reg['materia_nombre'],
                            'recursada_materia_id' => (int)$rec['materia_id'],
                            'recursada_materia_nombre' => $rec['materia_nombre'],
                        ];
                    }
                }
            }
        }
        return array_values($overlaps);
    }

    public function obtenerPorCursoConRecursantes(int $cursoId, int $schoolYear): array
    {
        $regulares = $this->obtenerPorCurso($cursoId);
        $recursantesRows = $this->estudianteMapper->obtenerEstudiantesRecursantesDeCurso($cursoId, $schoolYear);
        $recursantes = array_map([$this->estudianteMapper, 'mapRowToEstudiante'], $recursantesRows);

        $resultado = [];
        foreach ($regulares as $e) {
            $resultado[$e->getId()] = $e;
        }
        foreach ($recursantes as $e) {
            $resultado[$e->getId()] = $e;
        }

        usort($resultado, function($a, $b) {
            return strcasecmp($a->getApellido() . ', ' . $a->getNombre(), $b->getApellido() . ', ' . $b->getNombre());
        });

        return array_values($resultado);
    }
}
