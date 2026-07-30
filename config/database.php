<?php
// Cargar variables de entorno
require_once __DIR__ . '/../src/EnvLoader.php';
EnvLoader::load();

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        @ini_set('default_socket_timeout', '2');
        // Datos de conexión MySQL con variables de entorno (con fallback a Railway)
        $host     = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: EnvLoader::get('DB_HOST', 'localhost'));
        $port     = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: EnvLoader::get('DB_PORT', '3306'));
        $dbname   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: EnvLoader::get('DB_NAME', 'school_admin'));
        $username = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: EnvLoader::get('DB_USER', 'root'));
        $password = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: EnvLoader::get('DB_PASS', ''));
        
        // Validar configuración crítica
        if ($dbname === null || $dbname === '' || $username === null || $username === '' || $password === null) {
            throw new Exception("Configuración de base de datos incompleta en .env (DB_NAME, DB_USER, DB_PASS)");
        }
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::ATTR_PERSISTENT => false,
                ]
            );
            $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        // Validar parámetros
        if (!is_string($sql) || empty($sql)) {
            throw new \InvalidArgumentException('SQL query debe ser una cadena no vacía');
        }
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Parámetros deben ser un array');
        }
        
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error ejecutando consulta SQL: ' . $e->getMessage());
        }
    }
    
    public function fetch($sql, $params = []) {
        // Validar parámetros
        if (!is_string($sql) || empty($sql)) {
            throw new \InvalidArgumentException('SQL query debe ser una cadena no vacía');
        }
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Parámetros deben ser un array');
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        // Validar parámetros
        if (!is_string($sql) || empty($sql)) {
            throw new \InvalidArgumentException('SQL query debe ser una cadena no vacía');
        }
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Parámetros deben ser un array');
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        if (!$this->connection) {
            throw new \RuntimeException('Conexión a base de datos no disponible para obtener último ID');
        }
        
        try {
            return $this->connection->lastInsertId();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error obteniendo último ID insertado: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        if (!$this->connection) {
            throw new \RuntimeException('Conexión a base de datos no disponible');
        }
        
        // Verificar si la conexión está activa
        try {
            $this->connection->query('SELECT 1');
        } catch (\PDOException $e) {
            throw new \RuntimeException('Conexión a base de datos inactiva: ' . $e->getMessage());
        }
        
        return $this->connection;
    }
}
?>
