<?php

declare(strict_types=1);

/**
 * Sesión del portal de familias (sin usuario del sistema administrativo).
 */

if (!function_exists('familia_portal_session_key')) {
    function familia_portal_session_key(): string
    {
        return 'portal_familia';
    }
}

if (!function_exists('familia_portal_normalizar_dni')) {
    function familia_portal_normalizar_dni(string $dni): string
    {
        return preg_replace('/\D/', '', $dni);
    }
}

if (!function_exists('familia_portal_sesion_activa')) {
    function familia_portal_sesion_activa(): bool
    {
        $s = $_SESSION[familia_portal_session_key()] ?? null;
        return is_array($s)
            && !empty($s['estudiante_ids'])
            && is_array($s['estudiante_ids']);
    }
}

if (!function_exists('familia_portal_puede_ver_estudiante')) {
    function familia_portal_puede_ver_estudiante(int $estudianteId): bool
    {
        if (!familia_portal_sesion_activa()) {
            return false;
        }
        $ids = $_SESSION[familia_portal_session_key()]['estudiante_ids'];

        return in_array($estudianteId, array_map('intval', $ids), true);
    }
}

if (!function_exists('familia_portal_establecer_sesion')) {
    /**
     * @param list<int> $estudianteIds
     */
    function familia_portal_establecer_sesion(string $dniNormalizado, array $estudianteIds): void
    {
        $limpios = array_values(array_unique(array_filter(array_map(
            static fn ($id) => (int) $id,
            $estudianteIds
        ), static fn (int $id) => $id > 0)));

        $_SESSION[familia_portal_session_key()] = [
            'dni' => $dniNormalizado,
            'estudiante_ids' => $limpios,
            'inicio' => time(),
        ];
    }
}

if (!function_exists('familia_portal_cerrar')) {
    function familia_portal_cerrar(): void
    {
        unset($_SESSION[familia_portal_session_key()]);
    }
}
