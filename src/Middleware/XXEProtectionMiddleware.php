<?php

namespace SistemaAdmin\Middleware;

class XXEProtectionMiddleware
{
    /**
     * Configurar parser XML seguro
     */
    public static function createSecureXMLParser(): \DOMDocument
    {
        $dom = new \DOMDocument();
        
        // PROTECCIÓN CONTRA XXE (XML External Entity)
        // Deshabilitar entidades externas (XXE protection)
        libxml_disable_entity_loader(true);
        
        // Configurar opciones de seguridad
        $dom->strictErrorChecking = true;
        $dom->validateOnParse = false;
        $dom->resolveExternals = false;
        
        // Configurar manejador de entidades externas
        libxml_set_external_entity_loader(function($publicId, $systemId, $context) {
            // Bloquear todas las entidades externas
            error_log("XXE Protection: Intento de cargar entidad externa bloqueada. Public: {$publicId}, System: {$systemId}");
            return false;
        });
        
        return $dom;
    }
    
    /**
     * Cargar XML de forma segura
     */
    public static function loadXMLSecurely(string $xml): array
    {
        try {
            // PROTECCIÓN CONTRA XXE
            // Deshabilitar carga de entidades externas
            $previousValue = libxml_disable_entity_loader(true);
            
            // Limpiar errores previos
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            
            // Crear parser seguro
            $dom = new \DOMDocument();
            $dom->strictErrorChecking = true;
            $dom->validateOnParse = false;
            $dom->resolveExternals = false;
            
            // Configurar manejador de entidades externas
            libxml_set_external_entity_loader(function($publicId, $systemId, $context) {
                error_log("XXE Protection: Intento de cargar entidad externa bloqueada. Public: {$publicId}, System: {$systemId}");
                return false;
            });
            
            // Intentar cargar XML (sin LIBXML_NOENT para evitar entidades)
            $loaded = $dom->loadXML($xml, LIBXML_DTDLOAD | LIBXML_DTDATTR);
            
            // Restaurar configuración previa
            libxml_disable_entity_loader($previousValue);
            
            if (!$loaded) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                
                return [
                    'success' => false,
                    'error' => 'XML inválido',
                    'details' => $errors
                ];
            }
            
            // Verificar que no contenga entidades peligrosas
            if (self::containsDangerousEntities($xml)) {
                error_log("XXE Protection: XML contiene entidades peligrosas bloqueadas");
                return [
                    'success' => false,
                    'error' => 'XML contiene entidades peligrosas'
                ];
            }
            
            return [
                'success' => true,
                'dom' => $dom
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al procesar XML: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Detectar entidades peligrosas en XML
     */
    private static function containsDangerousEntities(string $xml): bool
    {
        $dangerousPatterns = [
            '/<!ENTITY/i',              // Declaraciones de entidades
            '/SYSTEM/i',                // Referencias a archivos externos
            '/PUBLIC/i',                // Referencias públicas
            '/file:\/\//i',             // Protocolo file
            '/php:\/\//i',              // Protocolo PHP
            '/expect:\/\//i',           // Protocolo expect
            '/data:\/\//i',             // Protocolo data
            '/ftp:\/\//i',              // Protocolo FTP
            '/<!DOCTYPE.*\[/is',        // DOCTYPE con DTD interna
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $xml)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitizar contenido XML
     */
    public static function sanitizeXMLContent(string $xml): string
    {
        // Remover declaraciones DOCTYPE
        $xml = preg_replace('/<!DOCTYPE[^>]*>/i', '', $xml);
        
        // Remover declaraciones de entidades
        $xml = preg_replace('/<!ENTITY[^>]*>/i', '', $xml);
        
        // Remover referencias a archivos externos
        $xml = preg_replace('/SYSTEM\s+["\'][^"\']*["\']/i', '', $xml);
        $xml = preg_replace('/PUBLIC\s+["\'][^"\']*["\']/i', '', $xml);
        
        return $xml;
    }
    
    /**
     * Validar archivo XML
     */
    public static function validateXMLFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'error' => 'Archivo no encontrado'
            ];
        }
        
        $content = file_get_contents($filePath);
        
        if (self::containsDangerousEntities($content)) {
            return [
                'valid' => false,
                'error' => 'El archivo XML contiene entidades peligrosas'
            ];
        }
        
        return self::loadXMLSecurely($content);
    }
}
