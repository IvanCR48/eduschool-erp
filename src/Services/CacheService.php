<?php
namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use \Exception;

/**
 * Servicio de Cache para optimizar consultas frecuentes
 * Implementa cache en memoria y base de datos
 */
class CacheService extends BaseService
{
    private array $memoryCache = [];
    private string $cacheTable = 'cache_configuraciones';
    private int $defaultTtl = 3600; // 1 hora por defecto
    private int $maxMemoryCacheSize = 1000; // Máximo 1000 elementos en memoria
    private int $maxMemoryCacheBytes = 104857600; // 100MB máximo en memoria
    private int $hitCount = 0; // Contador de aciertos
    private int $missCount = 0; // Contador de fallos

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->crearTablaCache();
    }

    /**
     * Crear tabla de cache si no existe
     */
    private function crearTablaCache(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->cacheTable} (
            cache_key VARCHAR(255) PRIMARY KEY,
            cache_value LONGTEXT,
            expires_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->database->query($sql);
    }

    /**
     * Obtener valor del cache
     */
    public function get(string $key): mixed
    {
        // Primero verificar cache en memoria
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];
            if ($cached['expires'] > time()) {
                $this->hitCount++;
                return $cached['value'];
            } else {
                unset($this->memoryCache[$key]);
            }
        }

        // Verificar cache en base de datos
        $sql = "SELECT cache_value, expires_at FROM {$this->cacheTable} 
                WHERE cache_key = ? AND expires_at > NOW()";
        
        $result = $this->database->fetch($sql, [$key]);
        
        if ($result) {
            $value = json_decode($result['cache_value'], true);
            
            // Guardar en cache de memoria con límites
            $this->manageMemoryCacheSize();
            $this->memoryCache[$key] = [
                'value' => $value,
                'expires' => strtotime($result['expires_at'])
            ];
            
            $this->hitCount++;
            return $value;
        }

        $this->missCount++;
        return null;
    }

    /**
     * Guardar valor en cache
     */
    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->defaultTtl;
            
            // Validar TTL para evitar integer overflow
            if ($ttl <= 0 || $ttl > 31536000) { // Máximo 1 año
                $this->logEvent('WARNING', 'TTL inválido para cache', [
                    'key' => $key,
                    'ttl' => $ttl,
                    'max_ttl' => 31536000
                ]);
                return false;
            }
            
            $currentTime = time();
            $expirationTime = $currentTime + $ttl;
            
            // Verificar overflow
            if ($expirationTime < $currentTime) {
                $this->logEvent('WARNING', 'Integer overflow detectado en TTL', [
                    'key' => $key,
                    'ttl' => $ttl,
                    'current_time' => $currentTime,
                    'expiration_time' => $expirationTime
                ]);
                return false;
            }
            
            $expiresAt = date('Y-m-d H:i:s', $expirationTime);
            
            // Sanitizar clave
            $key = $this->sanitizeKey($key);
            
            $serializedValue = json_encode($value);
            
            // Validar tamaño del valor serializado (máximo 1MB)
            if (strlen($serializedValue) > 1048576) {
                $this->logEvent('WARNING', 'Valor de cache demasiado grande', [
                    'key' => $key,
                    'size' => strlen($serializedValue),
                    'max_size' => 1048576
                ]);
                return false;
            }

            // Guardar en cache de memoria con límites
            $this->manageMemoryCacheSize();
            $this->memoryCache[$key] = [
                'value' => $value,
                'expires' => $expirationTime
            ];

            // Guardar en base de datos
            $sql = "INSERT INTO {$this->cacheTable} (cache_key, cache_value, expires_at) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    cache_value = VALUES(cache_value), 
                    expires_at = VALUES(expires_at)";

            $this->database->query($sql, [$key, $serializedValue, $expiresAt]);
            return true;
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error estableciendo cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sanitizar clave de cache
     */
    private function sanitizeKey(string $key): string
    {
        // Remover caracteres peligrosos y limitar longitud
        $key = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
        return substr($key, 0, 255);
    }

    /**
     * Gestionar el tamaño del cache en memoria
     */
    private function manageMemoryCacheSize(): void
    {
        // Verificar límite de elementos
        if (count($this->memoryCache) >= $this->maxMemoryCacheSize) {
            // Eliminar el 20% de elementos más antiguos
            $toRemove = (int)($this->maxMemoryCacheSize * 0.2);
            $keys = array_keys($this->memoryCache);
            for ($i = 0; $i < $toRemove; $i++) {
                unset($this->memoryCache[$keys[$i]]);
            }
        }

        // Verificar límite de memoria (aproximado)
        $memoryUsage = strlen(serialize($this->memoryCache));
        if ($memoryUsage > $this->maxMemoryCacheBytes) {
            // Eliminar elementos hasta reducir el uso de memoria
            $keys = array_keys($this->memoryCache);
            $removeCount = (int)(count($keys) * 0.3);
            for ($i = 0; $i < $removeCount; $i++) {
                unset($this->memoryCache[$keys[$i]]);
            }
        }
    }

    /**
     * Eliminar valor del cache
     */
    public function delete(string $key): bool
    {
        try {
            // Eliminar de memoria
            unset($this->memoryCache[$key]);

            // Eliminar de base de datos
            $sql = "DELETE FROM {$this->cacheTable} WHERE cache_key = ?";
            $this->database->query($sql, [$key]);
            return true;
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error eliminando del cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Limpiar cache expirado
     */
    public function cleanExpired(): int
    {
        $sql = "DELETE FROM {$this->cacheTable} WHERE expires_at <= NOW()";
        $stmt = $this->database->query($sql);
        $deletedRows = $stmt->rowCount();
        
        // Limpiar cache de memoria expirado
        $now = time();
        foreach ($this->memoryCache as $key => $cached) {
            if ($cached['expires'] <= $now) {
                unset($this->memoryCache[$key]);
            }
        }

        return $deletedRows;
    }

    /**
     * Limpiar todo el cache
     */
    public function clear(): bool
    {
        $this->memoryCache = [];
        $sql = "TRUNCATE TABLE {$this->cacheTable}";
        $this->database->query($sql);
        return true;
    }

    /**
     * Verificar si existe en cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Obtener o calcular (cache-aside pattern)
     */
    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }

    /**
     * Invalidar cache por patrón
     */
    public function invalidatePattern(string $pattern): int
    {
        $sql = "DELETE FROM {$this->cacheTable} WHERE cache_key LIKE ?";
        $stmt = $this->database->query($sql, [$pattern]);
        $deletedRows = $stmt->rowCount();
        
        // Limpiar memoria por patrón
        foreach (array_keys($this->memoryCache) as $key) {
            if (fnmatch($pattern, $key)) {
                unset($this->memoryCache[$key]);
            }
        }

        return $deletedRows;
    }

    /**
     * Obtener estadísticas del cache
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_entries,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_entries
                FROM {$this->cacheTable}";
        
        $result = $this->database->fetch($sql);
        
        return [
            'total_entries' => (int)$result['total_entries'],
            'active_entries' => (int)$result['active_entries'],
            'expired_entries' => (int)$result['expired_entries'],
            'memory_entries' => count($this->memoryCache),
            'hit_ratio' => $this->calculateHitRatio()
        ];
    }

    /**
     * Calcular ratio de aciertos real
     */
    private function calculateHitRatio(): float
    {
        $totalRequests = $this->hitCount + $this->missCount;
        if ($totalRequests === 0) {
            return 0.0;
        }
        return round(($this->hitCount / $totalRequests) * 100, 2);
    }
}
