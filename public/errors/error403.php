<?php

declare(strict_types=1);

require_once __DIR__ . '/error_page_init.php';

http_response_code(403);

$hrefCss = app_base_path('css/style.css');
$hrefIcon = app_base_path('img/logo-school.png');
$hrefHome = app_base_path('/');
$hrefLogin = app_base_path('/public/login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Sistema Escolar</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($hrefCss, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($hrefIcon, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="container">
        <div class="error-page">
            <div class="error-content">
                <div class="error-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1><?php echo htmlspecialchars(__('auto.403_acceso_denegado'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars(__('auto.no_tienes_permisos_para_acceder_a_este_recurs'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="error-actions">
                    <a href="<?php echo htmlspecialchars($hrefHome, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                        <i class="fas fa-home"></i><?php echo htmlspecialchars(__('auto.ir_al_inicio'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <a href="<?php echo htmlspecialchars($hrefLogin, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i><?php echo htmlspecialchars(__('auto.iniciar_sesi_n'), ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
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
</body>
</html>
