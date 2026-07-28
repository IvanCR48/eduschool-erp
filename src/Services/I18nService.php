<?php

namespace SistemaAdmin\Services;

/**
 * Servicio de Internacionalización (i18n)
 */
class I18nService
{
    private static ?string $currentLocale = null;
    private static array $translations = [];
    private static array $supportedLocales = ['en', 'es'];

    /**
     * Inicializar idioma actual desde GET, SESSION o Configuración
     */
    public static function init(?string $defaultConfigLanguage = 'en'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // 1. Manejar cambio explícito por URL (?lang=es o ?lang=en)
        if (isset($_GET['lang'])) {
            $requestedLang = strtolower(trim((string) $_GET['lang']));
            if (in_array($requestedLang, self::$supportedLocales, true)) {
                $_SESSION['app_lang'] = $requestedLang;
            }
        }

        // 2. Determinar idioma activo
        $locale = $_SESSION['app_lang'] 
            ?? $defaultConfigLanguage 
            ?? 'en';

        if (!in_array($locale, self::$supportedLocales, true)) {
            $locale = 'en';
        }

        self::setLocale($locale);
    }

    /**
     * Establecer idioma activo y cargar diccionario
     */
    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, self::$supportedLocales, true)) {
            $locale = 'en';
        }

        self::$currentLocale = $locale;
        $_SESSION['app_lang'] = $locale;

        $filePath = __DIR__ . '/../../lang/' . $locale . '.php';
        if (file_exists($filePath)) {
            self::$translations = require $filePath;
        } else {
            self::$translations = [];
        }
    }

    /**
     * Obtener idioma activo actual
     */
    public static function getLocale(): string
    {
        if (self::$currentLocale === null) {
            self::init();
        }
        return self::$currentLocale ?? 'en';
    }

    /**
     * Traducir una clave con reemplazos opcionales
     */
    public static function trans(string $key, array $replacements = []): string
    {
        if (self::$currentLocale === null) {
            self::init();
        }

        $text = self::$translations[$key] ?? $key;

        foreach ($replacements as $search => $replace) {
            $text = str_replace(':' . $search, (string) $replace, $text);
        }

        return $text;
    }

    /**
     * Obtener lista de idiomas soportados
     */
    public static function getSupportedLocales(): array
    {
        return [
            'en' => ['name' => 'English', 'flag' => '🇺🇸'],
            'es' => ['name' => 'Español', 'flag' => '🇪🇸'],
        ];
    }
}
