<?php

declare(strict_types=1);

/**
 * Alcance de sesión para rol profesor (cursos y materias asignados en profesor_materia).
 * Requiere ServicioAutenticacion::verificarSesion() (sincroniza profesor_curso_ids, etc.).
 */

if (!function_exists('es_profesor')) {
    function es_profesor(): bool
    {
        return ($_SESSION['rol'] ?? '') === 'profesor';
    }
}

if (!function_exists('profesor_id_sesion')) {
    /** ID del registro en la tabla `profesores` para el usuario logueado. */
    function profesor_id_sesion(): ?int
    {
        if (!es_profesor()) {
            return null;
        }
        $v = $_SESSION['profesor_id'] ?? null;
        return ($v !== null && $v > 0) ? (int) $v : null;
    }
}

if (!function_exists('profesor_curso_ids')) {
    /**
     * Cursos asignados al profesor logueado (de profesor_materia activo).
     *
     * @return list<int>
     */
    function profesor_curso_ids(): array
    {
        if (!es_profesor()) {
            return [];
        }
        $v = $_SESSION['profesor_curso_ids'] ?? null;
        if (!is_array($v) || $v === []) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $v))));
    }
}

if (!function_exists('profesor_materia_ids_en_curso')) {
    /**
     * IDs de materias asignadas al profesor logueado en un curso específico.
     *
     * @return list<int>
     */
    function profesor_materia_ids_en_curso(int $cursoId): array
    {
        if (!es_profesor() || $cursoId < 1) {
            return [];
        }
        $mapa = $_SESSION['profesor_materias_por_curso'] ?? [];
        if (!is_array($mapa)) {
            return [];
        }
        $v = $mapa[$cursoId] ?? [];
        return is_array($v) ? array_values(array_unique(array_filter(array_map('intval', $v)))) : [];
    }
}

if (!function_exists('profesor_puede_ver_curso')) {
    function profesor_puede_ver_curso(int $cursoId): bool
    {
        return in_array($cursoId, profesor_curso_ids(), true);
    }
}

if (!function_exists('profesor_puede_editar_materia_en_curso')) {
    function profesor_puede_editar_materia_en_curso(int $cursoId, int $materiaId): bool
    {
        return in_array($materiaId, profesor_materia_ids_en_curso($cursoId), true);
    }
}
