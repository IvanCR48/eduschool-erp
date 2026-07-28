<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Nota;
use SistemaAdmin\Mappers\BaseMapper;
use DateTime;

/**
 * Implementación concreta del NotaMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de calificaciones.
 */
class NotaMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Nota
    {
        $sql = "SELECT * FROM notas WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToNota($row);
    }

    public function findByEstudiante(int $estudianteId): array
    {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? ORDER BY creado_en DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId]);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    public function findByMateria(int $estudianteId, int $materiaId): array
    {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? AND materia_id = ? ORDER BY creado_en DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId, $materiaId]);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    public function findByBimestre(int $estudianteId, string $bimestre): array
    {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? AND bimestre = ? ORDER BY creado_en DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId, $bimestre]);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    /**
     * Filas crudas para el motor de estado (incluye evaluation_context, recovery_scope, school_year).
     *
     * @param list<int> $materiaIds
     * @return list<array<string, mixed>>
     */
    public function fetchNotasRowsPorEstudianteCicloMaterias(int $estudianteId, int $schoolYear, array $materiaIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v): int => (int) $v, $materiaIds),
            static fn (int $v): bool => $v > 0
        )));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, estudiante_id, materia_id, calificacion, bimestre, evaluation_context, recovery_scope, school_year, fecha, actualizado_en, creado_en
                FROM notas
                WHERE estudiante_id = ? AND school_year = ? AND materia_id IN ($placeholders)
                ORDER BY materia_id, id ASC";

        $params = array_merge([$estudianteId, $schoolYear], $ids);

        return $this->database->fetchAll($sql, $params);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM notas ORDER BY creado_en DESC";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    /**
     * Resuelve un profesor_id válido para INSERT en notas (FK → profesores).
     * Prioridad: docente de la materia en el curso del estudiante → cualquier docente de la materia → primer profesor del sistema.
     */
    public function resolverProfesorIdParaInsercionNota(int $estudianteId, int $materiaId): ?int
    {
        $est = $this->database->fetch(
            'SELECT curso_id FROM estudiantes WHERE id = ?',
            [$estudianteId]
        );
        $cursoId = isset($est['curso_id']) ? (int) $est['curso_id'] : 0;

        if ($cursoId > 0) {
            $pm = $this->database->fetch(
                'SELECT profesor_id FROM profesor_materia WHERE materia_id = ? AND curso_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1',
                [$materiaId, $cursoId]
            );
            if ($pm !== null && (int) $pm['profesor_id'] > 0) {
                return (int) $pm['profesor_id'];
            }
        }

        $pmAny = $this->database->fetch(
            'SELECT profesor_id FROM profesor_materia WHERE materia_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1',
            [$materiaId]
        );
        if ($pmAny !== null && (int) $pmAny['profesor_id'] > 0) {
            return (int) $pmAny['profesor_id'];
        }

        $first = $this->database->fetch('SELECT id FROM profesores ORDER BY id ASC LIMIT 1');
        if ($first !== null && (int) $first['id'] > 0) {
            return (int) $first['id'];
        }

        return null;
    }

    public function save(Nota $nota): Nota
    {
        $sql = "INSERT INTO notas (estudiante_id, materia_id, profesor_id, calificacion, bimestre, tipo_evaluacion, fecha, observaciones) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $profesorId = $this->resolverProfesorIdParaInsercionNota(
            $nota->getEstudianteId(),
            $nota->getMateriaId()
        );

        $params = [
            $nota->getEstudianteId(),
            $nota->getMateriaId(),
            $profesorId,
            $nota->getValor(),
            $nota->getBimestre(),
            'parcial', // tipo_evaluacion por defecto
            date('Y-m-d'), // fecha actual
            $nota->getObservaciones()
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $nota->setId($id);
        return $nota;
    }

    /**
     * Guarda una calificación de intensificación / recuperación (bimestre = 0 por convención).
     * Si ya existe una fila para la misma combinación (estudiante, materia, contexto, alcance, ciclo)
     * actualiza calificación, observaciones y fecha; de lo contrario inserta una nueva fila.
     *
     * @param 'intensification_first_semester'|'intensification_december'|'intensification_february_march' $evaluationContext
     * @param 'first_semester'|'second_semester'|'both' $recoveryScope
     * @return int id de la fila creada o actualizada
     */
    public function insertNotaIntensificacion(
        int $estudianteId,
        int $materiaId,
        float $calificacion,
        string $evaluationContext,
        string $recoveryScope,
        int $schoolYear,
        ?string $observaciones = null
    ): int {
        $existente = $this->database->fetch(<<<'SQL'
            SELECT id FROM notas
            WHERE estudiante_id = ? AND materia_id = ?
              AND evaluation_context = ? AND recovery_scope = ? AND school_year = ?
            LIMIT 1
            SQL,
            [$estudianteId, $materiaId, $evaluationContext, $recoveryScope, $schoolYear]
        );

        if ($existente !== null) {
            $id = (int) $existente['id'];
            $this->database->query(
                'UPDATE notas SET calificacion = ?, observaciones = ?, fecha = CURDATE() WHERE id = ?',
                [$calificacion, $observaciones, $id]
            );

            return $id;
        }

        $profesorId = $this->resolverProfesorIdParaInsercionNota($estudianteId, $materiaId);
        $this->database->query(<<<'SQL'
            INSERT INTO notas (
                estudiante_id, materia_id, profesor_id, calificacion, bimestre,
                tipo_evaluacion, fecha, observaciones,
                evaluation_context, recovery_scope, school_year
            ) VALUES (?, ?, ?, ?, 0, 'examen', CURDATE(), ?, ?, ?, ?)
            SQL,
            [
                $estudianteId,
                $materiaId,
                $profesorId,
                $calificacion,
                $observaciones,
                $evaluationContext,
                $recoveryScope,
                $schoolYear,
            ]
        );

        return (int) $this->database->lastInsertId();
    }

    public function update(Nota $nota): bool
    {
        $sql = "UPDATE notas SET calificacion = ?, observaciones = ? WHERE id = ?";
        
        $params = [
            $nota->getValor(),
            $nota->getObservaciones(),
            $nota->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM notas WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function getPromedioMateria(int $estudianteId, int $materiaId): float
    {
        $sql = "SELECT AVG(calificacion) as promedio FROM notas WHERE estudiante_id = ? AND materia_id = ?";
        $result = $this->database->fetch($sql, [$estudianteId, $materiaId]);
        
        return $result['promedio'] ? (float) $result['promedio'] : 0.0;
    }

    public function getPromedioGeneral(int $estudianteId): float
    {
        $sql = "SELECT AVG(calificacion) as promedio FROM notas WHERE estudiante_id = ?";
        $result = $this->database->fetch($sql, [$estudianteId]);
        
        return $result['promedio'] ? (float) $result['promedio'] : 0.0;
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
        
        $sql = "SELECT COUNT(*) as count FROM notas";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function getEstadisticas(?int $materiaId = null, ?string $bimestre = null): array
    {
        $whereConditions = [];
        $params = [];
        
        if ($materiaId !== null) {
            $whereConditions[] = "materia_id = ?";
            $params[] = $materiaId;
        }
        
        if ($bimestre !== null) {
            $whereConditions[] = "bimestre = ?";
            $params[] = $bimestre;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_notas,
                    AVG(calificacion) as promedio_general,
                    MIN(calificacion) as nota_minima,
                    MAX(calificacion) as nota_maxima,
                    SUM(CASE WHEN calificacion >= 6 THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN calificacion< 6 THEN 1 ELSE 0 END) as desaprobados
                FROM notas";
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        
        return [
            'total_notas' => (int) $result['total_notas'],
            'promedio_general' => $result['promedio_general'] ? (float) $result['promedio_general'] : 0.0,
            'nota_minima' => $result['nota_minima'] ? (float) $result['nota_minima'] : 0.0,
            'nota_maxima' => $result['nota_maxima'] ? (float) $result['nota_maxima'] : 0.0,
            'aprobados' => (int) $result['aprobados'],
            'desaprobados' => (int) $result['desaprobados']
        ];
    }

    /**
     * Convierte una fila de la base de datos en un objeto Nota
     */
    private function mapRowToNota(array $row): Nota
    {
        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $row['creado_en']);
        
        $bimestreRaw = $row['bimestre'] ?? 0;

        $nota = new Nota(
            (int) $row['estudiante_id'],
            (int) $row['materia_id'],
            (float) $row['calificacion'],
            (string) $bimestreRaw,
            $row['observaciones'] ?? '',
            $fecha
        );
        
        // Establecer el ID
        $nota->setId((int) $row['id']);
        
        return $nota;
    }
}
