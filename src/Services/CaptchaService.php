<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de CAPTCHA
 * 
 * Implementa CAPTCHA matemático simple para formularios críticos
 */
class CaptchaService extends BaseService
{
    private string $sessionKey = 'captcha_data';
    private int $expirationTime = 600; // 10 minutos
    private array $criticalActions = [
        'login', 'registro', 'cambiar_password', 
        'recuperar_password', 'contacto', 'comentario'
    ];

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
    }

    /**
     * Generar nueva pregunta CAPTCHA
     */
    public function generarCaptcha(bool $forzarNuevo = false): array
    {
        // Si no se fuerza un nuevo captcha, verificar si ya existe un CAPTCHA válido en sesión
        if (!$forzarNuevo && isset($_SESSION[$this->sessionKey])) {
            $captchaData = $_SESSION[$this->sessionKey];
            // Verificar si no ha expirado
            if (time() - $captchaData['timestamp'] < $this->expirationTime) {
                return [
                    'captcha_id' => $captchaData['id'],
                    'pregunta' => $captchaData['pregunta'],
                    'expires_in' => $this->expirationTime - (time() - $captchaData['timestamp'])
                ];
            } else {
                // Limpiar CAPTCHA expirado
                $this->limpiarCaptcha();
            }
        }
        
        // Generar operación matemática simple
        $operaciones = ['suma', 'resta', 'multiplicacion'];
        $operacion = $operaciones[array_rand($operaciones)];
        
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        
        // Asegurar que el resultado sea positivo
        if ($operacion === 'resta' && $num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        
        $pregunta = $this->formatearPregunta($operacion, $num1, $num2);
        $respuesta = $this->calcularRespuesta($operacion, $num1, $num2);
        
        // Generar ID único para el CAPTCHA
        $captchaId = bin2hex(random_bytes(16));
        
        // Guardar en sesión (asumir que la sesión ya está iniciada)
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }
        
        $timestamp = time();
        $_SESSION[$this->sessionKey] = [
            'id' => $captchaId,
            'respuesta' => $respuesta,
            'timestamp' => $timestamp,
            'pregunta' => $pregunta
        ];
        
        // Log para debug
        error_log("CAPTCHA Generated - ID: " . $captchaId . " | Timestamp: " . $timestamp . " | Time: " . date('Y-m-d H:i:s', $timestamp));
        
        return [
            'captcha_id' => $captchaId,
            'pregunta' => $pregunta,
            'expires_in' => $this->expirationTime
        ];
    }

    /**
     * Verificar respuesta CAPTCHA
     */
    public function verificarCaptcha(string $captchaId, string $respuesta): array
    {
        // Asumir que la sesión ya está iniciada
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }
        
        // Limpiar y validar parámetros
        $captchaId = trim($captchaId);
        $respuesta = trim($respuesta);
        
        if (empty($captchaId)) {
            return [
                'success' => false,
                'error' => 'ID de CAPTCHA vacío'
            ];
        }
        
        // Verificar que existe el CAPTCHA en sesión
        if (!isset($_SESSION[$this->sessionKey])) {
            return [
                'success' => false,
                'error' => 'No hay CAPTCHA activo'
            ];
        }
        
        $captchaData = $_SESSION[$this->sessionKey];
        
        // Verificar que los datos de sesión son válidos
        if (!isset($captchaData['id']) || !isset($captchaData['respuesta'])) {
            return [
                'success' => false,
                'error' => 'Datos de CAPTCHA corruptos'
            ];
        }
        
        // Verificar ID con comparación estricta
        if ($captchaData['id'] !== $captchaId) {
            // Log para debug
            error_log("CAPTCHA ID Mismatch - Session ID: '" . $captchaData['id'] . "' | POST ID: '" . $captchaId . "'");
            error_log("Session CAPTCHA Data: " . print_r($captchaData, true));
            return [
                'success' => false,
                'error' => 'ID de CAPTCHA inválido'
            ];
        }
        
        // Verificar expiración
        $tiempoTranscurrido = time() - $captchaData['timestamp'];
        if ($tiempoTranscurrido > $this->expirationTime) {
            // Log para debug
            error_log("CAPTCHA Expired - Current time: " . time() . " | Timestamp: " . $captchaData['timestamp'] . " | Elapsed: " . $tiempoTranscurrido . " | Expiration: " . $this->expirationTime);
            $this->limpiarCaptcha();
            return [
                'success' => false,
                'error' => 'CAPTCHA expirado'
            ];
        }
        
        // Verificar respuesta
        $respuestaCorrecta = (string)$captchaData['respuesta'];
        if (!hash_equals($respuestaCorrecta, trim($respuesta))) {
            // NO limpiar CAPTCHA después de intento fallido para permitir reintentos
            return [
                'success' => false,
                'error' => 'Respuesta incorrecta'
            ];
        }
        
        // CAPTCHA correcto, NO limpiar automáticamente - se limpiará solo cuando la autenticación sea exitosa
        return [
            'success' => true,
            'message' => 'CAPTCHA verificado correctamente'
        ];
    }

    /**
     * Generar CAPTCHA con imagen (alternativo)
     */
    public function generarCaptchaImagen(): array
    {
        // Generar texto aleatorio
        $texto = $this->generarTextoAleatorio(5);
        $captchaId = bin2hex(random_bytes(16));
        
        // Guardar en sesión (asumir que la sesión ya está iniciada)
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }
        
        $_SESSION[$this->sessionKey] = [
            'id' => $captchaId,
            'respuesta' => strtolower($texto),
            'timestamp' => time(),
            'texto' => $texto
        ];
        
        // Crear imagen
        $imagen = $this->crearImagenCaptcha($texto);
        
        return [
            'captcha_id' => $captchaId,
            'imagen_base64' => base64_encode($imagen),
            'expires_in' => $this->expirationTime
        ];
    }

    /**
     * Verificar CAPTCHA de imagen
     */
    public function verificarCaptchaImagen(string $captchaId, string $respuesta): array
    {
        return $this->verificarCaptcha($captchaId, strtolower($respuesta));
    }

    /**
     * Limpiar CAPTCHA de la sesión
     */
    private function limpiarCaptcha(): void
    {
        if (isset($_SESSION[$this->sessionKey])) {
            unset($_SESSION[$this->sessionKey]);
        }
    }
    
    /**
     * Limpiar CAPTCHA manualmente (para usar después de autenticación exitosa)
     */
    public function limpiarCaptchaManual(): void
    {
        $this->limpiarCaptcha();
    }

    /**
     * Formatear pregunta según la operación
     */
    private function formatearPregunta(string $operacion, int $num1, int $num2): string
    {
        switch ($operacion) {
            case 'suma':
                return "¿Cuánto es {$num1} + {$num2}?";
            case 'resta':
                return "¿Cuánto es {$num1} - {$num2}?";
            case 'multiplicacion':
                return "¿Cuánto es {$num1} × {$num2}?";
            default:
                return "¿Cuánto es {$num1} + {$num2}?";
        }
    }

    /**
     * Calcular respuesta según la operación
     */
    private function calcularRespuesta(string $operacion, int $num1, int $num2): int
    {
        switch ($operacion) {
            case 'suma':
                return $num1 + $num2;
            case 'resta':
                return $num1 - $num2;
            case 'multiplicacion':
                return $num1 * $num2;
            default:
                return $num1 + $num2;
        }
    }

    /**
     * Generar texto aleatorio para CAPTCHA
     */
    private function generarTextoAleatorio(int $longitud): string
    {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $texto = '';
        
        for ($i = 0; $i < $longitud; $i++) {
            $texto .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        
        return $texto;
    }

    /**
     * Crear imagen CAPTCHA
     */
    private function crearImagenCaptcha(string $texto): string
    {
        // Dimensiones de la imagen
        $ancho = 120;
        $alto = 40;
        
        // Crear imagen
        $imagen = imagecreate($ancho, $alto);
        
        // Colores
        $fondo = imagecolorallocate($imagen, 240, 240, 240);
        $texto_color = imagecolorallocate($imagen, 0, 0, 0);
        $linea_color = imagecolorallocate($imagen, 200, 200, 200);
        
        // Rellenar fondo
        imagefill($imagen, 0, 0, $fondo);
        
        // Agregar líneas aleatorias
        for ($i = 0; $i < 5; $i++) {
            imageline(
                $imagen,
                random_int(0, $ancho),
                random_int(0, $alto),
                random_int(0, $ancho),
                random_int(0, $alto),
                $linea_color
            );
        }
        
        // Agregar texto
        $fuente = 5; // Fuente básica de GD
        $x = ($ancho - strlen($texto) * 9) / 2;
        $y = ($alto - 8) / 2;
        
        imagestring($imagen, $fuente, $x, $y, $texto, $texto_color);
        
        // Generar imagen como string
        ob_start();
        imagepng($imagen);
        $imagenString = ob_get_contents();
        ob_end_clean();
        
        // Limpiar memoria
        imagedestroy($imagen);
        
        return $imagenString;
    }

    /**
     * Verificar si CAPTCHA es requerido para la acción
     */
    public function esRequeridoParaAccion(string $accion): bool
    {
        return in_array($accion, $this->criticalActions, true);
    }

    /**
     * Obtener tiempo de expiración del CAPTCHA
     */
    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }

    /**
     * Obtener estadísticas de CAPTCHA
     */
    public function obtenerEstadisticas(): array
    {
        // Implementar si es necesario
        return [
            'captchas_generados' => 0,
            'captchas_exitosos' => 0,
            'captchas_fallidos' => 0
        ];
    }

    /**
     * Configurar tiempo de expiración
     */
    public function configurarExpirationTime(int $segundos): void
    {
        $this->expirationTime = $segundos;
    }

    /**
     * Obtener tiempo de expiración actual
     */
    public function obtenerExpirationTime(): int
    {
        return $this->expirationTime;
    }
}
