<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

/**
 * OAuth 2.0 de Google para docentes: Google valida la contraseña en su propio sitio;
 * el sistema solo recibe un token y el email verificado.
 */
final class GoogleOAuthService
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    /**
     * @param array<string, string> $config
     */
    public function __construct(array $config)
    {
        $this->clientId = trim((string) ($config['client_id'] ?? ''));
        $this->clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $this->redirectUri = trim((string) ($config['redirect_uri'] ?? ''));
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->redirectUri !== '';
    }

    /**
     * @return array<string, string>|null
     */
    public static function loadLocalConfig(): ?array
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'google_oauth.local.php';
        if (!is_readable($path)) {
            return null;
        }
        $c = require $path;
        return is_array($c) ? $c : null;
    }

    public function buildAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{access_token: string}|null
     */
    public function exchangeAuthorizationCode(string $code): ?array
    {
        $body = http_build_query([
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ], '', '&');

        $ch = curl_init('https://oauth2.googleapis.com/token');
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
        ]);
        $raw = curl_exec($ch);
        $codeHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($raw) || $raw === '' || $codeHttp !== 200) {
            return null;
        }
        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['access_token'])) {
            return null;
        }

        return ['access_token' => (string) $json['access_token']];
    }

    /**
     * @return array{email: string, email_verified: bool, name?: string}|null
     */
    public function fetchUserInfo(string $accessToken): ?array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $codeHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($raw) || $raw === '' || $codeHttp !== 200) {
            return null;
        }
        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['email'])) {
            return null;
        }
        $verified = $json['email_verified'] ?? false;
        if (is_string($verified)) {
            $verified = strtolower($verified) === 'true';
        }

        $out = [
            'email' => strtolower(trim((string) $json['email'])),
            'email_verified' => (bool) $verified,
        ];
        if (!empty($json['name'])) {
            $out['name'] = (string) $json['name'];
        }

        return $out;
    }
}
