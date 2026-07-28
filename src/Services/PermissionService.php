<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * RBAC: permisos por rol. La fuente de verdad es la BD (`rbac_permissions`, `rbac_role_permissions`)
 * si está instalada; si no, se usa la matriz embebida (misma semántica que el sistema histórico).
 */
class PermissionService extends BaseService
{
    private SessionService $sessionService;

    private RbacRepository $rbacRepository;

    /** @var array<string, true> */
    private array $cachedSlugsFlip = [];

    private ?string $cachedRole = null;

    public function __construct(DatabaseInterface $database, SessionService $sessionService)
    {
        parent::__construct($database);
        $this->sessionService = $sessionService;
        $this->rbacRepository = new RbacRepository($database);
    }

    /**
     * Invalidar caché por request (p. ej. tras cambiar rol en sesión en el mismo request).
     */
    public function invalidarCachePermisos(): void
    {
        $this->cachedSlugsFlip = [];
        $this->cachedRole = null;
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function tienePermiso(string $permiso): bool
    {
        if ($permiso === '' || !is_string($permiso)) {
            $this->logEvent('WARNING', 'Intento de verificación de permiso inválido', [
                'permiso' => $permiso,
                'ip' => $this->obtenerIPCliente(),
            ]);

            return false;
        }

        $rol = $this->sessionService->obtenerRol();
        if (!$rol) {
            return false;
        }

        if ($rol === 'admin') {
            return true;
        }

        $this->ensureCacheForRole($rol);

        return isset($this->cachedSlugsFlip[$permiso]);
    }

    /**
     * Verificar si el usuario tiene alguno de los permisos especificados
     */
    public function tieneAlgunPermiso(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if ($this->tienePermiso((string) $permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el usuario tiene todos los permisos especificados
     */
    public function tieneTodosLosPermisos(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (!$this->tienePermiso((string) $permiso)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener todos los permisos del usuario actual
     *
     * @return list<string>
     */
    public function obtenerPermisosUsuario(): array
    {
        $rol = $this->sessionService->obtenerRol();
        if (!$rol) {
            return [];
        }

        if ($rol === 'admin') {
            return $this->obtenerTodosLosPermisosDesdeFuente();
        }

        $this->ensureCacheForRole($rol);

        return array_keys($this->cachedSlugsFlip);
    }

    /**
     * Verificar si el usuario puede acceder a una entidad específica
     */
    public function puedeAccederA(string $entidad, string $accion = 'ver'): bool
    {
        if ($entidad === '' || !is_string($entidad) || $accion === '' || !is_string($accion)) {
            $this->logEvent('WARNING', 'Intento de verificación de acceso con parámetros inválidos', [
                'entidad' => $entidad,
                'accion' => $accion,
                'ip' => $this->obtenerIPCliente(),
            ]);

            return false;
        }

        $permiso = $this->generarPermiso($entidad, $accion);

        return $this->tienePermiso($permiso);
    }

    public function puedeModificar(string $entidad): bool
    {
        if ($entidad === '' || !is_string($entidad)) {
            $this->logEvent('WARNING', 'Intento de verificación de modificación con entidad inválida', [
                'entidad' => $entidad,
                'ip' => $this->obtenerIPCliente(),
            ]);

            return false;
        }

        return $this->puedeAccederA($entidad, 'modificar');
    }

    public function puedeEliminar(string $entidad): bool
    {
        return $this->puedeAccederA($entidad, 'eliminar');
    }

    public function puedeCrear(string $entidad): bool
    {
        if ($entidad === '' || !is_string($entidad)) {
            $this->logEvent('WARNING', 'Intento de verificación de creación con entidad inválida', [
                'entidad' => $entidad,
                'ip' => $this->obtenerIPCliente(),
            ]);

            return false;
        }

        return $this->puedeAccederA($entidad, 'crear');
    }

    public function puedeAccederARecurso(string $recurso): bool
    {
        if ($recurso === '' || !is_string($recurso)) {
            $this->logEvent('WARNING', 'Intento de acceso a recurso inválido', [
                'recurso' => $recurso,
                'ip' => $this->obtenerIPCliente(),
            ]);

            return false;
        }

        $recurso = preg_replace('/[^a-zA-Z0-9_]/', '', $recurso) ?? '';

        $permisosRecurso = [
            'estudiantes' => 'ver_estudiantes',
            'profesores' => 'ver_profesores',
            'cursos' => 'ver_cursos',
            'materias' => 'ver_materias',
            'especialidades' => 'ver_especialidades',
            'horarios' => 'ver_horarios',
            'llamados' => 'ver_llamados',
            'notas' => 'ver_notas',
            'equipo' => 'ver_equipo',
            'reportes' => 'ver_reportes',
            'asistencia' => 'ver_asistencia',
        ];

        $permiso = $permisosRecurso[$recurso] ?? null;

        return $permiso !== null && $this->tienePermiso($permiso);
    }

    /**
     * @return list<string>
     */
    public function obtenerRolesDisponibles(): array
    {
        return ['admin', 'directivo', 'profesor', 'preceptor', 'secretario'];
    }

    public function esRolValido(string $rol): bool
    {
        if ($rol === '' || !is_string($rol)) {
            $this->logEvent('WARNING', 'Intento de validación de rol inválido', [
                'rol' => $rol,
                'ip' => $this->obtenerIPCliente(),
            ]);

            return false;
        }

        $rol = preg_replace('/[^a-zA-Z0-9_]/', '', $rol) ?? '';

        return in_array($rol, $this->obtenerRolesDisponibles(), true);
    }

    private function generarPermiso(string $entidad, string $accion): string
    {
        return $accion . '_' . $entidad;
    }

    private function ensureCacheForRole(string $rol): void
    {
        if ($this->cachedRole === $rol && $this->cachedSlugsFlip !== []) {
            return;
        }

        $this->cachedRole = $rol;
        $slugs = [];
        if ($this->rbacRepository->isInstalled()) {
            $slugs = $this->rbacRepository->getSlugsForRole($rol);
        }
        if ($slugs === []) {
            $slugs = self::fallbackMatrix()[$rol] ?? [];
        }

        $this->cachedSlugsFlip = array_fill_keys($slugs, true);
    }

    /**
     * @return list<string>
     */
    private function obtenerTodosLosPermisosDesdeFuente(): array
    {
        if ($this->rbacRepository->isInstalled()) {
            $all = $this->rbacRepository->getAllSlugs();
            if ($all !== []) {
                return $all;
            }
        }

        $merged = [];
        foreach (self::fallbackMatrix() as $perms) {
            $merged = array_merge($merged, $perms);
        }

        return array_values(array_unique($merged));
    }

    /**
     * Matriz por defecto (misma que el sistema antes de RBAC en BD).
     *
     * @return array<string, list<string>>
     */
    private static function fallbackMatrix(): array
    {
        return [
            'directivo' => [
                'ver_estudiantes',
                'ver_profesores',
                'ver_cursos',
                'ver_materias',
                'ver_especialidades',
                'ver_horarios',
                'ver_llamados',
                'ver_notas',
                'ver_equipo',
                'modificar_estudiantes',
                'modificar_profesores',
                'modificar_cursos',
                'modificar_materias',
                'modificar_especialidades',
                'modificar_horarios',
                'modificar_llamados',
                'modificar_notas',
                'modificar_equipo',
                'crear_estudiantes',
                'crear_profesores',
                'crear_cursos',
                'crear_materias',
                'crear_especialidades',
                'crear_horarios',
                'crear_llamados',
                'crear_notas',
                'crear_equipo',
                'eliminar_estudiantes',
                'eliminar_profesores',
                'eliminar_cursos',
                'eliminar_materias',
                'eliminar_especialidades',
                'eliminar_horarios',
                'eliminar_llamados',
                'eliminar_notas',
                'eliminar_equipo',
                'gestionar_usuarios',
                'ver_reportes',
                'exportar_datos',
            ],
            'profesor' => [
                'ver_estudiantes',
                'ver_cursos',
                'ver_horarios',
                'ver_notas',
                'modificar_notas',
                'crear_notas',
                'ver_mis_cursos',
                'ver_mis_materias',
                'ver_mis_horarios',
            ],
            'preceptor' => [
                'ver_estudiantes',
                'ver_cursos',
                'ver_horarios',
                'ver_llamados',
                'modificar_estudiantes',
                'modificar_llamados',
                'crear_llamados',
                'ver_asistencia',
            ],
            'secretario' => [
                'ver_estudiantes',
                'ver_profesores',
                'ver_cursos',
                'ver_materias',
                'ver_especialidades',
                'modificar_estudiantes',
                'modificar_profesores',
                'crear_estudiantes',
                'crear_profesores',
                'ver_reportes',
                'exportar_datos',
            ],
        ];
    }
}
