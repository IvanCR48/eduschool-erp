<?php
$pageTitle = 'Administración del Sistema - Documentación EEST2';
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
            <h1><i class="fas fa-clipboard-list"></i> Administración del Sistema</h1>
            <p>Guía completa para la administración y gestión del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-cogs"></i> Panel de Administración</h2>
            
            <div class="step-box">
                <p><strong>Para acceder al panel de administración:</strong></p>
                <ol>
                    <li>Inicia sesión con una cuenta de administrador</li>
                    <li>Desde el menú principal, haz clic en <strong>"Administración"</strong></li>
                    <li>Se abrirá el panel con todas las herramientas administrativas</li>
                </ol>
            </div>
            
            <div class="danger-box">
                <p><i class="fas fa-exclamation-triangle"></i> <strong>Acceso restringido:</strong> Solo los usuarios con rol de Administrador pueden acceder a este módulo. Todos los accesos son registrados en logs de seguridad.</p>
            </div>
            
            <h2><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-plus"></i> Crear Nuevos Usuarios</h4>
                
                <div class="step-box">
                    <p><strong>Para crear un nuevo usuario:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Gestión de Usuarios"</strong></li>
                        <li>Haz clic en <strong>"Agregar Usuario"</strong></li>
                        <li>Completa el formulario con los datos requeridos:</li>
                    </ol>
                </div>
                
                <h4>Datos obligatorios:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Nombre completo</li>
                        <li>☐ Email válido</li>
                        <li>☐ Contraseña segura</li>
                        <li>☐ Rol del usuario</li>
                        <li>☐ Estado (activo/inactivo)</li>
                    </ul>
                </div>
                
                <h4>Roles disponibles:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                        <li><strong>Secretario:</strong> Gestión administrativa</li>
                        <li><strong>Profesor:</strong> Acceso a materias asignadas</li>
                        <li><strong>Preceptor:</strong> Gestión de cursos específicos</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-edit"></i> Modificar Usuarios</h4>
                
                <div class="step-box">
                    <p><strong>Para modificar un usuario:</strong></p>
                    <ol>
                        <li>Busca el usuario en la lista</li>
                        <li>Haz clic en <strong>"Editar"</strong></li>
                        <li>Modifica los campos necesarios</li>
                        <li>Guarda los cambios</li>
                    </ol>
                </div>
                
                <h4>Campos modificables:</h4>
                <ul>
                    <li>Información personal</li>
                    <li>Datos de contacto</li>
                    <li>Rol y permisos</li>
                    <li>Estado del usuario</li>
                    <li>Email asociado (Google OAuth)</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-lock"></i> Políticas de Contraseñas</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar políticas de contraseñas:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Configuración de Seguridad"</strong></li>
                        <li>Selecciona <strong>"Políticas de Contraseñas"</strong></li>
                        <li>Configura los parámetros:</li>
                    </ol>
                </div>
                
                <h4>Parámetros configurables:</h4>
                <div class="warning-box checklist-box">
                    <ul>
                        <li>☐ Longitud mínima (8-20 caracteres)</li>
                        <li>☐ Requerir mayúsculas y minúsculas</li>
                        <li>☐ Requerir números</li>
                        <li>☐ Requerir caracteres especiales</li>
                        <li>☐ No permitir contraseñas comunes</li>
                        <li>☐ Caducidad de contraseñas (días)</li>
                        <li>☐ Historial de contraseñas</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Integraciones de Seguridad</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar las integraciones de acceso seguro:</strong></p>
                    <ol>
                        <li>Asegúrate de tener acceso a las claves del proyecto en Google Console y reCAPTCHA Admin.</li>
                        <li>Crea y configura el archivo local <code>config/google_oauth.local.php</code> para habilitar el login a docentes.</li>
                        <li>Crea y configura el archivo local <code>config/recaptcha.local.php</code> para activar la validación en el formulario de login.</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-database"></i> Gestión de Base de Datos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-save"></i> Backup y Restauración</h4>
                
                <div class="step-box">
                    <p><strong>Para realizar backup de la base de datos:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Gestión de Base de Datos"</strong></li>
                        <li>Haz clic en <strong>"Crear Backup"</strong></li>
                        <li>Selecciona el tipo de backup:</li>
                        <ul>
                            <li>Completo (todos los datos)</li>
                            <li>Incremental (solo cambios)</li>
                            <li>Específico (tablas seleccionadas)</li>
                        </ul>
                        <li>Configura la programación si es necesario</li>
                        <li>Inicia el proceso de backup</li>
                    </ol>
                </div>
                
                <h4>Opciones de backup:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Automático:</strong> Programado diariamente</li>
                        <li><strong>Manual:</strong> Cuando sea necesario</li>
                        <li><strong>Compresión:</strong> Para ahorrar espacio</li>
                        <li><strong>Encriptación:</strong> Para mayor seguridad</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-undo"></i> Restauración de Datos</h4>
                
                <div class="step-box">
                    <p><strong>Para restaurar desde backup:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Restaurar Backup"</strong></li>
                        <li>Selecciona el archivo de backup</li>
                        <li>Verifica la fecha y hora del backup</li>
                        <li>Confirma la restauración</li>
                        <li>Espera a que se complete el proceso</li>
                    </ol>
                </div>
                
                <div class="danger-box">
                    <p><i class="fas fa-exclamation-circle"></i> <strong>Advertencia:</strong> La restauración sobrescribirá todos los datos actuales. Asegúrate de hacer un backup antes de restaurar.</p>
                </div>
            </div>
            
            <h2><i class="fas fa-chart-line"></i> Monitoreo del Sistema</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-tachometer-alt"></i> Dashboard de Monitoreo</h4>
                
                <div class="step-box">
                    <p><strong>Métricas monitoreadas:</strong></p>
                    <ul>
                        <li><strong>Uso del servidor:</strong> CPU, memoria, disco</li>
                        <li><strong>Actividad de usuarios:</strong> Sesiones activas</li>
                        <li><strong>Rendimiento:</strong> Tiempo de respuesta</li>
                        <li><strong>Errores:</strong> Logs de errores</li>
                        <li><strong>Base de datos:</strong> Conexiones y consultas</li>
                    </ul>
                </div>
                
                <h4>Alertas automáticas:</h4>
                <div class="warning-box">
                    <ul>
                        <li>Alto uso de CPU (>80%)</li>
                        <li>Memoria insuficiente</li>
                        <li>Espacio en disco bajo</li>
                        <li>Errores críticos del sistema</li>
                        <li>Intentos de acceso no autorizados</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Gestión de Logs</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-list-alt"></i> Tipos de Logs</h4>
                
                <div class="step-box">
                    <p><strong>Logs disponibles:</strong></p>
                    <ul>
                        <li><strong>Logs de acceso:</strong> Intentos de login</li>
                        <li><strong>Logs de seguridad:</strong> Actividades sospechosas</li>
                        <li><strong>Logs de errores:</strong> Errores del sistema</li>
                        <li><strong>Logs de actividad:</strong> Acciones de usuarios</li>
                        <li><strong>Logs de base de datos:</strong> Consultas y transacciones</li>
                    </ul>
                </div>
                
                <h4>Gestión de logs:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Visualización:</strong> Ver logs en tiempo real</li>
                        <li><strong>Filtrado:</strong> Por fecha, usuario, tipo</li>
                        <li><strong>Exportación:</strong> Descargar logs en formato CSV</li>
                        <li><strong>Rotación:</strong> Archivo automático de logs antiguos</li>
                        <li><strong>Limpieza:</strong> Eliminar logs obsoletos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-cog"></i> Configuración del Sistema</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-sliders-h"></i> Parámetros Generales</h4>
                
                <div class="step-box">
                    <p><strong>Configuraciones disponibles:</strong></p>
                    <ul>
                        <li><strong>Información de la institución:</strong> Nombre, dirección, contacto</li>
                        <li><strong>Configuración académica:</strong> Años, especialidades, materias</li>
                        <li><strong>Configuración de sesiones:</strong> Tiempo de expiración</li>
                        <li><strong>Configuración de email:</strong> SMTP, plantillas</li>
                        <li><strong>Configuración de archivos:</strong> Tamaños máximos, tipos permitidos</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-calendar-alt"></i> Configuración Académica</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar el año académico:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Configuración Académica"</strong></li>
                        <li>Define el año lectivo actual</li>
                        <li>Configura los períodos de evaluación</li>
                        <li>Establece fechas importantes:</li>
                        <ul>
                            <li>Inicio y fin de clases</li>
                            <li>Períodos de exámenes</li>
                            <li>Vacaciones</li>
                            <li>Fechas de entrega de notas</li>
                        </ul>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-users"></i> Gestión de Roles y Permisos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Permisos por Rol</h4>
                
                <div class="step-box">
                    <p><strong>Para gestionar permisos:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Roles y Permisos"</strong></li>
                        <li>Selecciona el rol a modificar</li>
                        <li>Configura los permisos específicos:</li>
                    </ol>
                </div>
                
                <h4>Permisos por módulo:</h4>
                <div class="success-box">
                    <p><strong>Estudiantes:</strong></p>
                    <ul>
                        <li>Ver lista de estudiantes</li>
                        <li>Agregar/editar estudiantes</li>
                        <li>Ver fichas completas</li>
                        <li>Gestionar notas</li>
                        <li>Registrar asistencias</li>
                    </ul>
                    
                    <p><strong>Profesores:</strong></p>
                    <ul>
                        <li>Ver lista de profesores</li>
                        <li>Agregar/editar profesores</li>
                        <li>Asignar materias</li>
                        <li>Configurar horarios</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-tools"></i> Herramientas de Mantenimiento</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-wrench"></i> Mantenimiento Automático</h4>
                
                <div class="step-box">
                    <p><strong>Tareas de mantenimiento:</strong></p>
                    <ul>
                        <li><strong>Optimización de base de datos:</strong> Limpieza de índices</li>
                        <li><strong>Limpieza de logs:</strong> Archivo de logs antiguos</li>
                        <li><strong>Verificación de integridad:</strong> Chequeo de datos</li>
                        <li><strong>Actualización de estadísticas:</strong> Cálculo de métricas</li>
                        <li><strong>Limpieza de caché:</strong> Liberación de memoria</li>
                    </ul>
                </div>
                
                <h4>Programación de tareas:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Diario:</strong> Limpieza de logs y caché</li>
                        <li><strong>Semanal:</strong> Optimización de base de datos</li>
                        <li><strong>Mensual:</strong> Verificación completa del sistema</li>
                        <li><strong>Trimestral:</strong> Auditoría de seguridad</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Auditoría y Seguridad</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-search"></i> Auditoría del Sistema</h4>
                
                <div class="step-box">
                    <p><strong>Para realizar auditoría:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Auditoría del Sistema"</strong></li>
                        <li>Selecciona el tipo de auditoría:</li>
                        <ul>
                            <li>Auditoría de seguridad</li>
                            <li>Auditoría de datos</li>
                            <li>Auditoría de permisos</li>
                            <li>Auditoría de accesos</li>
                        </ul>
                        <li>Configura el período a auditar</li>
                        <li>Genera el reporte de auditoría</li>
                    </ol>
                </div>
                
                <h4>Elementos auditados:</h4>
                <div class="warning-box checklist-box">
                    <ul>
                        <li>☐ Accesos de usuarios</li>
                        <li>☐ Modificaciones de datos</li>
                        <li>☐ Cambios de permisos</li>
                        <li>☐ Intentos de acceso fallidos</li>
                        <li>☐ Actividades sospechosas</li>
                        <li>☐ Configuraciones del sistema</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Administración</h4>
                
                <p><strong>¿Cómo creo un nuevo usuario administrador?</strong><br>
                Ve a "Gestión de Usuarios" → "Agregar Usuario" → Selecciona rol "Administrador".</p>
                
                <p><strong>¿Cómo configuro backup automático?</strong><br>
                Ve a "Gestión de Base de Datos" → "Configurar Backup" → Programa la frecuencia.</p>
                
                <p><strong>¿Cómo veo los logs del sistema?</strong><br>
                Ve a "Gestión de Logs" y selecciona el tipo de log que deseas revisar.</p>
                
                <p><strong>¿Cómo cambio los permisos de un rol?</strong><br>
                Ve a "Roles y Permisos" → Selecciona el rol → Modifica los permisos específicos.</p>
                
                <p><strong>¿Cómo realizo mantenimiento del sistema?</strong><br>
                Ve a "Herramientas de Mantenimiento" → Ejecuta las tareas necesarias.</p>
            </div>
            
            <h2><i class="fas fa-lightbulb"></i> Consejos y Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-star"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Realiza backups regulares de la base de datos</li>
                    <li>Monitorea los logs de seguridad diariamente</li>
                    <li>Mantén las contraseñas seguras y actualizadas</li>
                    <li>Configura alertas automáticas para problemas críticos</li>
                    <li>Revisa periódicamente los permisos de usuarios</li>
                    <li>Ejecuta mantenimiento preventivo regularmente</li>
                    <li>Mantén actualizado el sistema y sus dependencias</li>
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
