<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioMateriasPrevias;
use SistemaAdmin\Services\PaginationService;
use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use DateTime;

/**
 * Controller para manejar las peticiones HTTP relacionadas con estudiantes
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio.
 */
class EstudianteController extends BaseService
{
    private ServicioEstudiantes $servicioEstudiantes;

    public function __construct(DatabaseInterface $database, ServicioEstudiantes $servicioEstudiantes)
    {
        parent::__construct($database);
        $this->servicioEstudiantes = $servicioEstudiantes;
    }

    /**
     * Maneja la petición GET para listar estudiantes
     */
    public function listar(): array
    {
        try {
            $estudiantes = $this->servicioEstudiantes->obtenerTodos();
            
            return [
                'success' => true,
                'data' => array_map(fn($estudiante) => $estudiante->toArray(), $estudiantes),
                'total' => count($estudiantes)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'listar_estudiantes'
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener un estudiante por ID
     */
    public function obtener(int $id): array
    {
        try {
            // Validar ID
            if ($id <= 0) {
                return [
                    'success' => false,
                    'error' => 'ID de estudiante inválido'
                ];
            }
            
            $estudiante = $this->servicioEstudiantes->buscarPorId($id);
            
            if (!$estudiante) {
                return [
                    'success' => false,
                    'error' => 'Estudiante no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => $estudiante->toArray()
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_estudiante',
                'id' => $id
            ]);
        }
    }

    /**
     * Maneja la petición POST para crear un nuevo estudiante
     */
    public function crear(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosCreacion($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Crear el estudiante
            $estudiante = new Estudiante(
                $datos['dni'],
                $datos['nombre'],
                $datos['apellido']
            );
            
            // Establecer datos opcionales
            if (isset($datos['fecha_nacimiento']) && !empty($datos['fecha_nacimiento'])) {
                $estudiante->setFechaNacimiento(new DateTime($datos['fecha_nacimiento']));
            }
            
            if (isset($datos['email'])) {
                $estudiante->setEmail($datos['email']);
            }
            
            if (isset($datos['telefono_celular'])) {
                $estudiante->setTelefonoCelular($datos['telefono_celular']);
            }
            
            if (isset($datos['domicilio'])) {
                $estudiante->setDomicilio($datos['domicilio']);
            }
            
            if (isset($datos['curso_id'])) {
                $estudiante->setCursoId($datos['curso_id']);
            }
            
            if (isset($datos['grupo_sanguineo'])) {
                $estudiante->setGrupoSanguineo($datos['grupo_sanguineo']);
            }
            
            if (isset($datos['obra_social'])) {
                $estudiante->setObraSocial($datos['obra_social']);
            }
            
            if (isset($datos['telefono_fijo'])) {
                $estudiante->setTelefonoFijo($datos['telefono_fijo']);
            }

            if (!empty($datos['dni_responsable'])) {
                $estudiante->setDniResponsable((string) $datos['dni_responsable']);
            }

            if (isset($datos['grupo_taller'])) {
                $estudiante->setGrupoTaller($datos['grupo_taller']);
            }

            // Guardar
            $estudianteGuardado = $this->servicioEstudiantes->crear($estudiante);

            // Procesar foto si se subió una
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $fileService = new \SistemaAdmin\Services\FileUploadService($this->database);
                $uploadRes = $fileService->uploadFile($_FILES['foto'], 'estudiantes');
                if ($uploadRes['success']) {
                    $fotoPath = 'uploads/estudiantes/' . $uploadRes['filename'];
                    $this->database->query("UPDATE estudiantes SET foto = ? WHERE id = ?", [$fotoPath, $estudianteGuardado->getId()]);
                }
            }
            
            $this->registrarAuditoria(
                'CREAR_ESTUDIANTE',
                'estudiante',
                $estudianteGuardado->getId(),
                $estudianteGuardado->toArray()
            );
            
            return [
                'success' => true,
                'data' => $estudianteGuardado->toArray(),
                'message' => 'Estudiante creado exitosamente'
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'crear_estudiante'
            ]);
        }
    }

    /**
     * Maneja la petición PUT para actualizar un estudiante
     */
    public function actualizar(int $id, array $datos): array
    {
        try {
            // Validar ID
            if ($id <= 0) {
                return [
                    'success' => false,
                    'error' => 'ID de estudiante inválido'
                ];
            }
            
            // Obtener el estudiante existente
            $estudiante = $this->servicioEstudiantes->buscarPorId($id);
            
            if (!$estudiante) {
                return [
                    'success' => false,
                    'error' => 'Estudiante no encontrado'
                ];
            }
            
            // Validar datos
            $errores = $this->validarDatosActualizacion($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Actualizar campos
            if (isset($datos['dni'])) {
                $estudiante->setDni($datos['dni']);
            }
            
            if (isset($datos['nombre'])) {
                $estudiante->setNombre($datos['nombre']);
            }
            
            if (isset($datos['apellido'])) {
                $estudiante->setApellido($datos['apellido']);
            }
            
            if (isset($datos['fecha_nacimiento'])) {
                $estudiante->setFechaNacimiento(
                    !empty($datos['fecha_nacimiento']) ? new DateTime($datos['fecha_nacimiento']) : null
                );
            }
            
            if (isset($datos['email'])) {
                $estudiante->setEmail($datos['email']);
            }
            
            if (isset($datos['telefono_celular'])) {
                $estudiante->setTelefonoCelular($datos['telefono_celular']);
            }
            
            if (isset($datos['domicilio'])) {
                $estudiante->setDomicilio($datos['domicilio']);
            }
            
            if (isset($datos['curso_id'])) {
                $estudiante->setCursoId($datos['curso_id']);
            }
            
            // Guardar cambios
            $estudianteActualizado = $this->servicioEstudiantes->actualizar($estudiante);
            $this->registrarAuditoria(
                'ACTUALIZAR_ESTUDIANTE',
                'estudiante',
                $estudianteActualizado->getId(),
                $estudianteActualizado->toArray()
            );
            
            return [
                'success' => true,
                'data' => $estudianteActualizado->toArray(),
                'message' => 'Estudiante actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'actualizar_estudiante',
                'id' => $id
            ]);
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un estudiante
     */
    public function eliminar(int $id): array
    {
        try {
            $estudiante = $this->servicioEstudiantes->buscarPorId($id);
            $this->servicioEstudiantes->eliminar($id);
            if ($estudiante) {
                $this->registrarAuditoria(
                    'ELIMINAR_ESTUDIANTE',
                    'estudiante',
                    $id,
                    $estudiante->toArray()
                );
            }
            
            return [
                'success' => true,
                'message' => 'Estudiante eliminado exitosamente'
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'eliminar_estudiante',
                'id' => $id
            ]);
        }
    }

    /**
     * Maneja la petición GET para buscar estudiantes
     */
    public function buscar(string $termino): array
    {
        try {
            $estudiantes = $this->servicioEstudiantes->buscarPorNombre($termino);
            
            return [
                'success' => true,
                'data' => array_map(fn($estudiante) => $estudiante->toArray(), $estudiantes),
                'total' => count($estudiantes)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'buscar_estudiantes',
                'termino' => $termino
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener estadísticas
     */
    public function estadisticas(): array
    {
        try {
            $estadisticas = $this->servicioEstudiantes->obtenerEstadisticas();
            
            return [
                'success' => true,
                'data' => $estadisticas
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_estadisticas_estudiantes'
            ]);
        }
    }

    /**
     * Maneja la petición GET para obtener cumpleañeros
     */
    public function cumpleaneros(?string $fecha = null): array
    {
        try {
            $fechaBusqueda = $fecha ? new DateTime($fecha) : new DateTime();
            $cumpleaneros = $this->servicioEstudiantes->obtenerCumpleaneros($fechaBusqueda);
            
            return [
                'success' => true,
                'data' => array_map(fn($estudiante) => $estudiante->toArray(), $cumpleaneros),
                'total' => count($cumpleaneros)
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_cumpleaneros',
                'fecha' => $fecha
            ]);
        }
    }

    /**
     * Valida los datos para crear un estudiante
     */
    private function validarDatosCreacion(array $datos): array
    {
        $errores = [];
        
        // Campos obligatorios
        if (empty($datos['dni'])) {
            $errores[] = 'El DNI es requerido';
        } elseif (!preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $datos['dni'])) {
            $errores[] = 'El DNI debe tener entre 7 y 8 dígitos';
        }

        $dniResp = isset($datos['dni_responsable']) ? preg_replace('/\D/', '', (string) $datos['dni_responsable']) : '';
        if ($dniResp === '') {
            $errores[] = 'El DNI del responsable (portal familias) es obligatorio';
        } elseif (!preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $dniResp)) {
            $errores[] = 'El DNI del responsable debe tener entre 7 y 8 dígitos';
        }

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        } elseif (strlen(trim($datos['nombre'])) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres';
        }
        
        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es requerido';
        } elseif (strlen(trim($datos['apellido'])) < 2) {
            $errores[] = 'El apellido debe tener al menos 2 caracteres';
        }
        
        // Validaciones opcionales (solo si se proporcionan)
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
            $errores[] = 'El email no tiene un formato válido';
        }
        
        if (!empty($datos['telefono_celular']) && !preg_match('/^\d{10,15}$/', $datos['telefono_celular'])) {
            $errores[] = 'El teléfono celular debe tener entre 10 y 15 dígitos';
        }
        
        if (!empty($datos['telefono_fijo']) && !preg_match('/^\d{7,15}$/', $datos['telefono_fijo'])) {
            $errores[] = 'El teléfono fijo debe tener entre 7 y 15 dígitos';
        }
        
        if (!empty($datos['fecha_nacimiento'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_nacimiento']);
            if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_nacimiento']) {
                $errores[] = 'La fecha de nacimiento debe tener el formato YYYY-MM-DD';
            } elseif ($fecha > new DateTime()) {
                $errores[] = 'La fecha de nacimiento no puede ser futura';
            }
        }
        
        return $errores;
    }

    /**
     * Valida los datos para actualizar un estudiante
     */
    private function validarDatosActualizacion(array $datos): array
    {
        // Limitar tamaño de datos para prevenir memory exhaustion
        if (count($datos) > 50) {
            $this->logEvent('WARNING', 'Intento de actualización con demasiados campos', [
                'fields_count' => count($datos),
                'ip' => $this->obtenerIPCliente()
            ]);
            return ['Demasiados campos de datos'];
        }
        
        $errores = [];
        
        if (isset($datos['dni']) && empty($datos['dni'])) {
            $errores[] = 'El DNI no puede estar vacío';
        }
        
        if (isset($datos['nombre']) && empty($datos['nombre'])) {
            $errores[] = 'El nombre no puede estar vacío';
        }
        
        if (isset($datos['apellido']) && empty($datos['apellido'])) {
            $errores[] = 'El apellido no puede estar vacío';
        }
        
        // Límite de errores para prevenir spam
        if (count($errores) >= 10) {
            $errores = array_slice($errores, 0, 10);
            $errores[] = 'Demasiados errores de validación';
        }
        
        return $errores;
    }

    public function actualizarContactoFicha(int $estudianteId, array $datos): array
    {
        try {
            // Procesar foto
            $eliminarFoto = isset($datos['eliminar_foto']) && $datos['eliminar_foto'] == '1';

            if ($eliminarFoto) {
                // Borrar foto actual en la base de datos
                $this->database->query("UPDATE estudiantes SET foto = NULL WHERE id = ?", [$estudianteId]);
            } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $fileService = new \SistemaAdmin\Services\FileUploadService($this->database);
                $uploadRes = $fileService->uploadFile($_FILES['foto'], 'estudiantes');
                if (!$uploadRes['success']) {
                    return [
                        'success' => false,
                        'error' => 'Error al subir la foto: ' . $uploadRes['error']
                    ];
                }
                $fotoPath = 'uploads/estudiantes/' . $uploadRes['filename'];
                $this->database->query("UPDATE estudiantes SET foto = ? WHERE id = ?", [$fotoPath, $estudianteId]);
            }

            $telefono = trim((string)($datos['telefono_celular'] ?? '')) ?: (trim((string)($datos['telefono_fijo'] ?? '')) ?: null);
            $email = trim((string)($datos['email'] ?? '')) ?: null;
            $domicilio = trim((string)($datos['domicilio'] ?? '')) ?: null;
            $grupoSanguineo = trim((string) ($datos['grupo_sanguineo'] ?? '')) ?: null;
            $obraSocial = trim((string) ($datos['obra_social'] ?? '')) ?: null;
            $nuevoTurnoId = isset($datos['nuevo_turno_id']) && $datos['nuevo_turno_id'] !== '' ? (int)$datos['nuevo_turno_id'] : null;

            $dniPortalRaw = trim((string) ($datos['dni_responsable_portal'] ?? ''));
            $dniPortalNorm = preg_replace('/\D/', '', $dniPortalRaw);
            $dniPortal = null;
            if ($dniPortalRaw !== '') {
                if (!preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $dniPortalNorm)) {
                    return [
                        'success' => false,
                        'error' => 'El DNI del responsable (portal familias) debe tener entre 7 y 8 dígitos.',
                    ];
                }
                $dniPortal = $dniPortalNorm;
            }

            $grupoTaller = trim((string)($datos['grupo_taller'] ?? '')) ?: null;
            if ($grupoTaller !== null) {
                $grupoTaller = strtoupper($grupoTaller);
                if (!in_array($grupoTaller, ['A', 'B', 'C', 'D', 'E'])) {
                    $grupoTaller = null;
                }
            }

            $fechaNacimiento = trim((string) ($datos['fecha_nacimiento'] ?? '')) ?: null;
            $fechaIngreso = trim((string) ($datos['fecha_ingreso'] ?? '')) ?: null;

            if ($fechaNacimiento !== null) {
                $fNac = \DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
                if (!$fNac) {
                    return [
                        'success' => false,
                        'error' => 'La fecha de nacimiento no es válida.',
                    ];
                }
            }
            if ($fechaIngreso !== null) {
                $fIng = \DateTime::createFromFormat('Y-m-d', $fechaIngreso);
                if (!$fIng) {
                    return [
                        'success' => false,
                        'error' => 'La fecha de ingreso no es válida.',
                    ];
                }
            }

            $resultado = $this->servicioEstudiantes->actualizarContactoFicha(
                $estudianteId,
                $telefono,
                $email,
                $domicilio,
                $grupoSanguineo,
                $obraSocial,
                $nuevoTurnoId,
                $dniPortal,
                $grupoTaller,
                $fechaNacimiento,
                $fechaIngreso
            );

            return [
                'success' => true,
                'message' => 'Información del estudiante actualizada correctamente',
                'warning' => $resultado['warning'] ?? null
            ];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'actualizar_contacto_ficha_estudiante', 'estudiante_id' => $estudianteId]);
        }
    }

    public function guardarResponsableFicha(int $estudianteId, array $datos): array
    {
        try {
            $this->servicioEstudiantes->guardarResponsableFicha(
                $estudianteId,
                trim((string)($datos['nombre'] ?? '')),
                trim((string)($datos['apellido'] ?? '')),
                trim((string)($datos['dni'] ?? '')) ?: null,
                trim((string)($datos['telefono'] ?? '')),
                trim((string)($datos['email'] ?? '')) ?: null,
                trim((string)($datos['parentesco'] ?? '')),
                isset($datos['es_contacto_emergencia']) ? 1 : 0
            );
            return ['success' => true, 'message' => 'Responsable agregado correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'guardar_responsable_ficha', 'estudiante_id' => $estudianteId]);
        }
    }

    public function guardarContactoEmergenciaFicha(int $estudianteId, array $datos): array
    {
        try {
            $this->servicioEstudiantes->guardarContactoEmergenciaFicha(
                $estudianteId,
                trim((string)($datos['nombre'] ?? '')),
                trim((string)($datos['telefono'] ?? '')),
                trim((string)($datos['parentesco'] ?? ''))
            );
            return ['success' => true, 'message' => 'Contacto de emergencia agregado correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'guardar_contacto_ficha', 'estudiante_id' => $estudianteId]);
        }
    }

    public function eliminarResponsableFicha(int $estudianteId, array $datos): array
    {
        try {
            $responsableId = (int)($datos['responsable_id'] ?? 0);
            if ($responsableId <= 0) {
                return ['success' => false, 'error' => 'Responsable inválido'];
            }
            $this->servicioEstudiantes->eliminarResponsableFicha($estudianteId, $responsableId);
            return ['success' => true, 'message' => 'Responsable eliminado correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'eliminar_responsable_ficha', 'estudiante_id' => $estudianteId]);
        }
    }

    public function eliminarContactoFicha(int $estudianteId, array $datos): array
    {
        try {
            $contactoId = (int)($datos['contacto_id'] ?? 0);
            if ($contactoId <= 0) {
                return ['success' => false, 'error' => 'Contacto inválido'];
            }
            $this->servicioEstudiantes->eliminarContactoEmergenciaFicha($estudianteId, $contactoId);
            return ['success' => true, 'message' => 'Contacto de emergencia eliminado correctamente'];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'eliminar_contacto_ficha', 'estudiante_id' => $estudianteId]);
        }
    }

    public function cambiarCursoFicha(int $estudianteId, array $datos): array
    {
        try {
            $nuevoCursoId = (int)($datos['nuevo_curso_id'] ?? 0);
            if ($nuevoCursoId <= 0) {
                return ['success' => false, 'error' => 'Debe seleccionar un curso válido'];
            }
            $resultado = $this->servicioEstudiantes->cambiarCursoFicha($estudianteId, $nuevoCursoId);
            return ['success' => true, 'data' => $resultado];
        } catch (\Exception $e) {
            return $this->handleError($e, ['action' => 'cambiar_curso_ficha', 'estudiante_id' => $estudianteId]);
        }
    }

    /**
     * @param list<int> $preceptorCids
     */
    private function errorSiPreceptorSinAlcanceFicha(int $estudianteId, bool $esPreceptor, array $preceptorCids): ?string
    {
        if (!$esPreceptor || $preceptorCids === []) {
            return null;
        }
        $cid = $this->servicioEstudiantes->obtenerCursoIdEstudianteActivo($estudianteId);
        if ($cid === null || !in_array($cid, $preceptorCids, true)) {
            return 'No puede modificar estudiantes fuera de sus cursos asignados.';
        }

        return null;
    }

    /** @var list<string> Orden estable: la primera clave presente en POST define la acción. */
    private const CLAVES_ACCION_POST_FICHA = [
        'actualizar_estudiante',
        'guardar_responsable',
        'guardar_contacto',
        'eliminar_responsable',
        'eliminar_contacto',
        'cambiar_curso',
        'aprobar_materia_previa',
        'guardar_recursada',
        'eliminar_recursada',
    ];

    /**
     * POST de ficha del estudiante con PRG (student_profile.php).
     *
     * @param array<string, mixed> $post
     * @param array{
     *   es_preceptor: bool,
     *   preceptor_cids: list<int>,
     *   puede_cambiar_curso_staff: bool
     * } $contextoAcceso permisos y alcance; evita la firma larga tipo "god method".
     *
     * @return array{redirect: string|null, error: string}
     */
    public function procesarPostFichaEstudiante(int $estudianteId, array $post, array $contextoAcceso): array
    {
        $sinCambio = ['redirect' => null, 'error' => ''];
        if ($estudianteId < 1) {
            return $sinCambio;
        }

        $accion = $this->detectarAccionPostFichaEstudiante($post);
        if ($accion === null) {
            return $sinCambio;
        }

        $esPreceptor = (bool) ($contextoAcceso['es_preceptor'] ?? false);
        $preceptorCids = $contextoAcceso['preceptor_cids'] ?? [];
        if (!is_array($preceptorCids)) {
            $preceptorCids = [];
        }
        /** @var list<int> $preceptorCids */
        $puedeCambiarCursoStaff = (bool) ($contextoAcceso['puede_cambiar_curso_staff'] ?? false);

        if ($accion === 'cambiar_curso' || $accion === 'aprobar_materia_previa' || $accion === 'guardar_recursada' || $accion === 'eliminar_recursada') {
            if (!$puedeCambiarCursoStaff) {
                $msg = $accion === 'cambiar_curso'
                    ? 'No tiene permisos para cambiar el curso del estudiante.'
                    : 'No tiene permisos para modificar la ficha escolar del estudiante.';
                return ['redirect' => null, 'error' => $msg];
            }
            if (($err = $this->errorSiPreceptorSinAlcanceFicha($estudianteId, $esPreceptor, $preceptorCids)) !== null) {
                return ['redirect' => null, 'error' => $err];
            }
        } elseif (($err = $this->errorSiPreceptorSinAlcanceFicha($estudianteId, $esPreceptor, $preceptorCids)) !== null) {
            return ['redirect' => null, 'error' => $err];
        }

        $base = function_exists('app_base_path')
            ? app_base_path('student_profile.php?id=' . $estudianteId)
            : ('student_profile.php?id=' . $estudianteId);

        return $this->ejecutarAccionPostFichaEstudiante($accion, $estudianteId, $post, $base);
    }

    /**
     * @param array<string, mixed> $post
     */
    private function detectarAccionPostFichaEstudiante(array $post): ?string
    {
        foreach (self::CLAVES_ACCION_POST_FICHA as $clave) {
            if (isset($post[$clave])) {
                return $clave;
            }
        }

        return null;
    }

    /**
     * @return array{redirect: string|null, error: string}
     */
    private function ejecutarAccionPostFichaEstudiante(string $accion, int $estudianteId, array $post, string $base): array
    {
        return match ($accion) {
            'actualizar_estudiante' => $this->resultadoPostFichaActualizarEstudiante($estudianteId, $post, $base),
            'guardar_responsable' => $this->resultadoPostFichaGuardarResponsable($estudianteId, $post, $base),
            'guardar_contacto' => $this->resultadoPostFichaGuardarContactoEmergencia($estudianteId, $post, $base),
            'eliminar_responsable' => $this->resultadoPostFichaEliminarResponsable($estudianteId, $post, $base),
            'eliminar_contacto' => $this->resultadoPostFichaEliminarContacto($estudianteId, $post, $base),
            'cambiar_curso' => $this->resultadoPostFichaCambiarCurso($estudianteId, $post, $base),
            'aprobar_materia_previa' => $this->resultadoPostFichaAprobarMateriaPrevia($estudianteId, $post, $base),
            'guardar_recursada' => $this->resultadoPostFichaGuardarRecursada($estudianteId, $post, $base),
            'eliminar_recursada' => $this->resultadoPostFichaEliminarRecursada($estudianteId, $post, $base),
        };
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaAprobarMateriaPrevia(int $estudianteId, array $post, string $base): array
    {
        $previaId = (int) ($post['previa_id'] ?? 0);
        if ($previaId < 1) {
            return ['redirect' => null, 'error' => 'Registro de materia previa inválido.'];
        }

        try {
            $svc = new ServicioMateriasPrevias($this->database);
            $svc->aprobarPreviaAdministrativaDesdeFicha($previaId, $estudianteId);
        } catch (\Throwable $e) {
            return ['redirect' => null, 'error' => $e->getMessage()];
        }

        return ['redirect' => $base . '&' . http_build_query(['success' => 'previa_aprobada']), 'error' => ''];
    }

    private function resultadoPostFichaGuardarRecursada(int $estudianteId, array $post, string $base): array
    {
        $materiaId = (int) ($post['recursada_materia_id'] ?? 0);
        $cursoId = (int) ($post['recursada_curso_id'] ?? 0);
        $schoolYear = (int) ($post['recursada_school_year'] ?? NotasSubjectGradesPayloadBuilder::inferSchoolYearCicloMarzoArgentina(new \DateTimeImmutable()));

        if ($materiaId < 1 || $cursoId < 1) {
            return ['redirect' => null, 'error' => 'Debe seleccionar materia y curso para la recursada.'];
        }

        $res = $this->servicioEstudiantes->guardarRecursada($estudianteId, $materiaId, $cursoId, $schoolYear);
        if ($res['success']) {
            return ['redirect' => $base . '&' . http_build_query(['success' => 'recursada_guardada']), 'error' => ''];
        }

        return ['redirect' => null, 'error' => $res['error']];
    }

    private function resultadoPostFichaEliminarRecursada(int $estudianteId, array $post, string $base): array
    {
        $recursadaId = (int) ($post['recursada_id'] ?? 0);
        if ($recursadaId < 1) {
            return ['redirect' => null, 'error' => 'Registro de recursada inválido.'];
        }

        $res = $this->servicioEstudiantes->eliminarRecursada($recursadaId);
        if ($res['success']) {
            return ['redirect' => $base . '&' . http_build_query(['success' => 'recursada_eliminada']), 'error' => ''];
        }

        return ['redirect' => null, 'error' => $res['error']];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaActualizarEstudiante(int $estudianteId, array $post, string $base): array
    {
        $r = $this->actualizarContactoFicha($estudianteId, $post);
        if (!empty($r['success'])) {
            $params = ['success' => 'actualizado'];
            if (!empty($r['warning'])) {
                $params['aviso'] = (string) $r['warning'];
            }

            return ['redirect' => $base . '&' . http_build_query($params), 'error' => ''];
        }

        return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al actualizar estudiante')];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaGuardarResponsable(int $estudianteId, array $post, string $base): array
    {
        $r = $this->guardarResponsableFicha($estudianteId, $post);
        if (!empty($r['success'])) {
            return ['redirect' => $base . '&' . http_build_query(['success' => 'responsable']), 'error' => ''];
        }

        return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al agregar responsable')];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaGuardarContactoEmergencia(int $estudianteId, array $post, string $base): array
    {
        $r = $this->guardarContactoEmergenciaFicha($estudianteId, $post);
        if (!empty($r['success'])) {
            return ['redirect' => $base . '&' . http_build_query(['success' => 'contacto_emergencia']), 'error' => ''];
        }

        return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al agregar contacto')];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaEliminarResponsable(int $estudianteId, array $post, string $base): array
    {
        $r = $this->eliminarResponsableFicha($estudianteId, $post);
        if (!empty($r['success'])) {
            return ['redirect' => $base . '&' . http_build_query(['success' => 'responsable_eliminado']), 'error' => ''];
        }

        return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al eliminar responsable')];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaEliminarContacto(int $estudianteId, array $post, string $base): array
    {
        $r = $this->eliminarContactoFicha($estudianteId, $post);
        if (!empty($r['success'])) {
            return ['redirect' => $base . '&' . http_build_query(['success' => 'emergencia_eliminado']), 'error' => ''];
        }

        return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al eliminar contacto')];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{redirect: string|null, error: string}
     */
    private function resultadoPostFichaCambiarCurso(int $estudianteId, array $post, string $base): array
    {
        $r = $this->cambiarCursoFicha($estudianteId, $post);
        if (!empty($r['success'])) {
            $data = $r['data'] ?? [];
            $nuevoCurso = $data['nuevo_curso'] ?? [];
            $cursoActual = $data['curso_actual'] ?? [];
            $params = ['success' => 'curso'];
            if (!empty($cursoActual['anio']) && $cursoActual['anio'] !== '' && !empty($cursoActual['division'])) {
                $params['desde_anio'] = (string) $cursoActual['anio'];
                $params['desde_div'] = (string) $cursoActual['division'];
            }
            $params['hasta_anio'] = (string) ($nuevoCurso['anio'] ?? '');
            $params['hasta_div'] = (string) ($nuevoCurso['division'] ?? '');

            return ['redirect' => $base . '&' . http_build_query($params), 'error' => ''];
        }

        return ['redirect' => null, 'error' => (string) ($r['error'] ?? 'Error al cambiar curso')];
    }

    public function obtenerFichaVista(int $estudianteId): array
    {
        try {
            if ($estudianteId <= 0) {
                return ['success' => false, 'error' => 'ID de estudiante inválido'];
            }
            $data = $this->servicioEstudiantes->obtenerVistaFichaEstudiante($estudianteId);
            if ($data === null) {
                return ['success' => false, 'error' => 'Estudiante no encontrado'];
            }
            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'obtener_ficha_vista_estudiante',
                'estudiante_id' => $estudianteId,
            ]);
        }
    }

    /**
     * Filtro de curso en el listado según alcance (preceptor/docente) o sin restricción (null).
     *
     * @param list<int>|null $alcanceCids
     */
    public static function resolverFiltroCursoPreceptor(?array $alcanceCids, string $cursoFromGet): string
    {
        if ($alcanceCids === null) {
            return $cursoFromGet;
        }
        if ($alcanceCids === []) {
            return '';
        }
        if (count($alcanceCids) === 1) {
            return (string) $alcanceCids[0];
        }
        if ($cursoFromGet !== '' && in_array((int) $cursoFromGet, $alcanceCids, true)) {
            return (string) (int) $cursoFromGet;
        }

        return '';
    }

    /**
     * Payload para crear() desde POST (reglas de curso para preceptor).
     *
     * @param list<int> $preceptorCids
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function construirDatosCreacionDesdePost(array $post, array $preceptorCids): array
    {
        $cursoPost = !empty($post['curso_id']) ? $post['curso_id'] : null;
        if ($preceptorCids !== []) {
            if (count($preceptorCids) === 1) {
                $cursoPost = (string) $preceptorCids[0];
            } else {
                $pid = (int) ($post['curso_id'] ?? 0);
                if (!in_array($pid, $preceptorCids, true)) {
                    throw new \InvalidArgumentException('Seleccione un curso válido entre los asignados a su cargo.');
                }
                $cursoPost = (string) $pid;
            }
        }

        $dniRespNorm = preg_replace('/\D/', '', (string) ($post['dni_responsable'] ?? ''));

        return [
            'dni' => $post['dni'] ?? '',
            'dni_responsable' => $dniRespNorm !== '' ? $dniRespNorm : null,
            'apellido' => $post['apellido'] ?? '',
            'nombre' => $post['nombre'] ?? '',
            'fecha_nacimiento' => !empty($post['fecha_nacimiento']) ? $post['fecha_nacimiento'] : null,
            'grupo_sanguineo' => !empty($post['grupo_sanguineo']) ? $post['grupo_sanguineo'] : null,
            'obra_social' => !empty($post['obra_social']) ? $post['obra_social'] : null,
            'domicilio' => !empty($post['domicilio']) ? $post['domicilio'] : null,
            'telefono_fijo' => !empty($post['telefono_fijo']) ? $post['telefono_fijo'] : null,
            'telefono_celular' => !empty($post['telefono_celular']) ? $post['telefono_celular'] : null,
            'email' => !empty($post['email']) ? $post['email'] : null,
            'curso_id' => $cursoPost !== null && $cursoPost !== '' ? $cursoPost : null,
            'grupo_taller' => !empty($post['grupo_taller']) ? $post['grupo_taller'] : null,
        ];
    }

    /**
     * Elimina con comprobación de alcance para preceptor.
     *
     * @param list<int> $preceptorCids
     */
    public function eliminarConAlcancePreceptor(int $id, array $preceptorCids): array
    {
        try {
            if ($preceptorCids !== [] && $id > 0) {
                $cid = $this->servicioEstudiantes->obtenerCursoIdEstudianteActivo($id);
                if ($cid === null || !in_array($cid, $preceptorCids, true)) {
                    return [
                        'success' => false,
                        'error' => 'No puede modificar estudiantes fuera de sus cursos asignados.',
                    ];
                }
            }

            return $this->eliminar($id);
        } catch (\Exception $e) {
            return $this->handleError($e, [
                'action' => 'eliminar_estudiante_preceptor',
                'id' => $id,
            ]);
        }
    }

    /**
     * Valores del formulario de alta para reenvío tras error.
     *
     * @param array<string, mixed> $post
     *
     * @return array<string, string>
     */
    public function formValuesEstudianteCreacionDesdePost(array $post): array
    {
        return [
            'dni' => (string) ($post['dni'] ?? ''),
            'dni_responsable' => (string) ($post['dni_responsable'] ?? ''),
            'apellido' => (string) ($post['apellido'] ?? ''),
            'nombre' => (string) ($post['nombre'] ?? ''),
            'fecha_nacimiento' => (string) ($post['fecha_nacimiento'] ?? ''),
            'grupo_sanguineo' => (string) ($post['grupo_sanguineo'] ?? ''),
            'obra_social' => (string) ($post['obra_social'] ?? ''),
            'domicilio' => (string) ($post['domicilio'] ?? ''),
            'telefono_fijo' => (string) ($post['telefono_fijo'] ?? ''),
            'telefono_celular' => (string) ($post['telefono_celular'] ?? ''),
            'email' => (string) ($post['email'] ?? ''),
            'curso_id' => (string) ($post['curso_id'] ?? ''),
            'grupo_taller' => (string) ($post['grupo_taller'] ?? ''),
        ];
    }

    /**
     * POST alta y baja en students.php (PRG si tiene éxito).
     *
     * @param array<string, mixed> $post
     * @param list<int> $preceptorCids
     *
     * @return array{
     *   redirect: string|null,
     *   error: string,
     *   action: string|null,
     *   form_values: array<string, string>|null
     * }
     */
    public function procesarPostEstudiantes(array $post, array $preceptorCids, bool $preceptorSinCurso): array
    {
        $sinCambio = ['redirect' => null, 'error' => '', 'action' => null, 'form_values' => null];
        $estudiantesListadoUrl = function_exists('app_base_path')
            ? app_base_path('students.php')
            : 'students.php';

        if (isset($post['guardar_estudiante'])) {
            if ($preceptorSinCurso) {
                return [
                    'redirect' => null,
                    'error' => 'No puede registrar estudiantes mientras no tenga cursos asignados como preceptor.',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            try {
                $data = $this->construirDatosCreacionDesdePost($post, $preceptorCids);
            } catch (\InvalidArgumentException $e) {
                return [
                    'redirect' => null,
                    'error' => $e->getMessage(),
                    'action' => 'nuevo',
                    'form_values' => $this->formValuesEstudianteCreacionDesdePost($post),
                ];
            }

            try {
                $resultado = $this->crear($data);
            } catch (\Throwable $e) {
                return [
                    'redirect' => null,
                    'error' => 'Error al registrar estudiante: ' . $e->getMessage(),
                    'action' => 'nuevo',
                    'form_values' => $this->formValuesEstudianteCreacionDesdePost($post),
                ];
            }

            if (!empty($resultado['success'])) {
                return [
                    'redirect' => $estudiantesListadoUrl . '?' . http_build_query(['success' => 'creado']),
                    'error' => '',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            $formValues = $this->formValuesEstudianteCreacionDesdePost($post);
            if (isset($resultado['errors']) && is_array($resultado['errors']) && $resultado['errors'] !== []) {
                $lines = array_map(static function ($err): string {
                    return '• ' . (string) $err;
                }, $resultado['errors']);

                return [
                    'redirect' => null,
                    'error' => "Los siguientes datos no son válidos:\n" . implode("\n", $lines),
                    'action' => 'nuevo',
                    'form_values' => $formValues,
                ];
            }

            return [
                'redirect' => null,
                'error' => (string) ($resultado['error'] ?? 'Error al registrar estudiante'),
                'action' => 'nuevo',
                'form_values' => $formValues,
            ];
        }

        if (isset($post['eliminar_estudiante'])) {
            if ($preceptorSinCurso) {
                return [
                    'redirect' => null,
                    'error' => 'No puede eliminar estudiantes mientras no tenga cursos asignados.',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            $estudianteId = (int) ($post['estudiante_id'] ?? 0);
            if ($estudianteId <= 0) {
                return [
                    'redirect' => null,
                    'error' => 'ID de estudiante inválido',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            $nombreEtiqueta = '';
            try {
                $estModel = $this->servicioEstudiantes->buscarPorId($estudianteId);
                $nombreEtiqueta = $estModel->getApellido() . ', ' . $estModel->getNombre();
            } catch (EstudianteNoEncontradoException) {
                $nombreEtiqueta = '';
            }

            try {
                $resultado = $this->eliminarConAlcancePreceptor($estudianteId, $preceptorCids);
            } catch (\Throwable $e) {
                return [
                    'redirect' => null,
                    'error' => 'Error al eliminar estudiante: ' . $e->getMessage(),
                    'action' => null,
                    'form_values' => null,
                ];
            }

            if (!empty($resultado['success'])) {
                $params = ['success' => 'eliminado'];
                if ($nombreEtiqueta !== '') {
                    $params['nombre'] = $nombreEtiqueta;
                }

                return [
                    'redirect' => $estudiantesListadoUrl . '?' . http_build_query($params),
                    'error' => '',
                    'action' => null,
                    'form_values' => null,
                ];
            }

            return [
                'redirect' => null,
                'error' => (string) ($resultado['error'] ?? 'Error al eliminar estudiante'),
                'action' => null,
                'form_values' => null,
            ];
        }

        return $sinCambio;
    }

    /**
     * Listado paginado para la vista students.php (SQL con LIMIT/OFFSET y COUNT paralelo).
     *
     * @param list<int>|null $alcanceCids
     *
     * @return array{
     *     filas: list<array<string, mixed>>,
     *     total_filtrado: int,
     *     pagination: array<string, mixed>
     * }
     */
    public function datosListadoPaginadoParaVista(
        string $search,
        string $cursoFilter,
        ?array $alcanceCids,
        bool $preceptorSinCurso,
        int $page,
        int $perPage = 20,
        ?string $grupoTaller = null
    ): array {
        if ($preceptorSinCurso) {
            return [
                'filas' => [],
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

        $cursoIdFiltro = $cursoFilter !== '' ? (int) $cursoFilter : null;
        $total = $this->servicioEstudiantes->contarListadoVista($search, $cursoIdFiltro, $alcanceCids, $grupoTaller);

        $paginationSvc = new PaginationService($this->database);
        $meta = $paginationSvc->calculatePagination($total, $page, $perPage);
        $filas = $this->servicioEstudiantes->listarVistaPaginado(
            $search,
            $cursoIdFiltro,
            $alcanceCids,
            (int) $meta['page_size'],
            (int) $meta['offset'],
            $grupoTaller
        );

        $pageNumbers = $paginationSvc->getPageNumbers((int) $meta['total_pages'], (int) $meta['current_page'], 7);

        return [
            'filas' => $filas,
            'total_filtrado' => $total,
            'pagination' => array_merge($meta, ['page_numbers' => $pageNumbers]),
        ];
    }

    /**
     * Añade etiquetas de curso (evita buscar en la vista) y fecha de nacimiento formateada.
     *
     * @param list<array<string, mixed>> $filas
     * @param list<array<string, mixed>> $cursos mismo origen que cursosParaVistaEstudiantes
     *
     * @return list<array<string, mixed>>
     */
    public function enriquecerFilasListadoParaVista(array $filas, array $cursos): array
    {
        $map = [];
        foreach ($cursos as $c) {
            $map[(int) ($c['id'] ?? 0)] = $c;
        }

        $out = [];
        foreach ($filas as $e) {
            $cid = (int) ($e['curso_id'] ?? 0);
            $curso = $cid > 0 ? ($map[$cid] ?? null) : null;

            $fn = $e['fecha_nacimiento'] ?? null;
            $e['fecha_nacimiento_dmY'] = '';
            if ($fn !== null && $fn !== '') {
                $ts = strtotime((string) $fn);
                if ($ts !== false) {
                    $e['fecha_nacimiento_dmY'] = date('d/m/Y', $ts);
                }
            }

            if ($cid <= 0) {
                $e['listado_curso_ok'] = false;
                $e['listado_curso_sin_asignar'] = true;
                $e['listado_curso_anio_div'] = '';
                $e['listado_curso_especialidad'] = '';
                $e['listado_curso_turno'] = '';
            } elseif ($curso === null) {
                $e['listado_curso_ok'] = false;
                $e['listado_curso_sin_asignar'] = false;
                $e['listado_curso_anio_div'] = '';
                $e['listado_curso_especialidad'] = '';
                $e['listado_curso_turno'] = '';
            } else {
                $e['listado_curso_ok'] = true;
                $e['listado_curso_sin_asignar'] = false;
                $e['listado_curso_anio_div'] = (string) ($curso['anio'] ?? '') . '° ' . (string) ($curso['division'] ?? '');
                $e['listado_curso_especialidad'] = self::etiquetaEspecialidadCursoListado($curso);
                $e['listado_curso_turno'] = !empty($curso['turno']) ? (string) $curso['turno'] : 'Sin turno';
            }

            $out[] = $e;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $curso fila de listarCursosActivosParaFormularioAlta
     */
    private static function etiquetaEspecialidadCursoListado(array $curso): string
    {
        if (!empty($curso['especialidad'])) {
            return (string) $curso['especialidad'];
        }

        return '';
    }

    /**
     * @param list<int>|null $alcanceCids
     *
     * @return list<array<string, mixed>>
     */
    public function cursosParaVistaEstudiantes(?array $alcanceCids): array
    {
        $cursos = $this->servicioEstudiantes->obtenerCursosActivosParaFormularioEstudiante();
        if ($alcanceCids === null) {
            return $cursos;
        }
        if ($alcanceCids === []) {
            return [];
        }

        return array_values(array_filter($cursos, static function ($c) use ($alcanceCids) {
            return in_array((int) $c['id'], $alcanceCids, true);
        }));
    }

    /**
     * @param list<int>|null $alcanceCids
     *
     * @return array{total_estudiantes: int, estudiantes_sin_curso: int}
     */
    public function totalesEncabezadoVista(int $totalListadoFiltrado, string $cursoFilter, ?array $alcanceCids, array $estadisticas): array
    {
        if ($cursoFilter !== '') {
            $totalEstudiantes = $totalListadoFiltrado;
        } else {
            $totalEstudiantes = $estadisticas['success']
                ? ($estadisticas['data']['total_estudiantes'] ?? $totalListadoFiltrado)
                : $totalListadoFiltrado;
        }
        $estudiantesSinCurso = $alcanceCids !== null
            ? 0
            : ($estadisticas['success'] ? ($estadisticas['data']['sin_contacto'] ?? 0) : 0);

        return [
            'total_estudiantes' => $totalEstudiantes,
            'estudiantes_sin_curso' => $estudiantesSinCurso,
        ];
    }

    /**
     * Procesa la importación de estudiantes desde una plantilla Excel (.xlsm).
     *
     * @param array $fileInfo Información de $_FILES['excel_file']
     * @param list<int>|null $preceptorCids Cursos asignados al preceptor actual (null si es admin)
     * @return array{success: bool, error?: string, curso?: string, importados?: int, duplicados?: int, errores?: list<string>}
     */
    public function importarExcel(array $fileInfo, ?array $preceptorCids = null): array
    {
        if (empty($fileInfo['tmp_name']) || $fileInfo['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'No se subió ningún archivo o ocurrió un error durante la carga.'
            ];
        }

        $extension = strtolower(pathinfo((string)$fileInfo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'xlsm') {
            return [
                'success' => false,
                'error' => 'El archivo debe tener la extensión .xlsm (plantilla Excel con macros).'
            ];
        }

        try {
            $excelService = new \SistemaAdmin\Services\ExcelImportService();
            $parsedData = $excelService->parseXlsm($fileInfo['tmp_name']);

            $cursoData = $parsedData['curso'];
            $estudiantesData = $parsedData['estudiantes'];

            $anio = $cursoData['anio'];
            $division = $cursoData['division'];
            $turnoNombre = $cursoData['turno'];
            $especialidadNombre = $cursoData['especialidad'];

            if ($anio === null || $division === null) {
                return [
                    'success' => false,
                    'error' => 'No se pudo identificar el año y división del curso en la celda A1 del Excel (debe tener el formato Ej: 3ro. 1ra. o 3° 1°).'
                ];
            }

            // 1. Resolver el curso
            $curso = $this->database->fetch(
                "SELECT * FROM cursos WHERE anio = ? AND division = ? AND activo = 1 LIMIT 1",
                [$anio, $division]
            );

            if (!$curso) {
                // Buscar uno inactivo para reactivarlo
                $curso = $this->database->fetch(
                    "SELECT * FROM cursos WHERE anio = ? AND division = ? LIMIT 1",
                    [$anio, $division]
                );
                if ($curso) {
                    $this->database->query("UPDATE cursos SET activo = 1 WHERE id = ?", [$curso['id']]);
                    $curso['activo'] = 1;
                } else {
                    // Crear nuevo curso
                    $turnoId = 1; // Mañana por defecto
                    if ($turnoNombre !== null) {
                        $turnoRow = $this->database->fetch(
                            "SELECT id FROM turnos WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1",
                            [$turnoNombre]
                        );
                        if ($turnoRow) {
                            $turnoId = (int)$turnoRow['id'];
                        }
                    }

                    $especialidadId = null;
                    if ($especialidadNombre !== null && strtolower(trim($especialidadNombre)) !== 'ciclo básico' && strtolower(trim($especialidadNombre)) !== 'ciclo basico') {
                        $espRow = $this->database->fetch(
                            "SELECT id FROM especialidades WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1",
                            [$especialidadNombre]
                        );
                        if ($espRow) {
                            $especialidadId = (int)$espRow['id'];
                        }
                    }

                    $this->database->query(
                        "INSERT INTO cursos (nombre, anio, division, especialidad_id, turno_id, capacidad_maxima, activo)
                         VALUES ('', ?, ?, ?, ?, 30, 1)",
                        [$anio, $division, $especialidadId, $turnoId]
                    );
                    $newCursoId = (int)$this->database->lastInsertId();
                    $curso = $this->database->fetch("SELECT * FROM cursos WHERE id = ?", [$newCursoId]);
                }
            }

            // 2. Control de alcance del preceptor
            if ($preceptorCids !== null && $preceptorCids !== []) {
                if (!in_array((int)$curso['id'], $preceptorCids, true)) {
                    return [
                        'success' => false,
                        'error' => sprintf(
                            'El curso %d° "%s" en el archivo Excel no coincide con ninguno de los cursos asignados a su cargo.',
                            $curso['anio'],
                            $curso['division']
                        )
                    ];
                }
            }

            // 3. Importar estudiantes
            $importados = 0;
            $duplicados = 0;
            $errores = [];

            foreach ($estudiantesData as $est) {
                $dni = $est['dni'];
                $apellido = $est['apellido'];
                $nombre = $est['nombre'];
                $grupo = $est['grupo_taller'];

                if ($this->servicioEstudiantes->dniExiste($dni)) {
                    $duplicados++;
                    continue;
                }

                try {
                    $estudiante = new Estudiante(
                        $dni,
                        $nombre,
                        $apellido
                    );
                    $estudiante->setCursoId((int)$curso['id']);
                    // Usar DNI del estudiante como DNI del responsable predeterminado
                    $estudiante->setDniResponsable($dni);
                    if ($grupo !== null) {
                        $estudiante->setGrupoTaller($grupo);
                    }

                    $this->servicioEstudiantes->crear($estudiante);
                    $importados++;
                } catch (\Throwable $e) {
                    $errores[] = sprintf('Estudiante DNI %s (%s, %s): %s', $dni, $apellido, $nombre, $e->getMessage());
                }
            }

            return [
                'success' => true,
                'curso' => sprintf('%d° "%s"', $curso['anio'], $curso['division']),
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Error al procesar el archivo Excel: ' . $e->getMessage()
            ];
        }
    }
}

