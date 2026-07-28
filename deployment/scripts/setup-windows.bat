@echo off
REM Script de configuración para Windows
echo 🚀 Configurando Sistema de Gestión Escolar en Windows...

REM Verificar Docker
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker no está instalado. Por favor instala Docker Desktop.
    pause
    exit /b 1
)

REM Verificar Docker Compose
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose no está instalado.
    pause
    exit /b 1
)

echo ✅ Docker y Docker Compose encontrados

REM Crear archivo .env si no existe
if not exist ".env" (
    if exist "env.example" (
        copy env.example .env
        echo ✅ Archivo .env creado desde env.example
        echo ⚠️  Por favor, edita el archivo .env con tus configuraciones
    ) else (
        echo ❌ No se encontró env.example
        pause
        exit /b 1
    )
)

REM Cambiar al directorio de Docker
cd deployment\docker

REM Construir y ejecutar contenedores
echo 🔨 Construyendo contenedores...
docker-compose down 2>nul
docker-compose up --build -d

REM Esperar a que los servicios estén listos
echo ⏳ Esperando a que los servicios estén listos...
timeout /t 30 /nobreak >nul

REM Verificar estado
echo 📊 Verificando estado de los servicios...
docker-compose ps

REM Volver al directorio raíz
cd ..\..\..

REM Verificar aplicación
echo 🌐 Verificando aplicación...
curl -f http://localhost:8080/health >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Sistema de Gestión Escolar está funcionando correctamente!
    echo 🌐 Aplicación disponible en: http://localhost:8080
    echo 👤 Usuario admin: admin / admin123
    echo 👤 Usuario director: director / director123
) else (
    echo ⚠️  La aplicación puede estar iniciando. Intenta acceder a http://localhost:8080 en unos minutos.
)

echo.
echo 📝 Comandos útiles:
echo    Ver logs: docker-compose logs -f
echo    Detener: docker-compose down
echo    Reiniciar: docker-compose restart
echo.
pause

