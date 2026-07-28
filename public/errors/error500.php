<?php

declare(strict_types=1);

require_once __DIR__ . '/error_page_init.php';

http_response_code(500);

$hrefCss = app_base_path('css/style.css');
$hrefIcon = app_base_path('img/logo-school.png');
$hrefHome = app_base_path('/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Servidor - Sistema Escolar</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($hrefCss, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hrefIcon, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="container">
        <div class="error-page">
            <div class="error-content">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1><?php echo htmlspecialchars(__('auto.500_error_del_servidor'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars(__('auto.ha_ocurrido_un_error_interno_del_servidor_nu'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="error-actions">
                    <a href="<?php echo htmlspecialchars($hrefHome, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                        <i class="fas fa-home"></i><?php echo htmlspecialchars(__('auto.ir_al_inicio'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <button type="button" id="error500-reload" class="btn btn-secondary">
                        <i class="fas fa-redo"></i><?php echo htmlspecialchars(__('auto.reintentar'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    /* Normalizar layout para evitar desplazamientos hacia la derecha */
    html, body { margin: 0; padding: 0; }
    .container { margin: 0 auto; padding: 0; max-width: 100%; }
    .error-page {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .error-content {
        text-align: center;
        background: white;
        padding: 3rem;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        max-width: 500px;
        width: 90%;
    }
    
    .error-icon {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }
    
    .error-content h1 {
        color: #dc3545;
        margin-bottom: 1rem;
        font-size: 2rem;
    }
    
    .error-content p {
        color: #6c757d;
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }
    
    .error-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #545b62;
        transform: translateY(-2px);
    }
    </style>
    <script>
    document.getElementById('error500-reload').addEventListener('click', function () { location.reload(); });
    </script>
</body>
</html>
