<?php

declare(strict_types=1);

/**
 * Permisos por rol en vistas PHP (sin depender de que header.php ya se haya incluido).
 */

if (!function_exists('hasRole')) {
    /**
     * @param string|string[] $roles Rol único o lista de roles permitidos
     */
    function hasRole($roles): bool
    {
        $userRole = $_SESSION['rol'] ?? '';
        if (is_array($roles)) {
            return in_array($userRole, $roles, true);
        }

        return $userRole === $roles;
    }
}

if (!function_exists('can')) {
    /**
     * RBAC granular: comprueba el slug de permiso (tablas rbac_* o matriz embebida).
     * Requiere sesión con usuario autenticado. Carga database_bootstrap si hace falta.
     */
    function can(string $permission): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }
        if (empty($_SESSION['usuario_id'] ?? null)) {
            return false;
        }
        if (!function_exists('sistema_admin_db_adapter')) {
            require_once __DIR__ . '/database_bootstrap.php';
        }

        static $permissionService = null;
        if ($permissionService === null) {
            $db = sistema_admin_db_adapter();
            $permissionService = new \SistemaAdmin\Services\PermissionService(
                $db,
                new \SistemaAdmin\Services\SessionService($db)
            );
        }

        return $permissionService->tienePermiso($permission);
    }
}

if (!function_exists('check_grade_correction_period')) {
    /**
     * Comprueba si el período de corrección de notas está abierto para un año lectivo.
     * Devuelve información de estado y fechas configuradas.
     *
     * @param \SistemaAdmin\Contracts\DatabaseInterface $db Adaptador de base de datos
     * @param int $schoolYear Año lectivo
     * @return array{is_open: bool, status: string, start_date: string|null, end_date: string|null, manual_enabled: int}
     */
    function check_grade_correction_period(\SistemaAdmin\Contracts\DatabaseInterface $db, int $schoolYear): array
    {
        try {
            $row = $db->fetch(
                'SELECT grade_correction_enabled, grade_correction_start_date, grade_correction_end_date 
                 FROM school_year_milestones 
                 WHERE school_year = ? 
                 LIMIT 1',
                [$schoolYear]
            );

            if ($row === null) {
                return [
                    'is_open' => false,
                    'status' => 'closed',
                    'start_date' => null,
                    'end_date' => null,
                    'manual_enabled' => 0
                ];
            }

            $manualEnabled = (int) ($row['grade_correction_enabled'] ?? 0);
            $start = $row['grade_correction_start_date'] ?? null;
            $end = $row['grade_correction_end_date'] ?? null;

            if ($manualEnabled === 1) {
                return [
                    'is_open' => true,
                    'status' => 'open_manual',
                    'start_date' => $start,
                    'end_date' => $end,
                    'manual_enabled' => 1
                ];
            }

            if ($start !== null && $end !== null && $start !== '' && $end !== '') {
                $today = date('Y-m-d');
                if ($today >= $start && $today <= $end) {
                    return [
                        'is_open' => true,
                        'status' => 'open_scheduled',
                        'start_date' => $start,
                        'end_date' => $end,
                        'manual_enabled' => 0
                    ];
                }
            }

            return [
                'is_open' => false,
                'status' => 'closed',
                'start_date' => $start,
                'end_date' => $end,
                'manual_enabled' => 0
            ];
        } catch (\Throwable $e) {
            return [
                'is_open' => false,
                'status' => 'closed',
                'start_date' => null,
                'end_date' => null,
                'manual_enabled' => 0
            ];
        }
    }
}

if (!function_exists('profesor_puede_modificar_notas')) {
    /**
     * Comprueba si el usuario logueado (si es docente) puede modificar notas.
     */
    function profesor_puede_modificar_notas(\SistemaAdmin\Contracts\DatabaseInterface $db, int $schoolYear): bool
    {
        if (!hasRole('profesor')) {
            return true;
        }

        $period = check_grade_correction_period($db, $schoolYear);
        return $period['is_open'];
    }
}
