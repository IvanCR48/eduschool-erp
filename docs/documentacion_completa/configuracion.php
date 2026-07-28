<?php
$pageTitle = 'Configuración del Sistema - Documentación EEST2';
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
            <h1><i class="fas fa-cog"></i> Configuración del Sistema</h1>
            <p>Guía completa para configurar todos los aspectos del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-server"></i> Configuración Inicial</h2>
            
            <p>Después de instalar el sistema, es necesario configurar varios aspectos para adaptarlo a las necesidades de tu institución.</p>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> Importante:</strong> Realiza estas configuraciones durante la primera instalación y antes de que los usuarios accedan al sistema.
            </div>
            
            <h3>Archivo de Variables de Entorno (.env)</h3>
            
            <p>El archivo <code>.env</code> es el corazón de la configuración del sistema. Aquí se definen todas las variables críticas:</p>
            
            <div class="code-block"><code># Configuración de Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sistema_admin_eest2
DB_USER=root
DB_PASS=tu_password_seguro

# Configuración de la Aplicación
APP_NAME="Sistema Admin EEST2"
APP_ENV=production  # development, staging, production
APP_DEBUG=false     # true solo en desarrollo
APP_URL=https://sistema.eest2.edu.ar

# Seguridad
APP_KEY=base64:tu_clave_generada_32_caracteres
SESSION_LIFETIME=120  # minutos
SESSION_SECURE=true   # true en producción con HTTPS

# Email (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@eest2.edu.ar
MAIL_PASSWORD=tu_password_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@eest2.edu.ar
MAIL_FROM_NAME="Sistema EEST2"

# Backup
BACKUP_PATH=/var/backups/sistema_eest2
BACKUP_RETENTION_DAYS=30

# Logs
LOG_CHANNEL=daily
LOG_LEVEL=info  # debug, info, warning, error</code></div>
            
            <div class="danger-box">
                <strong><i class="fas fa-lock"></i> CRÍTICO:</strong> Nunca versiones el archivo <code>.env</code> en Git. Debe estar incluido en <code>.gitignore</code>.
            </div>
            
            <h2><i class="fas fa-database"></i> Configuración de Base de Datos</h2>
            
            <h3>Crear Base de Datos</h3>
            
            <p>Primero, crea la base de datos en MySQL:</p>
            
            <div class="code-block"><code>mysql -u root -p

CREATE DATABASE sistema_admin_eest2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON sistema_admin_eest2.* 
    TO 'sistema_user'@'localhost' 
    IDENTIFIED BY 'password_seguro';

FLUSH PRIVILEGES;
EXIT;</code></div>
            
            <h3>Importar Estructura</h3>
            
            <div class="code-block"><code>mysql -u sistema_user -p sistema_admin_eest2 < database/sistema_admin_eest2.sql</code></div>
            
            <h3>Configuración de Conexión</h3>
            
            <p>El archivo <code>config/database.php</code> gestiona la conexión. No es necesario modificarlo si usas variables de entorno correctamente:</p>
            
            <div class="config-section">
                <h4>Parámetros de Conexión</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Descripción</th>
                            <th>Valor Recomendado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>charset</code></td>
                            <td>Codificación de caracteres</td>
                            <td>utf8mb4</td>
                        </tr>
                        <tr>
                            <td><code>collation</code></td>
                            <td>Reglas de comparación</td>
                            <td>utf8mb4_unicode_ci</td>
                        </tr>
                        <tr>
                            <td><code>strict</code></td>
                            <td>Modo estricto SQL</td>
                            <td>true</td>
                        </tr>
                        <tr>
                            <td><code>engine</code></td>
                            <td>Motor de almacenamiento</td>
                            <td>InnoDB</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <h2><i class="fas fa-school"></i> Configuración Institucional</h2>
            
            <h3>Información de la Escuela</h3>
            
            <p>Configura los datos institucionales desde <strong>Panel Admin → Configuración General</strong>:</p>
            
            <ul>
                <li><strong>Nombre completo:</strong> E.E.S.T N°2 "Educación y Trabajo"</li>
                <li><strong>Dirección:</strong> Calle y número</li>
                <li><strong>Ciudad:</strong> Localidad, Provincia</li>
                <li><strong>Teléfono:</strong> Número de contacto</li>
                <li><strong>Email institucional:</strong> contacto@eest2.edu.ar</li>
                <li><strong>CUE:</strong> Código Único de Establecimiento</li>
            </ul>
            
            <h3>Logo Institucional</h3>
            
            <ol>
                <li>Ve a <strong>Panel Admin → Configuración → Logo</strong></li>
                <li>Sube una imagen en formato PNG o JPG</li>
                <li><strong>Dimensiones recomendadas:</strong> 200x200 px</li>
                <li><strong>Tamaño máximo:</strong> 2MB</li>
                <li>El logo aparecerá en el header y documentos oficiales</li>
            </ol>
            
            <h3>Colores Institucionales</h3>
            
            <p>Personaliza los colores del sistema según la identidad visual de la institución:</p>
            
            <div class="code-block"><code>/* En css/style.css o mediante panel de administración */
:root {
    --primary-color: #0ea5a3;     /* Color principal */
    --primary-dark: #0b7f7e;      /* Variante oscura */
    --secondary-color: #4b5563;   /* Color secundario */
    --accent-color: #f59e0b;      /* Color de acento */
}</code></div>
            
            <h2><i class="fas fa-calendar-alt"></i> Configuración Académica</h2>
            
            <h3>Año Escolar</h3>
            
            <p>Configura el año escolar actual:</p>
            
            <div class="config-section">
                <h4>Parámetros del Año Escolar</h4>
                <ul>
                    <li><strong>Año lectivo:</strong> 2025</li>
                    <li><strong>Fecha de inicio:</strong> 01/03/2025</li>
                    <li><strong>Fecha de finalización:</strong> 20/12/2025</li>
                    <li><strong>Sistema de evaluación:</strong> Trimestral / Cuatrimestral</li>
                </ul>
            </div>
            
            <h3>Cuatrimestres</h3>
            
            <p>Define los períodos de evaluación:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Cierre de Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1er Cuatrimestre</td>
                        <td>01/03/2025</td>
                        <td>31/05/2025</td>
                        <td>05/06/2025</td>
                    </tr>
                    <tr>
                        <td>2do Cuatrimestre</td>
                        <td>01/06/2025</td>
                        <td>30/08/2025</td>
                        <td>05/09/2025</td>
                    </tr>
                    <tr>
                        <td>3er Cuatrimestre</td>
                        <td>01/09/2025</td>
                        <td>20/12/2025</td>
                        <td>23/12/2025</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>Escala de Calificación</h3>
            
            <div class="config-section">
                <h4>Sistema de Calificación Argentina</h4>
                <ul>
                    <li><strong>Escala:</strong> 1 a 10</li>
                    <li><strong>Nota mínima aprobación:</strong> 6</li>
                    <li><strong>Nota de desaprobación:</strong> 1 a 5</li>
                    <li><strong>Nota de excelencia:</strong> 9 a 10</li>
                    <li><strong>Calificación especial:</strong> A (Ausente), I (Incompleto)</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-envelope"></i> Configuración de Email</h2>
            
            <h3>Servidor SMTP</h3>
            
            <p>Configura el servidor de correo electrónico para que el sistema pueda enviar notificaciones:</p>
            
            <div class="config-section">
                <h4>Gmail (Recomendado para pruebas)</h4>
                <div class="code-block"><code>MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=contraseña_aplicacion
MAIL_ENCRYPTION=tls</code></div>
                
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> Nota:</strong> Para Gmail, debes generar una "Contraseña de aplicación" en tu cuenta de Google.
                </div>
            </div>
            
            <div class="config-section">
                <h4>Outlook/Office 365</h4>
                <div class="code-block"><code>MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@outlook.com
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls</code></div>
            </div>
            
            <h3>Plantillas de Email</h3>
            
            <p>Personaliza las plantillas de email en <code>resources/views/emails/</code>:</p>
            
            <ul>
                <li><strong>bienvenida.php</strong> - Email de bienvenida a nuevos usuarios</li>
                <li><strong>recuperacion_password.php</strong> - Recuperación de contraseña</li>
                <li><strong>notificacion_nota.php</strong> - Notificación de nueva calificación</li>
                <li><strong>alerta_ausencia.php</strong> - Alerta de ausencias</li>
            </ul>
            
            <h2><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h2>
            
            <h3>Políticas de Contraseñas</h3>
            
            <p>En <strong>Panel Admin → Seguridad → Políticas de Contraseñas</strong>:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Parámetro</th>
                        <th>Recomendado</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Longitud mínima</td>
                        <td>12 caracteres</td>
                        <td>Mayor seguridad</td>
                    </tr>
                    <tr>
                        <td>Complejidad</td>
                        <td>Alta</td>
                        <td>Mayúsculas, minúsculas, números, símbolos</td>
                    </tr>
                    <tr>
                        <td>Expiración</td>
                        <td>90 días</td>
                        <td>Cambio periódico obligatorio</td>
                    </tr>
                    <tr>
                        <td>Historial</td>
                        <td>5 contraseñas</td>
                        <td>No reutilizar contraseñas recientes</td>
                    </tr>
                    <tr>
                        <td>Intentos fallidos</td>
                        <td>5 intentos</td>
                        <td>Bloqueo temporal después</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>Acceso Seguro (Google OAuth y reCAPTCHA v2)</h3>
            
            <p>El sistema se integra de forma directa con Google OAuth y reCAPTCHA v2 mediante archivos locales de configuración:</p>
            
            <ol>
                <li>Crea el archivo <code>config/google_oauth.local.php</code> (usando el ejemplo) y configura las credenciales de cliente para permitir el inicio de sesión a docentes.</li>
                <li>Crea el archivo <code>config/recaptcha.local.php</code> (usando el ejemplo) con las claves de reCAPTCHA v2 de Google para el formulario de login.</li>
                <li>El sistema habilitará los flujos automáticamente tras detectar estos archivos locales configurados.</li>
            </ol>
            
            <h3>Sesiones</h3>
            
            <div class="config-section">
                <h4>Configuración de Sesiones</h4>
                <ul>
                    <li><strong>Timeout de inactividad:</strong> 30 minutos</li>
                    <li><strong>Cookie segura (HTTPS):</strong> Sí (en producción)</li>
                    <li><strong>HttpOnly:</strong> Sí</li>
                    <li><strong>SameSite:</strong> Lax</li>
                    <li><strong>Sesión única:</strong> No (permitir múltiples dispositivos)</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-user-cog"></i> Configuración de Roles y Permisos</h2>
            
            <h3>Roles del Sistema</h3>
            
            <p>Los roles predefinidos son:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Nivel de Acceso</th>
                        <th>Permisos Principales</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Administrador</strong></td>
                        <td>Total</td>
                        <td>Gestión completa del sistema</td>
                    </tr>
                    <tr>
                        <td><strong>Director</strong></td>
                        <td>Alto</td>
                        <td>Gestión académica y reportes</td>
                    </tr>
                    <tr>
                        <td><strong>Secretaría</strong></td>
                        <td>Medio-Alto</td>
                        <td>Gestión administrativa</td>
                    </tr>
                    <tr>
                        <td><strong>Preceptor</strong></td>
                        <td>Medio</td>
                        <td>Asistencias y disciplina</td>
                    </tr>
                    <tr>
                        <td><strong>Profesor</strong></td>
                        <td>Medio</td>
                        <td>Notas de sus materias</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>Permisos Personalizados</h3>
            
            <p>Puedes crear permisos específicos en <strong>Panel Admin → Roles y Permisos</strong>:</p>
            
            <ul>
                <li>Ver estudiantes</li>
                <li>Editar estudiantes</li>
                <li>Eliminar estudiantes</li>
                <li>Cargar notas</li>
                <li>Modificar notas</li>
                <li>Ver reportes</li>
                <li>Exportar datos</li>
                <li>Gestionar usuarios</li>
            </ul>
            
            <h2><i class="fas fa-save"></i> Configuración de Backups</h2>
            
            <h3>Backups Automáticos</h3>
            
            <p>Configura backups automáticos para proteger tus datos:</p>
            
            <div class="config-section">
                <h4>Configuración Recomendada</h4>
                <ul>
                    <li><strong>Frecuencia:</strong> Diaria (2:00 AM)</li>
                    <li><strong>Retención:</strong> 30 días</li>
                    <li><strong>Ubicación local:</strong> /var/backups/sistema_eest2/</li>
                    <li><strong>Ubicación remota:</strong> Cloud storage (Google Drive, Dropbox)</li>
                    <li><strong>Incluir:</strong> Base de datos + archivos subidos</li>
                </ul>
            </div>
            
            <h3>Configurar Cron para Backups</h3>
            
            <p>En Linux, agrega a crontab:</p>
            
            <div class="code-block"><code># Editar crontab
crontab -e

# Agregar línea de backup diario a las 2 AM
0 2 * * * /usr/bin/php /var/www/html/SistemaAdmin/deployment/scripts/backup.sh >> /var/log/backup.log 2>&1</code></div>
            
            <h2><i class="fas fa-chart-line"></i> Configuración de Logs</h2>
            
            <h3>Niveles de Log</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Nivel</th>
                        <th>Uso</th>
                        <th>Recomendado para</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>DEBUG</td>
                        <td>Información detallada</td>
                        <td>Desarrollo</td>
                    </tr>
                    <tr>
                        <td>INFO</td>
                        <td>Eventos normales</td>
                        <td>Producción</td>
                    </tr>
                    <tr>
                        <td>WARNING</td>
                        <td>Situaciones no críticas</td>
                        <td>Producción</td>
                    </tr>
                    <tr>
                        <td>ERROR</td>
                        <td>Errores que requieren atención</td>
                        <td>Siempre</td>
                    </tr>
                    <tr>
                        <td>CRITICAL</td>
                        <td>Fallos graves del sistema</td>
                        <td>Siempre</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>Rotación de Logs</h3>
            
            <div class="code-block"><code># Configurar rotación de logs
LOG_CHANNEL=daily
LOG_RETENTION_DAYS=30

# Los logs se guardan en:
# logs/system.log
# admin/logs/admin.log
# public/logs/access.log</code></div>
            
            <h2><i class="fas fa-globe"></i> Configuración de Entorno</h2>
            
            <h3>Desarrollo vs Producción</h3>
            
            <div class="config-section">
                <h4>Entorno de Desarrollo</h4>
                <div class="code-block"><code>APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
SESSION_SECURE=false  # OK sin HTTPS local</code></div>
            </div>
            
            <div class="config-section">
                <h4>Entorno de Producción</h4>
                <div class="code-block"><code>APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=info
SESSION_SECURE=true  # HTTPS obligatorio</code></div>
            </div>
            
            <div class="danger-box">
                <strong><i class="fas fa-exclamation-triangle"></i> CRÍTICO:</strong> En producción, SIEMPRE usa <code>APP_DEBUG=false</code> para no exponer información sensible.
            </div>
            
            <h2><i class="fas fa-check-circle"></i> Verificación de Configuración</h2>
            
            <h3>Checklist de Configuración</h3>
            
            <div class="success-box checklist-box">
                <h4 style="margin-top: 0;">Antes de poner el sistema en producción:</h4>
                <ul style="margin-bottom: 0;">
                    <li>☐ Archivo .env configurado correctamente</li>
                    <li>☐ Base de datos creada e importada</li>
                    <li>☐ Información institucional completada</li>
                    <li>☐ Logo institucional cargado</li>
                    <li>☐ Email SMTP configurado y probado</li>
                    <li>☐ Políticas de contraseñas definidas</li>
                    <li>☐ Google OAuth y reCAPTCHA v2 configurados</li>
                    <li>☐ Backups automáticos configurados</li>
                    <li>☐ HTTPS habilitado (SSL/TLS)</li>
                    <li>☐ APP_DEBUG=false</li>
                    <li>☐ Permisos de archivos correctos</li>
                    <li>☐ Usuarios de prueba eliminados</li>
                </ul>
            </div>
            
            <h3>Comando de Verificación</h3>
            
            <p>Ejecuta este comando para verificar la configuración:</p>
            
            <div class="code-block"><code>php deployment/scripts/check-config.php</code></div>
            
            <h2><i class="fas fa-book-open"></i> Recursos Adicionales</h2>
            
            <ul>
                <li><a href="instalacion.php">Guía de Instalación</a></li>
                <li><a href="seguridad.php">Documentación de Seguridad</a></li>
                <li><a href="mantenimiento.php">Guía de Mantenimiento</a></li>
            </ul>
            
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
