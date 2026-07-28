<?php
$pageTitle = 'Historial de Cambios - Documentación EEST2';
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
            <h1><i class="fas fa-history"></i> Historial de Cambios</h1>
            <p>Registro completo de todas las versiones y actualizaciones del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-info-circle"></i> Información General</h2>
            
            <div class="info-box">
                <p><i class="fas fa-calendar"></i> <strong>Última actualización:</strong> Junio 2026</p>
                <p><i class="fas fa-code-branch"></i> <strong>Versión actual:</strong> 2.1.1</p>
                <p><i class="fas fa-tag"></i> <strong>Próxima versión:</strong> 2.2.0 (Julio 2026)</p>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-bookmark"></i> Convenciones de Versionado</h4>
                
                <div class="step-box">
                    <p><strong>Formato de versiones:</strong> MAJOR.MINOR.PATCH (Semantic Versioning)</p>
                    <ul>
                        <li><strong>MAJOR:</strong> Cambios incompatibles con versiones anteriores</li>
                        <li><strong>MINOR:</strong> Nuevas funcionalidades compatibles</li>
                        <li><strong>PATCH:</strong> Correcciones de errores</li>
                    </ul>
                </div>
                
                <h4>Tipos de cambios:</h4>
                <div class="success-box">
                    <span class="change-type change-feature">Nueva Funcionalidad</span>
                    <span class="change-type change-fix">Corrección</span>
                    <span class="change-type change-security">Seguridad</span>
                    <span class="change-type change-breaking">Cambio Importante</span>
                </div>
            </div>
            
            <h2><i class="fas fa-clock"></i> Historial de Versiones</h2>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-mobile-alt"></i> Versión 2.1.1 - "Rediseño Móvil Premium de Asistencia"</h4>
                        <div class="version-date">Junio 2026</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Interfaz móvil nativa (App-like UX) para la toma de asistencia.</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de pestañas responsivas (Alumnos, Resumen, Filtros) para evitar el scroll infinito en celulares.</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Tarjeta compacta de estudiante con avatar con iniciales y badge de estado.</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Panel deslizable inferior (Bottom Sheet) para el registro de estados, notas y justificativos de forma táctil.</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Pestañas y tarjetas de información móvil en el Dashboard de Asistencia (Hoy, Justificados, En Riesgo) en reemplazo de tablas tradicionales.</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Lógica de guardado automático AJAX vinculada bidireccionalmente entre el Bottom Sheet y el formulario principal.</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-rocket"></i> Versión 2.1.0 - "Documentación Completa"</h4>
                        <div class="version-date">Diciembre 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Hub de documentación completo con navegación por categorías</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Documentación técnica detallada para desarrolladores</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Guías específicas por módulo (estudiantes, profesores, reportes)</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de backup automatizado con múltiples estrategias</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Optimización del sistema de carga de variables de entorno</li>
                            <li><span class="change-type change-fix">Corrección</span> Mejora en la gestión de logs y auditoría</li>
                            <li><span class="change-type change-fix">Corrección</span> Refinamiento de la interfaz de administración</li>
                        </ul>
                        
                        <h5>Correcciones:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Resolución de problemas de acceso a documentación</li>
                            <li><span class="change-type change-fix">Corrección</span> Corrección de enlaces rotos en el sistema de navegación</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-shield-alt"></i> Versión 2.0.0 - "Seguridad Avanzada"</h4>
                        <div class="version-date">Noviembre 2024</div>
                        
                        <h5>Cambios Importantes:</h5>
                        <ul>
                            <li><span class="change-type change-breaking">Cambio Importante</span> Implementación de autenticación de dos factores (MFA)</li>
                            <li><span class="change-type change-breaking">Cambio Importante</span> Nuevo sistema de roles y permisos granulares</li>
                            <li><span class="change-type change-breaking">Cambio Importante</span> Migración completa a PHP 8.1+</li>
                        </ul>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Dashboard de seguridad en tiempo real</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de auditoría completo</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Protección contra ataques de fuerza bruta</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Encriptación de datos sensibles</li>
                        </ul>
                        
                        <h5>Seguridad:</h5>
                        <ul>
                            <li><span class="change-type change-security">Seguridad</span> Implementación de políticas de contraseñas</li>
                            <li><span class="change-type change-security">Seguridad</span> Protección CSRF mejorada</li>
                            <li><span class="change-type change-security">Seguridad</span> Validación y sanitización de datos reforzada</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-chart-line"></i> Versión 1.8.0 - "Reportes Avanzados"</h4>
                        <div class="version-date">Octubre 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de reportes programados</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Exportación a múltiples formatos (PDF, Excel, CSV)</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Dashboard de estadísticas en tiempo real</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Reportes personalizables por usuario</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Optimización de consultas de base de datos</li>
                            <li><span class="change-type change-fix">Corrección</span> Mejora en la generación de PDFs</li>
                            <li><span class="change-type change-fix">Corrección</span> Interfaz de reportes más intuitiva</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-graduation-cap"></i> Versión 1.7.0 - "Gestión Académica"</h4>
                        <div class="version-date">Septiembre 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de gestión de materias y especialidades</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Planificación de horarios automática</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Control de asistencias mejorado</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de llamados y recuperatorios</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Cálculo automático de promedios</li>
                            <li><span class="change-type change-fix">Corrección</span> Generación automática de boletines</li>
                            <li><span class="change-type change-fix">Corrección</span> Interfaz móvil optimizada</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-users"></i> Versión 1.6.0 - "Gestión de Usuarios"</h4>
                        <div class="version-date">Agosto 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Gestión completa de profesores y estudiantes</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de fichas personales detalladas</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Búsqueda avanzada con múltiples filtros</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Importación masiva de datos desde Excel</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Validación mejorada de datos personales</li>
                            <li><span class="change-type change-fix">Corrección</span> Optimización de consultas de búsqueda</li>
                            <li><span class="change-type change-fix">Corrección</span> Interfaz de gestión más intuitiva</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-tools"></i> Versión 1.5.0 - "Mantenimiento y Optimización"</h4>
                        <div class="version-date">Julio 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de backup automatizado</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Monitoreo de rendimiento del sistema</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Herramientas de mantenimiento preventivo</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Logs de sistema detallados</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Optimización general del rendimiento</li>
                            <li><span class="change-type change-fix">Corrección</span> Reducción del tiempo de carga</li>
                            <li><span class="change-type change-fix">Corrección</span> Mejora en la gestión de memoria</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-mobile-alt"></i> Versión 1.4.0 - "Responsive Design"</h4>
                        <div class="version-date">Junio 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Diseño completamente responsive</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Optimización para dispositivos móviles</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Interfaz táctil mejorada</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Compatibilidad con tablets</li>
                            <li><span class="change-type change-fix">Corrección</span> Optimización de imágenes</li>
                            <li><span class="change-type change-fix">Corrección</span> Mejora en la navegación móvil</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-database"></i> Versión 1.3.0 - "Base de Datos"</h4>
                        <div class="version-date">Mayo 2024</div>
                        
                        <h5>Nuevas Funcionalidades:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Migración a MySQL 8.0</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de migraciones automatizado</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Optimización de índices</li>
                        </ul>
                        
                        <h5>Mejoras:</h5>
                        <ul>
                            <li><span class="change-type change-fix">Corrección</span> Mejora en el rendimiento de consultas</li>
                            <li><span class="change-type change-fix">Corrección</span> Optimización del uso de memoria</li>
                            <li><span class="change-type change-fix">Corrección</span> Mejora en la integridad de datos</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="version-card">
                        <h4><i class="fas fa-rocket"></i> Versión 1.0.0 - "Lanzamiento Inicial"</h4>
                        <div class="version-date">Abril 2024</div>
                        
                        <h5>Funcionalidades Base:</h5>
                        <ul>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de autenticación básico</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Gestión de estudiantes</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Gestión de profesores</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Sistema de calificaciones</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Reportes básicos</li>
                            <li><span class="change-type change-feature">Nueva Funcionalidad</span> Panel de administración</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <h2><i class="fas fa-road"></i> Roadmap Futuro</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-calendar-alt"></i> Próximas Versiones</h4>
                
                <div class="step-box">
                    <h5>Versión 2.2.0 - "Integración Avanzada" (Enero 2025)</h5>
                    <ul>
                        <li>Integración con sistemas externos (SIGE, SIU)</li>
                        <li>API REST completa</li>
                        <li>Sistema de notificaciones push</li>
                        <li>Dashboard personalizable</li>
                    </ul>
                    
                    <h5>Versión 2.3.0 - "Inteligencia Artificial" (Marzo 2025)</h5>
                    <ul>
                        <li>Análisis predictivo de rendimiento</li>
                        <li>Recomendaciones automáticas</li>
                        <li>Detección de patrones de asistencia</li>
                        <li>Alertas inteligentes</li>
                    </ul>
                    
                    <h5>Versión 3.0.0 - "Nueva Arquitectura" (Junio 2025)</h5>
                    <ul>
                        <li>Migración a microservicios</li>
                        <li>Arquitectura cloud-native</li>
                        <li>Escalabilidad horizontal</li>
                        <li>Alta disponibilidad</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-exclamation-triangle"></i> Notas de Migración</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-arrow-up"></i> Actualizaciones Importantes</h4>
                
                <div class="warning-box">
                    <h5>Versión 2.0.0 - Cambios Importantes</h5>
                    <ul>
                        <li><strong>PHP 8.1+ requerido:</strong> Actualiza tu servidor antes de la migración</li>
                        <li><strong>MFA obligatorio:</strong> Todos los usuarios deben configurar autenticación de dos factores</li>
                        <li><strong>Nuevos permisos:</strong> Revisa y actualiza los roles de usuario</li>
                        <li><strong>Base de datos:</strong> Ejecuta las migraciones antes de actualizar</li>
                    </ul>
                </div>
                
                <div class="info-box">
                    <h5>Versión 1.8.0 - Nuevas Funcionalidades</h5>
                    <ul>
                        <li><strong>Reportes programados:</strong> Configura reportes automáticos</li>
                        <li><strong>Nuevos formatos:</strong> Explora las opciones de exportación</li>
                        <li><strong>Dashboard:</strong> Personaliza tu vista principal</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-download"></i> Proceso de Actualización</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-cog"></i> Pasos para Actualizar</h4>
                
                <div class="step-box">
                    <p><strong>Antes de actualizar:</strong></p>
                    <ol>
                        <li>Haz backup completo del sistema</li>
                        <li>Revisa los requisitos de la nueva versión</li>
                        <li>Lee las notas de migración</li>
                        <li>Notifica a los usuarios sobre el mantenimiento</li>
                    </ol>
                </div>
                
                <div class="step-box">
                    <p><strong>Durante la actualización:</strong></p>
                    <ol>
                        <li>Detén el servicio web</li>
                        <li>Descarga la nueva versión</li>
                        <li>Ejecuta las migraciones de base de datos</li>
                        <li>Actualiza los archivos del sistema</li>
                        <li>Configura las nuevas funcionalidades</li>
                        <li>Reinicia el servicio web</li>
                    </ol>
                </div>
                
                <div class="step-box">
                    <p><strong>Después de actualizar:</strong></p>
                    <ol>
                        <li>Verifica que todo funcione correctamente</li>
                        <li>Prueba las nuevas funcionalidades</li>
                        <li>Notifica a los usuarios sobre los cambios</li>
                        <li>Actualiza la documentación si es necesario</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Changelog</h4>
                
                <p><strong>¿Cómo sé qué versión estoy usando?</strong><br>
                Ve al panel de administración → "Información del Sistema" para ver la versión actual.</p>
                
                <p><strong>¿Cuándo debo actualizar?</strong><br>
                Se recomienda actualizar cuando hay correcciones de seguridad o nuevas funcionalidades importantes.</p>
                
                <p><strong>¿Puedo saltar versiones?</strong><br>
                No se recomienda. Es mejor actualizar secuencialmente para evitar problemas de compatibilidad.</p>
                
                <p><strong>¿Qué hago si algo falla después de actualizar?</strong><br>
                Restaura el backup anterior y contacta al soporte técnico con los detalles del error.</p>
                
                <p><strong>¿Cómo obtengo las nuevas versiones?</strong><br>
                Las actualizaciones se notifican por email y están disponibles en el panel de administración.</p>
            </div>
            
            <h2><i class="fas fa-lightbulb"></i> Consejos de Actualización</h2>
            
            <div class="success-box">
                <p><i class="fas fa-star"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Siempre haz backup antes de actualizar</li>
                    <li>Lee las notas de migración completas</li>
                    <li>Prueba en un entorno de desarrollo primero</li>
                    <li>Actualiza durante horarios de bajo uso</li>
                    <li>Mantén un registro de las actualizaciones</li>
                    <li>Capacita a los usuarios sobre los cambios</li>
                    <li>Monitorea el sistema después de actualizar</li>
                    <li>Ten un plan de rollback preparado</li>
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
