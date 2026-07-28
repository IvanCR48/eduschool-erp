<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Configuración del Sistema
 * 
 * Maneja la configuración global del sistema
 */
class ConfigurationService extends BaseService
{
    private array $configCache = [];
    private static bool $tableEnsured = false;
    
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->ensureConfigTable();
    }
    
    /**
     * Asegurar que la tabla de configuración existe
     */
    private function ensureConfigTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        self::$tableEnsured = true;

        try {
            $this->database->query("
                CREATE TABLE IF NOT EXISTS configuracion_sistema (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    clave VARCHAR(255) NOT NULL UNIQUE,
                    valor TEXT,
                    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
                    categoria VARCHAR(100),
                    descripcion TEXT,
                    modificado_por INT,
                    modificado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_clave (clave),
                    INDEX idx_categoria (categoria)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Insertar configuraciones por defecto si no existen
            $this->insertarConfiguracionesDefault();
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error creando tabla de configuración', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Insertar configuraciones por defecto
     */
    private function insertarConfiguracionesDefault(): void
    {
        $defaults = [
            // Sistema
            ['clave' => 'sistema.nombre', 'valor' => 'EduSchool ERP', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'System Name'],
            ['clave' => 'sistema.subtitulo', 'valor' => 'School Management System', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'System Subtitle'],
            ['clave' => 'sistema.logo', 'valor' => 'img/logo.png', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'System Logo Path'],
            ['clave' => 'sistema.timezone', 'valor' => 'America/New_York', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'System Timezone'],
            ['clave' => 'sistema.mantenimiento', 'valor' => '0', 'tipo' => 'boolean', 'categoria' => 'sistema', 'descripcion' => 'Maintenance Mode'],
            ['clave' => 'sistema.email', 'valor' => 'admin@school.com', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'System Contact Email'],
            ['clave' => 'sistema.telefono', 'valor' => '+1 (555) 019-2834', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'System Contact Phone'],
            ['clave' => 'sistema.direccion', 'valor' => '123 Education Way, Campus Suite 100', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'School Address'],
            ['clave' => 'sistema.moneda_simbolo', 'valor' => '$', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'Currency Symbol'],
            ['clave' => 'sistema.moneda_codigo', 'valor' => 'USD', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'Currency Code'],
            ['clave' => 'sistema.idioma_defecto', 'valor' => 'en', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'Default Language'],
            
            // Seguridad
            ['clave' => 'seguridad.max_intentos_login', 'valor' => '5', 'tipo' => 'number', 'categoria' => 'seguridad', 'descripcion' => 'Máximo de intentos de login fallidos'],
            ['clave' => 'seguridad.tiempo_bloqueo', 'valor' => '30', 'tipo' => 'number', 'categoria' => 'seguridad', 'descripcion' => 'Tiempo de bloqueo en minutos'],
            ['clave' => 'seguridad.sesion_duracion', 'valor' => '480', 'tipo' => 'number', 'categoria' => 'seguridad', 'descripcion' => 'Duración de sesión en minutos'],
            ['clave' => 'seguridad.requiere_2fa', 'valor' => '0', 'tipo' => 'boolean', 'categoria' => 'seguridad', 'descripcion' => 'Requerir 2FA para todos los usuarios'],
            ['clave' => 'seguridad.password_min_longitud', 'valor' => '8', 'tipo' => 'number', 'categoria' => 'seguridad', 'descripcion' => 'Longitud mínima de contraseña'],
            
            // Backups
            ['clave' => 'backup.automatico', 'valor' => '1', 'tipo' => 'boolean', 'categoria' => 'backup', 'descripcion' => 'Backups automáticos activados'],
            ['clave' => 'backup.frecuencia', 'valor' => 'diario', 'tipo' => 'string', 'categoria' => 'backup', 'descripcion' => 'Frecuencia de backups (diario, semanal)'],
            ['clave' => 'backup.hora', 'valor' => '03:00', 'tipo' => 'string', 'categoria' => 'backup', 'descripcion' => 'Hora de ejecución de backup automático'],
            ['clave' => 'backup.max_backups', 'valor' => '30', 'tipo' => 'number', 'categoria' => 'backup', 'descripcion' => 'Número máximo de backups a mantener'],
            ['clave' => 'backup.last_automatic_run', 'valor' => '', 'tipo' => 'string', 'categoria' => 'backup', 'descripcion' => 'Última ejecución de backup automático (ISO fecha/hora)'],
            ['clave' => 'backup.cron_token', 'valor' => '', 'tipo' => 'string', 'categoria' => 'backup', 'descripcion' => 'Token secreto para URL de cron (backup automático)'],
            ['clave' => 'queue.cron_token', 'valor' => '', 'tipo' => 'string', 'categoria' => 'sistema', 'descripcion' => 'Token secreto para cron del worker de colas (jobs en segundo plano)'],
            
            // Notificaciones
            ['clave' => 'notificaciones.email_activo', 'valor' => '0', 'tipo' => 'boolean', 'categoria' => 'notificaciones', 'descripcion' => 'Notificaciones por email activadas'],
            ['clave' => 'notificaciones.email_admin', 'valor' => '', 'tipo' => 'string', 'categoria' => 'notificaciones', 'descripcion' => 'Email del administrador'],
            
            // Estructura Académica y Perfil de Escuela
            ['clave' => 'academico.perfil_escuela', 'valor' => 'general', 'tipo' => 'string', 'categoria' => 'academico', 'descripcion' => 'Perfil de Escuela (general, tecnica)'],
            ['clave' => 'academico.escala_notas', 'valor' => 'numeric_10', 'tipo' => 'string', 'categoria' => 'academico', 'descripcion' => 'Escala de Calificaciones (numeric_10, numeric_100, letter_af, gpa, tea_tep_ted)'],
            ['clave' => 'academico.cantidad_periodos', 'valor' => '2', 'tipo' => 'number', 'categoria' => 'academico', 'descripcion' => 'Cantidad de Períodos Académicos (2 cuatrimestres, 3 trimestres, 4 bimestres)'],

            // Rendimiento
            ['clave' => 'rendimiento.cache_activo', 'valor' => '1', 'tipo' => 'boolean', 'categoria' => 'rendimiento', 'descripcion' => 'Sistema de caché activado'],
            ['clave' => 'rendimiento.cache_duracion', 'valor' => '3600', 'tipo' => 'number', 'categoria' => 'rendimiento', 'descripcion' => 'Duración del caché en segundos'],
            ['clave' => 'rendimiento.logs_nivel', 'valor' => 'INFO', 'tipo' => 'string', 'categoria' => 'rendimiento', 'descripcion' => 'Nivel de logging (DEBUG, INFO, WARNING, ERROR)'],
            
            // Académico
            ['clave' => 'academico.anio_lectivo', 'valor' => date('Y'), 'tipo' => 'number', 'categoria' => 'academico', 'descripcion' => 'Año lectivo actual'],
            ['clave' => 'academico.periodo_actual', 'valor' => '1', 'tipo' => 'number', 'categoria' => 'academico', 'descripcion' => 'Período académico actual']
        ];
        
        foreach ($defaults as $config) {
            try {
                $exists = $this->database->fetch(
                    "SELECT id FROM configuracion_sistema WHERE clave = ?",
                    [$config['clave']]
                );
                
                if (!$exists) {
                    $this->database->query(
                        "INSERT INTO configuracion_sistema (clave, valor, tipo, categoria, descripcion) 
                         VALUES (?, ?, ?, ?, ?)",
                        [
                            $config['clave'],
                            $config['valor'],
                            $config['tipo'],
                            $config['categoria'],
                            $config['descripcion']
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Silenciosamente continuar si hay error
            }
        }
    }
    
    /**
     * Obtener valor de configuración
     */
    public function obtener(string $clave, $default = null)
    {
        // Verificar caché
        if (isset($this->configCache[$clave])) {
            return $this->configCache[$clave];
        }
        
        try {
            $config = $this->database->fetch(
                "SELECT valor, tipo FROM configuracion_sistema WHERE clave = ?",
                [$clave]
            );
            
            if (!$config) {
                return $default;
            }
            
            $valor = $this->convertirValor($config['valor'], $config['tipo']);
            $this->configCache[$clave] = $valor;
            
            return $valor;
            
        } catch (\Exception $e) {
            return $default;
        }
    }
    
    /**
     * Establecer valor de configuración
     */
    public function establecer(string $clave, $valor, ?int $usuarioId = null): bool
    {
        try {
            // Obtener el tipo actual o determinar el tipo
            $config = $this->database->fetch(
                "SELECT tipo FROM configuracion_sistema WHERE clave = ?",
                [$clave]
            );
            
            $tipo = $config ? $config['tipo'] : $this->determinarTipo($valor);
            $valorString = $this->convertirAString($valor, $tipo);
            
            // Actualizar o insertar
            $exists = $this->database->fetch(
                "SELECT id FROM configuracion_sistema WHERE clave = ?",
                [$clave]
            );
            
            if ($exists) {
                $this->database->query(
                    "UPDATE configuracion_sistema 
                     SET valor = ?, modificado_por = ? 
                     WHERE clave = ?",
                    [$valorString, $usuarioId, $clave]
                );
            } else {
                $this->database->query(
                    "INSERT INTO configuracion_sistema (clave, valor, tipo, modificado_por) 
                     VALUES (?, ?, ?, ?)",
                    [$clave, $valorString, $tipo, $usuarioId]
                );
            }
            
            // Actualizar caché
            $this->configCache[$clave] = $valor;
            
            $this->logEvent('INFO', 'Configuración actualizada', [
                'clave' => $clave,
                'usuario_id' => $usuarioId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error actualizando configuración', [
                'clave' => $clave,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Obtener todas las configuraciones por categoría
     */
    public function obtenerPorCategoria(string $categoria): array
    {
        try {
            $configs = $this->database->fetchAll(
                "SELECT clave, valor, tipo, descripcion 
                 FROM configuracion_sistema 
                 WHERE categoria = ? 
                 ORDER BY clave",
                [$categoria]
            );
            
            $resultado = [];
            foreach ($configs as $config) {
                $resultado[$config['clave']] = [
                    'valor' => $this->convertirValor($config['valor'], $config['tipo']),
                    'tipo' => $config['tipo'],
                    'descripcion' => $config['descripcion']
                ];
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener todas las configuraciones
     */
    public function obtenerTodas(): array
    {
        try {
            $configs = $this->database->fetchAll(
                "SELECT clave, valor, tipo, categoria, descripcion, modificado_en 
                 FROM configuracion_sistema 
                 ORDER BY categoria, clave"
            );
            
            $resultado = [];
            foreach ($configs as $config) {
                if (!isset($resultado[$config['categoria']])) {
                    $resultado[$config['categoria']] = [];
                }
                
                $resultado[$config['categoria']][$config['clave']] = [
                    'valor' => $this->convertirValor($config['valor'], $config['tipo']),
                    'tipo' => $config['tipo'],
                    'descripcion' => $config['descripcion'],
                    'modificado_en' => $config['modificado_en']
                ];
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Actualizar múltiples configuraciones
     */
    public function actualizarMultiples(array $configuraciones, ?int $usuarioId = null): array
    {
        $exitosos = 0;
        $fallidos = 0;
        $errores = [];
        
        foreach ($configuraciones as $clave => $valor) {
            if ($this->establecer($clave, $valor, $usuarioId)) {
                $exitosos++;
            } else {
                $fallidos++;
                $errores[] = $clave;
            }
        }
        
        return [
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'errores' => $errores
        ];
    }
    
    /**
     * Restaurar configuraciones por defecto
     */
    public function restaurarDefaults(?int $usuarioId = null): bool
    {
        try {
            // Eliminar todas las configuraciones
            $this->database->query("DELETE FROM configuracion_sistema");
            
            // Reinsertar configuraciones por defecto
            $this->insertarConfiguracionesDefault();
            
            // Limpiar caché
            $this->configCache = [];
            
            $this->logEvent('INFO', 'Configuraciones restauradas a valores por defecto', [
                'usuario_id' => $usuarioId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error restaurando configuraciones', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Exportar configuración
     */
    public function exportar(): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'configuracion' => $this->obtenerTodas()
        ];
    }
    
    /**
     * Importar configuración
     */
    public function importar(array $configuracion, ?int $usuarioId = null): bool
    {
        try {
            if (!isset($configuracion['configuracion'])) {
                throw new \Exception('Formato de configuración inválido');
            }
            
            foreach ($configuracion['configuracion'] as $categoria => $configs) {
                foreach ($configs as $clave => $data) {
                    $this->establecer($clave, $data['valor'], $usuarioId);
                }
            }
            
            $this->logEvent('INFO', 'Configuración importada', [
                'usuario_id' => $usuarioId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error importando configuración', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Convertir valor según tipo
     */
    private function convertirValor(string $valor, string $tipo)
    {
        switch ($tipo) {
            case 'number':
                return is_numeric($valor) ? (float)$valor : 0;
            case 'boolean':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($valor, true) ?? [];
            default:
                return $valor;
        }
    }
    
    /**
     * Convertir valor a string para almacenar
     */
    private function convertirAString($valor, string $tipo): string
    {
        switch ($tipo) {
            case 'boolean':
                return $valor ? '1' : '0';
            case 'json':
                return json_encode($valor);
            default:
                return (string)$valor;
        }
    }
    
    /**
     * Determinar tipo de dato
     */
    private function determinarTipo($valor): string
    {
        if (is_bool($valor)) return 'boolean';
        if (is_numeric($valor)) return 'number';
        if (is_array($valor)) return 'json';
        return 'string';
    }
    
    /**
     * Limpiar caché de configuración
     */
    public function limpiarCache(): void
    {
        $this->configCache = [];
    }
}
