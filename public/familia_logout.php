<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/familia_portal.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    familia_portal_cerrar();
    session_regenerate_id(true);
}

header('Location: portal.php', true, 302);
exit;
