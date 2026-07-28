<?php
$pageTitle = 'Sistema de Backup Completo - Documentación EEST2';
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
            <h1><i class="fas fa-save"></i> Sistema de Backup Completo</h1>
            <p>Guía completa para la gestión de respaldos y recuperación de datos en el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-shield-alt"></i> Importancia del Backup</h2>
            
            <div class="danger-box">
                <p><i class="fas fa-exclamation-triangle"></i> <strong>¿Por qué es crítico el backup?</strong></p>
                <ul>
                    <li><strong>Protección contra pérdida de datos:</strong> Fallos de hardware, errores humanos, ataques</li>
                    <li><strong>Cumplimiento legal:</strong> Requisitos de retención de datos educativos</li>
                    <li><strong>Continuidad del servicio:</strong> Recuperación rápida ante incidentes</li>
                    <li><strong>Tranquilidad:</strong> Seguridad de que los datos están protegidos</li>
                </ul>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> <strong>Regla 3-2-1:</strong> Mantén 3 copias de tus datos, en 2 tipos diferentes de almacenamiento, con 1 copia fuera del sitio.</p>
            </div>
            
            <h2><i class="fas fa-cogs"></i> Tipos de Backup</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-database"></i> Backup Completo</h4>
                
                <div class="step-box">
                    <p><strong>Características del backup completo:</strong></p>
                    <ul>
                        <li><strong>Incluye:</strong> Toda la base de datos y archivos del sistema</li>
                        <li><strong>Frecuencia:</strong> Semanal o mensual</li>
                        <li><strong>Tamaño:</strong> Grande (depende del volumen de datos)</li>
                        <li><strong>Tiempo de restauración:</strong> Lento</li>
                        <li><strong>Uso:</strong> Punto de partida para otros backups</li>
                    </ul>
                </div>
                
                <h4>Cuándo usar backup completo:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Antes de actualizaciones importantes</li>
                        <li>☐ Al final de cada período académico</li>
                        <li>☐ Como respaldo base semanal</li>
                        <li>☐ Antes de cambios estructurales</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-plus-circle"></i> Backup Incremental</h4>
                
                <div class="step-box">
                    <p><strong>Características del backup incremental:</strong></p>
                    <ul>
                        <li><strong>Incluye:</strong> Solo cambios desde el último backup</li>
                        <li><strong>Frecuencia:</strong> Diaria</li>
                        <li><strong>Tamaño:</strong> Pequeño</li>
                        <li><strong>Tiempo de restauración:</strong> Rápido</li>
                        <li><strong>Uso:</strong> Respaldos regulares</li>
                    </ul>
                </div>
                
                <h4>Ventajas del backup incremental:</h4>
                <div class="info-box">
                    <ul>
                        <li>Menor uso de espacio de almacenamiento</li>
                        <li>Proceso más rápido</li>
                        <li>Menor impacto en el rendimiento del sistema</li>
                        <li>Permite recuperación granular</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-clock"></i> Backup Diferencial</h4>
                
                <div class="step-box">
                    <p><strong>Características del backup diferencial:</strong></p>
                    <ul>
                        <li><strong>Incluye:</strong> Cambios desde el último backup completo</li>
                        <li><strong>Frecuencia:</strong> Cada 2-3 días</li>
                        <li><strong>Tamaño:</strong> Mediano</li>
                        <li><strong>Tiempo de restauración:</strong> Moderado</li>
                        <li><strong>Uso:</strong> Balance entre espacio y tiempo</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-tools"></i> Herramientas de Backup</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-terminal"></i> Backup Manual</h4>
                
                <div class="step-box">
                    <p><strong>Para realizar backup manual de la base de datos:</strong></p>
                    <ol>
                        <li>Accede al panel de administración</li>
                        <li>Ve a <strong>"Gestión de Base de Datos"</strong></li>
                        <li>Haz clic en <strong>"Crear Backup"</strong></li>
                        <li>Selecciona el tipo de backup</li>
                        <li>Configura las opciones:</li>
                        <ul>
                            <li>Incluir datos de estudiantes</li>
                            <li>Incluir datos de profesores</li>
                            <li>Incluir calificaciones</li>
                            <li>Incluir configuraciones</li>
                        </ul>
                        <li>Haz clic en <strong>"Iniciar Backup"</strong></li>
                    </ol>
                </div>
                
                <h4>Comando MySQL directo:</h4>
                <div class="code-block"><code># Backup completo de la base de datos
mysqldump -u root -p sistema_admin_eest2 > backup_completo_$(date +%Y%m%d_%H%M%S).sql

# Backup solo estructura
mysqldump -u root -p --no-data sistema_admin_eest2 > estructura_$(date +%Y%m%d).sql

# Backup solo datos
mysqldump -u root -p --no-create-info sistema_admin_eest2 > datos_$(date +%Y%m%d).sql</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-robot"></i> Backup Automático</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar backup automático:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Configuración de Backup"</strong></li>
                        <li>Activa <strong>"Backup Automático"</strong></li>
                        <li>Configura la programación:</li>
                        <ul>
                            <li>Frecuencia (diario, semanal, mensual)</li>
                            <li>Hora de ejecución</li>
                            <li>Tipo de backup</li>
                        </ul>
                        <li>Define la retención:</li>
                        <ul>
                            <li>Backups diarios: 7 días</li>
                            <li>Backups semanales: 4 semanas</li>
                            <li>Backups mensuales: 12 meses</li>
                        </ul>
                        <li>Configura la ubicación de almacenamiento</li>
                        <li>Guarda la configuración</li>
                    </ol>
                </div>
                
                <h4>Script de backup automático:</h4>
                <div class="code-block"><code>#!/bin/bash
# Script de backup automático
BACKUP_DIR="/backups/sistema_admin"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="sistema_admin_eest2"
DB_USER="root"
DB_PASS="tu_password"

# Crear directorio si no existe
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Backup de archivos del sistema
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/SistemaAdmin/

# Comprimir backup de BD
gzip $BACKUP_DIR/backup_$DATE.sql

# Limpiar backups antiguos (más de 30 días)
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completado: $DATE"</code></div>
            </div>
            
            <h2><i class="fas fa-cloud"></i> Estrategias de Almacenamiento</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-hdd"></i> Almacenamiento Local</h4>
                
                <div class="step-box">
                    <p><strong>Ventajas del almacenamiento local:</strong></p>
                    <ul>
                        <li><strong>Acceso rápido:</strong> Restauración inmediata</li>
                        <li><strong>Control total:</strong> Sin dependencias externas</li>
                        <li><strong>Costo:</strong> Sin costos de transferencia</li>
                        <li><strong>Seguridad:</strong> Datos no salen del servidor</li>
                    </ul>
                </div>
                
                <h4>Configuración recomendada:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Disco principal:</strong> Sistema operativo y aplicación</li>
                        <li><strong>Disco de backup:</strong> Solo para respaldos</li>
                        <li><strong>RAID 1:</strong> Espejo para redundancia</li>
                        <li><strong>Particiones separadas:</strong> Aislar backups del sistema</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-cloud-upload-alt"></i> Almacenamiento en la Nube</h4>
                
                <div class="step-box">
                    <p><strong>Ventajas del almacenamiento en la nube:</strong></p>
                    <ul>
                        <li><strong>Disponibilidad:</strong> Acceso desde cualquier lugar</li>
                        <li><strong>Escalabilidad:</strong> Espacio ilimitado</li>
                        <li><strong>Redundancia:</strong> Múltiples copias automáticas</li>
                        <li><strong>Seguridad:</strong> Encriptación y protección</li>
                    </ul>
                </div>
                
                <h4>Servicios recomendados:</h4>
                <div class="success-box">
                    <ul>
                        <li><strong>Google Drive:</strong> Integración fácil, 15GB gratis</li>
                        <li><strong>Dropbox:</strong> Sincronización automática</li>
                        <li><strong>OneDrive:</strong> Integración con Microsoft</li>
                        <li><strong>AWS S3:</strong> Profesional, escalable</li>
                        <li><strong>Backblaze:</strong> Especializado en backup</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-undo"></i> Proceso de Restauración</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-history"></i> Restauración Completa</h4>
                
                <div class="step-box">
                    <p><strong>Para restaurar desde backup completo:</strong></p>
                    <ol>
                        <li>Detén el servicio web (Apache/Nginx)</li>
                        <li>Accede a MySQL como administrador</li>
                        <li>Elimina la base de datos actual (si es necesario)</li>
                        <li>Crea una nueva base de datos</li>
                        <li>Restaura desde el archivo de backup</li>
                        <li>Verifica la integridad de los datos</li>
                        <li>Reinicia el servicio web</li>
                    </ol>
                </div>
                
                <h4>Comando de restauración:</h4>
                <div class="code-block"><code># Restaurar base de datos completa
mysql -u root -p sistema_admin_eest2 < backup_completo_20241201_120000.sql

# Restaurar solo estructura
mysql -u root -p sistema_admin_eest2 < estructura_20241201.sql

# Restaurar solo datos
mysql -u root -p sistema_admin_eest2 < datos_20241201.sql</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-search"></i> Restauración Selectiva</h4>
                
                <div class="step-box">
                    <p><strong>Para restaurar datos específicos:</strong></p>
                    <ol>
                        <li>Identifica las tablas a restaurar</li>
                        <li>Extrae solo esas tablas del backup</li>
                        <li>Haz backup de los datos actuales</li>
                        <li>Restaura las tablas específicas</li>
                        <li>Verifica la integridad</li>
                    </ol>
                </div>
                
                <h4>Ejemplo de restauración selectiva:</h4>
                <div class="code-block"><code># Extraer solo tabla de estudiantes
sed -n '/CREATE TABLE.*estudiantes/,/CREATE TABLE/p' backup.sql > estudiantes.sql

# Restaurar solo esa tabla
mysql -u root -p sistema_admin_eest2 < estudiantes.sql</code></div>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Seguridad de Backups</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-lock"></i> Encriptación</h4>
                
                <div class="step-box">
                    <p><strong>Para encriptar backups:</strong></p>
                    <ul>
                        <li><strong>Encriptación en reposo:</strong> Archivos encriptados en disco</li>
                        <li><strong>Encriptación en tránsito:</strong> Transferencia segura</li>
                        <li><strong>Claves de encriptación:</strong> Gestión segura de claves</li>
                        <li><strong>Algoritmos:</strong> AES-256 recomendado</li>
                    </ul>
                </div>
                
                <h4>Comando de encriptación:</h4>
                <div class="code-block"><code># Encriptar backup con GPG
gpg --cipher-algo AES256 --compress-algo 1 --symmetric backup.sql

# Encriptar con OpenSSL
openssl enc -aes-256-cbc -salt -in backup.sql -out backup.sql.enc

# Desencriptar
openssl enc -aes-256-cbc -d -in backup.sql.enc -out backup.sql</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Gestión de Accesos</h4>
                
                <div class="step-box">
                    <p><strong>Políticas de acceso a backups:</strong></p>
                    <ul>
                        <li><strong>Principio de menor privilegio:</strong> Solo acceso necesario</li>
                        <li><strong>Autenticación multifactor:</strong> MFA para accesos críticos</li>
                        <li><strong>Auditoría:</strong> Registro de todos los accesos</li>
                        <li><strong>Rotación de credenciales:</strong> Cambio regular de contraseñas</li>
                    </ul>
                </div>
                
                <h4>Permisos recomendados:</h4>
                <div class="warning-box checklist-box">
                    <ul>
                        <li>☐ Solo administradores pueden crear backups</li>
                        <li>☐ Solo administradores pueden restaurar</li>
                        <li>☐ Acceso de solo lectura para auditoría</li>
                        <li>☐ Logs de todos los accesos</li>
                        <li>☐ Notificaciones de accesos sospechosos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-calendar-check"></i> Plan de Retención</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-clock"></i> Política de Retención</h4>
                
                <div class="step-box">
                    <p><strong>Estrategia de retención recomendada:</strong></p>
                    <ul>
                        <li><strong>Backups diarios:</strong> Retener 7 días</li>
                        <li><strong>Backups semanales:</strong> Retener 4 semanas</li>
                        <li><strong>Backups mensuales:</strong> Retener 12 meses</li>
                        <li><strong>Backups anuales:</strong> Retener 7 años</li>
                    </ul>
                </div>
                
                <h4>Script de limpieza automática:</h4>
                <div class="code-block"><code>#!/bin/bash
# Script de limpieza de backups antiguos
BACKUP_DIR="/backups/sistema_admin"

# Eliminar backups diarios mayores a 7 días
find $BACKUP_DIR -name "backup_daily_*" -mtime +7 -delete

# Eliminar backups semanales mayores a 4 semanas
find $BACKUP_DIR -name "backup_weekly_*" -mtime +28 -delete

# Eliminar backups mensuales mayores a 12 meses
find $BACKUP_DIR -name "backup_monthly_*" -mtime +365 -delete

echo "Limpieza de backups completada"</code></div>
            </div>
            
            <h2><i class="fas fa-vial"></i> Pruebas de Restauración</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-check-circle"></i> Validación de Backups</h4>
                
                <div class="step-box">
                    <p><strong>Para probar la integridad de backups:</strong></p>
                    <ol>
                        <li>Programa pruebas regulares (mensuales)</li>
                        <li>Restaura en un entorno de prueba</li>
                        <li>Verifica la integridad de los datos</li>
                        <li>Prueba la funcionalidad del sistema</li>
                        <li>Documenta los resultados</li>
                    </ol>
                </div>
                
                <h4>Checklist de validación:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Backup se restaura sin errores</li>
                        <li>☐ Todas las tablas están presentes</li>
                        <li>☐ Los datos son consistentes</li>
                        <li>☐ El sistema funciona correctamente</li>
                        <li>☐ Los usuarios pueden acceder</li>
                        <li>☐ Los reportes se generan correctamente</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-exclamation-triangle"></i> Recuperación ante Desastres</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-fire"></i> Plan de Continuidad</h4>
                
                <div class="step-box">
                    <p><strong>En caso de desastre:</strong></p>
                    <ol>
                        <li><strong>Evaluación:</strong> Determinar el alcance del daño</li>
                        <li><strong>Comunicación:</strong> Notificar a usuarios y administradores</li>
                        <li><strong>Recuperación:</strong> Restaurar desde el backup más reciente</li>
                        <li><strong>Validación:</strong> Verificar que todo funciona</li>
                        <li><strong>Comunicación:</strong> Notificar la resolución</li>
                        <li><strong>Análisis:</strong> Revisar qué causó el problema</li>
                    </ol>
                </div>
                
                <h4>RTO y RPO objetivos:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>RTO (Recovery Time Objective):</strong> 4 horas máximo</li>
                        <li><strong>RPO (Recovery Point Objective):</strong> 24 horas máximo</li>
                        <li><strong>Disponibilidad objetivo:</strong> 99.5%</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Backup</h4>
                
                <p><strong>¿Con qué frecuencia debo hacer backup?</strong><br>
                Backup completo semanal, incremental diario. Ajusta según la criticidad de los datos.</p>
                
                <p><strong>¿Dónde debo almacenar los backups?</strong><br>
                Implementa la regla 3-2-1: 3 copias, 2 tipos de almacenamiento, 1 fuera del sitio.</p>
                
                <p><strong>¿Cómo verifico que mi backup funciona?</strong><br>
                Restaura periódicamente en un entorno de prueba y verifica la integridad.</p>
                
                <p><strong>¿Qué hago si el backup falla?</strong><br>
                Revisa los logs de error, verifica el espacio disponible y la conectividad.</p>
                
                <p><strong>¿Puedo hacer backup solo de datos específicos?</strong><br>
                Sí, puedes hacer backup selectivo de tablas o datos específicos.</p>
            </div>
            
            <h2><i class="fas fa-lightbulb"></i> Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-star"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Automatiza todos los backups críticos</li>
                    <li>Prueba regularmente la restauración</li>
                    <li>Mantén múltiples copias en diferentes ubicaciones</li>
                    <li>Encripta los backups sensibles</li>
                    <li>Documenta todos los procesos de backup</li>
                    <li>Monitorea el éxito de los backups</li>
                    <li>Mantén un plan de recuperación actualizado</li>
                    <li>Capacita al personal en procedimientos de emergencia</li>
                </ul>
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
