<?php

declare(strict_types=1);

if (!function_exists('sistema_admin_error_handler_register')) {
    /**
     * Registra manejo global de errores/excepciones con salida amigable para usuario final.
     */
    function sistema_admin_error_handler_register(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $isDev = sistema_admin_is_dev_env();
        @ini_set('display_errors', $isDev ? '1' : '0');
        @ini_set('display_startup_errors', $isDev ? '1' : '0');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $exception = new ErrorException($message, 0, $severity, $file, $line);
            sistema_admin_render_friendly_error($exception);
            return true;
        });

        set_exception_handler(static function (Throwable $exception): void {
            sistema_admin_render_friendly_error($exception);
        });

        register_shutdown_function(static function (): void {
            $lastError = error_get_last();
            if ($lastError === null) {
                return;
            }
            $fatals = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($lastError['type'], $fatals, true)) {
                return;
            }
            $exception = new ErrorException(
                (string) ($lastError['message'] ?? 'Error fatal del sistema'),
                0,
                (int) ($lastError['type'] ?? E_ERROR),
                (string) ($lastError['file'] ?? ''),
                (int) ($lastError['line'] ?? 0)
            );
            sistema_admin_render_friendly_error($exception);
        });
    }
}

if (!function_exists('sistema_admin_is_dev_env')) {
    function sistema_admin_is_dev_env(): bool
    {
        $appEnv = strtolower((string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: ''));
        if (in_array($appEnv, ['dev', 'development', 'local'], true)) {
            return true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
    }
}

if (!function_exists('sistema_admin_support_email')) {
    function sistema_admin_support_email(): string
    {
        $email = trim((string) ($_ENV['SUPPORT_EMAIL'] ?? getenv('SUPPORT_EMAIL') ?: 'soporte@sistemaadmin.local'));
        return $email !== '' ? $email : 'soporte@sistemaadmin.local';
    }
}

if (!function_exists('sistema_admin_render_friendly_error')) {
    function sistema_admin_render_friendly_error(Throwable $exception, ?bool $isDev = null): void
    {
        $isDev = $isDev ?? sistema_admin_is_dev_env();
        $errorId = date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $supportEmail = sistema_admin_support_email();

        error_log('[SistemaAdmin][' . $errorId . '] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
        error_log($exception->getTraceAsString());

        if (!headers_sent()) {
            http_response_code(500);
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $wantsJson = str_contains($accept, 'application/json');
        if ($wantsJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            $payload = [
                'success' => false,
                'error' => 'Ocurrio un problema inesperado. Contacta a soporte.',
                'support_email' => $supportEmail,
                'error_id' => $errorId,
            ];
            if ($isDev) {
                $payload['debug'] = [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $showDebug = $isDev || strtolower((string) (getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? ''))) === 'true' || isset($_GET['debug']);

        if ($showDebug) {
            $msgText = $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>System Error</title>';
            echo '<style>body{margin:0;background:#f6f8fb;font-family:Arial,sans-serif;color:#1f2937}.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}.card{max-width:700px;width:100%;background:#fff;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:28px}.title{margin:0 0 10px;font-size:24px;color:#dc2626}.msg{margin:0 0 14px;line-height:1.5;font-family:monospace;background:#f1f5f9;padding:12px;border-radius:6px;word-break:break-all}.meta{font-size:13px;color:#6b7280}</style></head><body>';
            echo '<div class="wrap"><div class="card"><h1 class="title">Application Error (500)</h1>';
            echo '<div class="msg">' . htmlspecialchars($msgText, ENT_QUOTES, 'UTF-8') . '</div>';
            echo '<pre style="font-size:11px;overflow:auto;max-height:200px">' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
            echo '</div></div></body></html>';
            exit;
        }

        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars(__('auto.error_del_sistema'), ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<style>body{margin:0;background:#f6f8fb;font-family:Arial,sans-serif;color:#1f2937}.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}.card{max-width:560px;width:100%;background:#fff;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:28px}.title{margin:0 0 10px;font-size:24px}.msg{margin:0 0 14px;line-height:1.5}.meta{font-size:13px;color:#6b7280}.mail{font-weight:700;color:#1d4ed8;text-decoration:none}</style></head><body>';
        echo '<div class="wrap"><div class="card"><h1 class="title">' . htmlspecialchars(__('auto.ups_ocurrio_un_problema'), ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p class="msg">' . htmlspecialchars(__('auto.no_te_preocupes_ya_registramos_el_incidente'), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p class="meta">' . htmlspecialchars(__('auto.soporte'), ENT_QUOTES, 'UTF-8') . ' <a class="mail" href="mailto:' . htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') . '</a><br>ID de error: ' . htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</div></div></body></html>';
        exit;
    }
}
