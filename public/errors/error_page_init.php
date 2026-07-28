<?php

declare(strict_types=1);

/**
 * Carga APP_BASE_PATH (.env) y helpers HTTP para páginas de error bajo public/errors/.
 * Sin sesión ni BD.
 */
require_once __DIR__ . '/../../src/EnvLoader.php';
EnvLoader::load();

require_once __DIR__ . '/../../includes/sistema_admin_http.php';
