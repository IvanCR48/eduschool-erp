<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Manejo de Errores
 * 
 * Centraliza el manejo de errores para evitar revelación de información sensible
 */
class ErrorHandlerService extends BaseService
{
    private bool $isProduction = false;
    private array $sensitivePatterns = [
        '/password/i',
        '/secret/i',
        '/key/i',
        '/token/i',
        '/connection/i',
        '/database/i',
        '/sql/i',
        '/mysql/i',
        '/localhost/i',
        '/127\.0\.0\.1/i',
        '/file:\/\//i',
        '/\/.*\.php/i'
    ];

    public function __construct(DatabaseInterface $database, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, null, $logger);
        
        // Detectar si estamos en producción
        $this->isProduction = $this->detectarProduccion();
        
        // Configurar manejo de errores global
        $this->configurarManejoErrores();
    }

    /**
     * Manejar error de forma segura
     */
    public function manejarError(\Throwable $error, array $contexto = []): array
    {
        // Log del error completo para administradores
        $this->logErrorCompleto($error, $contexto);
        
        // Determinar nivel de error
        $nivel = $this->determinarNivelError($error);
        
        // Generar respuesta segura para el usuario
        return $this->generarRespuestaSegura($error, $nivel);
    }

    /**
     * Manejar error de validación
     */
    public function manejarErrorValidacion(array $errores): array
    {
        return [
            'success' => false,
            'error' => 'Datos de entrada inválidos',
            'errors' => $errores,
            'code' => 'VALIDATION_ERROR'
        ];
    }

    /**
     * Manejar error de base de datos
     */
    public function manejarErrorBaseDatos(\PDOException $error): array
    {
        // Log detallado para administradores
        if ($this->logger) {
            $this->logger->registrarError(
                'Error de base de datos: ' . $error->getMessage(),
                $error->getFile(),
                $error->getLine(),
                [
                    'sql_state' => $error->errorInfo[0] ?? null,
                    'driver_code' => $error->errorInfo[1] ?? null,
                    'driver_message' => $error->errorInfo[2] ?? null
                ]
            );
        }

        // Respuesta segura para el usuario
        return [
            'success' => false,
            'error' => 'Error interno del sistema. Por favor, intente nuevamente.',
            'code' => 'DATABASE_ERROR'
        ];
    }

    /**
     * Manejar error de autenticación
     */
    public function manejarErrorAutenticacion(string $mensaje = 'Credenciales inválidas'): array
    {
        return [
            'success' => false,
            'error' => $mensaje,
            'code' => 'AUTHENTICATION_ERROR'
        ];
    }

    /**
     * Manejar error de autorización
     */
    public function manejarErrorAutorizacion(string $mensaje = 'No tiene permisos para realizar esta acción'): array
    {
        return [
            'success' => false,
            'error' => $mensaje,
            'code' => 'AUTHORIZATION_ERROR'
        ];
    }

    /**
     * Manejar error de recurso no encontrado
     */
    public function manejarErrorRecursoNoEncontrado(string $recurso = 'recurso'): array
    {
        return [
            'success' => false,
            'error' => "El {$recurso} solicitado no existe o no está disponible.",
            'code' => 'RESOURCE_NOT_FOUND'
        ];
    }

    /**
     * Manejar error de timeout
     */
    public function manejarErrorTimeout(string $operacion = 'operación'): array
    {
        $this->logEvent('WARNING', "Timeout en {$operacion}", [
            'operation' => $operacion,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]);

        return [
            'success' => false,
            'error' => "La {$operacion} tardó demasiado tiempo en completarse.",
            'code' => 'TIMEOUT_ERROR'
        ];
    }

    /**
     * Manejar error de memoria insuficiente
     */
    public function manejarErrorMemoria(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $this->logEvent('ERROR', 'Memoria insuficiente', [
            'current_usage' => $memoryUsage,
            'memory_limit' => $memoryLimit,
            'peak_memory' => memory_get_peak_usage(true)
        ]);

        return [
            'success' => false,
            'error' => 'Memoria insuficiente para completar la operación.',
            'code' => 'MEMORY_ERROR'
        ];
    }

    /**
     * Manejar error de rate limiting
     */
    public function manejarErrorRateLimit(int $tiempoRestante): array
    {
        return [
            'success' => false,
            'error' => "Demasiados intentos. Intente nuevamente en {$tiempoRestante} segundos.",
            'code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $tiempoRestante
        ];
    }

    /**
     * Manejar error genérico del servidor
     */
    public function manejarErrorServidor(\Throwable $error = null): array
    {
        if ($error) {
            $this->logErrorCompleto($error);
        }

        return [
            'success' => false,
            'error' => 'Error interno del servidor. Por favor, contacte al administrador.',
            'code' => 'INTERNAL_SERVER_ERROR'
        ];
    }

    /**
     * Validar y sanitizar entrada de error
     */
    public function sanitizarMensajeError(string $mensaje): string
    {
        foreach ($this->sensitivePatterns as $patron) {
            $mensaje = preg_replace($patron, '[REDACTED]', $mensaje);
        }

        return $mensaje;
    }

    /**
     * Configurar manejo de errores global
     */
    private function configurarManejoErrores(): void
    {
        set_error_handler([$this, 'manejarErrorPHP']);
        set_exception_handler([$this, 'manejarExcepcionPHP']);
        register_shutdown_function([$this, 'manejarErrorFatal']);
    }

    /**
     * Manejar errores PHP
     */
    public function manejarErrorPHP(int $severidad, string $mensaje, string $archivo, int $linea): bool
    {
        // Solo manejar errores fatales y warnings críticos
        if (!(error_reporting() & $severidad)) {
            return false;
        }

        $error = new \ErrorException($mensaje, 0, $severidad, $archivo, $linea);
        $this->logErrorCompleto($error);

        // En producción, no mostrar errores al usuario
        if ($this->isProduction) {
            return true;
        }

        return false;
    }

    /**
     * Manejar excepciones PHP
     */
    public function manejarExcepcionPHP(\Throwable $excepcion): void
    {
        $this->logErrorCompleto($excepcion);
        
        // En producción, mostrar página de error genérica
        if ($this->isProduction) {
            $this->mostrarPaginaError();
        }
    }

    /**
     * Manejar errores fatales
     */
    public function manejarErrorFatal(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $excepcion = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            $this->logErrorCompleto($excepcion);
            
            if ($this->isProduction) {
                $this->mostrarPaginaError();
            }
        }
    }

    /**
     * Log completo del error para administradores
     */
    private function logErrorCompleto(\Throwable $error, array $contexto = []): void
    {
        $detalles = [
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'context' => $contexto,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($this->logger) {
            $this->logger->registrarError(
                'Error del sistema: ' . $error->getMessage(),
                $error->getFile(),
                $error->getLine(),
                $detalles
            );
        }
    }

    /**
     * Determinar nivel de error
     */
    private function determinarNivelError(\Throwable $error): string
    {
        if ($error instanceof \PDOException) {
            return 'database';
        }
        
        if ($error instanceof \InvalidArgumentException) {
            return 'validation';
        }
        
        if ($error instanceof \Exception) {
            return 'application';
        }
        
        return 'system';
    }

    /**
     * Generar respuesta segura para el usuario
     */
    private function generarRespuestaSegura(\Throwable $error, string $nivel): array
    {
        $mensajesSeguros = [
            'database' => 'Error temporal del sistema. Por favor, intente nuevamente.',
            'validation' => 'Los datos proporcionados no son válidos.',
            'application' => 'Error en la aplicación. Por favor, intente nuevamente.',
            'system' => 'Error interno del sistema. Contacte al administrador.'
        ];

        $mensaje = $mensajesSeguros[$nivel] ?? $mensajesSeguros['system'];

        // En desarrollo, mostrar más detalles
        if (!$this->isProduction) {
            $mensaje = $this->sanitizarMensajeError($error->getMessage());
        }

        return [
            'success' => false,
            'error' => $mensaje,
            'code' => strtoupper($nivel . '_ERROR')
        ];
    }

    /**
     * Detectar si estamos en producción
     */
    private function detectarProduccion(): bool
    {
        // Verificar variables de entorno
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
            return true;
        }
        
        // Verificar si hay archivo de configuración de producción
        if (file_exists(__DIR__ . '/../../config/production.php')) {
            return true;
        }
        
        // Verificar dominio (para casos específicos)
        $dominio = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($dominio, 'localhost') === false && strpos($dominio, '127.0.0.1') === false) {
            return true;
        }
        
        return false;
    }

    /**
     * Mostrar página de error genérica
     */
    private function mostrarPaginaError(): void
    {
        http_response_code(500);
        
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error del sistema</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .error-container { max-width: 600px; margin: 0 auto; }
                .error-code { font-size: 72px; color: #e74c3c; margin: 0; }
                .error-message { font-size: 24px; color: #34495e; margin: 20px 0; }
                .error-description { font-size: 16px; color: #7f8c8d; margin: 20px 0; }
                .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1 class="error-code">500</h1>
                <h2 class="error-message">Error del Sistema</h2>
                <p class="error-description">
                    Ha ocurrido un error interno del sistema. Nuestro equipo técnico ha sido notificado y está trabajando para resolver el problema.
                </p>
                <a href="/" class="btn">Volver al Inicio</a>
            </div>
        </body>
        </html>';
        
        echo $html;
        exit;
    }

    /**
     * Obtener información de error para administradores
     */
    public function obtenerInfoError(string $errorId): ?array
    {
        // Implementar consulta a logs de errores si es necesario
        // Por ahora retornar null
        return null;
    }

    /**
     * Purga logs efímeros vía ServicioLogging (no afecta auditoría legal ni `audit.log`).
     */
    public function limpiarLogsAntiguos(int $dias = 30): int
    {
        if ($this->logger) {
            return $this->logger->limpiarLogsAntiguos($dias);
        }
        return 0;
    }
}
