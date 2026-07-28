<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Autenticación (Refactorizado)
 * 
 * Ahora solo maneja la lógica de autenticación, delegando
 * responsabilidades específicas a servicios especializados.
 * Servicio central de autenticación del sistema.
 */
class ServicioAutenticacion extends BaseService
{
    private UsuarioRepository $usuarioRepository;
    private SessionService $sessionService;
    private PermissionService $permissionService;
    private AuthCacheService $authCacheService;
    private ConfigurationService $configService;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->usuarioRepository = new UsuarioRepository($database);
        $this->sessionService = new SessionService($database);
        $this->permissionService = new PermissionService($database, $this->sessionService);
        $this->authCacheService = new AuthCacheService($database);
        $this->configService = new ConfigurationService($database);
    }

    public function autenticar(string $username, string $password): array
    {
        try {
            $maxIntentos = max(1, min(50, (int) round((float) $this->configService->obtener('seguridad.max_intentos_login', 5))));
            $minutosBloqueo = max(1, min(1440, (int) round((float) $this->configService->obtener('seguridad.tiempo_bloqueo', 30))));

            $usuario = $this->usuarioRepository->findByUsername($username);

            if (!$usuario) {
                return [
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos',
                ];
            }

            $uid = (int) $usuario['id'];

            if (!empty($usuario['bloqueado_hasta'])) {
                $hasta = strtotime((string) $usuario['bloqueado_hasta']);
                if ($hasta !== false && $hasta > time()) {
                    $minsLeft = max(1, (int) ceil(($hasta - time()) / 60));

                    return [
                        'success' => false,
                        'error' => 'Cuenta temporalmente bloqueada por intentos fallidos. Intente nuevamente en ' . $minsLeft . ' minuto(s).',
                    ];
                }
                $this->usuarioRepository->resetIntentosLogin($uid);
                $this->authCacheService->invalidateUserCache($uid, $username);
                $usuario = $this->usuarioRepository->findByUsername($username) ?? $usuario;
            }

            if (isset($usuario['activo']) && !$usuario['activo']) {
                $this->logEvent('WARNING', 'Intento de login de usuario inactivo', [
                    'username' => $username,
                    'ip' => $this->obtenerIPCliente(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Usuario inactivo',
                ];
            }

            if (!password_verify($password, (string) ($usuario['password_hash'] ?? ''))) {
                $this->usuarioRepository->registrarFalloLogin($uid, $maxIntentos, $minutosBloqueo);
                $this->authCacheService->invalidateUserCache($uid, $username);

                return [
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos',
                ];
            }

            $this->usuarioRepository->resetIntentosLogin($uid);
            $this->authCacheService->invalidateUserCache($uid, $username);

            // Verificar modo mantenimiento (solo acceso administrador)
            $modoMantenimiento = (bool)$this->configService->obtener('sistema.mantenimiento', false);
            if ($modoMantenimiento) {
                $rolUsuario = strtolower($usuario['rol'] ?? '');
                $rolesPermitidos = ['admin'];

                if (!in_array($rolUsuario, $rolesPermitidos, true)) {
                    $this->logEvent('WARNING', 'Acceso bloqueado por modo mantenimiento', [
                        'username' => $username,
                        'rol' => $usuario['rol'] ?? null,
                        'ip' => $this->obtenerIPCliente()
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Sistema en mantenimiento. Solo el administrador puede acceder temporalmente.'
                    ];
                }
            }
            
            // Usuario autenticado exitosamente
            return [
                'success' => true,
                'data' => [
                    'id' => $usuario['id'],
                    'username' => $usuario['dni'], // Usar dni como username
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'email' => $usuario['email'] ?? '',
                    'rol' => $usuario['rol'],
                    'must_change_password' => (int) ($usuario['must_change_password'] ?? 0) === 1,
                    'ultimo_acceso' => $usuario['ultimo_acceso'] ?? null
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    public function actualizarUltimoAcceso(int $usuarioId): bool
    {
        try {
            // Usar transacción para evitar race conditions
            $this->database->beginTransaction();
            
            $result = $this->usuarioRepository->actualizarUltimoAcceso($usuarioId);
            
            if ($result) {
                $this->database->commit();
                return true;
            } else {
                $this->database->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $this->database->rollBack();
            $this->logEvent('ERROR', 'Error actualizando último acceso', [
                'user_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function obtenerUsuarioPorId(int $usuarioId): ?array
    {
        return $this->authCacheService->getUserById($usuarioId);
    }

    public function cambiarPassword(int $usuarioId, string $passwordActual, string $passwordNuevo): array
    {
        try {
            // Validar entrada
            if (empty($passwordActual) || empty($passwordNuevo)) {
                return [
                    'success' => false,
                    'error' => 'Las contraseñas no pueden estar vacías'
                ];
            }
            
            $minLen = max(6, min(128, (int) round((float) $this->configService->obtener('seguridad.password_min_longitud', 8))));
            if (strlen($passwordNuevo) < $minLen) {
                return [
                    'success' => false,
                    'error' => 'La nueva contraseña debe tener al menos ' . $minLen . ' caracteres',
                ];
            }
            
            // Verificar contraseña actual usando el repositorio
            if (!$this->usuarioRepository->verificarPassword($usuarioId, $passwordActual)) {
                return [
                    'success' => false,
                    'error' => 'La contraseña actual es incorrecta'
                ];
            }
            
            // Actualizar contraseña usando el repositorio con algoritmo más seguro
            $passwordHash = password_hash($passwordNuevo, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iteraciones
                'threads' => 3          // 3 hilos
            ]);
            
            if ($this->usuarioRepository->cambiarPassword($usuarioId, $passwordHash)) {
                return [
                    'success' => true,
                    'message' => 'Contraseña actualizada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al actualizar la contraseña'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    public function cerrarSesion(): bool
    {
        try {
            $this->sessionService->cerrar();
            $this->logEvent('INFO', 'Sesión cerrada exitosamente', [
                'ip' => $this->obtenerIPCliente()
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error cerrando sesión', [
                'error' => $e->getMessage(),
                'ip' => $this->obtenerIPCliente()
            ]);
            return false;
        }
    }

    /**
     * Valida la sesión y devuelve los datos actuales del usuario (BD/cache), o null.
     * Preferible a leer $_SESSION directamente en vistas: incluye nombre, apellido, rol, ultimo_acceso, etc.
     */
    public function verificarSesion(): ?array
    {
        if (!$this->sessionService->tieneSesion()) {
            return null;
        }
        
        $usuarioId = $this->sessionService->obtenerUsuarioId();
        
        // Validar ID de usuario
        if (!$usuarioId || !is_numeric($usuarioId) || $usuarioId <= 0) {
            $this->logEvent('WARNING', 'ID de usuario inválido en sesión', [
                'usuario_id' => $usuarioId,
                'ip' => $this->obtenerIPCliente()
            ]);
            return null;
        }
        
        $usuario = $this->obtenerUsuarioPorId((int)$usuarioId);
        $this->sincronizarAlcancePreceptor();
        $this->sincronizarCargoEquipoDirectivo();
        $this->sincronizarAlcanceProfesor();
        return $usuario;
    }

    /**
     * Guarda en sesión el cargo en equipo_directivo (director, vicedirector, preceptor, etc.) para permisos de menú.
     */
    public function sincronizarCargoEquipoDirectivo(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $uid = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($uid <= 0) {
            unset($_SESSION['equipo_directivo_cargo']);
            return;
        }
        try {
            $row = $this->database->fetch(
                'SELECT LOWER(TRIM(cargo)) AS c FROM equipo_directivo WHERE usuario_id = ? AND activo = 1 LIMIT 1',
                [$uid]
            );
            if ($row && isset($row['c']) && $row['c'] !== '') {
                $_SESSION['equipo_directivo_cargo'] = $row['c'];
            } else {
                unset($_SESSION['equipo_directivo_cargo']);
            }
        } catch (\Throwable $e) {
            unset($_SESSION['equipo_directivo_cargo']);
        }
    }

    /**
     * Guarda en sesión el curso asignado al preceptor (tabla equipo_directivo).
     */
    public function sincronizarAlcancePreceptor(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (($_SESSION['rol'] ?? '') !== 'preceptor') {
            unset($_SESSION['preceptor_curso_id'], $_SESSION['preceptor_curso_label'], $_SESSION['preceptor_curso_ids']);
            return;
        }
        $uid = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($uid <= 0) {
            unset($_SESSION['preceptor_curso_id'], $_SESSION['preceptor_curso_label'], $_SESSION['preceptor_curso_ids']);
            return;
        }
        try {
            $ed = $this->database->fetch(
                'SELECT ed.id, ed.curso_id FROM equipo_directivo ed
                 WHERE ed.usuario_id = ? AND LOWER(TRIM(ed.cargo)) = \'preceptor\' AND ed.activo = 1
                 LIMIT 1',
                [$uid]
            );
            if (!$ed) {
                $_SESSION['preceptor_curso_id'] = null;
                $_SESSION['preceptor_curso_label'] = null;
                $_SESSION['preceptor_curso_ids'] = [];

                return;
            }
            $rows = [];
            try {
                $rows = $this->database->fetchAll(
                    'SELECT pc.curso_id, c.anio, c.division
                     FROM preceptor_curso pc
                     INNER JOIN cursos c ON c.id = pc.curso_id AND c.activo = 1
                     WHERE pc.equipo_directivo_id = ?
                     ORDER BY c.anio, c.division',
                    [(int) $ed['id']]
                );
            } catch (\Throwable $e) {
                $rows = [];
            }
            if (empty($rows) && !empty($ed['curso_id'])) {
                $fb = $this->database->fetch(
                    'SELECT id AS curso_id, anio, division FROM cursos WHERE id = ? AND activo = 1',
                    [(int) $ed['curso_id']]
                );
                if ($fb) {
                    $rows = [$fb];
                }
            }
            $ids = [];
            $labels = [];
            foreach ($rows as $r) {
                if (!empty($r['curso_id'])) {
                    $ids[] = (int) $r['curso_id'];
                    $labels[] = ($r['anio'] ?? '?') . '° ' . ($r['division'] ?? '');
                }
            }
            $ids = array_values(array_unique($ids));
            $_SESSION['preceptor_curso_ids'] = $ids;
            $_SESSION['preceptor_curso_id'] = $ids[0] ?? null;
            $_SESSION['preceptor_curso_label'] = $labels !== [] ? implode(', ', $labels) : null;
        } catch (\Throwable $e) {
            $_SESSION['preceptor_curso_id'] = null;
            $_SESSION['preceptor_curso_label'] = null;
            $_SESSION['preceptor_curso_ids'] = [];
        }
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->permissionService->tienePermiso($permiso);
    }

    /**
     * Guarda en sesión el ID de profesor y los cursos/materias asignados (tabla profesor_materia).
     * Solo actúa cuando el rol activo es 'profesor'.
     */
    public function sincronizarAlcanceProfesor(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (($_SESSION['rol'] ?? '') !== 'profesor') {
            unset($_SESSION['profesor_id'], $_SESSION['profesor_curso_ids'], $_SESSION['profesor_materias_por_curso']);
            return;
        }

        $uid = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($uid <= 0) {
            unset($_SESSION['profesor_id'], $_SESSION['profesor_curso_ids'], $_SESSION['profesor_materias_por_curso']);
            return;
        }

        try {
            $emailUsuario = $_SESSION['email'] ?? null;
            if (empty($emailUsuario)) {
                $uRow = $this->database->fetch('SELECT email FROM usuarios WHERE id = ? LIMIT 1', [$uid]);
                $emailUsuario = $uRow['email'] ?? null;
            }

            $profRow = null;
            if (!empty($emailUsuario)) {
                $emailNorm = strtolower(trim((string) $emailUsuario));
                if ($emailNorm !== '') {
                    $profRow = $this->database->fetch(
                        'SELECT id FROM profesores
                         WHERE LOWER(TRIM(email)) = ? AND activo = 1 LIMIT 1',
                        [$emailNorm]
                    );
                }
            }

            if (!$profRow) {
                $dniUsuario = isset($_SESSION['username']) ? trim((string) $_SESSION['username']) : '';
                if ($dniUsuario !== '') {
                    $profRow = $this->database->fetch(
                        'SELECT id FROM profesores WHERE TRIM(dni) = ? AND activo = 1 LIMIT 1',
                        [$dniUsuario]
                    );
                }
            }

            if (!$profRow) {
                $_SESSION['profesor_id'] = null;
                $_SESSION['profesor_curso_ids'] = [];
                $_SESSION['profesor_materias_por_curso'] = [];
                return;
            }

            $profesorId = (int) $profRow['id'];
            $_SESSION['profesor_id'] = $profesorId;

            $asignaciones = $this->database->fetchAll(
                'SELECT DISTINCT curso_id, materia_id FROM profesor_materia
                 WHERE profesor_id = ? AND activo = 1 ORDER BY curso_id, materia_id',
                [$profesorId]
            );

            $cursoIds = [];
            $materiasPorCurso = [];
            foreach ($asignaciones as $row) {
                $cid = (int) $row['curso_id'];
                $mid = (int) $row['materia_id'];
                if ($cid > 0 && !in_array($cid, $cursoIds, true)) {
                    $cursoIds[] = $cid;
                }
                if ($cid > 0 && $mid > 0) {
                    $materiasPorCurso[$cid][] = $mid;
                }
            }

            $cursosSoloProfesorCurso = $this->database->fetchAll(
                'SELECT DISTINCT curso_id FROM profesor_curso
                 WHERE profesor_id = ? AND activo = 1',
                [$profesorId]
            );
            foreach ($cursosSoloProfesorCurso as $row) {
                $cid = (int) ($row['curso_id'] ?? 0);
                if ($cid > 0 && !in_array($cid, $cursoIds, true)) {
                    $cursoIds[] = $cid;
                }
            }

            sort($cursoIds);
            $_SESSION['profesor_curso_ids'] = $cursoIds;
            $_SESSION['profesor_materias_por_curso'] = $materiasPorCurso;
        } catch (\Throwable $e) {
            error_log('sincronizarAlcanceProfesor: ' . $e->getMessage());
            $_SESSION['profesor_id'] = null;
            $_SESSION['profesor_curso_ids'] = [];
            $_SESSION['profesor_materias_por_curso'] = [];
        }
    }
}
