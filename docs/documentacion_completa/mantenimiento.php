<?php
$pageTitle = 'Mantenimiento del Sistema - Documentación EEST2';
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
            <h1><i class="fas fa-tools"></i> Mantenimiento del Sistema</h1>
            <p>Guía completa para el mantenimiento preventivo y correctivo del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-calendar-check"></i> Plan de Mantenimiento</h2>
            
            <p>Un mantenimiento regular garantiza el funcionamiento óptimo del sistema y previene problemas futuros. Este plan está organizado por frecuencia.</p>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Importante:</strong> Realiza siempre un backup completo antes de cualquier tarea de mantenimiento mayor.
            </div>
            
            <h2><i class="fas fa-check-circle"></i> Mantenimiento Diario</h2>
            
            <h3>Monitoreo General</h3>
            <p><strong>Tiempo estimado:</strong> 10-15 minutos</p>
            
            <ol>
                <li><strong>Verificar estado del sistema</strong>
                    <ul>
                        <li>Acceder al dashboard de administración</li>
                        <li>Revisar que todos los servicios estén funcionando</li>
                        <li>Verificar que no haya errores críticos</li>
                    </ul>
                </li>
                <li><strong>Revisar logs recientes</strong>
                    <div class="code-block"><code># Ver últimos 50 errores
tail -n 50 logs/error.log

# Buscar errores críticos
grep "CRITICAL\|ERROR" logs/system.log | tail -20</code></div>
                </li>
                <li><strong>Verificar backups</strong>
                    <ul>
                        <li>Confirmar que el backup nocturno se ejecutó correctamente</li>
                        <li>Revisar el tamaño del archivo de backup</li>
                        <li>Verificar que se guardó en la ubicación correcta</li>
                    </ul>
                </li>
            </ol>
            
            <h2><i class="fas fa-sync-alt"></i> Mantenimiento Semanal</h2>
            
            <h3>Revisión de Rendimiento</h3>
            <p><strong>Tiempo estimado:</strong> 30-45 minutos</p>
                
                <ol>
                    <li><strong>Analizar logs de seguridad</strong>
                        <ul>
                            <li>Revisar intentos de login fallidos</li>
                            <li>Identificar patrones sospechosos</li>
                            <li>Verificar accesos desde IPs desconocidas</li>
                        </ul>
                        <div class="code-block"><code># Ver intentos fallidos de la semana
grep "Failed login" admin/logs/security.log | wc -l

# Top 10 IPs con intentos fallidos
grep "Failed login" admin/logs/security.log | awk '{print $5}' | sort | uniq -c | sort -nr | head -10</code></div>
                    </li>
                    
                    <li><strong>Limpiar sesiones expiradas</strong>
                        <div class="code-block"><code># Ejecutar script de limpieza
php deployment/scripts/clean-sessions.php</code></div>
                    </li>
                    
                    <li><strong>Verificar espacio en disco</strong>
                        <div class="code-block"><code># En Linux
df -h

# Verificar tamaño de logs
du -sh logs/ admin/logs/ public/logs/

# Verificar tamaño de backups
du -sh backups/</code></div>
                    </li>
                    
                    <li><strong>Revisar usuarios bloqueados</strong>
                        <ul>
                            <li>Verificar usuarios que fueron bloqueados durante la semana</li>
                            <li>Investigar las causas del bloqueo</li>
                            <li>Desbloquear usuarios legítimos si es necesario</li>
                        </ul>
                    </li>
                </ol>
            
            <h3>Limpieza de Archivos Temporales</h3>
                
                <div class="code-block"><code># Limpiar cache
php deployment/scripts/clear-cache.php

# Eliminar archivos temporales
find tmp/ -type f -mtime +7 -delete

# Limpiar sesiones antiguas
find sessions/ -type f -mtime +1 -delete</code></div>
            
            <h2><i class="fas fa-calendar-alt"></i> Mantenimiento Mensual</h2>
            
            <h3>Optimización de Base de Datos</h3>
            <p><strong>Tiempo estimado:</strong> 1-2 horas</p>
                
                <ol>
                    <li><strong>Analizar tablas</strong>
                        <div class="code-block"><code>mysql -u root -p sistema_admin_eest2 -e "ANALYZE TABLE estudiantes, profesores, notas, usuarios;"</code></div>
                    </li>
                    
                    <li><strong>Optimizar tablas</strong>
                        <div class="code-block"><code>mysql -u root -p sistema_admin_eest2 -e "OPTIMIZE TABLE estudiantes, profesores, notas, usuarios, sesiones, logs;"</code></div>
                    </li>
                    
                    <li><strong>Verificar integridad</strong>
                        <div class="code-block"><code>mysql -u root -p sistema_admin_eest2 -e "CHECK TABLE estudiantes, profesores, notas;"</code></div>
                    </li>
                    
                    <li><strong>Limpiar registros antiguos</strong>
                        <div class="code-block"><code># Eliminar logs de auditoría mayores a 6 meses
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

# Eliminar sesiones expiradas
DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY));</code></div>
                    </li>
                </ol>
            
            <h3>Auditoría de Seguridad</h3>
                
                <ol>
                    <li><strong>Revisar permisos de usuarios</strong>
                        <ul>
                            <li>Verificar que cada usuario tenga solo los permisos necesarios</li>
                            <li>Eliminar permisos innecesarios</li>
                            <li>Desactivar cuentas de usuarios inactivos</li>
                        </ul>
                    </li>
                    
                    <li><strong>Verificar contraseñas expiradas</strong>
                        <ul>
                            <li>Listar usuarios con contraseñas que expiran pronto</li>
                            <li>Notificar a usuarios afectados</li>
                            <li>Forzar cambio de contraseña si es necesario</li>
                        </ul>
                    </li>
                    
                    <li><strong>Revisar configuración de Google OAuth y reCAPTCHA</strong>
                        <ul>
                            <li>Verificar que las claves de cliente en <code>config/google_oauth.local.php</code> estén correctas y vigentes.</li>
                            <li>Verificar el correcto funcionamiento de las claves de Google reCAPTCHA v2.</li>
                        </ul>
                    </li>
                </ol>
            
            <h3>Actualización de Dependencias</h3>
                
                <div class="warning-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Precaución:</strong> Siempre prueba las actualizaciones en un entorno de desarrollo antes de aplicarlas en producción.
                </div>
                
                <div class="code-block"><code># Ver dependencias desactualizadas
composer outdated

# Actualizar dependencias (solo patches de seguridad)
composer update --with-dependencies --prefer-stable

# Verificar vulnerabilidades conocidas
composer audit</code></div>
            
            <h2><i class="fas fa-calendar"></i> Mantenimiento Trimestral</h2>
            
            <h3>Análisis de Rendimiento</h3>
                
                <ol>
                    <li><strong>Revisar tiempos de respuesta</strong>
                        <ul>
                            <li>Analizar logs de acceso para identificar páginas lentas</li>
                            <li>Optimizar consultas SQL problemáticas</li>
                            <li>Considerar implementar cache si es necesario</li>
                        </ul>
                    </li>
                    
                    <li><strong>Analizar uso de recursos</strong>
                        <ul>
                            <li>Revisar uso de CPU y memoria</li>
                            <li>Identificar procesos que consumen muchos recursos</li>
                            <li>Optimizar configuración del servidor si es necesario</li>
                        </ul>
                    </li>
                    
                    <li><strong>Pruebas de carga</strong>
                        <ul>
                            <li>Simular múltiples usuarios concurrentes</li>
                            <li>Identificar cuellos de botella</li>
                            <li>Planificar mejoras de infraestructura</li>
                        </ul>
                    </li>
                </ol>
            
            <h3>Archivado de Datos</h3>
                
                <ol>
                    <li><strong>Archivar datos antiguos</strong>
                        <ul>
                            <li>Exportar datos de años anteriores</li>
                            <li>Guardar en formato CSV o SQL</li>
                            <li>Mantener copia de seguridad externa</li>
                        </ul>
                    </li>
                    
                    <li><strong>Limpiar datos obsoletos</strong>
                        <ul>
                            <li>Eliminar registros de prueba</li>
                            <li>Remover datos duplicados</li>
                            <li>Validar integridad referencial</li>
                        </ul>
                    </li>
                </ol>
            
            <h2><i class="fas fa-calendar-plus"></i> Mantenimiento Anual</h2>
            
            <h3>Preparación para Nuevo Año Escolar</h3>
                
                <ol>
                    <li><strong>Archivar datos del año anterior</strong>
                        <div class="code-block"><code># Exportar datos completos del año
php deployment/scripts/export-year-data.php 2024

# Crear backup especial fin de año
php deployment/scripts/backup-year-end.php</code></div>
                    </li>
                    
                    <li><strong>Actualizar configuración académica</strong>
                        <ul>
                            <li>Configurar nuevo año lectivo</li>
                            <li>Definir fechas de cuatrimestres</li>
                            <li>Actualizar calendario escolar</li>
                        </ul>
                    </li>
                    
                    <li><strong>Promoción de estudiantes</strong>
                        <ul>
                            <li>Promover estudiantes al siguiente año/curso</li>
                            <li>Marcar egresados</li>
                            <li>Registrar repitentes</li>
                        </ul>
                    </li>
                    
                    <li><strong>Renovar certificados SSL</strong>
                        <div class="code-block"><code># Con Let's Encrypt
certbot renew

# Verificar vigencia
openssl x509 -in /etc/ssl/certs/sistema.crt -noout -dates</code></div>
                    </li>
                </ol>
            
            <h3>Mantenimiento de Servidor</h3>
                
                <ol>
                    <li><strong>Actualizar sistema operativo</strong>
                        <div class="code-block"><code># Ubuntu/Debian
sudo apt update
sudo apt upgrade

# CentOS/RHEL
sudo yum update</code></div>
                    </li>
                    
                    <li><strong>Actualizar PHP</strong>
                        <div class="warning-box">
                            <strong><i class="fas fa-exclamation-triangle"></i> Importante:</strong> Verifica compatibilidad antes de actualizar versiones mayores de PHP.
                        </div>
                        <div class="code-block"><code># Verificar versión actual
php -v

# Actualizar PHP (Ubuntu)
sudo apt install php8.2</code></div>
                    </li>
                    
                    <li><strong>Actualizar MySQL</strong>
                        <div class="danger-box">
                            <strong><i class="fas fa-bomb"></i> CRÍTICO:</strong> SIEMPRE realiza un backup completo antes de actualizar MySQL.
                        </div>
                        <div class="code-block"><code># Backup antes de actualizar
mysqldump -u root -p --all-databases > backup_pre_upgrade.sql

# Actualizar MySQL
sudo apt upgrade mysql-server</code></div>
                    </li>
                </ol>
            
            <h2><i class="fas fa-exclamation-circle"></i> Mantenimiento de Emergencia</h2>
            
            <h3>Modo Mantenimiento</h3>
                
                <p>Cuando necesites realizar mantenimiento urgente:</p>
                
                <ol>
                    <li><strong>Activar modo mantenimiento</strong>
                        <div class="code-block"><code># Activar modo mantenimiento
php deployment/scripts/enable-maintenance.php

# O manualmente crear archivo
touch maintenance.flag</code></div>
                    </li>
                    
                    <li><strong>Realizar las tareas necesarias</strong>
                        <ul>
                            <li>Ejecutar scripts de reparación</li>
                            <li>Restaurar backups si es necesario</li>
                            <li>Corregir errores críticos</li>
                        </ul>
                    </li>
                    
                    <li><strong>Verificar funcionamiento</strong>
                        <ul>
                            <li>Probar funcionalidades críticas</li>
                            <li>Verificar acceso a base de datos</li>
                            <li>Comprobar permisos de archivos</li>
                        </ul>
                    </li>
                    
                    <li><strong>Desactivar modo mantenimiento</strong>
                        <div class="code-block"><code># Desactivar modo mantenimiento
php deployment/scripts/disable-maintenance.php

# O manualmente
rm maintenance.flag</code></div>
                    </li>
                </ol>
            
            <h2><i class="fas fa-clipboard-list"></i> Checklist de Mantenimiento</h2>
            
            <h3>Diario</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Verificar estado del sistema</li>
                    <li>☐ Revisar logs de errores</li>
                    <li>☐ Confirmar backup nocturno</li>
                </ul>
            </div>
            
            <h3>Semanal</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Analizar logs de seguridad</li>
                    <li>☐ Limpiar sesiones expiradas</li>
                    <li>☐ Verificar espacio en disco</li>
                    <li>☐ Revisar usuarios bloqueados</li>
                    <li>☐ Limpiar archivos temporales</li>
                </ul>
            </div>
            
            <h3>Mensual</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Optimizar base de datos</li>
                    <li>☐ Auditoría de seguridad</li>
                    <li>☐ Actualizar dependencias</li>
                    <li>☐ Revisar permisos de usuarios</li>
                    <li>☐ Verificar backups</li>
                </ul>
            </div>
            
            <h3>Trimestral</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Análisis de rendimiento</li>
                    <li>☐ Archivar datos antiguos</li>
                    <li>☐ Pruebas de carga</li>
                    <li>☐ Revisar y actualizar documentación</li>
                </ul>
            </div>
            
            <h3>Anual</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Archivar datos del año</li>
                    <li>☐ Configurar nuevo año escolar</li>
                    <li>☐ Renovar certificados SSL</li>
                    <li>☐ Actualizar sistema operativo</li>
                    <li>☐ Actualizar PHP/MySQL</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-book-open"></i> Recursos Adicionales</h2>
            
            <ul>
                <li><a href="configuracion.php">Guía de Configuración</a></li>
                <li><a href="troubleshooting.php">Solución de Problemas</a></li>
                <li><a href="backup.php">Sistema de Backup</a></li>
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
