#!/bin/sh
# Health check script para el contenedor

# Verificar que PHP-FPM esté funcionando
if ! pgrep php-fpm > /dev/null; then
    echo "ERROR: PHP-FPM no está funcionando"
    exit 1
fi

# Verificar que Nginx esté funcionando
if ! pgrep nginx > /dev/null; then
    echo "ERROR: Nginx no está funcionando"
    exit 1
fi

# Verificar conectividad a la base de datos
if ! php -r "
try {
    \$pdo = new PDO('mysql:host=database;dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
    \$pdo->query('SELECT 1');
    echo 'Database connection OK';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage();
    exit(1);
}
"; then
    echo "ERROR: No se puede conectar a la base de datos"
    exit 1
fi

# Verificar que los archivos críticos existan
if [ ! -f "/var/www/html/index.php" ]; then
    echo "ERROR: index.php no encontrado"
    exit 1
fi

if [ ! -f "/var/www/html/src/autoload.php" ]; then
    echo "ERROR: autoload.php no encontrado"
    exit 1
fi

echo "Health check passed"
exit 0
