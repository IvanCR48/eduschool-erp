<?php
/**
 * Herramientas de Administración - Sistema Admin EEST2
 * 
 * Documentación completa sobre las herramientas administrativas del sistema
 */

$pageTitle = 'Herramientas de Administración - E.E.S.T N°2';
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
            <h1><i class="fas fa-tools"></i> Herramientas de Administración</h1>
            <p>Sistema Administrativo E.E.S.T N°2 "Educación y Trabajo"</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-cogs"></i> Introducción</h2>
            <p>Las herramientas de administración del Sistema EEST2 proporcionan a los administradores un conjunto completo de utilidades para gestionar, monitorear y mantener el sistema de manera eficiente y segura.</p>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Acceso Administrativo</h4>
                <p>Solo los usuarios con rol de <strong>Administrador</strong> pueden acceder a estas herramientas. El acceso está protegido por validación reCAPTCHA v2 y auditoría completa.</p>
            </div>

            <h2><i class="fas fa-shield-alt"></i> Herramientas de Seguridad</h2>
            
            <div class="tool-grid">
                <div class="tool-card">
                    <h4><i class="fas fa-user-shield"></i> Gestión de Usuarios</h4>
                    <p><strong>Funcionalidades:</strong></p>
                    <ul>
                        <li>Crear, editar y eliminar usuarios</li>
                        <li>Asignar y modificar roles</li>
                        <li>Gestionar permisos específicos</li>
                        <li>Activar/desactivar cuentas</li>
                        <li>Resetear contraseñas</li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h4><i class="fas fa-key"></i> Políticas de Contraseñas</h4>
                    <p><strong>Configuración:</strong></p>
                    <ul>
                        <li>Longitud mínima requerida</li>
                        <li>Complejidad de caracteres</li>
                        <li>Caducidad de contraseñas</li>
                        <li>Historial de contraseñas</li>
                        <li>Bloqueo por intentos fallidos</li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h4><i class="fab fa-google"></i> Google OAuth & reCAPTCHA</h4>
                    <p><strong>Configuración de acceso:</strong></p>
                    <ul>
                        <li>Email de Google por usuario</li>
                        <li>Configuración local de Client ID y Secret</li>
                        <li>Protección contra bots con reCAPTCHA v2</li>
                        <li>Habilitación dinámica de widgets de login</li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h4><i class="fas fa-search"></i> Auditoría y Logs</h4>
                    <p><strong>Monitoreo:</strong></p>
                    <ul>
                        <li>Logs de acceso al sistema</li>
                        <li>Registro de acciones críticas</li>
                        <li>Alertas de seguridad</li>
                        <li>Reportes de actividad</li>
                        <li>Exportación de logs</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-database"></i> Herramientas de Base de Datos</h2>
            
            <h3>Gestión de Datos</h3>
            <ul>
                <li><strong>Backup Automático:</strong> Programación de respaldos diarios, semanales y mensuales</li>
                <li><strong>Restauración:</strong> Recuperación completa o parcial de datos</li>
                <li><strong>Optimización:</strong> Limpieza y optimización de tablas</li>
                <li><strong>Migración:</strong> Herramientas para actualizaciones de esquema</li>
                <li><strong>Monitoreo:</strong> Estado de conexiones y rendimiento</li>
            </ul>

            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Importante</h4>
                <p>Todas las operaciones de base de datos están registradas en logs de auditoría. Los cambios críticos requieren confirmación adicional.</p>
            </div>

            <h2><i class="fas fa-chart-line"></i> Herramientas de Monitoreo</h2>
            
            <h3>Métricas del Sistema</h3>
            <ul>
                <li><strong>Rendimiento:</strong> CPU, memoria, espacio en disco</li>
                <li><strong>Conectividad:</strong> Estado de servicios y conexiones</li>
                <li><strong>Usuarios Activos:</strong> Sesiones concurrentes y actividad</li>
                <li><strong>Errores:</strong> Logs de errores y excepciones</li>
                <li><strong>Seguridad:</strong> Intentos de acceso fallidos y alertas</li>
            </ul>

            <h3>Alertas Automáticas</h3>
            <ul>
                <li>Uso excesivo de recursos</li>
                <li>Intentos de acceso sospechosos</li>
                <li>Errores críticos del sistema</li>
                <li>Espacio en disco bajo</li>
                <li>Fallos en backups</li>
            </ul>

            <h2><i class="fas fa-cog"></i> Configuración del Sistema</h2>
            
            <div class="tool-grid">
                <div class="tool-card">
                    <h4><i class="fas fa-envelope"></i> Configuración de Email</h4>
                    <p><strong>Servidor SMTP:</strong></p>
                    <ul>
                        <li>Configuración de servidor</li>
                        <li>Autenticación</li>
                        <li>Plantillas de correo</li>
                        <li>Notificaciones automáticas</li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h4><i class="fas fa-clock"></i> Configuración de Sesiones</h4>
                    <p><strong>Parámetros:</strong></p>
                    <ul>
                        <li>Tiempo de expiración</li>
                        <li>Renovación automática</li>
                        <li>Sesiones concurrentes</li>
                        <li>Políticas de inactividad</li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h4><i class="fas fa-file-alt"></i> Configuración de Logs</h4>
                    <p><strong>Niveles:</strong></p>
                    <ul>
                        <li>Debug, Info, Warning, Error</li>
                        <li>Rotación de archivos</li>
                        <li>Retención de logs</li>
                        <li>Filtros por categoría</li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h4><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h4>
                    <p><strong>Parámetros:</strong></p>
                    <ul>
                        <li>Políticas de acceso</li>
                        <li>Configuración de firewall</li>
                        <li>Certificados SSL</li>
                        <li>Configuración de cookies</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-tools"></i> Herramientas de Mantenimiento</h2>
            
            <h3>Tareas Programadas</h3>
            <ul>
                <li><strong>Limpieza de Logs:</strong> Eliminación automática de logs antiguos</li>
                <li><strong>Optimización de BD:</strong> Mantenimiento periódico de la base de datos</li>
                <li><strong>Actualizaciones:</strong> Verificación y aplicación de actualizaciones</li>
                <li><strong>Monitoreo de Salud:</strong> Verificaciones automáticas del sistema</li>
            </ul>

            <h3>Herramientas Manuales</h3>
            <ul>
                <li><strong>Cache:</strong> Limpieza manual de caché del sistema</li>
                <li><strong>Archivos Temporales:</strong> Eliminación de archivos temporales</li>
                <li><strong>Verificación de Integridad:</strong> Comprobación de archivos del sistema</li>
                <li><strong>Diagnóstico:</strong> Herramientas de diagnóstico avanzado</li>
            </ul>

            <div class="success-box">
                <h4><i class="fas fa-check-circle"></i> Mejores Prácticas</h4>
                <ul>
                    <li>Realizar backups antes de cambios importantes</li>
                    <li>Probar cambios en ambiente de desarrollo</li>
                    <li>Documentar todas las modificaciones</li>
                    <li>Monitorear el sistema después de cambios</li>
                    <li>Mantener logs de auditoría actualizados</li>
                </ul>
            </div>

            <h2><i class="fas fa-question-circle"></i> Solución de Problemas</h2>
            
            <h3>Problemas Comunes</h3>
            <ul>
                <li><strong>Usuarios bloqueados:</strong> Verificar políticas de contraseñas e intentos fallidos</li>
                <li><strong>Errores de conexión:</strong> Revisar configuración de base de datos</li>
                <li><strong>Problemas de rendimiento:</strong> Analizar logs y métricas del sistema</li>
                <li><strong>Fallos de backup:</strong> Verificar permisos y espacio en disco</li>
            </ul>

            <h3>Herramientas de Diagnóstico</h3>
            <ul>
                <li>Verificador de conectividad de base de datos</li>
                <li>Analizador de logs de errores</li>
                <li>Monitor de recursos del sistema</li>
                <li>Verificador de permisos de archivos</li>
            </ul>

            <div class="info-box">
                <h4><i class="fas fa-phone"></i> Soporte Técnico</h4>
                <p>Para problemas complejos o emergencias, contacta al equipo de soporte técnico a través de la sección de <a href="contacto.php" style="color: #1e40af; font-weight: 600;">Contacto</a>.</p>
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
