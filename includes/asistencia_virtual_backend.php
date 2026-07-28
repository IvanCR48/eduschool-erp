<?php

declare(strict_types=1);

/**
 * asistencia_virtual_backend.php — thin wrapper de compatibilidad.
 *
 * Las funciones procedurales originales se mantienen para no romper
 * código que pudiera llamarlas. Internamente delegan al nuevo
 * ServicioAsistencia + AsistenciaMapper.
 *
 * Una vez que la vista usa AsistenciaController directamente, este
 * archivo puede eliminarse.
 */

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Mappers\AsistenciaMapper;
use SistemaAdmin\Services\ServicioAsistencia;

if (!function_exists('asistencia_virtual_ids_activos_curso')) {
    /**
     * Devuelve los IDs de estudiantes activos de un curso.
     *
     * @return list<int>
     */
    function asistencia_virtual_ids_activos_curso(DatabaseInterface $db, int $cursoId): array
    {
        $mapper = new AsistenciaMapper($db);
        return $mapper->idsActivosPorCurso($cursoId);
    }
}

if (!function_exists('asistencia_virtual_guardar_por_presentes')) {
    /**
     * API de compatibilidad: guarda asistencia a partir de la lista de IDs
     * marcados como presentes. El resto quedan como 'Ausente'.
     *
     * @param list<int> $idsPresentesPost
     */
    function asistencia_virtual_guardar_por_presentes(
        DatabaseInterface $db,
        int               $cursoId,
        string            $fechaYmd,
        array             $idsPresentesPost,
        int               $usuarioId
    ): void {
        $mapper   = new AsistenciaMapper($db);
        $servicio = new ServicioAsistencia($db, $mapper);
        $activos  = $mapper->idsActivosPorCurso($cursoId);

        $presenteSet = array_fill_keys(array_map('intval', $idsPresentesPost), true);

        /** @var array<int,string> $estados */
        $estados = [];
        foreach ($activos as $eid) {
            $estados[$eid] = isset($presenteSet[$eid]) ? 'Presente' : 'Ausente';
        }

        $servicio->guardar($cursoId, $fechaYmd, $estados, [], [], $activos, $usuarioId);
    }
}
