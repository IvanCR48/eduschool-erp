@echo off
echo Reiniciando servidor Apache...
echo.

echo Deteniendo Apache...
net stop Apache2.4

echo Esperando 3 segundos...
timeout /t 3 /nobreak > nul

echo Iniciando Apache...
net start Apache2.4

echo.
echo Servidor reiniciado correctamente!
echo.
echo Ahora puedes probar el sistema en: http://localhost/SistemaAdmin/
echo.
pause
