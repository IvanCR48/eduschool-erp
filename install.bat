@echo off
REM Script de instalación automática para Sistema de Gestión Escolar (Windows)
REM Este script configura automáticamente el entorno de desarrollo en Windows

echo 🚀 Instalando Sistema de Gestión Escolar...
echo ==================================

REM Verificar si estamos en el directorio correcto
if not exist "composer.json" (
    echo ✗ No se encontró composer.json. Ejecuta este script desde la raíz del proyecto.
    pause
    exit /b 1
)

REM 1. Verificar dependencias
echo.
echo 📋 Verificando dependencias...

REM Verificar PHP
php --version >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ PHP encontrado
) else (
    echo ✗ PHP no está instalado. Instala XAMPP o PHP 8.1+
    pause
    exit /b 1
)

REM Verificar Composer
composer --version >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Composer encontrado
) else (
    echo ✗ Composer no está instalado. Instala Composer desde https://getcomposer.org/
    pause
    exit /b 1
)

REM 2. Configurar archivo .env
echo.
echo ⚙️ Configurando variables de entorno...

if not exist "env.php" (
    if exist "env.production.example.php" (
        copy env.production.example.php env.php >nul
        echo ✓ env.php created from env.production.example.php
        echo ⚠ IMPORTANT: Edit env.php with your database credentials before continuing!
    ) else (
        echo ✗ env.production.example.php not found. Creating minimal env.php...
        (
            echo ^<?php return [
            echo     'SCHOOL_NAME' =^> 'Your School Name',
            echo     'APP_ENV' =^> 'production',
            echo     'APP_DEBUG' =^> 'false',
            echo     'APP_KEY' =^> 'CHANGE_ME_GENERATE_A_LONG_RANDOM_KEY',
            echo     'APP_URL' =^> 'http://localhost/SistemaAdmin',
            echo     'APP_BASE_PATH' =^> '/SistemaAdmin',
            echo     'DB_HOST' =^> 'localhost',
            echo     'DB_PORT' =^> '3306',
            echo     'DB_NAME' =^> 'school_admin',
            echo     'DB_USER' =^> 'root',
            echo     'DB_PASS' =^> '',
            echo     'SESSION_LIFETIME' =^> '120',
            echo     'MAX_LOGIN_ATTEMPTS' =^> '5',
            echo     'BACKUP_ENCRYPTION_KEY' =^> 'CHANGE_ME_BASE64_32_OR_MORE',
            echo     'BACKUP_DOWNLOAD_SECRET' =^> 'CHANGE_ME_BASE64_32_OR_MORE',
            echo     'SUPPORT_EMAIL' =^> 'admin@yourschool.edu',
            echo     'LOG_LEVEL' =^> 'error',
            echo ];
        ) > env.php
        echo ✓ Minimal env.php created — please edit it now!
    )
) else (
    echo ✓ env.php already exists
)

REM 3. Instalar dependencias de Composer
echo.
echo 📦 Instalando dependencias de Composer...

if exist "composer.json" (
    composer install --no-dev --optimize-autoloader
    echo ✓ Dependencias de Composer instaladas
) else (
    echo ✗ No se encontró composer.json
    pause
    exit /b 1
)

REM 4. Crear directorios necesarios
echo.
echo 📁 Configurando estructura de directorios...

if not exist "logs" mkdir logs
if not exist "backups" mkdir backups
if not exist "public\logs" mkdir public\logs
if not exist "admin\logs" mkdir admin\logs

REM Crear archivos .gitkeep
echo. > logs\.gitkeep
echo. > backups\.gitkeep
echo. > public\logs\.gitkeep
echo. > admin\logs\.gitkeep

echo ✓ Estructura de directorios configurada

REM 5. Verificar instalación
echo.
echo 🔍 Verificando instalación...

REM Verificar archivos críticos
set "critical_files=index.php config\database.php src\EnvLoader.php .env"
for %%f in (%critical_files%) do (
    if exist "%%f" (
        echo ✓ Archivo crítico encontrado: %%f
    ) else (
        echo ✗ Archivo crítico faltante: %%f
    )
)

REM 6. Show final info
echo.
echo 🎉 Installation complete!
echo ==========================
echo.
echo 📋 Next steps:
echo 1. Edit env.php with your database credentials (DB_NAME, DB_USER, DB_PASS)
echo 2. Create database: mysql -u root -p -e "CREATE DATABASE school_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo 3. Import schema:   mysql -u root -p school_admin ^< database\school_admin.sql
echo 4. Import demo data: mysql -u root -p school_admin ^< database\demo_data.sql
echo 5. Open in browser: http://localhost/SistemaAdmin
echo.
echo 👤 Demo login credentials (password: admin123 for all):
echo    Admin:     admin@escuela.edu
echo    Director:  director@greenfield.edu
echo    Preceptor: preceptor@greenfield.edu
echo    Teacher:   p.williams@greenfield.edu
echo.
echo ⚠  IMPORTANT: Change passwords after first login in a production environment!
echo.
echo 📚 Documentation:
echo    - README.md:        Quick start guide
echo    - INSTALLATION.md:  Full step-by-step guide
echo    - docs\:            Complete technical documentation
echo.

echo ✓ Instalación exitosa! 🚀
pause

