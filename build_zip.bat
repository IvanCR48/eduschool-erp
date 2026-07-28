@echo off
REM =============================================================
REM EduSchool ERP — Clean Distributable Zip Builder
REM =============================================================
REM This script creates a clean, Codester-ready zip package.
REM Run from the project root: build_zip.bat
REM =============================================================

echo.
echo =============================================
echo  EduSchool ERP — Build Distributable Zip
echo =============================================
echo.

REM Check we're in the right directory
if not exist "composer.json" (
    echo ERROR: Run this script from the SistemaAdmin project root.
    pause
    exit /b 1
)

REM Define output name
set "VERSION=2.1.1"
set "OUT_DIR=%~dp0..\EduSchool-ERP-v%VERSION%"
set "ZIP_NAME=EduSchool-ERP-v%VERSION%.zip"

REM Check for 7-Zip (preferred) or PowerShell fallback
set "SEVEN_ZIP="
if exist "C:\Program Files\7-Zip\7z.exe" set "SEVEN_ZIP=C:\Program Files\7-Zip\7z.exe"
if exist "C:\Program Files (x86)\7-Zip\7z.exe" set "SEVEN_ZIP=C:\Program Files (x86)\7-Zip\7z.exe"

echo [1/4] Cleaning previous build output...
if exist "%OUT_DIR%" rmdir /s /q "%OUT_DIR%"
mkdir "%OUT_DIR%"
echo     Done.

echo.
echo [2/4] Copying project files (excluding dev artifacts)...

REM Use xcopy to copy all files
xcopy /e /i /q /y "." "%OUT_DIR%\SistemaAdmin\" ^
  /exclude:build_exclude.txt

echo     Done.

echo.
echo [3/4] Removing excluded paths from build output...

REM Remove development/internal directories
set "B=%OUT_DIR%\SistemaAdmin"

if exist "%B%\.git"                                    rmdir /s /q "%B%\.git"
if exist "%B%\.github"                                 rmdir /s /q "%B%\.github"
if exist "%B%\school-management-system-codester"       rmdir /s /q "%B%\school-management-system-codester"
if exist "%B%\scratch"                                 rmdir /s /q "%B%\scratch"
if exist "%B%\vendor\phpunit"                          rmdir /s /q "%B%\vendor\phpunit"
if exist "%B%\vendor\squizlabs"                        rmdir /s /q "%B%\vendor\squizlabs"
if exist "%B%\vendor\phpstan"                          rmdir /s /q "%B%\vendor\phpstan"
if exist "%B%\vendor\symfony\var-dumper"               rmdir /s /q "%B%\vendor\symfony\var-dumper"
if exist "%B%\vendor\roave"                            rmdir /s /q "%B%\vendor\roave"

REM Remove sensitive/dev files
if exist "%B%\env.php"                                 del /q "%B%\env.php"
if exist "%B%\composer.phar"                           del /q "%B%\composer.phar"
if exist "%B%\.env"                                    del /q "%B%\.env"
if exist "%B%\composer-audit.json"                     del /q "%B%\composer-audit.json"
if exist "%B%\school-management-system-codester.zip"   del /q "%B%\school-management-system-codester.zip"
if exist "%B%\datos_prueba_escuela.sql"                del /q "%B%\datos_prueba_escuela.sql"
if exist "%B%\despliegue_linux.md"                     del /q "%B%\despliegue_linux.md"
if exist "%B%\ESTRUCTURA_CARPETAS.md"                  del /q "%B%\ESTRUCTURA_CARPETAS.md"
if exist "%B%\ESTRUCTURA_PROYECTO.md"                  del /q "%B%\ESTRUCTURA_PROYECTO.md"

REM Clear log and backup contents (keep directories)
for /r "%B%\logs" %%f in (*.log *.txt) do del /q "%%f" 2>nul
for /r "%B%\backups" %%f in (*.sql *.zip *.enc) do del /q "%%f" 2>nul
for /r "%B%\public\logs" %%f in (*.log *.txt) do del /q "%%f" 2>nul

REM Clear upload contents (keep directory)
for /r "%B%\uploads" %%f in (*) do del /q "%%f" 2>nul

echo     Done.

echo.
echo [4/4] Creating zip archive...

if defined SEVEN_ZIP (
    echo     Using 7-Zip...
    "%SEVEN_ZIP%" a -tzip "..\%ZIP_NAME%" "%OUT_DIR%\*" -mx=5
) else (
    echo     Using PowerShell Compress-Archive...
    powershell -Command "Compress-Archive -Path '%OUT_DIR%\*' -DestinationPath '..\%ZIP_NAME%' -Force"
)

echo.
echo =============================================
echo  BUILD COMPLETE
echo =============================================
echo.
echo  Output zip: ..\%ZIP_NAME%
echo.
echo  CHECKLIST before submitting to Codester:
echo  [x] env.php removed (buyers create their own)
echo  [x] composer.phar removed
echo  [x] .git / .github removed
echo  [x] scratch/ dev files removed
echo  [x] school-management-system-codester/ removed
echo  [x] logs/ and backups/ contents cleared
echo  [x] datos_prueba_escuela.sql replaced by demo_data.sql
echo  [x] vendor dev packages removed
echo.
echo  Verify zip contents before upload!
echo.
pause
