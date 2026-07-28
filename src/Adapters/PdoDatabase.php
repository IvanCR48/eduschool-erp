<?php

namespace SistemaAdmin\Adapters;

use PDO;
use PDOException;

/**
 * Conexión PDO sin singleton; expone la misma API mínima que Database (legacy) para usar con DatabaseAdapter.
 */
class PdoDatabase
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function createFromEnv(): self
    {
        require_once __DIR__ . '/../EnvLoader.php';
        \EnvLoader::load();

        $host = \EnvLoader::get('DB_HOST', 'localhost');
        $port = \EnvLoader::get('DB_PORT', '3306');
        $dbname = \EnvLoader::get('DB_NAME');
        $username = \EnvLoader::get('DB_USER');
        $password = \EnvLoader::get('DB_PASS');

        if ($dbname === null || $dbname === '' || $username === null || $username === '' || $password === null) {
            throw new \RuntimeException('Configuración de base de datos incompleta en .env (DB_NAME, DB_USER, DB_PASS)');
        }

        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 30,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                ]
            );
        } catch (PDOException $e) {
            throw new \Exception('Connection failed: ' . $e->getMessage());
        }

        return new self($pdo);
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if ($sql === '') {
            throw new \InvalidArgumentException('SQL query debe ser una cadena no vacía');
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \RuntimeException('Error ejecutando consulta SQL: ' . $e->getMessage());
        }
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        $rows = $stmt->fetchAll();
        return $rows === false ? [] : $rows;
    }

    /**
     * @return string|int
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
