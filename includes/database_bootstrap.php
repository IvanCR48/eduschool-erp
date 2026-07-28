<?php

/**
 * Conexión a BD por request (PdoDatabase + DatabaseAdapter), sin Database::getInstance().
 */

declare(strict_types=1);

use SistemaAdmin\Adapters\DatabaseAdapter;
use SistemaAdmin\Adapters\PdoDatabase;
use SistemaAdmin\Contracts\DatabaseInterface;

require_once __DIR__ . '/sistema_admin_autoload.php';
sistema_admin_load_autoload();

require_once __DIR__ . '/sistema_admin_error_handler.php';
sistema_admin_error_handler_register();

use SistemaAdmin\Bootstrap\AppRequestInit;

/**
 * Devuelve el mismo DatabaseAdapter durante toda la petición HTTP.
 */
function sistema_admin_db_adapter(): DatabaseInterface
{
    static $adapter = null;
    if ($adapter === null) {
        $adapter = new DatabaseAdapter(PdoDatabase::createFromEnv());
        AppRequestInit::applyFromDatabase($adapter);
    }

    return $adapter;
}

/**
 * Devuelve el objeto PDO directamente de la conexión del adaptador.
 */
function sistema_admin_pdo(): PDO
{
    return sistema_admin_db_adapter()->getPdo();
}
