<?php

/**

 * Alcance de sesión para rol preceptor (cursos asignados en preceptor_curso / equipo_directivo).

 * Requiere ServicioAutenticacion::verificarSesion() (sincroniza preceptor_curso_ids).

 */



if (!function_exists('preceptor_curso_ids')) {

    function preceptor_curso_ids(): array

    {

        if (($_SESSION['rol'] ?? '') !== 'preceptor') {

            return [];

        }

        $v = $_SESSION['preceptor_curso_ids'] ?? null;

        if (is_array($v) && $v !== []) {

            return array_values(array_unique(array_map('intval', array_filter($v, static function ($x) {

                return $x !== null && $x !== '';

            }))));

        }

        $one = $_SESSION['preceptor_curso_id'] ?? null;

        if ($one !== null && $one !== '') {

            return [(int) $one];

        }



        return [];

    }

}



if (!function_exists('preceptor_curso_id')) {

    /** Primer curso asignado (compatibilidad con código que asume un solo curso). */

    function preceptor_curso_id(): ?int

    {

        $ids = preceptor_curso_ids();



        return $ids[0] ?? null;

    }

}



if (!function_exists('preceptor_curso_etiqueta')) {

    function preceptor_curso_etiqueta(): string

    {

        return (string) ($_SESSION['preceptor_curso_label'] ?? '');

    }

}



if (!function_exists('preceptor_permitido_curso')) {

    function preceptor_permitido_curso(int $cursoId): bool

    {

        return in_array($cursoId, preceptor_curso_ids(), true);

    }

}



if (!function_exists('puedeGestionarPreceptoresCurso')) {

    /** Director o vicedirector según cargo en equipo_directivo (sesión sincronizada en verificarSesion). */

    function puedeGestionarPreceptoresCurso(): bool

    {

        $c = strtolower(trim((string) ($_SESSION['equipo_directivo_cargo'] ?? '')));

        $permitidos = ['director', 'directora', 'vicedirector', 'vicedirectora'];



        return in_array($c, $permitidos, true);

    }

}



if (!function_exists('puedeAccesoPestanaPreceptor')) {

    /** Admin, rol directivo (director/vicedirector en usuarios) o cargo equivalente en equipo_directivo. */

    function puedeAccesoPestanaPreceptor(): bool

    {

        $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));

        if ($rol === 'admin') {

            return true;

        }

        // En staff.php director y vicedirector reciben rol «directivo»; no depende de equipo_directivo.usuario_id.

        if ($rol === 'directivo' || $rol === 'director' || $rol === 'vicedirector') {

            return true;

        }



        return puedeGestionarPreceptoresCurso();

    }

}


