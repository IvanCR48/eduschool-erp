<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Services\BaseService;

/**
 * Clase base para todos los mappers
 * 
 * Proporciona funcionalidad común para evitar duplicación de código
 */
abstract class BaseMapper extends BaseService
{
    /**
     * Mapear array de filas a array de objetos
     */
    protected function mapRowsToObjects(array $rows, string $methodName): array
    {
        return array_map([$this, $methodName], $rows);
    }

    /**
     * Validar que un ID sea válido
     */
    protected function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID debe ser un número positivo');
        }
    }

    /**
     * Construir condición WHERE dinámica
     */
    protected function buildWhereClause(array $criteria): array
    {
        $conditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                if (is_array($value)) {
                    // Para arrays, usar IN clause
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $conditions[] = "{$field} IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $conditions[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
        }
        
        return [
            'conditions' => $conditions,
            'params' => $params
        ];
    }

    /**
     * Construir ORDER BY dinámico
     */
    protected function buildOrderClause(array $orderBy = []): string
    {
        if (empty($orderBy)) {
            return '';
        }
        
        $orderParts = [];
        foreach ($orderBy as $field => $direction) {
            $direction = strtoupper($direction);
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'ASC';
            }
            
            // Sanitizar nombre de campo para prevenir inyección SQL
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
            if (!empty($field)) {
                $orderParts[] = "{$field} {$direction}";
            }
        }
        
        return empty($orderParts) ? '' : ' ORDER BY ' . implode(', ', $orderParts);
    }

    /**
     * Construir LIMIT dinámico
     */
    protected function buildLimitClause(?int $limit = null, ?int $offset = null): string
    {
        if ($limit === null) {
            return '';
        }
        
        $limit = max(1, (int) $limit);
        $clause = " LIMIT {$limit}";
        
        if ($offset !== null) {
            $offset = max(0, (int) $offset);
            $clause .= " OFFSET {$offset}";
        }
        
        return $clause;
    }

    /**
     * Ejecutar consulta COUNT con criterios
     */
    protected function countByCriteria(string $table, array $criteria = []): int
    {
        $whereClause = $this->buildWhereClause($criteria);
        
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($whereClause['conditions'])) {
            $sql .= " WHERE " . implode(' AND ', $whereClause['conditions']);
        }
        
        $result = $this->database->fetch($sql, $whereClause['params']);
        return (int) $result['count'];
    }

    /**
     * Ejecutar consulta SELECT con criterios dinámicos
     */
    protected function findByCriteria(string $table, array $criteria = [], array $options = []): array
    {
        $whereClause = $this->buildWhereClause($criteria);
        
        $sql = "SELECT * FROM {$table}";
        if (!empty($whereClause['conditions'])) {
            $sql .= " WHERE " . implode(' AND ', $whereClause['conditions']);
        }
        
        // Agregar ORDER BY si se especifica
        if (isset($options['orderBy'])) {
            $sql .= $this->buildOrderClause($options['orderBy']);
        }
        
        // Agregar LIMIT si se especifica
        if (isset($options['limit']) || isset($options['offset'])) {
            $sql .= $this->buildLimitClause($options['limit'] ?? null, $options['offset'] ?? null);
        }
        
        return $this->database->fetchAll($sql, $whereClause['params']);
    }

    /**
     * Validar campos requeridos en un array
     */
    protected function validateRequiredFields(array $data, array $requiredFields): void
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Faltan campos requeridos: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Sanitizar nombre de tabla para prevenir inyección SQL
     */
    protected function sanitizeTableName(string $table): string
    {
        $utilityService = new \SistemaAdmin\Services\UtilityService($this->database);
        return $utilityService->sanitizeTableName($table);
    }

    /**
     * Sanitizar nombre de campo para prevenir inyección SQL
     */
    protected function sanitizeFieldName(string $field): string
    {
        $utilityService = new \SistemaAdmin\Services\UtilityService($this->database);
        return $utilityService->sanitizeFieldName($field);
    }
}
