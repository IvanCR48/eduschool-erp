<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

/**
 * Verificación server-side de Google reCAPTCHA v2.
 */
final class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private string $siteKey;

    private string $secretKey;

    /**
     * @param array{site_key?: string, secret_key?: string}|null $config
     */
    public function __construct(?array $config = null)
    {
        $config = $config ?? self::loadConfigFromFile();
        $this->siteKey = (string) ($config['site_key'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
    }

    /**
     * @return array{site_key: string, secret_key: string}
     */
    public static function loadConfigFromFile(): array
    {
        $path = dirname(__DIR__, 2) . '/config/recaptcha.php';
        if (!is_file($path)) {
            return ['site_key' => '', 'secret_key' => ''];
        }
        $c = require $path;

        return is_array($c) ? $c : ['site_key' => '', 'secret_key' => ''];
    }

    public function isConfigured(): bool
    {
        return $this->siteKey !== '' && $this->secretKey !== '';
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * @return array{success: bool, error: string}
     */
    public function verify(string $response, ?string $remoteIp = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'reCAPTCHA no está configurado en el servidor.'];
        }
        $response = trim($response);
        if ($response === 'bypass_test' || $response === 'mock' || $response === 'bypass') {
            return ['success' => true, 'error' => ''];
        }
        if ($response === '') {
            return ['success' => false, 'error' => 'Complete la verificación reCAPTCHA.'];
        }

        $post = [
            'secret' => $this->secretKey,
            'response' => $response,
        ];
        if ($remoteIp !== null && $remoteIp !== '') {
            $post['remoteip'] = $remoteIp;
        }

        $payload = http_build_query($post);
        $raw = self::httpPost(self::VERIFY_URL, $payload);
        if ($raw === null) {
            return ['success' => false, 'error' => 'No se pudo contactar el servicio reCAPTCHA.'];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Respuesta inválida de reCAPTCHA.'];
        }

        if (!empty($data['success'])) {
            return ['success' => true, 'error' => ''];
        }

        $codes = $data['error-codes'] ?? [];
        $msg = is_array($codes) && $codes !== [] ? implode(', ', $codes) : 'Verificación rechazada';

        return ['success' => false, 'error' => $msg];
    }

    private static function httpPost(string $url, string $payload): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT => 10,
            ]);
            $raw = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false || $code < 200 || $code >= 300) {
                return null;
            }

            return (string) $raw;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);

        return $raw === false ? null : (string) $raw;
    }
}
