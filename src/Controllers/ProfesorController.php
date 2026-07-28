<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioProfesores;
use SistemaAdmin\Models\Profesor;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Exceptions\ProfesorNoEncontradoException;
use DateTime;

/**
 * Controller para manejar las peticiones HTTP relacionadas con profesores
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para profesores.
 */
class ProfesorController extends BaseService
{
    private ServicioProfesores $servicioProfesores;

    public function __construct(DatabaseInterface $database, ServicioProfesores $servicioProfesores)
    {
        parent::__construct($database);
        $this->servicioProfesores = $servicioProfesores;
    }

    /**
     * Maneja la petición GET para listar profesores
     */
    public function listar(array $filtros = []): array
    {
        try {
            $profesores = $this->servicioProfesores->obtenerTodos();
            
            // Aplicar filtros si se proporcionan
            if (!empty($filtros['especialidad'])) {
                $profesores = array_filter($profesores, function($profesor) use ($filtros) {
                    return stripos($profesor->getEspecialidad() ?? '', $filtros['especialidad']) !== false;
                });
            }
            
            if (!empty($filtros['search'])) {
                $search = strtolower($filtros['search']);
                $profesores = array_filter($profesores, function($profesor) use ($search) {
                    return stripos($profesor->getApellido(), $search) !== false ||
                           stripos($profesor->getNombre(), $search) !== false ||
                           stripos($profesor->getDni(), $search) !== false;
                });
            }
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'dni' => $profesor->getDni(),
                    'apellido' => $profesor->getApellido(),
                    'nombre' => $profesor->getNombre(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'fecha_nacimiento' => $profesor->getFechaNacimiento()?->format('Y-m-d'),
                    'edad' => $profesor->getEdad(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'titulo' => $profesor->getTitulo(),
                    'telefono_fijo' => $profesor->getTelefonoFijo(),
                    'telefono_celular' => $profesor->getTelefonoCelular(),
                    'email' => $profesor->getEmail(),
                    'domicilio' => $profesor->getDomicilio(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d'),
                    'activo' => $profesor->esActivo(),
                    'tiene_cursos' => false // Temporalmente deshabilitado hasta crear tabla profesor_curso
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => array_values($profesoresArray),
                'total' => count($profesoresArray)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_profesores'
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener un profesor por ID
     */
    public function obtener(int $profesorId): array
    {
        try {
            $profesor = $this->servicioProfesores->obtenerPorId($profesorId);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $profesor->getId(),
                    'dni' => $profesor->getDni(),
                    'apellido' => $profesor->getApellido(),
                    'nombre' => $profesor->getNombre(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'fecha_nacimiento' => $profesor->getFechaNacimiento()?->format('Y-m-d'),
                    'edad' => $profesor->getEdad(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'titulo' => $profesor->getTitulo(),
                    'telefono_fijo' => $profesor->getTelefonoFijo(),
                    'telefono_celular' => $profesor->getTelefonoCelular(),
                    'email' => $profesor->getEmail(),
                    'domicilio' => $profesor->getDomicilio(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d'),
                    'activo' => $profesor->esActivo(),
                    'cursos_asignados' => $this->servicioProfesores->obtenerCursosAsignados($profesorId),
                    'materias_asignadas' => $this->servicioProfesores->obtenerMateriasAsignadas($profesorId)
                ]
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_profesor',
                'profesor_id' => $profesorId
            ]);
        }
    }

    /**
     * Maneja la petición POST para crear un nuevo profesor
     */
    public function crear(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosProfesor($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Crear el objeto Profesor
            try {
                $profesor = new Profesor(
                    $datos['dni'],
                    $datos['apellido'],
                    $datos['nombre'],
                    !empty($datos['fecha_nacimiento']) ? new DateTime($datos['fecha_nacimiento']) : null,
                    $datos['domicilio'] ?? null,
                    $datos['telefono_fijo'] ?? null,
                    $datos['telefono_celular'] ?? null,
                    $datos['email'] ?? null,
                    $datos['titulo'] ?? null,
                    !empty($datos['especialidad_id']) ? (int)$datos['especialidad_id'] : null,
                    $datos['especialidad'] ?? null,
                    !empty($datos['fecha_ingreso']) ? new DateTime($datos['fecha_ingreso']) : null
                );
            } catch (\InvalidArgumentException $e) {
                return [
                    'success' => false,
                    'errors' => [$e->getMessage()]
                ];
            }
            
            // Guardar el profesor
            $profesorGuardado = $this->servicioProfesores->crear($profesor);
            $this->registrarAuditoria(
                'CREAR_PROFESOR',
                'profesor',
                $profesorGuardado->getId(),
                $profesorGuardado->toArray()
            );
            
            return [
                'success' => true,
                'data' => [
                    'id' => $profesorGuardado->getId(),
                    'nombre_completo' => $profesorGuardado->getNombreCompleto(),
                    'dni' => $profesorGuardado->getDni()
                ],
                'message' => 'Profesor creado exitosamente'
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'crear_profesor'
            ]);
        }
    }

    /**
     * Maneja la petición PUT para actualizar un profesor
     */
    public function actualizar(int $profesorId, array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosProfesor($datos, $profesorId);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Obtener el profesor existente
            $profesor = $this->servicioProfesores->obtenerPorId($profesorId);
            
            // Actualizar los datos
            $profesor->setDni($datos['dni']);
            $profesor->setApellido($datos['apellido']);
            $profesor->setNombre($datos['nombre']);
            $profesor->setFechaNacimiento(!empty($datos['fecha_nacimiento']) ? new DateTime($datos['fecha_nacimiento']) : null);
            $profesor->setDomicilio($datos['domicilio'] ?? null);
            $profesor->setTelefonoFijo($datos['telefono_fijo'] ?? null);
            $profesor->setTelefonoCelular($datos['telefono_celular'] ?? null);
            $profesor->setEmail($datos['email'] ?? null);
            $profesor->setTitulo($datos['titulo'] ?? null);
            $profesor->setEspecialidad($datos['especialidad'] ?? null);
            $profesor->setFechaIngreso(!empty($datos['fecha_ingreso']) ? new DateTime($datos['fecha_ingreso']) : null);
            
            // Guardar los cambios
            $this->servicioProfesores->actualizar($profesor);
            $this->registrarAuditoria(
                'ACTUALIZAR_PROFESOR',
                'profesor',
                $profesor->getId(),
                $profesor->toArray()
            );
            
            return [
                'success' => true,
                'data' => [
                    'id' => $profesor->getId(),
                    'nombre_completo' => $profesor->getNombreCompleto()
                ],
                'message' => 'Profesor actualizado exitosamente'
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'actualizar_profesor',
                'profesor_id' => $profesorId
            ]);
        }
    }

    public function actualizarInformacionFicha(int $profesorId, array $datos): array
    {
        try {
            $normalizarTexto = static function ($valor): ?string {
                if (!isset($valor)) {
                    return null;
                }
                $valor = trim((string) $valor);
                return $valor === '' ? null : $valor;
            };

            $especialidadId = null;
            if (isset($datos['especialidad_id']) && $datos['especialidad_id'] !== '') {
                $especialidadId = (int) $datos['especialidad_id'];
            }

            $this->servicioProfesores->actualizarInformacionFicha(
                $profesorId,
                $normalizarTexto($datos['telefono_fijo'] ?? null),
                $normalizarTexto($datos['telefono_celular'] ?? null),
                $normalizarTexto($datos['email'] ?? null),
                $normalizarTexto($datos['domicilio'] ?? null),
                $normalizarTexto($datos['titulo'] ?? null),
                $especialidadId,
                $normalizarTexto($datos['fecha_nacimiento'] ?? null),
                $normalizarTexto($datos['fecha_ingreso'] ?? null)
            );

            return [
                'success' => true,
                'message' => 'Información del profesor actualizada correctamente'
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'actualizar_informacion_ficha_profesor',
                'profesor_id' => $profesorId
            ]);
        }
    }

    public function asignarCursoFicha(int $profesorId, array $datos): array
    {
        try {
            $cursoId = (int) ($datos['curso_id'] ?? 0);
            if ($cursoId <= 0) {
                return ['success' => false, 'error' => 'Curso inválido'];
            }
            $this->servicioProfesores->asignarCurso($profesorId, $cursoId);
            return ['success' => true, 'message' => 'Curso asignado correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'asignar_curso_ficha', 'profesor_id' => $profesorId]);
        }
    }

    public function desasignarCursoFicha(int $profesorId, array $datos): array
    {
        try {
            $asignacionId = (int) ($datos['asignacion_id'] ?? 0);
            if ($asignacionId <= 0) {
                return ['success' => false, 'error' => 'Asignación inválida'];
            }
            $this->servicioProfesores->desasignarCurso($profesorId, $asignacionId);
            return ['success' => true, 'message' => 'Curso desasignado correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'desasignar_curso_ficha', 'profesor_id' => $profesorId]);
        }
    }

    public function asignarMateriaFicha(int $profesorId, array $datos): array
    {
        try {
            $materiaId = (int) ($datos['materia_id'] ?? 0);
            $cursoId = (int) ($datos['curso_id'] ?? 0);
            if ($materiaId <= 0 || $cursoId <= 0) {
                return ['success' => false, 'error' => 'Debe seleccionar tanto la materia como el curso'];
            }
            $grupoTaller = isset($datos['grupo_taller']) ? trim((string) $datos['grupo_taller']) : null;
            $resultado = $this->servicioProfesores->asignarMateria($profesorId, $materiaId, $cursoId, $grupoTaller);
            if (empty($resultado['success'])) {
                return $resultado;
            }
            return ['success' => true, 'message' => 'Materia asignada correctamente al curso especificado'];
        } catch (ProfesorNoEncontradoException $e) {
            return ['success' => false, 'error' => 'No se encontró el docente en la base de datos.'];
        } catch (\Throwable $e) {
            $raw = $e->getMessage();
            error_log('ProfesorController::asignarMateriaFicha id=' . $profesorId . ' — ' . $raw);

            $esDuplicado = stripos($raw, 'Duplicate') !== false
                || stripos($raw, '1062') !== false
                || stripos($raw, 'UNIQUE') !== false
                || stripos($raw, 'Entrada duplicada') !== false
                || stripos($raw, 'duplicad') !== false
                || stripos($raw, 'SQLSTATE[23000]') !== false;

            if ($esDuplicado) {
                return [
                    'success' => false,
                    'error' => 'Ya existe un registro de esa materia y curso para este año lectivo. Recargue la ficha: si la materia no figura como asignada, puede haber una fila inactiva; en ese caso use «desasignar» y vuelva a asignar, o repare la tabla profesor_materia.',
                ];
            }
            if (stripos($raw, 'foreign key') !== false || stripos($raw, '1452') !== false || stripos($raw, 'Cannot add or update a child row') !== false) {
                return [
                    'success' => false,
                    'error' => 'La materia o el curso no coincide con los datos en la base (clave foránea). Verifique que el curso y la materia existan y que la materia pueda dictarse en ese curso.',
                ];
            }

            // No usar handleError: con config/production.php el sistema oculta el detalle y solo muestra texto genérico.
            $detalle = preg_replace('/^Error ejecutando consulta SQL:\s*/i', '', $raw);
            if (strlen($detalle) > 400) {
                $detalle = substr($detalle, 0, 400) . '…';
            }

            return [
                'success' => false,
                'error' => 'No se pudo guardar la asignación. Detalle: ' . $detalle,
            ];
        }
    }

    public function desasignarMateriaFicha(int $profesorId, array $datos): array
    {
        try {
            $materiaCursoId = (int) ($datos['materia_curso_id'] ?? 0);
            if ($materiaCursoId <= 0) {
                return ['success' => false, 'error' => 'Asignación de materia inválida'];
            }
            $this->servicioProfesores->desasignarMateria($profesorId, $materiaCursoId);
            return ['success' => true, 'message' => 'Materia desasignada correctamente del curso especificado'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'desasignar_materia_ficha', 'profesor_id' => $profesorId]);
        }
    }

    public function crearSuplenciaFicha(int $profesorId, array $datos, int $usuarioId): array
    {
        try {
            $materiaId = (int) ($datos['materia_id'] ?? 0);
            $fechaInicio = trim((string) ($datos['fecha_inicio'] ?? ''));
            $fechaFin = trim((string) ($datos['fecha_fin'] ?? ''));
            $motivo = trim((string) ($datos['motivo'] ?? ''));
            $suplenteId = isset($datos['suplente_id']) && $datos['suplente_id'] !== '' ? (int) $datos['suplente_id'] : null;
            $fueraServicio = isset($datos['fuera_servicio']) ? 1 : 0;

            if ($materiaId <= 0 || $fechaInicio === '' || $motivo === '') {
                return ['success' => false, 'error' => 'Debe completar todos los campos requeridos (materia, fecha de inicio y motivo)'];
            }

            $resultado = $this->servicioProfesores->crearSuplencia(
                $profesorId,
                $materiaId,
                $fechaInicio,
                $fechaFin === '' ? null : $fechaFin,
                $motivo,
                $suplenteId,
                $fueraServicio,
                $usuarioId
            );

            if (empty($resultado['success'])) {
                return $resultado;
            }
            return ['success' => true, 'message' => 'Suplencia creada correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'crear_suplencia_ficha', 'profesor_id' => $profesorId]);
        }
    }

    public function finalizarSuplenciaFicha(int $profesorId, array $datos): array
    {
        try {
            $suplenciaId = (int) ($datos['suplencia_id'] ?? 0);
            if ($suplenciaId <= 0) {
                return ['success' => false, 'error' => 'Suplencia inválida'];
            }
            $this->servicioProfesores->finalizarSuplencia($profesorId, $suplenciaId);
            return ['success' => true, 'message' => 'Suplencia finalizada correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'finalizar_suplencia_ficha', 'profesor_id' => $profesorId]);
        }
    }

    public function guardarSuplenteFicha(array $datos): array
    {
        try {
            $dni = trim((string) ($datos['dni'] ?? ''));
            $apellido = trim((string) ($datos['apellido'] ?? ''));
            $nombre = trim((string) ($datos['nombre'] ?? ''));
            if ($dni === '' || $apellido === '' || $nombre === '') {
                return ['success' => false, 'error' => 'Debe completar DNI, Apellido y Nombre del suplente'];
            }

            $resultado = $this->servicioProfesores->guardarSuplente(
                $dni,
                $apellido,
                $nombre,
                ($datos['telefono_celular'] ?? '') !== '' ? trim((string)$datos['telefono_celular']) : null,
                ($datos['email'] ?? '') !== '' ? trim((string)$datos['email']) : null,
                ($datos['especialidad'] ?? '') !== '' ? trim((string)$datos['especialidad']) : null
            );

            if (empty($resultado['success'])) {
                return $resultado;
            }
            return ['success' => true, 'message' => 'Suplente registrado correctamente', 'suplente_id' => $resultado['suplente_id'] ?? null];
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'uk_suplente_dni') !== false) {
                return ['success' => false, 'error' => 'Ya existe un suplente con este DNI. Por favor, verifique los datos o use el suplente existente.'];
            }
            return $this->handleError($e, ['action' => 'guardar_suplente_ficha']);
        }
    }

    /**
     * POST de ficha del profesor con PRG (teacher_profile.php).
     *
     * @param array<string, mixed> $post
     *
     * @return array{redirect: string|null, error: string}
     */
    public function procesarPostFichaProfesor(int $profesorId, array $post, bool $puedeEditarAdmin, int $usuarioRegistroId): array
    {
        $sinCambio = ['redirect' => null, 'error' => ''];
        if ($profesorId < 1) {
            return $sinCambio;
        }

        $base = 'teacher_profile.php?id=' . $profesorId;
        $sinPermiso = ['redirect' => null, 'error' => 'No tiene permisos para realizar esta acción.'];

        if (isset($post['actualizar_profesor'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->actualizarInformacionFicha($profesorId, $post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'actualizado']), 'error' => ''];
            }
            $errores = $r['errors'] ?? [];
            $msg = (string) ($r['error'] ?? (is_array($errores) && $errores !== [] ? (string) $errores[0] : 'Error al actualizar profesor'));

            return ['redirect' => null, 'error' => $msg];
        }

        if (isset($post['asignar_curso'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->asignarCursoFicha($profesorId, $post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'curso_asignado']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al asignar curso')];
        }

        if (isset($post['desasignar_curso'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->desasignarCursoFicha($profesorId, $post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'curso_desasignado']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al desasignar curso')];
        }

        if (isset($post['crear_suplencia'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->crearSuplenciaFicha($profesorId, $post, $usuarioRegistroId);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'suplencia_creada']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al crear suplencia')];
        }

        if (isset($post['finalizar_suplencia'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->finalizarSuplenciaFicha($profesorId, $post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'suplencia_finalizada']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al finalizar suplencia')];
        }

        if (isset($post['asignar_materia'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->asignarMateriaFicha($profesorId, $post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'materia_asignada']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al asignar materia')];
        }

        if (isset($post['desasignar_materia'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->desasignarMateriaFicha($profesorId, $post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'materia_desasignada']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al desasignar materia')];
        }

        if (isset($post['guardar_suplente'])) {
            if (!$puedeEditarAdmin) {
                return $sinPermiso;
            }
            $r = $this->guardarSuplenteFicha($post);
            if (!empty($r['success'])) {
                return ['redirect' => $base . '&' . http_build_query(['success' => 'suplente_registrado']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al registrar suplente')];
        }

        return $sinCambio;
    }

    public function obtenerFicha(int $profesorId): array
    {
        try {
            $data = $this->servicioProfesores->obtenerDatosFicha($profesorId);
            if (!$data) {
                return ['success' => false, 'error' => 'Profesor no encontrado'];
            }
            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            return $this->handleError($e, ['action' => 'obtener_ficha_profesor', 'profesor_id' => $profesorId]);
        }
    }

    /**
     * Años de antigüedad desde fecha de ingreso (Y-m-d).
     */
    public static function antiguedadAniosDesdeFecha(?string $fechaIngreso): int
    {
        if ($fechaIngreso === null || $fechaIngreso === '') {
            return 0;
        }
        $t = strtotime($fechaIngreso);
        if ($t === false) {
            return 0;
        }

        return (int) floor((time() - $t) / (365.25 * 24 * 3600));
    }

    /**
     * Edad en años completos desde fecha de nacimiento, o null si no hay dato válido.
     */
    public static function edadAniosDesdeFecha(?string $fechaNac): ?int
    {
        if ($fechaNac === null || $fechaNac === '') {
            return null;
        }
        $t = strtotime($fechaNac);
        if ($t === false) {
            return null;
        }

        return (int) floor((time() - $t) / (365.25 * 24 * 3600));
    }

    /**
     * Texto de curso para selects y listados en la ficha (≤3° sin especialidad en etiqueta larga).
     *
     * @param array<string, mixed> $c
     */
    public static function etiquetaCursoFicha(array $c): string
    {
        $anio = (int) ($c['anio'] ?? 0);
        $div = (string) ($c['division'] ?? '');
        $turno = (string) ($c['turno'] ?? '');
        $esp = (string) ($c['especialidad'] ?? '');
        $anioStr = (string) ($c['anio'] ?? '');
        if ($anio <= 3) {
            return $anioStr . '° ' . $div . ' (' . $turno . ')';
        }

        return $anioStr . '° ' . $div . ' - ' . $esp . ' (' . $turno . ')';
    }

    /**
     * Enriquece datos de ficha con etiquetas de curso y cálculos de antigüedad/edad (vista sin lógica repetida).
     *
     * @param array<string, mixed> $data datos de obtenerDatosFicha
     *
     * @return array<string, mixed>
     */
    public function aplicarPresentacionFichaProfesor(array $data): array
    {
        $prof = $data['profesor'] ?? [];
        $data['anios_antiguedad'] = self::antiguedadAniosDesdeFecha(isset($prof['fecha_ingreso']) ? (string) $prof['fecha_ingreso'] : null);
        $data['edad_anios'] = self::edadAniosDesdeFecha(isset($prof['fecha_nacimiento']) ? (string) $prof['fecha_nacimiento'] : null);

        if (!empty($data['cursos_disponibles']) && is_array($data['cursos_disponibles'])) {
            foreach ($data['cursos_disponibles'] as $k => $c) {
                if (is_array($c)) {
                    $data['cursos_disponibles'][$k]['etiqueta_opcion'] = self::etiquetaCursoFicha($c);
                }
            }
        }
        if (!empty($data['cursos_asignados']) && is_array($data['cursos_asignados'])) {
            foreach ($data['cursos_asignados'] as $k => $c) {
                if (!is_array($c)) {
                    continue;
                }
                $data['cursos_asignados'][$k]['etiqueta_opcion'] = self::etiquetaCursoFicha($c);
                $data['cursos_asignados'][$k]['etiqueta_corto'] = (string) ($c['anio'] ?? '') . '° ' . (string) ($c['division'] ?? '');
                $data['cursos_asignados'][$k]['es_curso_inferior'] = (int) ($c['anio'] ?? 0) <= 3;
            }
        }

        return $data;
    }

    /**
     * Etiqueta de curso en la tabla de profesores (tags con turno opcional; especialidad solo si año > 3).
     *
     * @param array<string, mixed> $c
     */
    public static function etiquetaCursoListadoProfesores(array $c): string
    {
        $anio = (int) ($c['anio'] ?? 0);
        $et = (string) ($c['anio'] ?? '') . '° ' . (string) ($c['division'] ?? '');
        if (!empty($c['especialidad']) && $anio > 3) {
            $et .= ' - ' . (string) $c['especialidad'];
        }
        if (!empty($c['turno'])) {
            $et .= ' (' . (string) $c['turno'] . ')';
        }

        return $et;
    }

    /**
     * Etiqueta de curso en fila de preceptor (equipo mismo curso).
     *
     * @param array<string, mixed> $prec
     */
    public static function etiquetaCursoPreceptorEquipo(array $prec): string
    {
        $anio = (int) ($prec['anio'] ?? 0);
        $et = (string) ($prec['anio'] ?? '') . '° ' . (string) ($prec['division'] ?? '');
        if (!empty($prec['especialidad_curso']) && $anio > 3) {
            $et .= ' — ' . (string) $prec['especialidad_curso'];
        }

        return $et;
    }

    public function obtenerMateriasCursoAjax(array $datos): array
    {
        try {
            $cursoId = (int)($datos['curso_id'] ?? 0);
            if ($cursoId <= 0) {
                return ['success' => false, 'error' => 'ID de curso requerido'];
            }
            return $this->servicioProfesores->obtenerMateriasPorCurso($cursoId);
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'obtener_materias_curso_ajax']);
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un profesor
     */
    public function eliminar(int $profesorId): array
    {
        try {
            $profesor = $this->servicioProfesores->obtenerPorId($profesorId);
            $this->servicioProfesores->eliminar($profesorId);
            if ($profesor) {
                $this->registrarAuditoria(
                    'ELIMINAR_PROFESOR',
                    'profesor',
                    $profesorId,
                    $profesor->toArray()
                );
            }
            
            return [
                'success' => true,
                'message' => 'Profesor eliminado exitosamente'
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_profesores'
            ]);
        }
    }

    /**
     * Maneja la petición GET para buscar profesores
     */
    public function buscar(string $termino): array
    {
        try {
            $profesores = $this->servicioProfesores->buscarPorNombre($termino);
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'dni' => $profesor->getDni(),
                    'apellido' => $profesor->getApellido(),
                    'nombre' => $profesor->getNombre(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'fecha_nacimiento' => $profesor->getFechaNacimiento()?->format('Y-m-d'),
                    'edad' => $profesor->getEdad(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'titulo' => $profesor->getTitulo(),
                    'telefono_fijo' => $profesor->getTelefonoFijo(),
                    'telefono_celular' => $profesor->getTelefonoCelular(),
                    'email' => $profesor->getEmail(),
                    'domicilio' => $profesor->getDomicilio(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d'),
                    'activo' => $profesor->esActivo(),
                    'tiene_cursos' => $this->servicioProfesores->tieneCursosAsignados($profesor->getId())
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => $profesoresArray,
                'total' => count($profesoresArray)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_profesores'
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener estadísticas
     */
    public function estadisticas(): array
    {
        try {
            $estadisticas = $this->servicioProfesores->obtenerEstadisticas();
            
            return [
                'success' => true,
                'data' => $estadisticas
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_profesores'
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener profesores por especialidad
     */
    public function porEspecialidad(string $especialidad): array
    {
        try {
            $profesores = $this->servicioProfesores->buscarPorEspecialidad($especialidad);
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'dni' => $profesor->getDni(),
                    'titulo' => $profesor->getTitulo()
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => $profesoresArray,
                'total' => count($profesoresArray)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_profesores'
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener profesores sin cursos
     */
    public function sinCursos(): array
    {
        try {
            $profesores = $this->servicioProfesores->obtenerProfesoresSinCursos();
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'dni' => $profesor->getDni(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d')
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => $profesoresArray,
                'total' => count($profesoresArray)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_profesores'
            ]);
        }
    }

    /**
     * Arma listados, mapas y datos auxiliares para la vista teachers.php.
     *
     * @param list<int|string> $preceptorCids
     * @return array{
     *   preceptores_mismo_curso: list<array<string, mixed>>|null,
     *   profesores_data: list<array<string, mixed>>,
     *   cursos_por_profesor: array<int, list<array<string, mixed>>>,
     *   total_profesores: int,
     *   profesores_sin_cursos: int,
     *   especialidades: list<array<string, mixed>>,
     *   especialidades_formulario: list<array<string, mixed>>,
     *   curso_info_etiqueta: array<string, mixed>|null
     * }
     */
    public function datosVistaListaProfesores(
        bool $esPreceptor,
        array $preceptorCids,
        string $search,
        string $especialidadFilter,
        string $cursoFilter,
        int $page = 1,
        int $perPage = 20
    ): array {
        $especialidadesFormulario = [];
        if (!$esPreceptor) {
            $especialidadesFormulario = $this->servicioProfesores->listarEspecialidadesActivasIdNombre();
        }

        $cursoInfoEtiqueta = null;
        if (!$esPreceptor && trim($cursoFilter) !== '') {
            $cursoInfoEtiqueta = $this->servicioProfesores->obtenerFilaCursoParaEtiqueta((int) $cursoFilter);
        }

        if ($esPreceptor && $preceptorCids !== []) {
            $lista = $this->servicioProfesores->listarPreceptoresCompartenCursos($preceptorCids);
            $lista = $this->servicioProfesores->filtrarPreceptoresEquipoPorTexto($lista, $search);

            $totalFiltrado = count($lista);
            $paginationSvc = new \SistemaAdmin\Services\PaginationService($this->database);
            $meta = $paginationSvc->calculatePagination($totalFiltrado, $page, $perPage);
            $pageNumbers = $paginationSvc->getPageNumbers((int) $meta['total_pages'], (int) $meta['current_page'], 7);
            $pagination = array_merge($meta, ['page_numbers' => $pageNumbers]);

            $listaSlic = array_slice($lista, (int) $meta['offset'], (int) $meta['page_size']);

            return [
                'preceptores_mismo_curso' => $listaSlic,
                'profesores_data' => [],
                'cursos_por_profesor' => [],
                'total_profesores' => $totalFiltrado,
                'profesores_sin_cursos' => 0,
                'especialidades' => [],
                'especialidades_formulario' => $especialidadesFormulario,
                'curso_info_etiqueta' => $cursoInfoEtiqueta,
                'total_filtrado' => $totalFiltrado,
                'pagination' => $pagination,
            ];
        }

        if ($esPreceptor && $preceptorCids === []) {
            return [
                'preceptores_mismo_curso' => [],
                'profesores_data' => [],
                'cursos_por_profesor' => [],
                'total_profesores' => 0,
                'profesores_sin_cursos' => 0,
                'especialidades' => [],
                'especialidades_formulario' => $especialidadesFormulario,
                'curso_info_etiqueta' => $cursoInfoEtiqueta,
                'total_filtrado' => 0,
                'pagination' => [
                    'current_page' => 1,
                    'page_size' => $perPage,
                    'total_items' => 0,
                    'total_pages' => 0,
                    'offset' => 0,
                    'has_previous' => false,
                    'has_next' => false,
                    'previous_page' => null,
                    'next_page' => null,
                    'start_item' => 0,
                    'end_item' => 0,
                    'page_numbers' => [],
                ],
            ];
        }

        if ($search !== '') {
            $resultado = $this->buscar($search);
            $profesoresData = $resultado['success'] ? $resultado['data'] : [];
        } else {
            $resultado = $this->listar();
            $profesoresData = $resultado['success'] ? $resultado['data'] : [];
        }

        if ($especialidadFilter !== '' && $profesoresData !== []) {
            $profesoresData = array_values(array_filter($profesoresData, function (array $profesor) use ($especialidadFilter): bool {
                return strpos($profesor['especialidad'] ?? '', $especialidadFilter) !== false;
            }));
        }

        $totalFiltrado = count($profesoresData);
        $paginationSvc = new \SistemaAdmin\Services\PaginationService($this->database);
        $meta = $paginationSvc->calculatePagination($totalFiltrado, $page, $perPage);
        $pageNumbers = $paginationSvc->getPageNumbers((int) $meta['total_pages'], (int) $meta['current_page'], 7);
        $pagination = array_merge($meta, ['page_numbers' => $pageNumbers]);

        $slicedData = array_slice($profesoresData, (int) $meta['offset'], (int) $meta['page_size']);

        $profesorIds = array_column($slicedData, 'id');
        $cursosPorProfesor = $this->servicioProfesores->mapaCursosActivosPorProfesorIds($profesorIds);

        $especialidades = $this->servicioProfesores->listarNombresEspecialidadFiltroProfesoresActivos();

        $estadisticas = $this->estadisticas();
        $totalProfesores = $estadisticas['success']
            ? ($estadisticas['data']['total_profesores'] ?? $totalFiltrado)
            : $totalFiltrado;
        $profesoresSinCursos = $estadisticas['success']
            ? ($estadisticas['data']['sin_cursos'] ?? 0)
            : 0;

        return [
            'preceptores_mismo_curso' => null,
            'profesores_data' => $slicedData,
            'cursos_por_profesor' => $cursosPorProfesor,
            'total_profesores' => $totalProfesores,
            'profesores_sin_cursos' => $profesoresSinCursos,
            'especialidades' => $especialidades,
            'especialidades_formulario' => $especialidadesFormulario,
            'curso_info_etiqueta' => $cursoInfoEtiqueta,
            'total_filtrado' => $totalFiltrado,
            'pagination' => $pagination,
        ];
    }

    /**
     * POST alta/baja con PRG (teachers.php).
     *
     * @param array<string, mixed> $post
     * @return array{
     *   redirect: string|null,
     *   error: string,
     *   action: string|null,
     *   form_values: array<string, string>|null
     * }
     */
    public function procesarPostProfesores(array $post, bool $esPreceptor, bool $puedeEliminarProfesor): array
    {
        $sinCambio = ['redirect' => null, 'error' => '', 'action' => null, 'form_values' => null];
        if ($esPreceptor) {
            return $sinCambio;
        }
        if (!isset($post['guardar_profesor']) && !isset($post['eliminar_profesor'])) {
            return $sinCambio;
        }

        if (isset($post['guardar_profesor'])) {
            $emailRaw = trim((string) ($post['email'] ?? ''));
            $datos = [
                'dni' => trim((string) ($post['dni'] ?? '')),
                'apellido' => trim((string) ($post['apellido'] ?? '')),
                'nombre' => trim((string) ($post['nombre'] ?? '')),
                'fecha_nacimiento' => !empty($post['fecha_nacimiento']) ? trim((string) $post['fecha_nacimiento']) : null,
                'domicilio' => !empty($post['domicilio']) ? trim((string) $post['domicilio']) : null,
                'telefono_fijo' => !empty($post['telefono_fijo']) ? trim((string) $post['telefono_fijo']) : null,
                'telefono_celular' => !empty($post['telefono_celular']) ? trim((string) $post['telefono_celular']) : null,
                'email' => $emailRaw !== '' ? $emailRaw : null,
                'titulo' => !empty($post['titulo']) ? trim((string) $post['titulo']) : null,
                'especialidad_id' => isset($post['especialidad_id']) && $post['especialidad_id'] !== ''
                    ? $post['especialidad_id']
                    : null,
                'fecha_ingreso' => !empty($post['fecha_ingreso']) ? trim((string) $post['fecha_ingreso']) : null,
            ];

            try {
                $resultado = $this->crear($datos);
                if (!empty($resultado['success'])) {
                    return [
                        'redirect' => 'teachers.php?success=creado',
                        'error' => '',
                        'action' => null,
                        'form_values' => null,
                    ];
                }
                $errores = $resultado['errors'] ?? [];
                if ($errores !== []) {
                    $msg = 'Por favor corrija: ' . implode(' • ', $errores);
                } else {
                    $msg = (string) ($resultado['error'] ?? 'Error al registrar profesor');
                }

                return [
                    'redirect' => null,
                    'error' => $msg,
                    'action' => 'nuevo',
                    'form_values' => $this->formValuesProfesorDesdePost($post),
                ];
            } catch (\Throwable $e) {
                return [
                    'redirect' => null,
                    'error' => 'Error al registrar profesor: ' . $e->getMessage(),
                    'action' => 'nuevo',
                    'form_values' => $this->formValuesProfesorDesdePost($post),
                ];
            }
        }

        if (!$puedeEliminarProfesor) {
            return [
                'redirect' => null,
                'error' => 'No tiene permisos para eliminar profesores.',
                'action' => null,
                'form_values' => null,
            ];
        }

        $id = isset($post['profesor_id']) ? (int) $post['profesor_id'] : 0;
        if ($id < 1) {
            return [
                'redirect' => null,
                'error' => 'Profesor no válido.',
                'action' => null,
                'form_values' => null,
            ];
        }

        try {
            $profesor = $this->servicioProfesores->obtenerPorId($id);
            $nombreEtiqueta = $profesor->getApellido() . ', ' . $profesor->getNombre();
            $resultado = $this->eliminar($id);
            if (!empty($resultado['success'])) {
                return [
                    'redirect' => 'teachers.php?' . http_build_query([
                        'success' => 'eliminado',
                        'nombre' => $nombreEtiqueta,
                    ]),
                    'error' => '',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            return [
                'redirect' => null,
                'error' => (string) ($resultado['error'] ?? 'Error al eliminar profesor'),
                'action' => null,
                'form_values' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'redirect' => null,
                'error' => 'Error al eliminar profesor: ' . $e->getMessage(),
                'action' => null,
                'form_values' => null,
            ];
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    private function formValuesProfesorDesdePost(array $post): array
    {
        return [
            'dni' => (string) ($post['dni'] ?? ''),
            'apellido' => (string) ($post['apellido'] ?? ''),
            'nombre' => (string) ($post['nombre'] ?? ''),
            'fecha_nacimiento' => (string) ($post['fecha_nacimiento'] ?? ''),
            'domicilio' => (string) ($post['domicilio'] ?? ''),
            'telefono_fijo' => (string) ($post['telefono_fijo'] ?? ''),
            'telefono_celular' => (string) ($post['telefono_celular'] ?? ''),
            'email' => (string) ($post['email'] ?? ''),
            'titulo' => (string) ($post['titulo'] ?? ''),
            'especialidad_id' => (string) ($post['especialidad_id'] ?? ''),
            'fecha_ingreso' => (string) ($post['fecha_ingreso'] ?? ''),
        ];
    }

    /**
     * Valida los datos de un profesor
     */
    private function validarDatosProfesor(array $datos, ?int $profesorId = null): array
    {
        $errores = [];
        
        
        
        // Campos obligatorios
        if (empty($datos['dni'])) {
            $errores[] = 'El DNI es requerido';
        } elseif (!preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $datos['dni'])) {
            $errores[] = 'El DNI debe tener entre 7 y 8 dígitos';
        }
        
        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es requerido';
        } elseif (strlen(trim($datos['apellido'])) < 2) {
            $errores[] = 'El apellido debe tener al menos 2 caracteres';
        }
        
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        } elseif (strlen(trim($datos['nombre'])) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres';
        }
        
        // Validaciones opcionales (solo si se proporcionan)
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
            $errores[] = 'El email no tiene un formato válido';
        }
        
        // Permitir formatos flexibles para teléfonos (con guiones, espacios, paréntesis)
        if (!empty($datos['telefono_fijo'])) {
            $telefono_limpio = preg_replace('/[\s\-\(\)]/', '', $datos['telefono_fijo']);
            if (!preg_match('/^\d{6,15}$/', $telefono_limpio)) {
                $errores[] = 'El teléfono fijo debe tener entre 6 y 15 dígitos';
            }
        }
        
        if (!empty($datos['telefono_celular'])) {
            $celular_limpio = preg_replace('/[\s\-\(\)]/', '', $datos['telefono_celular']);
            if (!preg_match('/^\d{6,15}$/', $celular_limpio)) {
                $errores[] = 'El teléfono celular debe tener entre 6 y 15 dígitos';
            }
        }
        
        
        return $errores;
    }
}
