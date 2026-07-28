<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';

use SistemaAdmin\Services\AuthCacheService;
use SistemaAdmin\Services\GoogleOAuthService;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioLogging;
use SistemaAdmin\Services\ServicioSeguridad;
use SistemaAdmin\Services\UsuarioRepository;

$databaseAdapter = sistema_admin_db_adapter();
$servicioSeguridad = new ServicioSeguridad($databaseAdapter);
$servicioSeguridad->configurarHeadersSeguridad();

$err = (string) (filter_input(INPUT_GET, 'error', FILTER_DEFAULT) ?? '');
if ($err !== '') {
    header('Location: login.php?error=google_denegado');
    exit();
}

$stateGet = (string) (filter_input(INPUT_GET, 'state', FILTER_DEFAULT) ?? '');
$code = (string) (filter_input(INPUT_GET, 'code', FILTER_DEFAULT) ?? '');
$stateSes = (string) ($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);

if ($stateGet === '' || $stateSes === '' || !hash_equals($stateSes, $stateGet) || $code === '') {
    header('Location: login.php?error=google_estado_invalido');
    exit();
}

$config = GoogleOAuthService::loadLocalConfig();
$oauth = new GoogleOAuthService($config ?? []);
if (!$oauth->isConfigured()) {
    header('Location: login.php?error=google_oauth_no_config');
    exit();
}

$tokens = $oauth->exchangeAuthorizationCode($code);
if ($tokens === null) {
    header('Location: login.php?error=google_token');
    exit();
}

$userInfo = $oauth->fetchUserInfo($tokens['access_token']);
if ($userInfo === null || !$userInfo['email_verified']) {
    header('Location: login.php?error=google_email_no_verificado');
    exit();
}

$emailNorm = $userInfo['email'];

$usuarioRepo = new UsuarioRepository($databaseAdapter);
$profRow = $databaseAdapter->fetch(
    'SELECT id, dni, nombre, apellido, email FROM profesores
     WHERE LOWER(TRIM(email)) = ? AND activo = 1 LIMIT 1',
    [$emailNorm]
);
if ($profRow === null) {
    $usuario = $usuarioRepo->findActiveByEmailNormalized($emailNorm);
    if ($usuario !== null && strtolower((string) ($usuario['rol'] ?? '')) === 'profesor') {
        $dniProf = trim((string) ($usuario['dni'] ?? ''));
        $profActual = $databaseAdapter->fetch(
            'SELECT email FROM profesores WHERE dni = ? AND activo = 1 LIMIT 1',
            [$dniProf]
        );
        if ($profActual !== null && strtolower(trim((string) $profActual['email'])) !== strtolower(trim($emailNorm))) {
            header('Location: login.php?error=google_email_en_desuso');
            exit();
        }
    }
    header('Location: login.php?error=google_profesor_no_registrado');
    exit();
}

$usuario = $usuarioRepo->findActiveByEmailNormalized($emailNorm);
$dniProf = trim((string) ($profRow['dni'] ?? ''));
$nombreProf = trim((string) ($profRow['nombre'] ?? ''));
$apellidoProf = trim((string) ($profRow['apellido'] ?? ''));

if ($usuario !== null) {
    if (strtolower((string) ($usuario['rol'] ?? '')) !== 'profesor' || trim((string) ($usuario['dni'] ?? '')) !== $dniProf) {
        header('Location: login.php?error=google_email_en_uso');
        exit();
    }
}

if ($usuario === null) {
    $dummyHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ]);
    if ($dniProf === '') {
        header('Location: login.php?error=google_profesor_sin_dni');
        exit();
    }
    $newId = $databaseAdapter->insert(
        'INSERT INTO usuarios (dni, password_hash, nombre, apellido, email, rol, activo, must_change_password)
         VALUES (?, ?, ?, ?, ?, \'profesor\', 1, 0)',
        [$dniProf, $dummyHash, $nombreProf, $apellidoProf, $emailNorm]
    );
    $usuario = $usuarioRepo->findById($newId);
} else {
    $uid = (int) $usuario['id'];
    $databaseAdapter->query(
        'UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, rol = \'profesor\', activo = 1, must_change_password = 0 WHERE id = ?',
        [$nombreProf, $apellidoProf, $dniProf, $uid]
    );
    $usuario = $usuarioRepo->findById($uid);
}

if ($usuario === null) {
    header('Location: login.php?error=google_usuario');
    exit();
}

$usuarioId = (int) $usuario['id'];
$authCache = new AuthCacheService($databaseAdapter);
$authCache->invalidateUserCache($usuarioId, (string) ($usuario['dni'] ?? ''));

if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}

$_SESSION['usuario_id'] = $usuarioId;
$_SESSION['username'] = (string) ($usuario['dni'] ?? '');
$_SESSION['rol'] = 'profesor';
$_SESSION['nombre'] = (string) ($usuario['nombre'] ?? $nombreProf);
$_SESSION['apellido'] = (string) ($usuario['apellido'] ?? $apellidoProf);
$_SESSION['email'] = $emailNorm;
$_SESSION['login_via'] = 'google_oauth';
// Los docentes con Google nunca deben cambiar contraseña: usan la de su cuenta Google.
$_SESSION['must_change_password'] = 0;

$servicioAutenticacion = new ServicioAutenticacion($databaseAdapter);
$servicioAutenticacion->sincronizarAlcancePreceptor();
$servicioAutenticacion->sincronizarCargoEquipoDirectivo();
$servicioAutenticacion->sincronizarAlcanceProfesor();
$servicioAutenticacion->actualizarUltimoAcceso($usuarioId);

$servicioLogging = new ServicioLogging($databaseAdapter);
$servicioLogging->registrarEventoSeguridad(
    'LOGIN_SUCCESS',
    'Login docente vía Google OAuth',
    ['user_id' => $usuarioId, 'email' => $emailNorm]
);

$destInicio = (($_SESSION['rol'] ?? '') === 'profesor')
    ? app_base_path('/courses.php')
    : app_base_path('/');
header('Location: ' . $destInicio);
exit();
