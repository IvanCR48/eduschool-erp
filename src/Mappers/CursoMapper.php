<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Curso;
use SistemaAdmin\Mappers\BaseMapper;

/**
 * Implementación concreta del CursoMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de cursos.
 */
class CursoMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Curso
    {
        $sql = "SELECT * FROM cursos WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToCurso($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM cursos ORDER BY anio, division";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM cursos WHERE activo = 1 ORDER BY anio, division";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function findByAnio(int $anio): array
    {
        $sql = "SELECT * FROM cursos WHERE anio = ? AND activo = 1 ORDER BY division";
        $rows = $this->database->fetchAll($sql, [$anio]);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function findByEspecialidad(int $especialidadId): array
    {
        $sql = "SELECT * FROM cursos WHERE especialidad_id = ? AND activo = 1 ORDER BY anio, division";
        $rows = $this->database->fetchAll($sql, [$especialidadId]);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function findByTurno(int $turnoId): array
    {
        $sql = "SELECT * FROM cursos WHERE turno_id = ? AND activo = 1 ORDER BY anio, division";
        $rows = $this->database->fetchAll($sql, [$turnoId]);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
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
        
        $sql = "SELECT * FROM cursos";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY anio, division";
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function save(Curso $curso): Curso
    {
        $sql = "INSERT INTO cursos (anio, division, especialidad_id, turno_id, activo) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $curso->getAnio(),
            $curso->getDivision(),
            $curso->getEspecialidadId(),
            $curso->getTurnoId(),
            $curso->esActivo() ? 1 : 0
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $curso->setId($id);
        return $curso;
    }

    public function update(Curso $curso): bool
    {
        $sql = "UPDATE cursos SET anio = ?, division = ?, especialidad_id = ?, turno_id = ?, activo = ? WHERE id = ?";
        
        $params = [
            $curso->getAnio(),
            $curso->getDivision(),
            $curso->getEspecialidadId(),
            $curso->getTurnoId(),
            $curso->esActivo() ? 1 : 0,
            $curso->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        // Soft delete - marcamos como inactivo
        $sql = "UPDATE cursos SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function existsCurso(int $anio, string $division, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM cursos WHERE anio = ? AND division = ?";
        $params = [$anio, $division];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->database->fetch($sql, $params);
        return $result['count'] > 0;
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
        
        $sql = "SELECT COUNT(*) as count FROM cursos";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function getCicloBasico(): array
    {
        $sql = "SELECT * FROM cursos WHERE anio >= 1 AND anio<= 3 AND activo = 1 ORDER BY anio, division";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function getCicloSuperior(): array
    {
        $sql = "SELECT * FROM cursos WHERE anio >= 4 AND anio<= 7 AND activo = 1 ORDER BY anio, division";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToCurso'], $rows);
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_cursos,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as cursos_activos,
                    SUM(CASE WHEN anio >= 1 AND anio <= 3 THEN 1 ELSE 0 END) as ciclo_basico,
                    SUM(CASE WHEN anio >= 4 AND anio <= 7 THEN 1 ELSE 0 END) as ciclo_superior
                FROM cursos";
        
        $result = $this->database->fetch($sql);
        
        return [
            'total_cursos' => (int) $result['total_cursos'],
            'cursos_activos' => (int) $result['cursos_activos'],
            'ciclo_basico' => (int) $result['ciclo_basico'],
            'ciclo_superior' => (int) $result['ciclo_superior']
        ];
    }

    /**
     * Convierte una fila de la base de datos en un objeto Curso
     */
    private function mapRowToCurso(array $row): Curso
    {
        $curso = new Curso(
            (int) $row['anio'],
            $row['division'],
            $row['especialidad_id'],
            $row['turno_id'],
            (bool) $row['activo']
        );
        
        // Establecer el ID
        $curso->setId((int) $row['id']);
        
        return $curso;
    }
}
