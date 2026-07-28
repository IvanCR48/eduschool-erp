<?php
$pageTitle = 'Guía de Administrador - Documentación EEST2';
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
            <h1><i class="fas fa-user-shield"></i> Guía de Administrador</h1>
            <p>Manual completo para la administración del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-crown"></i> Rol del Administrador</h2>
            
            <p>Como administrador del sistema, tienes acceso completo a todas las funcionalidades y la responsabilidad de mantener el sistema funcionando de manera óptima. Esta guía te ayudará a gestionar eficientemente todos los aspectos del sistema.</p>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> IMPORTANTE:</strong> Los administradores tienen permisos completos sobre el sistema. Usa estos privilegios con responsabilidad.
            </div>
            
            <h2><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
            
            <h3>Crear Nuevos Usuarios</h3>
            <ol>
                <li>Ve a <strong>Panel Admin → Gestión de Usuarios</strong></li>
                <li>Haz clic en <strong>"Nuevo Usuario"</strong></li>
                <li>Completa el formulario:
                    <ul>
                        <li><strong>Nombre de usuario:</strong> Único, sin espacios</li>
                        <li><strong>Email:</strong> Correo institucional válido</li>
                        <li><strong>Contraseña:</strong> Mínimo 8 caracteres, debe cumplir políticas de seguridad</li>
                        <li><strong>Rol:</strong> Selecciona el rol apropiado</li>
                        <li><strong>Permisos:</strong> Asigna permisos específicos si es necesario</li>
                    </ul>
                </li>
                <li>Haz clic en <strong>"Crear Usuario"</strong></li>
            </ol>
            
            <div class="info-box">
                <strong><i class="fas fa-lightbulb"></i> Consejo:</strong> Activa la opción "Forzar cambio de contraseña en primer login" para nuevos usuarios.
            </div>
            
            <h3>Roles y Permisos del Sistema</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Permisos</th>
                        <th>Acceso a</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Administrador</strong></td>
                        <td>Acceso total</td>
                        <td>Todo el sistema, configuración, usuarios, backups</td>
                    </tr>
                    <tr>
                        <td><strong>Director</strong></td>
                        <td>Gestión académica completa</td>
                        <td>Estudiantes, profesores, reportes, estadísticas</td>
                    </tr>
                    <tr>
                        <td><strong>Secretaria</strong></td>
                        <td>Gestión administrativa</td>
                        <td>Estudiantes, documentos, certificados</td>
                    </tr>
                    <tr>
                        <td><strong>Preceptor</strong></td>
                        <td>Gestión de asistencia</td>
                        <td>Asistencia, llamados, estudiantes de su turno</td>
                    </tr>
                    <tr>
                        <td><strong>Profesor</strong></td>
                        <td>Gestión de notas</td>
                        <td>Notas de sus materias, sus estudiantes</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>Modificar Usuarios Existentes</h3>
            <ol>
                <li>Busca el usuario en la lista</li>
                <li>Haz clic en el botón <strong>"Editar"</strong></li>
                <li>Modifica la información necesaria</li>
                <li>Guarda los cambios</li>
            </ol>
            
            <h3>Desactivar/Activar Usuarios</h3>
            <p>En lugar de eliminar usuarios, es recomendable desactivarlos:</p>
            <ol>
                <li>Selecciona el usuario</li>
                <li>Cambia el estado a <strong>"Inactivo"</strong></li>
                <li>El usuario no podrá iniciar sesión pero se conserva su historial</li>
            </ol>
            
            <h3>Resetear Contraseñas</h3>
            <ol>
                <li>Selecciona el usuario</li>
                <li>Haz clic en <strong>"Resetear Contraseña"</strong></li>
                <li>Genera una contraseña temporal</li>
                <li>Comunícala al usuario de forma segura</li>
                <li>Marca la opción "Forzar cambio en próximo login"</li>
            </ol>
            
            <h2><i class="fas fa-shield-alt"></i> Seguridad del Sistema</h2>
            
            <h3>Configuración de Google OAuth y reCAPTCHA v2</h3>
            
            <div class="success-box">
                <strong><i class="fas fa-check-circle"></i> Recomendado:</strong> Protege el acceso y simplifica el ingreso configurando Google OAuth y reCAPTCHA.
            </div>
            
            <ol>
                <li>Crea el archivo <code>config/google_oauth.local.php</code> a partir del archivo de ejemplo en el proyecto. Completa las claves <code>client_id</code> y <code>client_secret</code> provistas por Google Console.</li>
                <li>Crea el archivo <code>config/recaptcha.local.php</code> a partir de su ejemplo y completa las claves del sitio y secretas de Google reCAPTCHA v2.</li>
                <li>El sistema activará de forma automática el botón "Continuar con Google" y el widget reCAPTCHA en el login al detectar las configuraciones.</li>
            </ol>
            
            <h3>Políticas de Contraseñas</h3>
            <p>Configura las políticas de contraseñas del sistema:</p>
            <ul>
                <li><strong>Longitud mínima:</strong> 8-12 caracteres (recomendado: 12)</li>
                <li><strong>Complejidad:</strong> Mayúsculas, minúsculas, números y caracteres especiales</li>
                <li><strong>Expiración:</strong> 90 días (configurable)</li>
                <li><strong>Historial:</strong> No reutilizar las últimas 5 contraseñas</li>
                <li><strong>Intentos fallidos:</strong> Bloqueo después de 5 intentos</li>
            </ul>
            
            <h3>Monitoreo de Sesiones</h3>
            <ol>
                <li>Ve a <strong>Panel Admin → Sesiones Activas</strong></li>
                <li>Visualiza:
                    <ul>
                        <li>Usuarios conectados actualmente</li>
                        <li>IP y ubicación aproximada</li>
                        <li>Hora de inicio de sesión</li>
                        <li>Última actividad</li>
                    </ul>
                </li>
                <li>Puedes cerrar sesiones sospechosas manualmente</li>
            </ol>
            
            <h3>Revisión de Logs de Seguridad</h3>
            <ol>
                <li>Accede a <strong>Panel Admin → Logs de Seguridad</strong></li>
                <li>Revisa eventos importantes:
                    <ul>
                        <li>Intentos de login fallidos</li>
                        <li>Cambios en permisos de usuarios</li>
                        <li>Accesos a información sensible</li>
                        <li>Modificaciones en configuración</li>
                    </ul>
                </li>
                <li>Exporta logs para auditoría externa si es necesario</li>
            </ol>
            
            <div class="danger-box">
                <strong><i class="fas fa-exclamation-circle"></i> ALERTA:</strong> Si detectas actividad sospechosa, cambia inmediatamente las contraseñas afectadas y revisa los permisos.
            </div>
            
            <h2><i class="fas fa-database"></i> Gestión de Backups</h2>
            
            <h3>Crear Backup Manual</h3>
            <ol>
                <li>Ve a <strong>Panel Admin → Backups</strong></li>
                <li>Haz clic en <strong>"Crear Backup Ahora"</strong></li>
                <li>Selecciona qué incluir:
                    <ul>
                        <li><strong>Base de datos:</strong> Siempre recomendado</li>
                        <li><strong>Archivos del sistema:</strong> Código fuente</li>
                        <li><strong>Archivos subidos:</strong> Documentos, imágenes</li>
                    </ul>
                </li>
                <li>Espera a que el backup se complete</li>
                <li>Descarga el archivo generado</li>
            </ol>
            
            <h3>Configurar Backups Automáticos</h3>
            <ol>
                <li>En <strong>Panel Admin → Backups → Configuración</strong></li>
                <li>Habilita <strong>"Backups Automáticos"</strong></li>
                <li>Configura:
                    <ul>
                        <li><strong>Frecuencia:</strong> Diaria, Semanal, Mensual</li>
                        <li><strong>Hora de ejecución:</strong> Fuera del horario escolar (ej: 2:00 AM)</li>
                        <li><strong>Retención:</strong> Últimos 30 días recomendado</li>
                        <li><strong>Ubicación:</strong> Local + Nube (si está disponible)</li>
                    </ul>
                </li>
            </ol>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Importante:</strong> Guarda los backups en una ubicación externa (USB, servidor remoto, nube) además del servidor local.
            </div>
            
            <h3>Restaurar desde Backup</h3>
            
            <div class="danger-box">
                <strong><i class="fas fa-bomb"></i> PRECAUCIÓN:</strong> Restaurar un backup sobrescribirá los datos actuales. Asegúrate de que sea necesario.
            </div>
            
            <ol>
                <li>Ve a <strong>Panel Admin → Backups → Restaurar</strong></li>
                <li>Selecciona el backup a restaurar</li>
                <li>Verifica la fecha y hora del backup</li>
                <li>Haz clic en <strong>"Restaurar"</strong></li>
                <li>Confirma la acción</li>
                <li>Espera a que el proceso se complete</li>
                <li>Verifica que todo funcione correctamente</li>
            </ol>
            
            <h2><i class="fas fa-cogs"></i> Configuración del Sistema</h2>
            
            <h3>Configuración General</h3>
            <p>En <strong>Panel Admin → Configuración General</strong> puedes ajustar:</p>
            
            <ul>
                <li><strong>Información de la Institución:</strong>
                    <ul>
                        <li>Nombre de la escuela</li>
                        <li>Logo institucional</li>
                        <li>Dirección y contacto</li>
                        <li>Colores corporativos</li>
                    </ul>
                </li>
                <li><strong>Configuración Académica:</strong>
                    <ul>
                        <li>Año escolar actual</li>
                        <li>Cuatrimestres</li>
                        <li>Fechas de cierre de notas</li>
                        <li>Escala de calificación (1-10)</li>
                    </ul>
                </li>
                <li><strong>Notificaciones:</strong>
                    <ul>
                        <li>Email de notificaciones del sistema</li>
                        <li>Plantillas de emails</li>
                        <li>Destinatarios predeterminados</li>
                    </ul>
                </li>
            </ul>
            
            <h3>Configuración de Email</h3>
            <ol>
                <li>Ve a <strong>Panel Admin → Configuración → Email</strong></li>
                <li>Configura el servidor SMTP:
                    <div class="code-block"><code>Servidor SMTP: smtp.gmail.com
Puerto: 587
Seguridad: TLS
Usuario: tu_email@eest2.edu.ar
Contraseña: [contraseña de aplicación]</code></div>
                </li>
                <li>Haz una prueba de envío</li>
            </ol>
            
            <h3>Mantenimiento del Sistema</h3>
            
            <h4>Modo Mantenimiento</h4>
            <p>Activa el modo mantenimiento para realizar actualizaciones:</p>
            <ol>
                <li>Ve a <strong>Panel Admin → Mantenimiento</strong></li>
                <li>Activa <strong>"Modo Mantenimiento"</strong></li>
                <li>Configura el mensaje para los usuarios</li>
                <li>Realiza las tareas de mantenimiento</li>
                <li>Desactiva el modo cuando termines</li>
            </ol>
            
            <h4>Limpieza de Base de Datos</h4>
            <p>Mantén la base de datos optimizada:</p>
            <ul>
                <li><strong>Eliminar sesiones expiradas:</strong> Automático cada 24 horas</li>
                <li><strong>Limpiar logs antiguos:</strong> Mantener últimos 90 días</li>
                <li><strong>Optimizar tablas:</strong> Mensualmente</li>
            </ul>
            
            <h2><i class="fas fa-chart-line"></i> Reportes y Estadísticas</h2>
            
            <h3>Dashboard de Estadísticas</h3>
            <p>El dashboard de administrador muestra:</p>
            <ul>
                <li>Total de usuarios activos</li>
                <li>Estudiantes por curso y turno</li>
                <li>Profesores activos</li>
                <li>Uso del sistema (logins diarios)</li>
                <li>Espacio en disco utilizado</li>
                <li>Estado de backups</li>
            </ul>
            
            <h3>Generar Reportes Personalizados</h3>
            <ol>
                <li>Ve a <strong>Panel Admin → Reportes</strong></li>
                <li>Selecciona el tipo de reporte:
                    <ul>
                        <li>Académico</li>
                        <li>Administrativo</li>
                        <li>Seguridad</li>
                        <li>Uso del sistema</li>
                    </ul>
                </li>
                <li>Define filtros y parámetros</li>
                <li>Genera el reporte</li>
                <li>Exporta a PDF, Excel o CSV</li>
            </ol>
            
            <h2><i class="fas fa-bell"></i> Notificaciones y Alertas</h2>
            
            <h3>Configurar Alertas Automáticas</h3>
            <p>Recibe notificaciones sobre eventos importantes:</p>
            <ul>
                <li><strong>Seguridad:</strong> Intentos de login fallidos, cambios en permisos</li>
                <li><strong>Sistema:</strong> Errores críticos, espacio en disco bajo</li>
                <li><strong>Backups:</strong> Fallos en backups automáticos</li>
                <li><strong>Académico:</strong> Cierre de cuatrimestres, fechas límite</li>
            </ul>
            
            <h2><i class="fas fa-book-open"></i> Mejores Prácticas</h2>
            
            <div class="success-box">
                <h3 style="margin-top: 0;">Recomendaciones para Administradores</h3>
                <ul style="margin-bottom: 0;">
                    <li>✅ Realiza backups antes de cualquier cambio importante</li>
                    <li>✅ Mantén actualizadas las contraseñas de administrador</li>
                    <li>✅ Revisa los logs de seguridad semanalmente</li>
                    <li>✅ Documenta todos los cambios importantes</li>
                    <li>✅ Capacita a los usuarios en el uso del sistema</li>
                    <li>✅ Mantén el sistema actualizado</li>
                    <li>✅ Realiza auditorías de seguridad trimestrales</li>
                    <li>✅ Ten un plan de recuperación ante desastres</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Resolución de Problemas Comunes</h2>
            
            <h3>Problema: Usuario no puede iniciar sesión</h3>
            <p><strong>Solución:</strong></p>
            <ol>
                <li>Verifica que el usuario esté activo</li>
                <li>Revisa si la cuenta está bloqueada por intentos fallidos</li>
                <li>Desbloquea la cuenta si es necesario</li>
                <li>Resetea la contraseña si persiste el problema</li>
            </ol>
            
            <h3>Problema: Sistema lento</h3>
            <p><strong>Solución:</strong></p>
            <ol>
                <li>Revisa el uso de recursos del servidor</li>
                <li>Optimiza la base de datos</li>
                <li>Limpia logs antiguos</li>
                <li>Verifica conexiones activas</li>
            </ol>
            
            <h3>Problema: Error en backup automático</h3>
            <p><strong>Solución:</strong></p>
            <ol>
                <li>Revisa los logs de backup</li>
                <li>Verifica espacio en disco</li>
                <li>Comprueba permisos de escritura</li>
                <li>Ejecuta un backup manual para diagnosticar</li>
            </ol>
            
            <h2><i class="fas fa-phone-alt"></i> Soporte Técnico</h2>
            
            <p>Si necesitas ayuda adicional:</p>
            <ul>
                <li><strong>Email:</strong> admin@eest2.edu.ar</li>
                <li><strong>Documentación Técnica:</strong> <a href="../api/README.md">Ver API Docs</a></li>
                <li><strong>Sistema de Tickets:</strong> Panel Admin → Soporte</li>
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
