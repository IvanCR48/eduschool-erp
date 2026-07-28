<?php

namespace SistemaAdmin\Services;

class QRCodeService
{
    private $baseUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    
    /**
     * Genera un código QR para un estudiante
     */
    public function generarQRParaEstudiante($estudiante_id, $tamaño = 200)
    {
        $url = $this->baseUrl . "?size={$tamaño}x{$tamaño}&data=" . urlencode($this->generarURLEstudiante($estudiante_id));
        return $url;
    }
    
    /**
     * Genera un código QR para una URL personalizada
     */
    public function generarQRParaURL($url, $tamaño = 200)
    {
        return $this->baseUrl . "?size={$tamaño}x{$tamaño}&data=" . urlencode($url);
    }
    
    /**
     * Genera un código QR para texto plano
     */
    public function generarQRParaTexto($texto, $tamaño = 200)
    {
        return $this->baseUrl . "?size={$tamaño}x{$tamaño}&data=" . urlencode($texto);
    }
    
    /**
     * Genera la URL del estudiante para el QR
     */
    private function generarURLEstudiante($estudiante_id)
    {
        $baseUrl = $this->obtenerBaseURL();
        return $baseUrl . "student_profile.php?id=" . $estudiante_id;
    }
    
    /**
     * Obtiene la URL base del sistema
     */
    private function obtenerBaseURL()
    {
        // Verificar si estamos en un entorno web
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['SCRIPT_NAME']);
            return $protocol . '://' . $host . $path . '/';
        }

        // Fallback para CLI o entornos sin HTTP_HOST: usa APP_URL del .env
        $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost/SistemaAdmin'), '/');
        return $appUrl . '/';
    }
    
    /**
     * Genera un QR con información del estudiante
     */
    public function generarQRConInfoEstudiante($estudiante, $tamaño = 200)
    {
        $info = [
            'nombre' => $estudiante['nombre'] . ' ' . $estudiante['apellido'],
            'dni' => $estudiante['dni'],
            'curso' => $estudiante['curso'] ?? 'Sin curso',
            'url' => $this->generarURLEstudiante($estudiante['id'])
        ];
        
        $texto = "ESTUDIANTE: " . $info['nombre'] . "\n";
        $texto .= "DNI: " . $info['dni'] . "\n";
        $texto .= "CURSO: " . $info['curso'] . "\n";
        $texto .= "URL: " . $info['url'];
        
        return $this->generarQRParaTexto($texto, $tamaño);
    }
}
