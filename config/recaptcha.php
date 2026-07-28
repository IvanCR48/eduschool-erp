<?php

declare(strict_types=1);

/**
 * Claves reCAPTCHA v2 ("No soy un robot").
 * Opción 1: variables de entorno RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY
 * Opción 2: copie recaptcha.local.php.example a recaptcha.local.php y complete las claves (no versionar ese archivo).
 */

$siteKey = (string) (getenv('RECAPTCHA_SITE_KEY') ?: '');
$secretKey = (string) (getenv('RECAPTCHA_SECRET_KEY') ?: '');

$localFile = __DIR__ . '/recaptcha.local.php';
if (is_file($localFile)) {
    $override = require $localFile;
    if (is_array($override)) {
        if (($override['site_key'] ?? '') !== '') {
            $siteKey = (string) $override['site_key'];
        }
        if (($override['secret_key'] ?? '') !== '') {
            $secretKey = (string) $override['secret_key'];
        }
    }
}

return [
    'site_key' => $siteKey,
    'secret_key' => $secretKey,
];
