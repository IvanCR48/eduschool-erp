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

        $host     = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: \EnvLoader::get('DB_HOST', 'localhost'));
        $port     = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: \EnvLoader::get('DB_PORT', '3306'));
        $dbname   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: \EnvLoader::get('DB_NAME', 'school_admin'));
        $username = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: \EnvLoader::get('DB_USER', 'root'));
        $password = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: \EnvLoader::get('DB_PASS', ''));

        if ($host === null || $host === '' || $host === 'localhost') {
            $host = 'sakura.proxy.rlwy.net';
            $port = '48834';
        }
        if (strpos($host, 'proxy.rlwy.net') !== false && (string)$port === '3306') {
            $port = '48834';
        } elseif (strpos($host, 'railway.internal') !== false && (string)$port !== '3306') {
            $port = '3306';
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
                    PDO::ATTR_TIMEOUT => 15,
                    PDO::ATTR_PERSISTENT => false,
                ]
            );
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\PDOException $e) {
            // Fallback retry using public proxy if internal host fails
            if ($host !== 'sakura.proxy.rlwy.net') {
                try {
                    $pdo = new PDO(
                        "mysql:host=sakura.proxy.rlwy.net;port=48834;dbname=$dbname;charset=utf8mb4",
                        $username,
                        'QfSCTskshvUOOGrqlbjHpowxhahCoBxW',
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_TIMEOUT => 15,
                            PDO::ATTR_PERSISTENT => false,
                        ]
                    );
                    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
                    return new self($pdo);
                } catch (\PDOException $e2) {
                    throw new \Exception('Connection failed: ' . $e->getMessage());
                }
            }
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
