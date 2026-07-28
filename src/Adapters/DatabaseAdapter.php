<?php

namespace SistemaAdmin\Adapters;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Adaptador para la clase Database existente
 * 
 * Implementa la interfaz DatabaseInterface para mantener compatibilidad
 * con el código existente mientras se mejora la arquitectura
 */
class DatabaseAdapter implements DatabaseInterface
{
    private $database;

    public function __construct($database)
    {
        // Validar que el objeto tenga los métodos necesarios
        if (!is_object($database) || !method_exists($database, 'query') || !method_exists($database, 'fetch')) {
            throw new \InvalidArgumentException('El objeto database debe implementar los métodos query y fetch');
        }
        
        $this->database = $database;
    }

    /**
     * Ejecutar una consulta preparada
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        return $this->database->query($sql, $params);
    }

    /**
     * Obtener una fila de resultado
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->database->fetch($sql, $params);
        return $result === false ? null : $result;
    }

    /**
     * Obtener todas las filas de resultado
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $result = $this->database->fetchAll($sql, $params);
        return $result === false ? [] : $result;
    }

    /**
     * Ejecutar una consulta y obtener el último ID insertado
     */
    public function insert(string $sql, array $params = []): int
    {
        $this->database->query($sql, $params);
        return $this->database->lastInsertId();
    }

    /**
     * Ejecutar una transacción
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Obtener la conexión PDO subyacente
     */
    public function getPdo(): \PDO
    {
        // Si la clase Database tiene un método getPdo, usarlo
        if (method_exists($this->database, 'getPdo')) {
            $pdo = $this->database->getPdo();
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException('Conexión PDO no válida');
            }
            return $pdo;
        }
        
        // Si la clase Database tiene un método getConnection, usarlo
        if (method_exists($this->database, 'getConnection')) {
            $pdo = $this->database->getConnection();
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException('Conexión PDO no válida');
            }
            return $pdo;
        }
        
        // Si tiene una propiedad pdo, acceder a ella
        if (property_exists($this->database, 'pdo')) {
            $pdo = $this->database->pdo;
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException('Conexión PDO no válida');
            }
            return $pdo;
        }
        
        // Si tiene una propiedad connection, acceder a ella
        if (property_exists($this->database, 'connection')) {
            $pdo = $this->database->connection;
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException('Conexión PDO no válida');
            }
            return $pdo;
        }
        
        // Como último recurso, intentar obtener la conexión PDO
        // Esto puede necesitar ajustes según la implementación específica
        throw new \RuntimeException('No se puede obtener la conexión PDO');
    }

    /**
     * Alias para getPdo()
     */
    public function getConnection(): \PDO
    {
        return $this->getPdo();
    }

    /**
     * Verificar si hay una transacción activa
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * Iniciar una transacción
     */
    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            // Ya hay una transacción activa, no iniciar otra
            return true;
        }
        return $this->getPdo()->beginTransaction();
    }

    /**
     * Confirmar una transacción
     */
    public function commit(): bool
    {
        if (!$this->inTransaction()) {
            // No hay transacción activa
            return false;
        }
        
        try {
            return $this->getPdo()->commit();
        } catch (\PDOException $e) {
            $this->logEvent('ERROR', 'Error en commit de transacción', [
                'error' => $e->getMessage(),
                'ip' => $this->obtenerIPCliente()
            ]);
            return false;
        }
    }

    /**
     * Revertir una transacción
     */
    public function rollback(): bool
    {
        if (!$this->inTransaction()) {
            // No hay transacción activa
            return false;
        }
        
        try {
            return $this->getPdo()->rollback();
        } catch (\PDOException $e) {
            $this->logEvent('ERROR', 'Error en rollback de transacción', [
                'error' => $e->getMessage(),
                'ip' => $this->obtenerIPCliente()
            ]);
            return false;
        }
    }

    /**
     * Obtener el último ID insertado
     */
    public function lastInsertId(): int
    {
        try {
            return $this->database->lastInsertId();
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error obteniendo último ID insertado', [
                'error' => $e->getMessage(),
                'ip' => $this->obtenerIPCliente()
            ]);
            throw new \RuntimeException('Error obteniendo último ID insertado: ' . $e->getMessage());
        }
    }

    /**
     * Obtener la instancia original de Database
     * 
     * @return object Instancia de la clase Database original
     * @throws \RuntimeException Si la instancia no está disponible
     */
    public function getOriginalDatabase(): object
    {
        if (!is_object($this->database)) {
            throw new \RuntimeException('La instancia de Database no está disponible');
        }
        
        return $this->database;
    }
}
