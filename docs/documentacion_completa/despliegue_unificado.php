<?php
/**
 * Despliegue Unificado - Sistema Admin EEST2
 * 
 * Documentación completa sobre el despliegue y configuración del sistema
 */

$pageTitle = 'Despliegue Unificado - E.E.S.T N°2';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/SistemaAdmin/css/style.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <link rel="stylesheet" href="/SistemaAdmin/css/docs.css">
</head>
<body>
    <div class="doc-container">
        <div class="doc-header">
            <h1><i class="fas fa-cloud"></i> Despliegue Unificado</h1>
            <p>Sistema Administrativo E.E.S.T N°2 "Educación y Trabajo"</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-rocket"></i> Introducción al Despliegue</h2>
            <p>Esta guía proporciona instrucciones completas para el despliegue del Sistema EEST2 en diferentes entornos, desde desarrollo local hasta producción. El sistema está diseñado para ser flexible y adaptable a diversas configuraciones de servidor.</p>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Objetivo del Despliegue</h4>
                <p>Implementar el sistema de manera segura, eficiente y escalable, garantizando alta disponibilidad y rendimiento óptimo para la gestión educativa institucional.</p>
            </div>

            <h2><i class="fas fa-server"></i> Requisitos del Sistema</h2>
            
            <div class="deployment-grid">
                <div class="deployment-card">
                    <h4><i class="fas fa-microchip"></i> Requisitos Mínimos</h4>
                    <p><strong>Servidor:</strong></p>
                    <ul>
                        <li>CPU: 2 núcleos</li>
                        <li>RAM: 4GB</li>
                        <li>Disco: 20GB SSD</li>
                        <li>Red: 100 Mbps</li>
                        <li>OS: Linux/Windows Server</li>
                    </ul>
                </div>
                
                <div class="deployment-card">
                    <h4><i class="fas fa-rocket"></i> Requisitos Recomendados</h4>
                    <p><strong>Producción:</strong></p>
                    <ul>
                        <li>CPU: 4+ núcleos</li>
                        <li>RAM: 8GB+</li>
                        <li>Disco: 50GB+ SSD</li>
                        <li>Red: 1 Gbps</li>
                        <li>Redundancia: RAID 1</li>
                    </ul>
                </div>
                
                <div class="deployment-card">
                    <h4><i class="fas fa-code"></i> Software Requerido</h4>
                    <p><strong>Stack Tecnológico:</strong></p>
                    <ul>
                        <li>PHP 8.0+</li>
                        <li>MySQL 5.7+ / MariaDB 10.3+</li>
                        <li>Apache 2.4+ / Nginx 1.18+</li>
                        <li>OpenSSL 1.1.1+</li>
                        <li>Composer 2.0+</li>
                    </ul>
                </div>
                
                <div class="deployment-card">
                    <h4><i class="fas fa-shield-alt"></i> Requisitos de Seguridad</h4>
                    <p><strong>Certificados:</strong></p>
                    <ul>
                        <li>SSL/TLS válido</li>
                        <li>Firewall configurado</li>
                        <li>Actualizaciones automáticas</li>
                        <li>Monitoreo de seguridad</li>
                        <li>Backup encriptado</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-download"></i> Métodos de Instalación</h2>
            
            <h3>Instalación Automática (Recomendado)</h3>
            <p>El sistema incluye scripts de instalación automática para diferentes plataformas:</p>
            
            <h4>Windows (XAMPP)</h4>
            <div class="code-block">
                <code># Ejecutar como administrador
install.bat</code>
            </div>
            
            <h4>Linux/macOS</h4>
            <div class="code-block">
                <code># Dar permisos de ejecución
chmod +x install.sh

# Ejecutar instalación
./install.sh</code>
            </div>

            <h3>Instalación Manual</h3>
            <ol>
                <li><strong>Descargar Archivos:</strong> Obtener el código fuente del repositorio</li>
                <li><strong>Configurar Servidor Web:</strong> Apache/Nginx con PHP habilitado</li>
                <li><strong>Crear Base de Datos:</strong> MySQL/MariaDB con usuario dedicado</li>
                <li><strong>Configurar Variables:</strong> Archivo .env con parámetros del sistema</li>
                <li><strong>Importar Datos:</strong> Ejecutar scripts SQL de inicialización</li>
                <li><strong>Configurar Permisos:</strong> Establecer permisos de archivos correctos</li>
                <li><strong>Probar Sistema:</strong> Verificar funcionamiento completo</li>
            </ol>

            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Consideraciones Importantes</h4>
                <ul>
                    <li>Siempre realizar backup antes de la instalación</li>
                    <li>Verificar compatibilidad de versiones</li>
                    <li>Configurar firewall antes del despliegue</li>
                    <li>Probar en ambiente de desarrollo primero</li>
                </ul>
            </div>

            <h2><i class="fas fa-cog"></i> Configuración del Entorno</h2>
            
            <h3>Variables de Entorno (.env)</h3>
            <div class="code-block">
                <code># Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sistema_admin_eest2
DB_USER=usuario_db
DB_PASS=contraseña_segura

# Configuración del Sistema
APP_NAME="Sistema Admin EEST2"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Seguridad
APP_KEY=clave-secreta-generada
SESSION_LIFETIME=120
PASSWORD_RESET_EXPIRES=60

# Email
MAIL_HOST=smtp.tu-servidor.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@dominio.com
MAIL_PASSWORD=contraseña_email
MAIL_ENCRYPTION=tls</code>
            </div>

            <h3>Configuración del Servidor Web</h3>
            
            <h4>Apache (.htaccess)</h4>
            <div class="code-block">
                <code># Habilitar mod_rewrite
RewriteEngine On

# Redirecciones de seguridad
RewriteRule ^(src|config|database|tests|backups|logs|\.git)(/.*)?$ - [F,L]

# Permitir documentación PHP
RewriteRule ^docs/documentacion_completa/.*\.php$ - [L]

# Redirecciones de compatibilidad
RewriteRule ^login\.php$ public/login.php [R=301,L]
RewriteRule ^admin_tools\.php$ admin/admin_tools.php [R=301,L]</code>
            </div>

            <h4>Nginx</h4>
            <div class="code-block">
                <code>server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/sistema-admin;
    index index.php;

    # Seguridad
    location ~ /\.(git|env) {
        deny all;
    }

    # PHP Processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }

    # Static files
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}</code>
            </div>

            <h2><i class="fas fa-database"></i> Configuración de Base de Datos</h2>
            
            <h3>Creación de Base de Datos</h3>
            <div class="code-block">
                <code># Conectar a MySQL
mysql -u root -p

# Crear base de datos
CREATE DATABASE sistema_admin_eest2 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

# Crear usuario dedicado
CREATE USER 'sistema_admin'@'localhost' 
IDENTIFIED BY 'contraseña_segura';

# Asignar permisos
GRANT ALL PRIVILEGES ON sistema_admin_eest2.* 
TO 'sistema_admin'@'localhost';

# Aplicar cambios
FLUSH PRIVILEGES;</code>
            </div>

            <h3>Importación de Datos</h3>
            <div class="code-block">
                <code># Importar estructura y datos
mysql -u sistema_admin -p sistema_admin_eest2 < database/sistema_admin_eest2.sql

# Verificar importación
mysql -u sistema_admin -p -e "USE sistema_admin_eest2; SHOW TABLES;"</code>
            </div>

            <h2><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h2>
            
            <h3>Certificados SSL/TLS</h3>
            <ul>
                <li><strong>Let's Encrypt:</strong> Certificados gratuitos automáticos</li>
                <li><strong>Certificados Comerciales:</strong> Para mayor confianza</li>
                <li><strong>Renovación Automática:</strong> Configurar cron jobs</li>
                <li><strong>HSTS:</strong> Habilitar HTTP Strict Transport Security</li>
            </ul>

            <h3>Configuración de Firewall</h3>
            <div class="code-block">
                <code># UFW (Ubuntu)
sudo ufw enable
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw deny 3306/tcp   # MySQL (solo local)</code>
            </div>

            <h2><i class="fas fa-cloud-upload-alt"></i> Despliegue en Producción</h2>
            
            <h3>Preparación del Servidor</h3>
            <ol>
                <li><strong>Actualizar Sistema:</strong> Aplicar todas las actualizaciones</li>
                <li><strong>Configurar Usuario:</strong> Crear usuario no-root para la aplicación</li>
                <li><strong>Instalar Dependencias:</strong> PHP, MySQL, servidor web</li>
                <li><strong>Configurar Servicios:</strong> Habilitar servicios necesarios</li>
                <li><strong>Configurar Firewall:</strong> Establecer reglas de seguridad</li>
            </ol>

            <h3>Proceso de Despliegue</h3>
            <ol>
                <li><strong>Backup:</strong> Crear respaldo del sistema actual</li>
                <li><strong>Descarga:</strong> Obtener código fuente actualizado</li>
                <li><strong>Configuración:</strong> Aplicar configuraciones específicas</li>
                <li><strong>Base de Datos:</strong> Ejecutar migraciones si es necesario</li>
                <li><strong>Pruebas:</strong> Verificar funcionamiento completo</li>
                <li><strong>Monitoreo:</strong> Activar sistemas de monitoreo</li>
            </ol>

            <div class="success-box checklist-box">
                <h4><i class="fas fa-check-circle"></i> Checklist de Despliegue</h4>
                <ul>
                    <li>☐ Servidor configurado y actualizado</li>
                    <li>☐ Base de datos creada y configurada</li>
                    <li>☐ Certificados SSL instalados</li>
                    <li>☐ Firewall configurado</li>
                    <li>☐ Backup automático configurado</li>
                    <li>☐ Monitoreo activado</li>
                    <li>☐ Pruebas de funcionamiento completadas</li>
                    <li>☐ Documentación actualizada</li>
                </ul>
            </div>

            <h2><i class="fas fa-tools"></i> Mantenimiento Post-Despliegue</h2>
            
            <h3>Tareas Regulares</h3>
            <ul>
                <li><strong>Actualizaciones:</strong> Aplicar parches de seguridad</li>
                <li><strong>Backups:</strong> Verificar integridad de respaldos</li>
                <li><strong>Logs:</strong> Revisar y limpiar logs antiguos</li>
                <li><strong>Rendimiento:</strong> Monitorear métricas del sistema</li>
                <li><strong>Seguridad:</strong> Revisar logs de seguridad</li>
            </ul>

            <h3>Monitoreo Continuo</h3>
            <ul>
                <li><strong>Disponibilidad:</strong> Uptime del sistema</li>
                <li><strong>Rendimiento:</strong> Tiempo de respuesta</li>
                <li><strong>Recursos:</strong> CPU, memoria, disco</li>
                <li><strong>Errores:</strong> Logs de errores críticos</li>
                <li><strong>Seguridad:</strong> Intentos de acceso no autorizado</li>
            </ul>

            <div class="info-box">
                <h4><i class="fas fa-phone"></i> Soporte Post-Despliegue</h4>
                <p>Para asistencia con el despliegue o mantenimiento del sistema, contacta al equipo de soporte técnico a través de la sección de <a href="contacto.php" style="color: #1e40af; font-weight: 600;">Contacto</a>.</p>
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
