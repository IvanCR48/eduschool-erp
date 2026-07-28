<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';
require_once __DIR__ . '/../includes/familia_portal.php';

sistema_admin_send_html_security_headers();

if (!familia_portal_sesion_activa()) {
    header('Location: portal.php', true, 302);
    exit;
}

$key = familia_portal_session_key();
$resumen = $_SESSION[$key]['resumen_hijos'] ?? null;
$ids = $_SESSION[$key]['estudiante_ids'] ?? [];

if (!is_array($resumen) || $resumen === []) {
    $resumen = [];
    foreach ($ids as $eid) {
        $resumen[] = ['id' => (int) $eid, 'apellido' => '', 'nombre' => 'Estudiante #' . (int) $eid];
    }
}

$pageTitle = 'Elegir estudiante - Portal familias';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f0f9ff; min-height: 100vh; padding: 2rem 1rem; }
        .fam-sel { max-width: 520px; margin: 0 auto; }
        .fam-sel h1 { font-size: 1.35rem; margin-bottom: 1rem; color: #0f172a; }
        .fam-sel p { color: #64748b; margin-bottom: 1.25rem; font-size: 0.95rem; }
        .fam-card {
            display: block; background: #fff; border-radius: 12px; padding: 1rem 1.15rem;
            margin-bottom: 0.65rem; text-decoration: none; color: #0f172a;
            border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06);
            transition: border-color .2s, box-shadow .2s;
        }
        .fam-card:hover { border-color: #0ea5e9; box-shadow: 0 4px 14px rgba(14,165,233,.15); }
        .fam-card strong { display: block; font-size: 1.05rem; }
        .fam-out { margin-top: 1.5rem; text-align: center; }
        .fam-out a { color: #0284c7; font-weight: 600; }
    </style>
</head>
<body>
    <div class="fam-sel">
        <h1><i class="fas fa-users" aria-hidden="true"></i> Tus estudiantes</h1>
        <p>Elegí a quién querés ver. El acceso está vinculado al DNI que ingresaste.</p>
        <?php foreach ($resumen as $h): ?>
            <a class="fam-card" href="../student_profile.php?id=<?php echo (int) ($h['id'] ?? 0); ?>">
                <strong><?php echo htmlspecialchars(trim(($h['apellido'] ?? '') . ', ' . ($h['nombre'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span style="font-size:.85rem;color:#64748b;"><?php echo htmlspecialchars(__('auto.ver_ficha_y_bolet_n'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
        <div class="fam-out">
            <a href="familia_logout.php"><i class="fas fa-sign-out-alt"></i><?php echo htmlspecialchars(__('auto.salir'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </div>
</body>
</html>
