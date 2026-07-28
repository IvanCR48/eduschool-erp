@echo off
echo =====================================================
echo INSTALACION SISTEMA ADMINISTRATIVO COMPLETO
echo E.E.S.T. N°2 "Educacion y Trabajo"
echo =====================================================
echo.
echo Este script instalara la base de datos completa del sistema
echo incluyendo todos los modulos y datos de ejemplo.
echo.
echo IMPORTANTE: Se creara una nueva base de datos.
echo Si ya existe una base de datos con el mismo nombre,
echo se eliminara y se creara una nueva.
echo.
set /p continuar="¿Deseas continuar? (S/N): "
if /i not "%continuar%"=="S" (
    echo Instalacion cancelada.
    pause
    exit /b
)

echo.
echo Instalando sistema completo...
echo.

REM Eliminar base de datos existente si existe
echo Eliminando base de datos existente...
mysql -u root -p -e "DROP DATABASE IF EXISTS school_admin;"

REM Crear e instalar nueva base de datos
echo Creando nueva base de datos...
mysql -u root -p < database\sistema_completo.sql

if %errorlevel% == 0 (
    echo.
    echo =====================================================
    echo INSTALACION COMPLETADA EXITOSAMENTE
    echo =====================================================
    echo.
    echo El sistema administrativo ha sido instalado correctamente.
    echo.
    echo CREDENCIALES DE ACCESO:
    echo =====================================================
    echo.
    echo ADMINISTRADOR:
    echo Usuario: admin
    echo Contraseña: admin123
    echo.
    echo DIRECTOR:
    echo Usuario: 12345678
    echo Contraseña: admin123
    echo.
    echo PRECEPTOR:
    echo Usuario: 87654321
    echo Contraseña: admin123
    echo.
    echo SECRETARIA:
    echo Usuario: 11223344
    echo Contraseña: admin123
    echo.
    echo =====================================================
    echo.
    echo IMPORTANTE: Cambiar todas las contraseñas despues
    echo del primer login por seguridad.
    echo.
    echo URL de acceso:
    echo http://localhost/SistemaAdmin/public/login.php
    echo.
    echo DATOS INCLUIDOS:
    echo - 4 especialidades tecnicas
    echo - 16 cursos (4 años x 4 especialidades)
    echo - 6 profesores de ejemplo
    echo - 13 materias (generales y tecnicas)
    echo - 5 estudiantes de ejemplo
    echo - 7 horarios de ejemplo
    echo - 8 notas de ejemplo
    echo - 3 periodos academicos 2024
    echo.
) else (
    echo.
    echo =====================================================
    echo ERROR EN LA INSTALACION
    echo =====================================================
    echo.
    echo Hubo un error durante la instalacion.
    echo Revisa que:
    echo 1. MySQL este ejecutandose
    echo 2. Tengas permisos de administrador
    echo 3. Las credenciales sean correctas
    echo.
)

echo.
pause

