<?php
namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Cache específico para autenticación
 * Optimiza consultas de usuarios y permisos
 */
class AuthCacheService extends BaseService
{
    private CacheService $cacheService;
    private int $userCacheTtl = 1800; // 30 minutos
    private int $permissionCacheTtl = 3600; // 1 hora

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->cacheService = new CacheService($database);
    }

    /**
     * Cache de usuario por ID
     */
    public function getUserById(int $userId): ?array
    {
        $cacheKey = "user:{$userId}";
        
        return $this->cacheService->remember($cacheKey, function() use ($userId) {
            $usuarioRepository = new UsuarioRepository($this->database);
            return $usuarioRepository->findById($userId);
        }, $this->userCacheTtl);
    }

    /**
     * Cache de usuario por username
     */
    public function getUserByUsername(string $username): ?array
    {
        $cacheKey = "user:username:{$username}";
        
        return $this->cacheService->remember($cacheKey, function() use ($username) {
            $usuarioRepository = new UsuarioRepository($this->database);
            return $usuarioRepository->findByUsername($username);
        }, $this->userCacheTtl);
    }

    /**
     * Cache de permisos por rol
     */
    public function getPermissionsByRole(string $role): array
    {
        $cacheKey = "permissions:role:{$role}";
        
        return $this->cacheService->remember($cacheKey, function() use ($role) {
            return $this->getRolePermissions($role);
        }, $this->permissionCacheTtl);
    }

    /**
     * Invalidar cache de usuario
     */
    public function invalidateUserCache(int $userId, string $username = null): void
    {
        $this->cacheService->delete("user:{$userId}");
        
        if ($username) {
            $this->cacheService->delete("user:username:{$username}");
        }
    }

    /**
     * Invalidar cache de permisos
     */
    public function invalidatePermissionCache(string $role = null): void
    {
        if ($role) {
            $this->cacheService->delete("permissions:role:{$role}");
        } else {
            $this->cacheService->invalidatePattern("permissions:*");
        }
    }

    /**
     * Cache de sesión activa
     */
    public function getActiveSession(int $userId): ?array
    {
        $cacheKey = "session:active:{$userId}";
        return $this->cacheService->get($cacheKey);
    }

    /**
     * Guardar sesión activa en cache
     */
    public function setActiveSession(int $userId, array $sessionData): void
    {
        $cacheKey = "session:active:{$userId}";
        $this->cacheService->set($cacheKey, $sessionData, $this->userCacheTtl);
    }

    /**
     * Eliminar sesión del cache
     */
    public function removeActiveSession(int $userId): void
    {
        $cacheKey = "session:active:{$userId}";
        $this->cacheService->delete($cacheKey);
    }

    /**
     * Cache de intentos de login por IP
     */
    public function getLoginAttempts(string $ip): array
    {
        $cacheKey = "login_attempts:{$ip}";
        return $this->cacheService->get($cacheKey) ?? [];
    }

    /**
     * Guardar intento de login
     */
    public function recordLoginAttempt(string $ip, bool $success): void
    {
        $cacheKey = "login_attempts:{$ip}";
        $attempts = $this->getLoginAttempts($ip);
        
        $attempts[] = [
            'timestamp' => time(),
            'success' => $success
        ];
        
        // Mantener solo los últimos 10 intentos
        $attempts = array_slice($attempts, -10);
        
        $this->cacheService->set($cacheKey, $attempts, 3600); // 1 hora
    }

    /**
     * Obtener permisos específicos por rol
     */
    private function getRolePermissions(string $role): array
    {
        $permissions = [
            'admin' => [
                'ver_estudiantes', 'crear_estudiantes', 'editar_estudiantes', 'eliminar_estudiantes',
                'ver_profesores', 'crear_profesores', 'editar_profesores', 'eliminar_profesores',
                'ver_cursos', 'crear_cursos', 'editar_cursos', 'eliminar_cursos',
                'ver_notas', 'crear_notas', 'editar_notas', 'eliminar_notas',
                'ver_llamados', 'crear_llamados', 'editar_llamados', 'eliminar_llamados',
                'ver_horarios', 'crear_horarios', 'editar_horarios', 'eliminar_horarios',
                'ver_materias', 'crear_materias', 'editar_materias', 'eliminar_materias',
                'ver_especialidades', 'crear_especialidades', 'editar_especialidades', 'eliminar_especialidades',
                'ver_equipo', 'crear_equipo', 'editar_equipo', 'eliminar_equipo',
                'ver_reportes', 'exportar_datos', 'configurar_sistema'
            ],
            'directivo' => [
                'ver_estudiantes', 'editar_estudiantes',
                'ver_profesores', 'editar_profesores',
                'ver_cursos', 'editar_cursos',
                'ver_notas', 'editar_notas',
                'ver_llamados', 'crear_llamados', 'editar_llamados',
                'ver_horarios', 'editar_horarios',
                'ver_materias', 'editar_materias',
                'ver_especialidades', 'editar_especialidades',
                'ver_equipo', 'editar_equipo',
                'ver_reportes', 'exportar_datos'
            ],
            'profesor' => [
                'ver_estudiantes', 'ver_profesores', 'ver_cursos',
                'ver_notas', 'crear_notas', 'editar_notas',
                'ver_llamados', 'crear_llamados',
                'ver_horarios', 'ver_materias'
            ],
            'preceptor' => [
                'ver_estudiantes', 'editar_estudiantes',
                'ver_cursos', 'ver_notas',
                'ver_llamados', 'crear_llamados', 'editar_llamados',
                'ver_horarios'
            ],
            'usuario' => [
                'ver_estudiantes', 'ver_profesores', 'ver_cursos'
            ]
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Limpiar todo el cache de autenticación
     */
    public function clearAuthCache(): void
    {
        $this->cacheService->invalidatePattern("user:*");
        $this->cacheService->invalidatePattern("permissions:*");
        $this->cacheService->invalidatePattern("session:*");
        $this->cacheService->invalidatePattern("login_attempts:*");
    }

    /**
     * Obtener estadísticas del cache de autenticación
     */
    public function getAuthCacheStats(): array
    {
        $stats = $this->cacheService->getStats();
        
        return [
            'total_cache_entries' => $stats['total_entries'],
            'memory_entries' => $stats['memory_entries'],
            'hit_ratio' => $stats['hit_ratio'],
            'auth_specific_entries' => $this->countAuthEntries()
        ];
    }

    /**
     * Contar entradas específicas de autenticación
     */
    private function countAuthEntries(): int
    {
        $patterns = ['user:*', 'permissions:*', 'session:*', 'login_attempts:*'];
        $count = 0;
        
        foreach ($patterns as $pattern) {
            $count += $this->cacheService->invalidatePattern($pattern);
        }
        
        return $count;
    }
}
