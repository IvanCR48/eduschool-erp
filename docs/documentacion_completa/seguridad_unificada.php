<?php
/**
 * Seguridad Unificada - Sistema Admin EEST2
 * 
 * Documentación completa sobre todas las medidas de seguridad implementadas
 */

$pageTitle = 'Seguridad Unificada - E.E.S.T N°2';
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
            <h1><i class="fas fa-shield-alt"></i> Seguridad Unificada</h1>
            <p>Sistema Administrativo E.E.S.T N°2 "Educación y Trabajo"</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-lock"></i> Introducción a la Seguridad</h2>
            <p>La seguridad del Sistema EEST2 está diseñada con un enfoque de múltiples capas, implementando las mejores prácticas de la industria para proteger tanto los datos institucionales como la información personal de estudiantes y personal docente.</p>
            
            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Compromiso de Seguridad</h4>
                <p>La protección de datos es nuestra máxima prioridad. Implementamos medidas de seguridad robustas que cumplen con estándares internacionales y regulaciones locales de protección de datos.</p>
            </div>

            <h2><i class="fas fa-user-shield"></i> Autenticación y Autorización</h2>
            
            <div class="security-grid">
                <div class="security-card">
                    <h4><i class="fas fa-key"></i> Acceso Seguro (Google OAuth)</h4>
                    <p><strong>Implementación:</strong></p>
                    <ul>
                        <li>Single Sign-On mediante Google OAuth 2.0 para docentes.</li>
                        <li>Desafío de bots Google reCAPTCHA v2 para login estándar.</li>
                        <li>Middleware de seguridad de sesión con chequeo de IP y User Agent.</li>
                        <li>Forzado automático de cambio de contraseña obligatoria.</li>
                    </ul>
                </div>
                
                <div class="security-card">
                    <h4><i class="fas fa-user-lock"></i> Políticas de Contraseñas</h4>
                    <p><strong>Requisitos:</strong></p>
                    <ul>
                        <li>Mínimo 8 caracteres (configurable)</li>
                        <li>Combinación de mayúsculas, minúsculas, números y caracteres especiales</li>
                        <li>Al menos un carácter especial</li>
                        <li>Forzado de cambio obligatorio en primer acceso o reseteo</li>
                    </ul>
                </div>
                
                <div class="security-card">
                    <h4><i class="fas fa-users-cog"></i> Control de Acceso Basado en Roles (RBAC)</h4>
                    <p><strong>Roles implementados:</strong></p>
                    <ul>
                        <li><strong>Administrador:</strong> Acceso completo</li>
                        <li><strong>Secretario:</strong> Gestión administrativa</li>
                        <li><strong>Profesor:</strong> Acceso académico limitado</li>
                        <li><strong>Preceptor:</strong> Seguimiento estudiantil</li>
                    </ul>
                </div>
                
                <div class="security-card">
                    <h4><i class="fas fa-clock"></i> Gestión de Sesiones</h4>
                    <p><strong>Características:</strong></p>
                    <ul>
                        <li>Expiración automática por inactividad (por defecto 2 horas)</li>
                        <li>Regeneración del ID de sesión al loguearse con éxito</li>
                        <li>Validación continua de IP y User Agent contra secuestro de sesión</li>
                        <li>Notificaciones y logs en caso de actividad sospechosa</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-shield-virus"></i> Protección contra Ataques</h2>
            
            <h3>Protección contra Fuerza Bruta</h3>
            <ul>
                <li><strong>Bloqueo Temporal:</strong> Configurable (por defecto 5 intentos fallidos = bloqueo de 30 minutos).</li>
                <li><strong>reCAPTCHA v2:</strong> Desafío obligatorio contra bots y spam.</li>
                <li><strong>Restablecimiento manual:</strong> El administrador del sistema puede desbloquear cuentas.</li>
                <li><strong>Logs de auditoría:</strong> Registro de cada login fallido y bloqueo automático en los logs de seguridad.</li>
            </ul>

            <h3>Protección contra Inyección SQL</h3>
            <ul>
                <li><strong>Prepared Statements:</strong> Uso exclusivo de consultas preparadas</li>
                <li><strong>Validación de Entrada:</strong> Sanitización de todos los datos</li>
                <li><strong>Escape de Caracteres:</strong> Protección adicional contra caracteres especiales</li>
                <li><strong>Principio de Menor Privilegio:</strong> Usuario de BD con permisos mínimos</li>
                <li><strong>Monitoreo:</strong> Detección automática de intentos de inyección</li>
            </ul>

            <h3>Protección contra XSS (Cross-Site Scripting)</h3>
            <ul>
                <li><strong>Escape de HTML:</strong> Sanitización de salida HTML</li>
                <li><strong>Content Security Policy (CSP):</strong> Restricción de fuentes de contenido</li>
                <li><strong>Validación de Entrada:</strong> Filtrado de scripts maliciosos</li>
                <li><strong>Headers de Seguridad:</strong> X-XSS-Protection habilitado</li>
                <li><strong>Sanitización:</strong> Limpieza de datos antes de mostrar</li>
            </ul>

            <h3>Protección contra CSRF (Cross-Site Request Forgery)</h3>
            <ul>
                <li><strong>Tokens CSRF:</strong> Validación de tokens únicos por sesión</li>
                <li><strong>SameSite Cookies:</strong> Configuración de cookies segura para evitar CSRF</li>
                <li><strong>Validación de origen:</strong> Verificación de referrer en peticiones críticas</li>
            </ul>

            <div class="success-box">
                <h4><i class="fas fa-check-circle"></i> Medidas de Seguridad Implementadas</h4>
                <ul>
                    <li>Encriptación de datos sensibles en base de datos</li>
                    <li>Comunicación segura con HTTPS/TLS</li>
                    <li>Logs de auditoría completos</li>
                    <li>Backup encriptado de datos</li>
                    <li>Monitoreo continuo de seguridad</li>
                </ul>
            </div>

            <h2><i class="fas fa-search"></i> Auditoría y Monitoreo</h2>
            
            <h3>Logs de Auditoría</h3>
            <ul>
                <li><strong>Accesos al Sistema:</strong> Registro de todos los logins y accesos Google OAuth</li>
                <li><strong>Acciones Críticas:</strong> Modificaciones de datos importantes</li>
                <li><strong>Cambios de Configuración:</strong> Modificaciones del sistema</li>
                <li><strong>Acceso a Datos Sensibles:</strong> Consultas a información personal</li>
                <li><strong>Errores de Seguridad:</strong> Intentos de acceso no autorizado y bloqueos</li>
            </ul>

            <h3>Monitoreo en Tiempo Real</h3>
            <ul>
                <li><strong>Alertas Automáticas:</strong> Notificaciones inmediatas de eventos críticos</li>
                <li><strong>Dashboard de Seguridad:</strong> Vista en tiempo real del estado</li>
                <li><strong>Métricas de Acceso:</strong> Estadísticas de uso y patrones</li>
                <li><strong>Detección de Anomalías:</strong> Identificación de comportamientos sospechosos mediante middleware</li>
            </ul>

            <h2><i class="fas fa-database"></i> Protección de Datos</h2>
            
            <h3>Encriptación de Datos</h3>
            <ul>
                <li><strong>Encriptación en Tránsito:</strong> TLS 1.3 para todas las comunicaciones</li>
                <li><strong>Encriptación en Reposo:</strong> AES-256 para datos sensibles</li>
                <li><strong>Hashing de Contraseñas:</strong> bcrypt o Argon2id</li>
            </ul>

            <h3>Respaldo y Recuperación</h3>
            <ul>
                <li><strong>Backups Diarios:</strong> Respaldos automáticos programados</li>
                <li><strong>Encriptación de Backups:</strong> Protección de datos respaldados</li>
                <li><strong>Almacenamiento Seguro:</strong> Ubicaciones físicas protegidas</li>
                <li><strong>Pruebas de Recuperación:</strong> Verificación mensual de integridad</li>
            </ul>

            <h2><i class="fas fa-server"></i> Seguridad del Servidor</h2>
            
            <h3>Configuración del Servidor Web</h3>
            <ul>
                <li><strong>Headers de Seguridad:</strong> Implementación completa de headers HTTP (CSP, HSTS)</li>
                <li><strong>Configuración SSL/TLS:</strong> Certificados válidos y configuración segura</li>
                <li><strong>Limitación de Recursos:</strong> Protección contra ataques DoS</li>
                <li><strong>Filtrado de Archivos:</strong> Bloqueo de acceso a archivos sensibles (.env, logs)</li>
            </ul>

            <h3>Configuración de PHP</h3>
            <ul>
                <li><strong>Configuración Segura:</strong> Parámetros PHP optimizados para seguridad</li>
                <li><strong>Deshabilitación de Funciones:</strong> Bloqueo de funciones peligrosas</li>
                <li><strong>Límites de Recursos:</strong> Restricción de memoria y tiempo de ejecución</li>
                <li><strong>Validación de Entrada:</strong> Filtros de entrada habilitados</li>
            </ul>

            <h2><i class="fas fa-tools"></i> Mantenimiento de Seguridad</h2>
            
            <h3>Tareas Regulares</h3>
            <ul>
                <li><strong>Actualizaciones de Seguridad:</strong> Aplicación de parches críticos</li>
                <li><strong>Revisión de Logs:</strong> Análisis semanal de eventos de seguridad</li>
                <li><strong>Auditoría de Permisos:</strong> Verificación mensual de accesos</li>
                <li><strong>Pruebas de Penetración:</strong> Evaluación trimestral de vulnerabilidades</li>
            </ul>

            <h3>Respuesta a Incidentes</h3>
            <ul>
                <li><strong>Plan de Respuesta:</strong> Procedimientos documentados para emergencias</li>
                <li><strong>Equipo de Respuesta:</strong> Personal capacitado para incidentes</li>
                <li><strong>Comunicación:</strong> Protocolos de notificación a autoridades</li>
                <li><strong>Recuperación:</strong> Procedimientos de restauración de servicios</li>
            </ul>

            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Cumplimiento Normativo</h4>
                <p>El sistema cumple con las regulaciones locales de protección de datos y está diseñado para facilitar auditorías de cumplimiento. Todos los procesos están documentados y son auditables.</p>
            </div>

            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes sobre Seguridad</h2>
            
            <h3>¿Cómo puedo reportar una vulnerabilidad de seguridad?</h3>
            <p>Contacta inmediatamente al equipo de seguridad a través de los canales oficiales. No publiques vulnerabilidades hasta que hayan sido corregidas.</p>

            <h3>¿Qué hacer si sospecho que mi cuenta ha sido comprometida?</h3>
            <p>Cambia tu contraseña inmediatamente, revisa la actividad reciente y contacta al administrador del sistema.</p>

            <h3>¿Con qué frecuencia se actualiza el sistema de seguridad?</h3>
            <p>Las actualizaciones de seguridad se aplican tan pronto como están disponibles, con pruebas previas en ambiente de desarrollo.</p>

            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Recordatorio Importante</h4>
                <p>La seguridad es responsabilidad de todos. Mantén tus credenciales seguras, no compartas contraseñas y reporta cualquier actividad sospechosa inmediatamente.</p>
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
