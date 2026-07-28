<?php

declare(strict_types=1);

/**
 * Filas del boletín seguras para pintar en tabla (evita notices y datos rotos).
 *
 * @param mixed $notas
 * @return array<int|string, array<string, mixed>>
 */
function ficha_estudiante_filas_boletin_normalizadas($notas): array
{
    if (!is_array($notas)) {
        return [];
    }
    $out = [];
    foreach ($notas as $materiaId => $datos) {
        if (!is_array($datos)) {
            continue;
        }
        $nombre = $datos['materia']['nombre'] ?? null;
        if (!is_string($nombre) || $nombre === '') {
            continue;
        }
        if (!isset($datos['materia']) || !is_array($datos['materia'])) {
            $datos['materia'] = ['nombre' => $nombre];
        }
        $av = $datos['avances'] ?? null;
        if (!is_array($av)) {
            $av = [];
        }
        $a1 = $av['avance1'] ?? [];
        $a2 = $av['avance2'] ?? [];
        $datos['avances'] = [
            'avance1' => is_array($a1) ? $a1 : [],
            'avance2' => is_array($a2) ? $a2 : [],
        ];
        $tr = $datos['cuatrimestres'] ?? null;
        $datos['cuatrimestres'] = is_array($tr) ? $tr : [];
        $datos['promedio_calculado'] = !empty($datos['promedio_calculado']);
        $datos['promedio'] = isset($datos['promedio']) ? (float) $datos['promedio'] : 0.0;

        $out[$materiaId] = $datos;
    }

    return $out;
}
