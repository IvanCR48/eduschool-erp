<?php

declare(strict_types=1);

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Materia;

/**
 * Mapper alineado al esquema `materias`: activa, carga_horaria, anio_materia, es_taller.
 */
class MateriaMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Materia
    {
        $sql = 'SELECT * FROM materias WHERE id = ?';
        $row = $this->database->fetch($sql, [$id]);

        if ($row === null) {
            return null;
        }

        return $this->mapRowToMateria($row);
    }

    public function findByCodigo(string $codigo): ?Materia
    {
        $sql = 'SELECT * FROM materias WHERE codigo = ?';
        $row = $this->database->fetch($sql, [$codigo]);

        if ($row === null) {
            return null;
        }

        return $this->mapRowToMateria($row);
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM materias ORDER BY nombre';
        $rows = $this->database->fetchAll($sql);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findActive(): array
    {
        $sql = 'SELECT * FROM materias WHERE activa = 1 ORDER BY nombre';
        $rows = $this->database->fetchAll($sql);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findByEspecialidad(int $especialidadId): array
    {
        $sql = 'SELECT * FROM materias WHERE especialidad_id = ? AND activa = 1 ORDER BY nombre';
        $rows = $this->database->fetchAll($sql, [$especialidadId]);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findComunes(): array
    {
        $sql = 'SELECT * FROM materias WHERE especialidad_id IS NULL AND activa = 1 ORDER BY nombre';
        $rows = $this->database->fetchAll($sql);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findByNombre(string $termino): array
    {
        $sql = 'SELECT * FROM materias WHERE nombre LIKE ? AND activa = 1 ORDER BY nombre';
        $rows = $this->database->fetchAll($sql, ['%' . $termino . '%']);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findBy(array $criteria): array
    {
        $whereConditions = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }

        $sql = 'SELECT * FROM materias';
        if ($whereConditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        $sql .= ' ORDER BY nombre';

        $rows = $this->database->fetchAll($sql, $params);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    /**
     * Inserta fila alineada al esquema actual. Usa anio_materia = 1 y es_taller = 0 por defecto
     * (el alta desde subjects.php usa ServicioMaterias::crearMateriaConCursos con año derivado del curso).
     */
    public function save(Materia $materia): Materia
    {
        $sql = <<<'SQL'
            INSERT INTO materias (nombre, codigo, especialidad_id, anio_materia, carga_horaria, es_taller, activa)
            VALUES (?, ?, ?, 1, ?, 0, ?)
            SQL;

        $params = [
            $materia->getNombre(),
            $materia->getCodigo(),
            $materia->getEspecialidadId(),
            $materia->getHorasSemanales(),
            $materia->esActiva() ? 1 : 0,
        ];

        $this->database->query($sql, $params);
        $id = (int) $this->database->lastInsertId();
        $materia->setId($id);

        return $materia;
    }

    public function update(Materia $materia): bool
    {
        $id = $materia->getId();
        if ($id === null) {
            return false;
        }

        $sql = <<<'SQL'
            UPDATE materias
            SET nombre = ?, codigo = ?, especialidad_id = ?, carga_horaria = ?, activa = ?
            WHERE id = ?
            SQL;

        $params = [
            $materia->getNombre(),
            $materia->getCodigo(),
            $materia->getEspecialidadId(),
            $materia->getHorasSemanales(),
            $materia->esActiva() ? 1 : 0,
            $id,
        ];

        $stmt = $this->database->query($sql, $params);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = 'UPDATE materias SET activa = 0 WHERE id = ?';
        $stmt = $this->database->query($sql, [$id]);

        return $stmt->rowCount() > 0;
    }

    public function codigoExiste(string $codigo, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS count FROM materias WHERE codigo = ?';
        $params = [$codigo];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $result = $this->database->fetch($sql, $params);

        return isset($result['count']) && (int) $result['count'] > 0;
    }

    public function findByCargaHoraria(int $horasMinimas, int $horasMaximas): array
    {
        $sql = 'SELECT * FROM materias WHERE carga_horaria >= ? AND carga_horaria <= ? AND activa = 1 ORDER BY nombre';
        $rows = $this->database->fetchAll($sql, [$horasMinimas, $horasMaximas]);

        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function countBy(array $criteria): int
    {
        $whereConditions = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }

        $sql = 'SELECT COUNT(*) AS count FROM materias';
        if ($whereConditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }

        $result = $this->database->fetch($sql, $params);

        return (int) ($result['count'] ?? 0);
    }

    public function getEstadisticas(): array
    {
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS total_materias,
                SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) AS materias_activas,
                SUM(CASE WHEN especialidad_id IS NULL THEN 1 ELSE 0 END) AS materias_comunes,
                SUM(CASE WHEN especialidad_id IS NOT NULL THEN 1 ELSE 0 END) AS materias_especificas,
                AVG(carga_horaria) AS promedio_carga_horaria
            FROM materias
            SQL;

        $result = $this->database->fetch($sql);

        return [
            'total_materias' => (int) ($result['total_materias'] ?? 0),
            'materias_activas' => (int) ($result['materias_activas'] ?? 0),
            'materias_comunes' => (int) ($result['materias_comunes'] ?? 0),
            'materias_especificas' => (int) ($result['materias_especificas'] ?? 0),
            'promedio_horas_semanales' => isset($result['promedio_carga_horaria']) && $result['promedio_carga_horaria'] !== null
                ? (float) $result['promedio_carga_horaria']
                : 0.0,
        ];
    }

    private function mapRowToMateria(array $row): Materia
    {
        $carga = $row['carga_horaria'] ?? $row['horas_semanales'] ?? null;
        $carga = $carga !== null && $carga !== '' ? (int) $carga : null;
        $activa = (bool) (int) ($row['activa'] ?? $row['activo'] ?? 0);

        $materia = new Materia(
            (string) $row['nombre'],
            isset($row['codigo']) && $row['codigo'] !== null && $row['codigo'] !== '' ? (string) $row['codigo'] : null,
            $carga,
            isset($row['especialidad_id']) && $row['especialidad_id'] !== null && $row['especialidad_id'] !== ''
                ? (int) $row['especialidad_id']
                : null,
            $activa
        );
        $materia->setId((int) $row['id']);

        return $materia;
    }
}
