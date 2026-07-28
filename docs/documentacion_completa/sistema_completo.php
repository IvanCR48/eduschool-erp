<?php
/**
 * Sistema Completo - Sistema Admin EEST2
 * 
 * Documentación completa sobre todas las funcionalidades del sistema
 */

$pageTitle = 'Sistema Completo - E.E.S.T N°2';
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
            <h1><i class="fas fa-cogs"></i> Sistema Completo</h1>
            <p>Sistema Administrativo E.E.S.T N°2 "Educación y Trabajo"</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-graduation-cap"></i> Visión General</h2>
            <p>El Sistema Administrativo EEST2 es una plataforma integral diseñada específicamente para la gestión educativa de la Escuela de Educación Secundaria Técnica N°2 "Educación y Trabajo". El sistema abarca todas las áreas administrativas, académicas y de gestión estudiantil.</p>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Objetivo Principal</h4>
                <p>Centralizar y automatizar todos los procesos administrativos y académicos de la institución, proporcionando herramientas eficientes para la gestión de estudiantes, profesores, cursos y reportes.</p>
            </div>

            <h2><i class="fas fa-users"></i> Módulos Principales</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h4><i class="fas fa-user-graduate"></i> Gestión de Estudiantes</h4>
                    <p><strong>Funcionalidades completas:</strong></p>
                    <ul>
                        <li>Registro y actualización de datos personales</li>
                        <li>Gestión de cursos y especialidades</li>
                        <li>Seguimiento académico y asistencia</li>
                        <li>Generación de documentos oficiales</li>
                        <li>Historial académico completo</li>
                        <li>Estados de matrícula</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4><i class="fas fa-chalkboard-teacher"></i> Gestión de Profesores</h4>
                    <p><strong>Administración docente:</strong></p>
                    <ul>
                        <li>Registro de personal docente</li>
                        <li>Asignación de materias y cursos</li>
                        <li>Gestión de horarios</li>
                        <li>Seguimiento de carga horaria</li>
                        <li>Documentación profesional</li>
                        <li>Evaluaciones y reportes</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4><i class="fas fa-book"></i> Gestión Académica</h4>
                    <p><strong>Procesos educativos:</strong></p>
                    <ul>
                        <li>Configuración de materias</li>
                        <li>Planificación de cursos</li>
                        <li>Gestión de horarios</li>
                        <li>Seguimiento curricular</li>
                        <li>Calificaciones y evaluaciones</li>
                        <li>Promociones y repitencias</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4><i class="fas fa-calendar-check"></i> Gestión de Asistencia</h4>
                    <p><strong>Control de presentismo:</strong></p>
                    <ul>
                        <li>Toma de lista virtual por curso y materia</li>
                        <li>Cálculo de porcentaje de asistencia en tiempo real</li>
                        <li>Subida y visualización de justificativos médicos (adjuntos)</li>
                        <li>Dashboard con alumnos en riesgo por inasistencias</li>
                        <li>Panel diario de inasistencias justificadas</li>
                        <li>Historial completo en la ficha del estudiante</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4><i class="fas fa-chart-bar"></i> Sistema de Reportes</h4>
                    <p><strong>Análisis y estadísticas:</strong></p>
                    <ul>
                        <li>Reportes de estudiantes</li>
                        <li>Estadísticas académicas</li>
                        <li>Análisis de asistencia</li>
                        <li>Reportes de profesores</li>
                        <li>Métricas institucionales</li>
                        <li>Exportación de datos</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4><i class="fas fa-shield-alt"></i> Seguridad y Auditoría</h4>
                    <p><strong>Protección de datos:</strong></p>
                    <ul>
                        <li>Acceso seguro con Google OAuth y reCAPTCHA</li>
                        <li>Políticas de contraseñas</li>
                        <li>Logs de auditoría</li>
                        <li>Control de acceso por roles (RBAC)</li>
                        <li>Encriptación de datos</li>
                        <li>Monitoreo de seguridad</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4><i class="fas fa-database"></i> Administración del Sistema</h4>
                    <p><strong>Mantenimiento y configuración:</strong></p>
                    <ul>
                        <li>Gestión de usuarios y roles</li>
                        <li>Configuración del sistema</li>
                        <li>Backup y recuperación</li>
                        <li>Monitoreo de rendimiento</li>
                        <li>Mantenimiento preventivo</li>
                        <li>Actualizaciones del sistema</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-cogs"></i> Características Técnicas</h2>
            
            <h3>Arquitectura del Sistema</h3>
            <ul>
                <li><strong>Backend:</strong> PHP 8+ con arquitectura MVC</li>
                <li><strong>Base de Datos:</strong> MySQL/MariaDB con optimizaciones</li>
                <li><strong>Frontend:</strong> HTML5, CSS3, JavaScript moderno</li>
                <li><strong>Seguridad:</strong> Google OAuth, reCAPTCHA v2, encriptación, auditoría</li>
                <li><strong>API:</strong> RESTful para integraciones externas</li>
                <li><strong>Responsive:</strong> Diseño adaptable a todos los dispositivos</li>
            </ul>

            <h3>Funcionalidades Avanzadas</h3>
            <ul>
                <li><strong>Búsqueda Inteligente:</strong> Filtros avanzados y búsqueda por múltiples criterios</li>
                <li><strong>Exportación de Datos:</strong> PDF, Excel, CSV para reportes</li>
                <li><strong>Notificaciones:</strong> Sistema de alertas y notificaciones automáticas</li>
                <li><strong>Backup Automático:</strong> Respaldos programados y recuperación</li>
                <li><strong>Logs Detallados:</strong> Registro completo de todas las acciones</li>
                <li><strong>Configuración Flexible:</strong> Parámetros personalizables por institución</li>
            </ul>

            <div class="success-box">
                <h4><i class="fas fa-check-circle"></i> Beneficios del Sistema</h4>
                <ul>
                    <li><strong>Eficiencia:</strong> Automatización de procesos administrativos</li>
                    <li><strong>Precisión:</strong> Eliminación de errores manuales</li>
                    <li><strong>Accesibilidad:</strong> Acceso desde cualquier dispositivo</li>
                    <li><strong>Seguridad:</strong> Protección avanzada de datos sensibles</li>
                    <li><strong>Escalabilidad:</strong> Capacidad de crecimiento con la institución</li>
                    <li><strong>Integración:</strong> Compatibilidad con sistemas externos</li>
                </ul>
            </div>

            <h2><i class="fas fa-user-tie"></i> Roles y Permisos</h2>
            
            <h3>Administrador</h3>
            <ul>
                <li>Acceso completo al sistema</li>
                <li>Gestión de usuarios y roles</li>
                <li>Configuración del sistema</li>
                <li>Acceso a logs de auditoría</li>
                <li>Herramientas de mantenimiento</li>
            </ul>

            <h3>Secretario</h3>
            <ul>
                <li>Gestión completa de estudiantes</li>
                <li>Generación de documentos oficiales</li>
                <li>Acceso a reportes administrativos</li>
                <li>Gestión de cursos y materias</li>
            </ul>

            <h3>Profesor</h3>
            <ul>
                <li>Acceso a sus cursos asignados</li>
                <li>Gestión de calificaciones</li>
                <li>Registro de asistencia</li>
                <li>Reportes de sus estudiantes</li>
            </ul>

            <h3>Preceptor</h3>
            <ul>
                <li>Seguimiento de estudiantes</li>
                <li>Gestión de asistencia</li>
                <li>Comunicación con familias</li>
                <li>Reportes de comportamiento</li>
            </ul>

            <h2><i class="fas fa-mobile-alt"></i> Acceso Multiplataforma</h2>
            
            <h3>Dispositivos Soportados</h3>
            <ul>
                <li><strong>Computadoras de Escritorio:</strong> Experiencia completa de escritorio</li>
                <li><strong>Laptops:</strong> Funcionalidad completa con portabilidad</li>
                <li><strong>Tablets:</strong> Interfaz optimizada para pantallas táctiles</li>
                <li><strong>Smartphones:</strong> Acceso móvil premium (App-like UX) con barra de pestañas, listas de alta densidad y paneles Bottom Sheet interactivos en módulos críticos (Asistencia y Dashboard).</li>
            </ul>

            <h3>Navegadores Compatibles</h3>
            <ul>
                <li>Chrome 90+</li>
                <li>Firefox 88+</li>
                <li>Safari 14+</li>
                <li>Edge 90+</li>
            </ul>

            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Requisitos del Sistema</h4>
                <ul>
                    <li>PHP 8.0 o superior</li>
                    <li>MySQL 5.7+ o MariaDB 10.3+</li>
                    <li>Servidor web (Apache/Nginx)</li>
                    <li>Mínimo 2GB RAM</li>
                    <li>Espacio en disco: 1GB + datos</li>
                </ul>
            </div>

            <h2><i class="fas fa-rocket"></i> Implementación y Despliegue</h2>
            
            <h3>Proceso de Instalación</h3>
            <ol>
                <li><strong>Preparación:</strong> Verificación de requisitos del servidor</li>
                <li><strong>Instalación:</strong> Despliegue de archivos y configuración</li>
                <li><strong>Configuración:</strong> Parámetros iniciales y base de datos</li>
                <li><strong>Pruebas:</strong> Verificación de funcionalidades</li>
                <li><strong>Capacitación:</strong> Entrenamiento del personal</li>
                <li><strong>Producción:</strong> Puesta en marcha del sistema</li>
            </ol>

            <h3>Soporte y Mantenimiento</h3>
            <ul>
                <li><strong>Documentación Completa:</strong> Manuales y guías detalladas</li>
                <li><strong>Soporte Técnico:</strong> Asistencia especializada</li>
                <li><strong>Actualizaciones:</strong> Mejoras y nuevas funcionalidades</li>
                <li><strong>Capacitación:</strong> Cursos y talleres de actualización</li>
                <li><strong>Monitoreo:</strong> Supervisión continua del sistema</li>
            </ul>

            <div class="info-box">
                <h4><i class="fas fa-phone"></i> Contacto y Soporte</h4>
                <p>Para más información sobre el sistema completo, consulta la sección de <a href="contacto.php" style="color: #1e40af; font-weight: 600;">Contacto</a> o revisa la <a href="faq.php" style="color: #1e40af; font-weight: 600;">FAQ</a> para preguntas frecuentes.</p>
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
