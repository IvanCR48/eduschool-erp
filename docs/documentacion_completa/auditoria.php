<?php
$pageTitle = 'Sistema de Auditoría - Documentación EEST2';
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
            <h1><i class="fas fa-search"></i> Sistema de Auditoría</h1>
            <p>Monitoreo, registro y análisis de actividades del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-info-circle"></i> Introducción a la Auditoría</h2>
            
            <div class="info-box">
                <p><i class="fas fa-shield-alt"></i> <strong>Propósito:</strong> El sistema de auditoría registra y monitorea todas las actividades críticas para garantizar la seguridad, integridad y cumplimiento normativo.</p>
                <p><i class="fas fa-eye"></i> <strong>Cobertura:</strong> Todas las acciones de usuarios, cambios de datos, accesos al sistema y eventos de seguridad.</p>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-target"></i> Objetivos de la Auditoría</h4>
                
                <div class="step-box">
                    <p><strong>Los objetivos principales son:</strong></p>
                    <ul>
                        <li><strong>Seguridad:</strong> Detectar accesos no autorizados y actividades sospechosas</li>
                        <li><strong>Integridad:</strong> Verificar que los datos no sean modificados indebidamente</li>
                        <li><strong>Cumplimiento:</strong> Asegurar el cumplimiento de políticas y regulaciones</li>
                        <li><strong>Responsabilidad:</strong> Establecer trazabilidad de acciones</li>
                        <li><strong>Mejora continua:</strong> Identificar áreas de mejora en procesos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-list"></i> Tipos de Auditoría</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-shield-alt"></i> Auditoría de Seguridad</h4>
                
                <div class="audit-card">
                    <h4><i class="fas fa-lock"></i> Eventos de Seguridad</h4>
                    <p><strong>Se registran los siguientes eventos:</strong></p>
                    <ul>
                        <li><span class="audit-type audit-security">Seguridad</span> Intentos de login exitosos y fallidos</li>
                        <li><span class="audit-type audit-security">Seguridad</span> Cambios de contraseñas</li>
                        <li><span class="audit-type audit-security">Seguridad</span> Intentos de Google OAuth y logouts</li>
                        <li><span class="audit-type audit-security">Seguridad</span> Bloqueos de cuentas</li>
                        <li><span class="audit-type audit-security">Seguridad</span> Accesos desde IPs no reconocidas</li>
                        <li><span class="audit-type audit-security">Seguridad</span> Intentos de acceso no autorizado</li>
                    </ul>
                </div>
                
                <h4>Configuración de alertas:</h4>
                <div class="warning-box">
                    <ul>
                        <li><strong>Múltiples fallos de login:</strong> Alerta después de 3 intentos fallidos</li>
                        <li><strong>Acceso desde nueva IP:</strong> Notificación inmediata</li>
                        <li><strong>Cambios de permisos:</strong> Alerta a administradores</li>
                        <li><strong>Actividad fuera de horario:</strong> Monitoreo especial</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-database"></i> Auditoría de Datos</h4>
                
                <div class="audit-card">
                    <h4><i class="fas fa-edit"></i> Cambios de Datos</h4>
                    <p><strong>Se registran todas las modificaciones:</strong></p>
                    <ul>
                        <li><span class="audit-type audit-data">Datos</span> Creación de nuevos estudiantes/profesores</li>
                        <li><span class="audit-type audit-data">Datos</span> Modificación de información personal</li>
                        <li><span class="audit-type audit-data">Datos</span> Cambios en calificaciones</li>
                        <li><span class="audit-type audit-data">Datos</span> Actualización de asistencias</li>
                        <li><span class="audit-type audit-data">Datos</span> Eliminación de registros</li>
                        <li><span class="audit-type audit-data">Datos</span> Importación masiva de datos</li>
                    </ul>
                </div>
                
                <h4>Información registrada:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Usuario:</strong> Quién realizó el cambio</li>
                        <li><strong>Timestamp:</strong> Cuándo ocurrió</li>
                        <li><strong>Acción:</strong> Qué se modificó</li>
                        <li><strong>Valores anteriores:</strong> Estado previo</li>
                        <li><strong>Valores nuevos:</strong> Estado posterior</li>
                        <li><strong>IP:</strong> Desde dónde se realizó</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-check"></i> Auditoría de Accesos</h4>
                
                <div class="audit-card">
                    <h4><i class="fas fa-sign-in-alt"></i> Registro de Accesos</h4>
                    <p><strong>Se monitorean los siguientes accesos:</strong></p>
                    <ul>
                        <li><span class="audit-type audit-access">Acceso</span> Login y logout de usuarios</li>
                        <li><span class="audit-type audit-access">Acceso</span> Acceso a módulos específicos</li>
                        <li><span class="audit-type audit-access">Acceso</span> Visualización de datos sensibles</li>
                        <li><span class="audit-type audit-access">Acceso</span> Exportación de información</li>
                        <li><span class="audit-type audit-access">Acceso</span> Generación de reportes</li>
                        <li><span class="audit-type audit-access">Acceso</span> Acceso a configuraciones</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-cogs"></i> Auditoría del Sistema</h4>
                
                <div class="audit-card">
                    <h4><i class="fas fa-server"></i> Eventos del Sistema</h4>
                    <p><strong>Se registran eventos técnicos:</strong></p>
                    <ul>
                        <li><span class="audit-type audit-system">Sistema</span> Inicio y parada de servicios</li>
                        <li><span class="audit-type audit-system">Sistema</span> Actualizaciones del software</li>
                        <li><span class="audit-type audit-system">Sistema</span> Cambios de configuración</li>
                        <li><span class="audit-type audit-system">Sistema</span> Operaciones de backup</li>
                        <li><span class="audit-type audit-system">Sistema</span> Errores críticos</li>
                        <li><span class="audit-type audit-system">Sistema</span> Cambios de permisos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-chart-line"></i> Monitoreo en Tiempo Real</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-eye"></i> Dashboard de Auditoría</h4>
                
                <div class="step-box">
                    <p><strong>El dashboard muestra:</strong></p>
                    <ul>
                        <li><strong>Actividad reciente:</strong> Últimas 24 horas</li>
                        <li><strong>Alertas activas:</strong> Eventos que requieren atención</li>
                        <li><strong>Estadísticas:</strong> Métricas de uso y seguridad</li>
                        <li><strong>Usuarios activos:</strong> Sesiones en curso</li>
                        <li><strong>Eventos críticos:</strong> Actividades de alto riesgo</li>
                    </ul>
                </div>
                
                <h4>Métricas clave:</h4>
                <div class="success-box">
                    <ul>
                        <li><strong>Intentos de login fallidos:</strong> Por hora/día</li>
                        <li><strong>Accesos fuera de horario:</strong> Patrones anómalos</li>
                        <li><strong>Modificaciones de datos:</strong> Volumen y frecuencia</li>
                        <li><strong>Usuarios activos:</strong> Distribución por roles</li>
                        <li><strong>Errores del sistema:</strong> Frecuencia y tipo</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Generación de Reportes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-chart-bar"></i> Tipos de Reportes</h4>
                
                <div class="step-box">
                    <p><strong>Reportes disponibles:</strong></p>
                    <ul>
                        <li><strong>Reporte de seguridad:</strong> Eventos de seguridad por período</li>
                        <li><strong>Reporte de accesos:</strong> Patrones de uso de usuarios</li>
                        <li><strong>Reporte de cambios:</strong> Modificaciones de datos</li>
                        <li><strong>Reporte de cumplimiento:</strong> Adherencia a políticas</li>
                        <li><strong>Reporte ejecutivo:</strong> Resumen para administración</li>
                    </ul>
                </div>
                
                <h4>Configuración de reportes:</h4>
                <div class="info-box">
                    <ul>
                        <li><strong>Período:</strong> Diario, semanal, mensual, personalizado</li>
                        <li><strong>Filtros:</strong> Por usuario, tipo de evento, severidad</li>
                        <li><strong>Formato:</strong> PDF, Excel, CSV</li>
                        <li><strong>Distribución:</strong> Email automático a destinatarios</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-clock"></i> Retención de Logs</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-archive"></i> Política de Retención</h4>
                
                <div class="step-box">
                    <p><strong>Períodos de retención por tipo:</strong></p>
                    <ul>
                        <li><strong>Logs de seguridad:</strong> 2 años</li>
                        <li><strong>Logs de acceso:</strong> 1 año</li>
                        <li><strong>Logs de cambios de datos:</strong> 5 años</li>
                        <li><strong>Logs del sistema:</strong> 6 meses</li>
                        <li><strong>Logs de auditoría crítica:</strong> 7 años</li>
                    </ul>
                </div>
                
                <h4>Archivado automático:</h4>
                <div class="warning-box">
                    <ul>
                        <li>Los logs se archivan automáticamente</li>
                        <li>Se comprimen para ahorrar espacio</li>
                        <li>Se mantienen en almacenamiento seguro</li>
                        <li>Se eliminan según política de retención</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-exclamation-triangle"></i> Alertas y Notificaciones</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-bell"></i> Sistema de Alertas</h4>
                
                <div class="step-box">
                    <p><strong>Tipos de alertas configuradas:</strong></p>
                    <ul>
                        <li><span class="audit-type audit-critical">Crítico</span> Múltiples fallos de login</li>
                        <li><span class="audit-type audit-critical">Crítico</span> Acceso no autorizado</li>
                        <li><span class="audit-type audit-critical">Crítico</span> Modificaciones masivas de datos</li>
                        <li><span class="audit-type audit-critical">Crítico</span> Errores críticos del sistema</li>
                        <li><span class="audit-type audit-critical">Crítico</span> Cambios de configuración</li>
                    </ul>
                </div>
                
                <h4>Canales de notificación:</h4>
                <div class="success-box">
                    <ul>
                        <li><strong>Email:</strong> Para alertas importantes</li>
                        <li><strong>Dashboard:</strong> Notificaciones en tiempo real</li>
                        <li><strong>SMS:</strong> Para eventos críticos</li>
                        <li><strong>Logs:</strong> Registro permanente</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Cumplimiento Normativo</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-gavel"></i> Requisitos Legales</h4>
                
                <div class="step-box">
                    <p><strong>El sistema cumple con:</strong></p>
                    <ul>
                        <li><strong>Ley de Protección de Datos:</strong> Registro de accesos a datos personales</li>
                        <li><strong>Normativas educativas:</strong> Trazabilidad de calificaciones</li>
                        <li><strong>Estándares de seguridad:</strong> ISO 27001</li>
                        <li><strong>Auditoría interna:</strong> Procesos de control</li>
                    </ul>
                </div>
                
                <h4>Evidencia de cumplimiento:</h4>
                <div class="info-box">
                    <ul>
                        <li>Reportes de auditoría regulares</li>
                        <li>Registros de acceso a datos sensibles</li>
                        <li>Trail de cambios en información crítica</li>
                        <li>Documentación de procesos de seguridad</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-tools"></i> Herramientas de Auditoría</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-search"></i> Búsqueda y Filtrado</h4>
                
                <div class="step-box">
                    <p><strong>Capacidades de búsqueda:</strong></p>
                    <ul>
                        <li><strong>Por usuario:</strong> Actividad de usuarios específicos</li>
                        <li><strong>Por fecha:</strong> Eventos en rangos de tiempo</li>
                        <li><strong>Por tipo:</strong> Filtrado por categoría de evento</li>
                        <li><strong>Por IP:</strong> Actividad desde direcciones específicas</li>
                        <li><strong>Por severidad:</strong> Eventos críticos, importantes, informativos</li>
                    </ul>
                </div>
                
                <h4>Exportación de datos:</h4>
                <div class="success-box">
                    <ul>
                        <li>Exportación a Excel para análisis</li>
                        <li>Generación de reportes PDF</li>
                        <li>Exportación de logs en formato estándar</li>
                        <li>Integración con herramientas externas</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-user-shield"></i> Roles y Permisos de Auditoría</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Acceso a Información de Auditoría</h4>
                
                <div class="step-box">
                    <p><strong>Niveles de acceso:</strong></p>
                    <ul>
                        <li><strong>Auditor completo:</strong> Acceso total a todos los logs</li>
                        <li><strong>Auditor de seguridad:</strong> Solo eventos de seguridad</li>
                        <li><strong>Auditor de datos:</strong> Solo cambios de datos</li>
                        <li><strong>Consultor:</strong> Solo lectura de reportes</li>
                    </ul>
                </div>
                
                <h4>Permisos específicos:</h4>
                <div class="warning-box">
                    <ul>
                        <li>Solo administradores pueden ver logs de otros administradores</li>
                        <li>Los usuarios solo pueden ver su propia actividad</li>
                        <li>Los logs de seguridad requieren permisos especiales</li>
                        <li>La exportación de datos está restringida</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Auditoría</h4>
                
                <p><strong>¿Qué información se registra sobre mi actividad?</strong><br>
                Se registra cuándo accedes, qué módulos usas, qué datos modificas y desde qué IP, pero no el contenido específico de tus acciones.</p>
                
                <p><strong>¿Puedo ver mis propios logs de actividad?</strong><br>
                Sí, puedes acceder a un resumen de tu actividad reciente desde tu perfil de usuario.</p>
                
                <p><strong>¿Los logs se pueden modificar o eliminar?</strong><br>
                No, los logs son inmutables y solo pueden ser eliminados automáticamente según la política de retención.</p>
                
                <p><strong>¿Cómo se protegen los logs de auditoría?</strong><br>
                Los logs están encriptados, tienen acceso restringido y se almacenan en servidores seguros separados.</p>
                
                <p><strong>¿Qué hago si veo actividad sospechosa en mis logs?</strong><br>
                Contacta inmediatamente al administrador del sistema o al departamento de seguridad.</p>
            </div>
            
            <h2><i class="fas fa-lightbulb"></i> Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-star"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Revisa regularmente los reportes de auditoría</li>
                    <li>Configura alertas apropiadas para tu rol</li>
                    <li>Mantén actualizada la información de contacto</li>
                    <li>Reporta inmediatamente cualquier actividad sospechosa</li>
                    <li>Usa contraseñas seguras y accede con Google OAuth si eres docente</li>
                    <li>Cierra sesión cuando termines de usar el sistema</li>
                    <li>No compartas tus credenciales de acceso</li>
                    <li>Mantén actualizado tu navegador y sistema operativo</li>
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
