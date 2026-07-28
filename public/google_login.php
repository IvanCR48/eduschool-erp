<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/sistema_admin_session.php';

use SistemaAdmin\Services\GoogleOAuthService;

$config = GoogleOAuthService::loadLocalConfig();
$oauth = new GoogleOAuthService($config ?? []);
if (!$oauth->isConfigured()) {
    header('Location: login.php?error=google_oauth_no_config');
    exit();
}

$state = bin2hex(random_bytes(24));
$_SESSION['google_oauth_state'] = $state;

header('Location: ' . $oauth->buildAuthorizationUrl($state));
exit();
