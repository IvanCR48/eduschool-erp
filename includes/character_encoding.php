<?php
/**
 * Manejo centralizado de codificación de caracteres
 * 
 * Este archivo proporciona funciones para normalizar y corregir
 * problemas de codificación de caracteres UTF-8 en la aplicación.
 */

// Configurar codificación interna
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

/**
 * Normaliza caracteres UTF-8 y corrige problemas de codificación
 * 
 * @param string $texto El texto a normalizar
 * @return string El texto normalizado
 */
function normalizarTexto($texto) {
    if (empty($texto)) {
        return $texto;
    }
    
    // Detectar y convertir codificación si es necesario
    $encoding = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    if ($encoding && $encoding !== 'UTF-8') {
        $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
    }
    
    // Normalizar caracteres UTF-8
    if (class_exists('Normalizer')) {
        $texto = Normalizer::normalize($texto, Normalizer::FORM_C);
    }
    
    // Corregir caracteres problemáticos comunes
    $correcciones = [
        // Caracteres con problemas de codificación
        '??' => 'á', '??' => 'é', '??' => 'í', '??' => 'ó', '??' => 'ú',
        '??' => 'ñ', '??' => 'ü', '??' => 'Á', '??' => 'É', '??' => 'Í',
        '??' => 'Ó', '??' => 'Ú', '??' => 'Ñ', '??' => 'Ü',
        
        // Caracteres con problemas de escape
        '&aacute;' => 'á', '&eacute;' => 'é', '&iacute;' => 'í',
        '&oacute;' => 'ó', '&uacute;' => 'ú', '&ntilde;' => 'ñ',
        '&Aacute;' => 'Á', '&Eacute;' => 'É', '&Iacute;' => 'Í',
        '&Oacute;' => 'Ó', '&Uacute;' => 'Ú', '&Ntilde;' => 'Ñ',
        
        // Correcciones específicas comunes
        'Matematica' => 'Matemática', 'Fisica' => 'Física',
        'Ingles' => 'Inglés', 'Frances' => 'Francés'
    ];
    
    foreach ($correcciones as $incorrecto => $correcto) {
        $texto = str_replace($incorrecto, $correcto, $texto);
    }
    
    return $texto;
}

/**
 * Asegura que el texto esté correctamente codificado para HTML
 * 
 * @param string $texto El texto a procesar
 * @return string El texto listo para HTML
 */
function textoParaHTML($texto) {
    $texto = normalizarTexto($texto);
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Configura los headers HTTP para UTF-8
 */
function configurarHeadersUTF8() {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
}

// Configurar headers automáticamente
configurarHeadersUTF8();
?>
