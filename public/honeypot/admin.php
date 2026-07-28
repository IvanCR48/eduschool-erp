<?php
/**
 * HONEYPOT - Fake Admin Panel
 * Este archivo está diseñado para detectar ataques automatizados
 * y bots que buscan paneles de administración comunes.
 */

// Iniciar sesión para tracking
session_start();

// Headers de seguridad
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Log de acceso sospechoso
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'honeypot' => 'admin_panel',
    'suspicious' => true
];

// Escribir a log de honeypot
$logFile = __DIR__ . '/../../logs/honeypot.log';
$logLine = json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n";
file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// Si hay intento de login, log adicional
if ($_POST) {
    $loginAttempt = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'honeypot' => 'admin_login_attempt',
        'post_data' => $_POST,
        'severity' => 'HIGH'
    ];
    
    $loginLog = json_encode($loginAttempt, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $loginLog, FILE_APPEND | LOCK_EX);
    
    // Simular delay para parecer real
    sleep(2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('auto.panel_de_administracion'), ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: #5a6fd8;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔐 Panel de Administración</h1>
        </div>
        
        <div class="error-message" id="errorMessage"><?php echo htmlspecialchars(__('auto.usuario_o_contrase_a_incorrectos'), ENT_QUOTES, 'UTF-8'); ?></div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><?php echo htmlspecialchars(__('auto.usuario'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password"><?php echo htmlspecialchars(__('auto.contrase_a'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn"><?php echo htmlspecialchars(__('auto.iniciar_sesi_n'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
        
        <div class="warning">
            ⚠️ <strong><?php echo htmlspecialchars(__('auto.honeypot_activo'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
            Este es un panel de administración falso diseñado para detectar ataques automatizados.
            Cualquier intento de acceso será registrado y monitoreado.
        </div>
    </div>

    <script>
        // Simular comportamiento real del panel
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.style.display = 'block';
            
            // Simular delay de autenticación
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        });
        
        // Detectar herramientas de automatización
        if (window.phantom || window._phantom || window.callPhantom) {
            // PhantomJS detectado
            document.body.innerHTML = '<div style="text-align:center;padding:50px;"><?php echo htmlspecialchars(__('auto.cargando'), ENT_QUOTES, 'UTF-8'); ?></div>';
        }
        
        // Detectar Selenium
        if (window.document.$cdc_asdjflasutopfhvcZLmcfl_ || window.document.$chrome_asyncScriptInfo) {
            document.body.innerHTML = '<div style="text-align:center;padding:50px;">Cargando...</div>';
        }
    </script>
</body>
</html>
