<?php

declare(strict_types=1);

/**
 * POST: valida CSRF y DNI del responsable; abre sesión portal familias y redirige a ficha o selección.
 */

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/csrf_functions.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';
require_once __DIR__ . '/../includes/familia_portal.php';

use SistemaAdmin\Services\FamiliaPortalService;

sistema_admin_send_html_security_headers();

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Location: portal.php', true, 302);
    exit;
}

$csrf = (string) (filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT) ?? '');
if (!verifyCSRFToken($csrf)) {
    header('Location: portal.php?familia_error=' . rawurlencode('No se pudo validar la solicitud. Actualizá la página e intentá de nuevo.'), true, 302);
    exit;
}

$dniRaw = trim((string) (filter_input(INPUT_POST, 'dni_responsable', FILTER_DEFAULT) ?? ''));
$dniNorm = familia_portal_normalizar_dni($dniRaw);
if (strlen($dniNorm) < 7 || strlen($dniNorm) > 8) {
    header('Location: portal.php?familia_error=' . rawurlencode('Ingresá un DNI válido (7 u 8 dígitos).'), true, 302);
    exit;
}

$intentos = (int) ($_SESSION['familia_login_intentos'] ?? 0);
$ultimo = (int) ($_SESSION['familia_login_ultimo'] ?? 0);
if ($intentos >= 10 && time() - $ultimo < 900) {
    header('Location: portal.php?familia_error=' . rawurlencode('Demasiados intentos. Probá de nuevo en unos minutos.'), true, 302);
    exit;
}

$db = sistema_admin_db_adapter();
$svc = new FamiliaPortalService($db);
$matches = $svc->buscarEstudiantesPorDniResponsable($dniNorm);

if ($matches === []) {
    $_SESSION['familia_login_intentos'] = $intentos + 1;
    $_SESSION['familia_login_ultimo'] = time();
    header('Location: portal.php?familia_error=' . rawurlencode('No hay estudiantes asociados a ese DNI. Si recién cargaron tus datos, verificá el número o consultá en secretaría.'), true, 302);
    exit;
}

unset($_SESSION['familia_login_intentos'], $_SESSION['familia_login_ultimo']);

familia_portal_establecer_sesion($dniNorm, array_column($matches, 'id'));
if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}

if (count($matches) === 1) {
    header('Location: ../student_profile.php?id=' . (int) $matches[0]['id'], true, 302);
    exit;
}

$_SESSION[familia_portal_session_key()]['resumen_hijos'] = $matches;
header('Location: familia_seleccion.php', true, 302);
exit;
