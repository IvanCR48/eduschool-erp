#!/bin/bash

# Script de instalación automática para Sistema de Gestión Escolar
# Este script configura automáticamente el entorno de desarrollo

set -e  # Salir si hay algún error

echo "🚀 Instalando Sistema de Gestión Escolar..."
echo "=================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
show_message() {
    echo -e "${GREEN}✓${NC} $1"
}

show_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

show_error() {
    echo -e "${RED}✗${NC} $1"
}

# Verificar si estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    show_error "No se encontró composer.json. Ejecuta este script desde la raíz del proyecto."
    exit 1
fi

# 1. Verificar dependencias
echo ""
echo "📋 Verificando dependencias..."

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    show_message "PHP $PHP_VERSION encontrado"
else
    show_error "PHP no está instalado. Instala PHP 8.1 o superior."
    exit 1
fi

# Verificar Composer
if command -v composer &> /dev/null; then
    show_message "Composer encontrado"
else
    show_error "Composer no está instalado. Instala Composer desde https://getcomposer.org/"
    exit 1
fi

# Verificar MySQL
if command -v mysql &> /dev/null; then
    show_message "MySQL encontrado"
else
    show_warning "MySQL no encontrado. Asegúrate de tener MySQL instalado y funcionando."
fi

# 2. Configurar archivo .env
echo ""
echo "⚙️ Configurando variables de entorno..."

if [ ! -f "env.php" ]; then
    if [ -f "env.production.example.php" ]; then
        cp env.production.example.php env.php
        show_message "env.php created from env.production.example.php"
        show_warning "IMPORTANT: Edit env.php with your database credentials before continuing!"
    else
        show_error "env.production.example.php not found. Creating minimal env.php..."
        cat > env.php << 'EOF'
<?php return [
    'SCHOOL_NAME'   => 'Your School Name',
    'SCHOOL_SLOGAN' => 'Excellence in Education',
    'APP_ENV'       => 'production',
    'APP_DEBUG'     => 'false',
    'APP_KEY'       => 'CHANGE_ME_GENERATE_A_LONG_RANDOM_KEY',
    'APP_URL'       => 'http://localhost/SistemaAdmin',
    'APP_BASE_PATH' => '/SistemaAdmin',
    'DB_HOST'       => 'localhost',
    'DB_PORT'       => '3306',
    'DB_NAME'       => 'school_admin',
    'DB_USER'       => 'root',
    'DB_PASS'       => '',
    'SESSION_LIFETIME'       => '120',
    'MAX_LOGIN_ATTEMPTS'     => '5',
    'BACKUP_ENCRYPTION_KEY'  => 'CHANGE_ME_BASE64_32_OR_MORE',
    'BACKUP_DOWNLOAD_SECRET' => 'CHANGE_ME_BASE64_32_OR_MORE',
    'SUPPORT_EMAIL' => 'admin@yourschool.edu',
    'LOG_LEVEL'     => 'error',
];
EOF
        show_message "Minimal env.php created - please edit it now!"
    fi
else
    show_message "env.php already exists"
fi

# 3. Instalar dependencias de Composer
echo ""
echo "📦 Instalando dependencias de Composer..."

if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
    show_message "Dependencias de Composer instaladas"
else
    show_error "No se encontró composer.json"
    exit 1
fi

# 4. Configurar permisos
echo ""
echo "🔐 Configurando permisos..."

# Crear directorios necesarios
mkdir -p logs
mkdir -p backups
mkdir -p public/logs
mkdir -p admin/logs
mkdir -p uploads

# Configurar permisos (Linux/Mac)
if [[ "$OSTYPE" != "msys" ]] && [[ "$OSTYPE" != "cygwin" ]] && [[ "$OSTYPE" != "win32" ]]; then
    find . -type d -exec chmod 755 {} \;
    find . -type f -exec chmod 644 {} \;
    # Hacer ejecutables todos los scripts de shell
    find . -name '*.sh' -exec chmod +x {} \;
    show_message "Scripts .sh marcados como ejecutables"
    # Directorios escribibles por PHP (www-data en Debian/Ubuntu)
    chmod -R 775 logs backups public/logs admin/logs uploads
    show_message "Permisos 775 aplicados a directorios escribibles"
    # Asignar propietario del servidor web si tenemos sudo
    if command -v chown &> /dev/null && id -u www-data &> /dev/null 2>&1; then
        chown -R www-data:www-data logs backups public/logs admin/logs reports uploads
        show_message "Propietario www-data asignado a directorios escribibles"
    else
        show_warning "No se pudo aplicar chown www-data (ejecuta manualmente si es necesario):"
        echo -e "   ${YELLOW}sudo chown -R www-data:www-data logs backups public/logs admin/logs reports uploads${NC}"
    fi
else
    show_message "Sistema Windows detectado - omitiendo configuración de permisos"
fi

# 5. Verificar base de datos
echo ""
echo "🗄️ Verificando conexión a base de datos..."

# Leer configuración de .env
if [ -f ".env" ]; then
    source .env 2>/dev/null || true
    
    # Intentar conectar a MySQL
    if command -v mysql &> /dev/null; then
        if mysql -h"${DB_HOST:-localhost}" -u"${DB_USER:-root}" -p"${DB_PASS:-}" -e "SELECT 1;" 2>/dev/null; then
            show_message "Conexión a base de datos exitosa"
            
            # Verificar si la base de datos existe
            if mysql -h"${DB_HOST:-localhost}" -u"${DB_USER:-root}" -p"${DB_PASS:-}" -e "USE ${DB_NAME:-school_admin};" 2>/dev/null; then
                show_message "Base de datos '${DB_NAME:-school_admin}' encontrada"
            else
                show_warning "Base de datos '${DB_NAME:-school_admin}' no existe"
                echo "Para crear la base de datos, ejecuta:"
                echo "mysql -u${DB_USER:-root} -p${DB_PASS:-} -e \"CREATE DATABASE ${DB_NAME:-school_admin};\""
                echo "mysql -u${DB_USER:-root} -p${DB_PASS:-} ${DB_NAME:-school_admin} < database/school_admin.sql"
            fi
        else
            show_warning "No se pudo conectar a la base de datos. Verifica las credenciales en .env"
        fi
    else
        show_warning "MySQL no encontrado. Instala MySQL para continuar."
    fi
fi

# 6. Crear archivos .gitkeep para directorios vacíos
echo ""
echo "📁 Configurando estructura de directorios..."

touch logs/.gitkeep
touch backups/.gitkeep
touch public/logs/.gitkeep
touch admin/logs/.gitkeep
show_message "Archivos .gitkeep creados"

# 7. Verificar instalación
echo ""
echo "🔍 Verificando instalación..."

# Verificar archivos críticos
CRITICAL_FILES=(
    "index.php"
    "config/database.php"
    "src/EnvLoader.php"
    "env.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        show_message "Archivo crítico encontrado: $file"
    else
        show_error "Archivo crítico faltante: $file"
    fi
done

# 8. Final summary
echo ""
echo "🎉 Installation complete!"
echo "=========================="
echo ""
echo "📋 Next steps:"
echo "1. Edit env.php with your database credentials (DB_NAME, DB_USER, DB_PASS)"
echo "2. Create DB:        mysql -u root -p -e \"CREATE DATABASE school_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
echo "3. Import schema:    mysql -u root -p school_admin < database/school_admin.sql"
echo "4. Import demo data: mysql -u root -p school_admin < database/demo_data.sql"
echo "5. Open in browser:  http://your-server/SistemaAdmin"
echo ""
echo "👤 Demo login credentials (password: admin123 for all):"
echo "   Admin:     admin@escuela.edu"
echo "   Director:  director@greenfield.edu"
echo "   Preceptor: preceptor@greenfield.edu"
echo "   Teacher:   p.williams@greenfield.edu"
echo ""
echo "⚠️  IMPORTANT: Change all passwords in a production environment!"
echo ""
echo "📚 Documentation:"
echo "   - README.md:       Quick start guide"
echo "   - INSTALLATION.md: Full step-by-step guide"
echo "   - docs/:           Technical documentation"
echo ""

show_message "Setup complete! 🚀"

