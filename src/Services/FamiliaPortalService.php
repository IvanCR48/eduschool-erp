<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Búsqueda de estudiantes visibles para el portal de familias por DNI del responsable.
 */
final class FamiliaPortalService
{
    public function __construct(
        private DatabaseInterface $database
    ) {
    }

    /**
     * Coincidencia por DNI del responsable:
     * - columna `estudiantes.dni_responsable` (alta / pestaña contacto “portal familias”);
     * - `responsables.dni` (padre/madre/tutor agregados desde la ficha).
     *
     * @return list<array{id: int, nombre: string, apellido: string}>
     */
    public function buscarEstudiantesPorDniResponsable(string $dniNormalizado): array
    {
        if ($dniNormalizado === '' || strlen($dniNormalizado) < 7 || strlen($dniNormalizado) > 8) {
            return [];
        }

        $idsVistos = [];
        $out = [];

        $rowsEst = $this->database->fetchAll(
            'SELECT id, nombre, apellido, dni_responsable FROM estudiantes
             WHERE activo = 1 AND dni_responsable IS NOT NULL AND TRIM(dni_responsable) <> \'\''
        );
        foreach ($rowsEst as $r) {
            $dr = preg_replace('/\D/', '', (string) ($r['dni_responsable'] ?? ''));
            if ($dr !== $dniNormalizado) {
                continue;
            }
            $id = (int) $r['id'];
            if (isset($idsVistos[$id])) {
                continue;
            }
            $idsVistos[$id] = true;
            $out[] = [
                'id' => $id,
                'nombre' => (string) ($r['nombre'] ?? ''),
                'apellido' => (string) ($r['apellido'] ?? ''),
            ];
        }

        $rowsResp = $this->database->fetchAll(
            'SELECT e.id, e.nombre, e.apellido, r.dni AS dni_responsable
             FROM responsables r
             INNER JOIN estudiantes e ON e.id = r.estudiante_id AND e.activo = 1
             WHERE r.dni IS NOT NULL AND TRIM(r.dni) <> \'\''
        );
        foreach ($rowsResp as $r) {
            $dr = preg_replace('/\D/', '', (string) ($r['dni_responsable'] ?? ''));
            if ($dr !== $dniNormalizado) {
                continue;
            }
            $id = (int) $r['id'];
            if (isset($idsVistos[$id])) {
                continue;
            }
            $idsVistos[$id] = true;
            $out[] = [
                'id' => $id,
                'nombre' => (string) ($r['nombre'] ?? ''),
                'apellido' => (string) ($r['apellido'] ?? ''),
            ];
        }

        return $out;
    }
}
