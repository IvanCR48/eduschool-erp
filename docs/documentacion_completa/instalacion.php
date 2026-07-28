<?php
$pageTitle = 'Instalación del Sistema - Documentación EEST2';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/SistemaAdmin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/SistemaAdmin/css/docs.css">
    <style>
        :root {
            --primary-color: #0ea5a3;
            --primary-dark: #0b7f7e;
            --secondary-color: #4b5563;
            --success-color: #16a34a;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #1f2937;
        }
        
        .doc-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .doc-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(14, 165, 163, 0.3);
        }
        
        .doc-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .doc-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .doc-content {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .doc-content h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .doc-content h3 {
            color: var(--secondary-color);
            font-size: 1.4rem;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .doc-content p {
            line-height: 1.8;
            margin-bottom: 1rem;
        }
        
        .code-block {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
        }
        
        .code-block code {
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 1.5rem 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .info-box ul,
        .info-box ol {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid var(--warning-color);
            padding: 1.5rem 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .warning-box ul,
        .warning-box ol {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .success-box {
            background: #d1fae5;
            border-left: 4px solid var(--success-color);
            padding: 1.5rem 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .success-box ul,
        .success-box ol {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }
        
        .step-list li {
            counter-increment: step-counter;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 10px;
            position: relative;
            padding-left: 5rem;
        }
        
        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 1.5rem;
            top: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .btn-back {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary-color), #374151);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(75, 85, 99, 0.4);
        }
        
        ul {
            line-height: 2;
        }
        
        li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="doc-container">
        <div class="doc-header">
            <h1><i class="fas fa-download"></i> Guía de Instalación</h1>
            <p>Instrucciones completas para instalar el Sistema Admin EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-check-circle"></i> Requisitos del Sistema</h2>
            
            <h3>Requisitos Mínimos</h3>
            <ul>
                <li><strong>PHP:</strong> 8.1 o superior</li>
                <li><strong>MySQL:</strong> 8.0 o superior</li>
                <li><strong>Apache/Nginx:</strong> Con mod_rewrite habilitado</li>
                <li><strong>Composer:</strong> Para gestión de dependencias PHP</li>
                <li><strong>Memoria RAM:</strong> 512MB mínimo</li>
                <li><strong>Espacio en disco:</strong> 2GB mínimo</li>
            </ul>
            
            <h3>Requisitos Recomendados</h3>
            <ul>
                <li><strong>PHP:</strong> 8.2+</li>
                <li><strong>MySQL:</strong> 8.0+ con motor InnoDB</li>
                <li><strong>Servidor:</strong> Nginx con PHP-FPM</li>
                <li><strong>Memoria RAM:</strong> 2GB o más</li>
                <li><strong>Docker:</strong> Para despliegue containerizado (opcional)</li>
            </ul>
            
            <h3>Extensiones PHP Requeridas</h3>
            <ul>
                <li>pdo_mysql</li>
                <li>mbstring</li>
                <li>openssl</li>
                <li>json</li>
                <li>curl</li>
                <li>zip</li>
                <li>gd</li>
            </ul>
            
            <h2><i class="fas fa-rocket"></i> Métodos de Instalación</h2>
            
            <h3>Opción 1: Instalación Automática (Recomendado)</h3>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Nota:</strong> El script automático configura todo lo necesario de forma rápida y segura.
            </div>
            
            <h4>En Windows (XAMPP):</h4>
            <ol class="step-list">
                <li>
                    <strong>Descarga el sistema</strong>
                    <p>Clona o descarga el repositorio en tu carpeta de XAMPP</p>
                    <div class="code-block"><code>cd C:\xampp\htdocs
git clone [repositorio] SistemaAdmin</code></div>
                </li>
                <li>
                    <strong>Ejecuta el instalador</strong>
                    <p>Haz doble clic en <code>install.bat</code> o ejecútalo desde cmd:</p>
                    <div class="code-block"><code>cd SistemaAdmin
install.bat</code></div>
                </li>
                <li>
                    <strong>Configura variables de entorno</strong>
                    <p>El instalador creará el archivo <code>.env</code>. Edita las credenciales de base de datos si es necesario.</p>
                </li>
                <li>
                    <strong>Importa la base de datos</strong>
                    <p>Desde phpMyAdmin o línea de comandos:</p>
                    <div class="code-block"><code>mysql -u root -p sistema_admin_eest2 < database/sistema_admin_eest2.sql</code></div>
                </li>
                <li>
                    <strong>Accede al sistema</strong>
                    <p>Abre tu navegador y ve a:</p>
                    <div class="code-block"><code>http://localhost/SistemaAdmin</code></div>
                </li>
            </ol>
            
            <h4>En Linux/Mac:</h4>
            <div class="code-block"><code># Hacer ejecutable el instalador
chmod +x install.sh

# Ejecutar instalación
./install.sh</code></div>
            
            <h3>Opción 2: Instalación Manual</h3>
            
            <ol class="step-list">
                <li>
                    <strong>Copiar archivos</strong>
                    <p>Copia la carpeta del sistema a tu directorio web:</p>
                    <div class="code-block"><code>cp -r sistema-admin-eest2 /var/www/html/
# o en XAMPP
cp -r sistema-admin-eest2 C:\xampp\htdocs\</code></div>
                </li>
                <li>
                    <strong>Configurar archivo .env</strong>
                    <div class="code-block"><code>cp env.example .env
nano .env  # Editar con tus credenciales</code></div>
                </li>
                <li>
                    <strong>Instalar dependencias de Composer</strong>
                    <div class="code-block"><code>composer install --no-dev --optimize-autoloader</code></div>
                </li>
                <li>
                    <strong>Configurar base de datos</strong>
                    <div class="code-block"><code># Crear base de datos
mysql -u root -p -e "CREATE DATABASE sistema_admin_eest2;"

# Importar estructura
mysql -u root -p sistema_admin_eest2 < database/sistema_admin_eest2.sql</code></div>
                </li>
                <li>
                    <strong>Configurar permisos</strong>
                    <div class="code-block"><code># En Linux/Mac
chmod -R 755 .
chmod -R 777 logs/ backups/ public/logs/ admin/logs/</code></div>
                </li>
            </ol>
            
            <h3>Opción 3: Instalación con Docker</h3>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Requisito:</strong> Docker y Docker Compose instalados en tu sistema.
            </div>
            
            <ol class="step-list">
                <li>
                    <strong>Navegar al directorio Docker</strong>
                    <div class="code-block"><code>cd deployment/docker</code></div>
                </li>
                <li>
                    <strong>Configurar variables de entorno</strong>
                    <p>Copia y edita el archivo de configuración:</p>
                    <div class="code-block"><code>cp .env.example .env
nano .env</code></div>
                </li>
                <li>
                    <strong>Iniciar contenedores</strong>
                    <div class="code-block"><code># Modo desarrollo
docker-compose up -d

# Modo producción
docker-compose -f docker-compose.prod.yml up -d</code></div>
                </li>
                <li>
                    <strong>Acceder al sistema</strong>
                    <p>El sistema estará disponible en:</p>
                    <div class="code-block"><code>http://localhost:8080</code></div>
                </li>
            </ol>
            
            <h2><i class="fas fa-user-shield"></i> Usuarios por Defecto</h2>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> IMPORTANTE:</strong> Cambia estas contraseñas inmediatamente después de la primera instalación.
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 1rem; text-align: left;">Rol</th>
                        <th style="padding: 1rem; text-align: left;">Usuario</th>
                        <th style="padding: 1rem; text-align: left;">Contraseña</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #f9fafb;">
                        <td style="padding: 1rem;">Administrador</td>
                        <td style="padding: 1rem;"><code>admin</code></td>
                        <td style="padding: 1rem;"><code>admin123</code></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem;">Director</td>
                        <td style="padding: 1rem;"><code>director</code></td>
                        <td style="padding: 1rem;"><code>director123</code></td>
                    </tr>
                    <tr style="background: #f9fafb;">
                        <td style="padding: 1rem;">Preceptor</td>
                        <td style="padding: 1rem;"><code>preceptor</code></td>
                        <td style="padding: 1rem;"><code>preceptor123</code></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem;">Secretaria</td>
                        <td style="padding: 1rem;"><code>secretaria</code></td>
                        <td style="padding: 1rem;"><code>secretaria123</code></td>
                    </tr>
                </tbody>
            </table>
            
            <h2><i class="fas fa-cog"></i> Configuración Post-Instalación</h2>
            
            <h3>1. Cambiar Contraseñas</h3>
            <p>Inicia sesión con cada usuario y cambia la contraseña desde el perfil.</p>
            
            <h3>2. Configurar Google OAuth y reCAPTCHA (Recomendado)</h3>
            <p>Configura las variables de integración para habilitar el login seguro:</p>
            <ul>
                <li>Crea <code>config/google_oauth.local.php</code> copiando <code>config/google_oauth.local.php.example</code> e ingresa tus claves de cliente de Google Console para el ingreso de docentes.</li>
                <li>Crea <code>config/recaptcha.local.php</code> a partir de <code>config/recaptcha.local.php.example</code> e ingresa tus claves de Google reCAPTCHA v2 para proteger el inicio de sesión estándar.</li>
            </ul>
            
            <h3>3. Configurar Backups Automáticos</h3>
            <p>Programa backups automáticos desde el panel de administración o mediante cron:</p>
            <div class="code-block"><code># Agregar a crontab (Linux)
0 2 * * * php /var/www/html/SistemaAdmin/deployment/scripts/backup.sh</code></div>
            
            <h3>4. Verificar Permisos</h3>
            <p>Asegúrate de que los directorios de logs y backups tengan permisos de escritura.</p>
            
            <h2><i class="fas fa-bug"></i> Solución de Problemas</h2>
            
            <h3>Error: "Connection failed"</h3>
            <div class="code-block"><code># Verifica las credenciales en .env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sistema_admin_eest2
DB_USER=root
DB_PASS=tu_password</code></div>
            
            <h3>Error: "Unknown database"</h3>
            <p>La base de datos no existe. Créala manualmente:</p>
            <div class="code-block"><code>mysql -u root -p -e "CREATE DATABASE sistema_admin_eest2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"</code></div>
            
            <h3>Error 500 - Internal Server Error</h3>
            <p>Verifica los permisos de archivos y logs de PHP:</p>
            <div class="code-block"><code># Ver logs de error
tail -f logs/php_errors.log

# Verificar permisos
ls -la</code></div>
            
            <div class="success-box">
                <strong><i class="fas fa-check-circle"></i> ¡Instalación Completa!</strong>
                <p>Si todo funciona correctamente, deberías poder acceder al sistema y ver la pantalla de login.</p>
            </div>
            
        </div>
        <div class="doc-actions">
            <a href="/SistemaAdmin/documentacion.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver a Documentación
            </a>
            <a href="../../public/login.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Ir al Login
            </a>
        </div>
    </div>
</body>
</html>
