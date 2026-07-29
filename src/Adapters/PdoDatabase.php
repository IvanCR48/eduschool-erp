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
        $dbname   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: \EnvLoader::get('DB_NAME', 'railway'));
        $username = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: \EnvLoader::get('DB_USER', 'root'));
        $password = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: \EnvLoader::get('DB_PASS', 'QfSCTskshvUOOGrqlbjHpowxhahCoBxW'));
        $envHost  = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: \EnvLoader::get('DB_HOST', ''));
        $envPort  = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: \EnvLoader::get('DB_PORT', ''));

        $candidates = [
            ['host' => 'mysql.railway.internal', 'port' => 3306, 'user' => $username, 'pass' => $password],
            ['host' => 'sakura.proxy.rlwy.net', 'port' => 48834, 'user' => 'root', 'pass' => 'QfSCTskshvUOOGrqlbjHpowxhahCoBxW'],
        ];

        if ($envHost !== '' && $envHost !== 'mysql.railway.internal' && $envHost !== 'sakura.proxy.rlwy.net') {
            array_unshift($candidates, ['host' => $envHost, 'port' => $envPort ?: 3306, 'user' => $username, 'pass' => $password]);
        }

        $lastError = null;
        foreach ($candidates as $c) {
            try {
                $pdo = new PDO(
                    "mysql:host={$c['host']};port={$c['port']};dbname=$dbname;charset=utf8mb4",
                    $c['user'],
                    $c['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::ATTR_PERSISTENT => false,
                    ]
                );
                $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
                return new self($pdo);
            } catch (\PDOException $e) {
                $lastError = $e;
            }
        }

        throw new \Exception('Connection failed: ' . ($lastError ? $lastError->getMessage() : 'Unknown PDO error'));

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
