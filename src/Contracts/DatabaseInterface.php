<?php

namespace SistemaAdmin\Contracts;

// Verificar versión mínima de PHP
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('Este sistema requiere PHP 8.0 o superior. Versión actual: ' . PHP_VERSION);
}

/**
 * Interface para el manejo de base de datos
 * 
 * Define el contrato que debe cumplir cualquier implementación de base de datos
 * para mantener consistencia en toda la aplicación
 */
interface DatabaseInterface
{
    /**
     * Ejecutar una consulta preparada
     * 
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para los placeholders
     * @return \PDOStatement Resultado de la consulta
     * @throws \PDOException Si hay error en la consulta
     */
    public function query(string $sql, array $params = []): \PDOStatement;

    /**
     * Obtener una fila de resultado
     * 
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para los placeholders
     * @return array|null Fila de resultado o null si no hay datos
     * @throws \PDOException Si hay error en la consulta
     */
    public function fetch(string $sql, array $params = []): ?array;

    /**
     * Obtener todas las filas de resultado
     * 
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para los placeholders
     * @return array Lista de filas de resultado
     * @throws \PDOException Si hay error en la consulta
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Ejecutar una consulta y obtener el último ID insertado
     * 
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para los placeholders
     * @return int ID del último registro insertado
     * @throws \PDOException Si hay error en la consulta
     */
    public function insert(string $sql, array $params = []): int;

    /**
     * Ejecutar una transacción
     * 
     * @param callable $callback Función a ejecutar dentro de la transacción
     * @return mixed Resultado de la función callback
     * @throws \Exception Si hay error en la transacción
     */
    public function transaction(callable $callback);

    /**
     * Obtener la conexión PDO subyacente
     * 
     * @return \PDO Conexión PDO
     */
    public function getPdo(): \PDO;

    /**
     * Verificar si hay una transacción activa
     * 
     * @return bool True si hay transacción activa
     */
    public function inTransaction(): bool;

    /**
     * Iniciar una transacción
     * 
     * @return bool True si se inició correctamente
     */
    public function beginTransaction(): bool;

    /**
     * Confirmar una transacción
     * 
     * @return bool True si se confirmó correctamente
     */
    public function commit(): bool;

    /**
     * Revertir una transacción
     * 
     * @return bool True si se revirtió correctamente
     */
    public function rollback(): bool;
}
