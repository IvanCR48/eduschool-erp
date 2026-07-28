<?php
/**
 * Helper global de traducción i18n
 */

use SistemaAdmin\Services\I18nService;

if (!function_exists('__')) {
    function __(string $key, array $replacements = []): string {
        return I18nService::trans($key, $replacements);
    }
}

if (!function_exists('current_lang')) {
    function current_lang(): string {
        return I18nService::getLocale();
    }
}
