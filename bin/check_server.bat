@echo off
echo Verificando estado del servidor...
echo.

echo Estado de Apache:
sc query Apache2.4

echo.
echo Estado de MySQL:
sc query MySQL

echo.
echo Puerto 80 (Apache):
netstat -an | findstr :80

echo.
echo Puerto 3306 (MySQL):
netstat -an | findstr :3306

echo.
pause
