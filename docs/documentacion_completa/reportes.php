<?php
$pageTitle = 'Sistema de Reportes - Documentación EEST2';
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
            <h1><i class="fas fa-chart-bar"></i> Sistema de Reportes</h1>
            <p>Guía completa para generar reportes y estadísticas en el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-list"></i> Acceso al Módulo de Reportes</h2>
            
            <div class="step-box">
                <p><strong>Para acceder al módulo de reportes:</strong></p>
                <ol>
                    <li>Inicia sesión en el sistema</li>
                    <li>Desde el menú principal, haz clic en <strong>"Reportes"</strong></li>
                    <li>Se abrirá el panel de reportes con todas las opciones disponibles</li>
                </ol>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> <strong>Permisos requeridos:</strong> Los usuarios con rol de Administrador, Secretario o Profesor pueden acceder a diferentes tipos de reportes según sus permisos.</p>
            </div>
            
            <h2><i class="fas fa-graduation-cap"></i> Reportes de Estudiantes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-users"></i> Lista de Estudiantes</h4>
                
                <div class="step-box">
                    <p><strong>Para generar lista de estudiantes:</strong></p>
                    <ol>
                        <li>Selecciona <strong>"Reportes de Estudiantes"</strong></li>
                        <li>Elige el tipo de lista:</li>
                        <ul>
                            <li>Lista completa de estudiantes</li>
                            <li>Estudiantes por curso</li>
                            <li>Estudiantes por estado</li>
                            <li>Estudiantes por especialidad</li>
                        </ul>
                        <li>Configura los filtros necesarios</li>
                        <li>Selecciona el formato de salida</li>
                        <li>Haz clic en <strong>"Generar Reporte"</strong></li>
                    </ol>
                </div>
                
                <h4>Filtros disponibles:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Por año de ingreso</li>
                        <li>☐ Por curso específico</li>
                        <li>☐ Por estado (activo/inactivo)</li>
                        <li>☐ Por especialidad técnica</li>
                        <li>☐ Por rango de edad</li>
                        <li>☐ Por fecha de nacimiento</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-chart-line"></i> Estadísticas Académicas</h4>
                
                <div class="step-box">
                    <p><strong>Reportes estadísticos disponibles:</strong></p>
                    <ul>
                        <li><strong>Promedios por curso:</strong> Rendimiento académico por división</li>
                        <li><strong>Distribución de calificaciones:</strong> Gráficos de notas</li>
                        <li><strong>Estudiantes destacados:</strong> Mejores promedios</li>
                        <li><strong>Estudiantes en riesgo:</strong> Bajos promedios</li>
                        <li><strong>Evolución académica:</strong> Progreso por trimestre</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-chalkboard-teacher"></i> Reportes de Profesores</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-tie"></i> Información de Profesores</h4>
                
                <div class="step-box">
                    <p><strong>Para generar reportes de profesores:</strong></p>
                    <ol>
                        <li>Selecciona <strong>"Reportes de Profesores"</strong></li>
                        <li>Elige el tipo de reporte:</li>
                        <ul>
                            <li>Lista completa de profesores</li>
                            <li>Profesores por materia</li>
                            <li>Distribución de carga horaria</li>
                            <li>Horarios de profesores</li>
                        </ul>
                        <li>Configura los parámetros</li>
                        <li>Genera el reporte</li>
                    </ol>
                </div>
                
                <h4>Información incluida:</h4>
                <div class="info-box">
                    <ul>
                        <li>Datos personales y de contacto</li>
                        <li>Materias asignadas</li>
                        <li>Carga horaria semanal</li>
                        <li>Cursos donde enseña</li>
                        <li>Especialidad técnica</li>
                        <li>Años de experiencia</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-calendar-check"></i> Reportes de Asistencias</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-clipboard-list"></i> Control de Asistencias</h4>
                
                <div class="step-box">
                    <p><strong>Para generar reportes de asistencia:</strong></p>
                    <ol>
                        <li>Selecciona <strong>"Reportes de Asistencias"</strong></li>
                        <li>Configura el período (desde/hasta)</li>
                        <li>Selecciona el curso o estudiante específico</li>
                        <li>Elige el tipo de reporte:</li>
                        <ul>
                            <li>Asistencias por estudiante</li>
                            <li>Asistencias por curso</li>
                            <li>Resumen de faltas</li>
                            <li>Justificaciones de inasistencias</li>
                        </ul>
                        <li>Genera el reporte</li>
                    </ol>
                </div>
                
                <h4>Métricas incluidas:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Total de días de clase</li>
                        <li>☐ Días asistidos</li>
                        <li>☐ Faltas justificadas</li>
                        <li>☐ Faltas injustificadas</li>
                        <li>☐ Porcentaje de asistencia</li>
                        <li>☐ Retiros tempranos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Reportes de Calificaciones</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-graduation-cap"></i> Boletines y Calificaciones</h4>
                
                <div class="step-box">
                    <p><strong>Para generar reportes de calificaciones:</strong></p>
                    <ol>
                        <li>Selecciona <strong>"Reportes de Calificaciones"</strong></li>
                        <li>Elige el período académico</li>
                        <li>Selecciona el curso o estudiante</li>
                        <li>Configura el tipo de reporte:</li>
                        <ul>
                            <li>Boletín individual</li>
                            <li>Boletines por curso</li>
                            <li>Promedios por materia</li>
                            <li>Ranking de estudiantes</li>
                        </ul>
                        <li>Genera el reporte</li>
                    </ol>
                </div>
                
                <h4>Información incluida:</h4>
                <ul>
                    <li><strong>Calificaciones:</strong> Notas por materia y período</li>
                    <li><strong>Promedios:</strong> Por materia y general</li>
                    <li><strong>Observaciones:</strong> Comentarios de profesores</li>
                    <li><strong>Asistencias:</strong> Registro de faltas</li>
                    <li><strong>Estado académico:</strong> Regular, condicional, etc.</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-chart-pie"></i> Reportes Estadísticos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-chart-area"></i> Estadísticas Generales</h4>
                
                <div class="step-box">
                    <p><strong>Reportes estadísticos disponibles:</strong></p>
                    <ul>
                        <li><strong>Población estudiantil:</strong> Distribución por curso y especialidad</li>
                        <li><strong>Rendimiento académico:</strong> Promedios generales</li>
                        <li><strong>Asistencias:</strong> Estadísticas de asistencia</li>
                        <li><strong>Egresados:</strong> Número de egresados por año</li>
                        <li><strong>Deserción:</strong> Estudiantes que abandonaron</li>
                    </ul>
                </div>
                
                <h4>Gráficos incluidos:</h4>
                <div class="info-box">
                    <ul>
                        <li>Gráficos de barras para comparaciones</li>
                        <li>Gráficos circulares para distribuciones</li>
                        <li>Gráficos de líneas para evolución temporal</li>
                        <li>Tablas con datos numéricos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-download"></i> Formatos de Exportación</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-file-export"></i> Formatos Disponibles</h4>
                
                <div class="step-box">
                    <p><strong>Formatos de salida:</strong></p>
                    <ul>
                        <li><strong>PDF:</strong> Para impresión y archivo oficial</li>
                        <li><strong>Excel (.xlsx):</strong> Para análisis y cálculos</li>
                        <li><strong>CSV:</strong> Para importar en otros sistemas</li>
                        <li><strong>HTML:</strong> Para visualización web</li>
                    </ul>
                </div>
                
                <h4>Características por formato:</h4>
                <div class="success-box">
                    <p><strong>PDF:</strong></p>
                    <ul>
                        <li>Formato oficial para documentos</li>
                        <li>Incluye gráficos y tablas</li>
                        <li>Optimizado para impresión</li>
                        <li>Firma digital disponible</li>
                    </ul>
                    
                    <p><strong>Excel:</strong></p>
                    <ul>
                        <li>Fórmulas y cálculos automáticos</li>
                        <li>Filtros y ordenamiento</li>
                        <li>Gráficos interactivos</li>
                        <li>Múltiples hojas de trabajo</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-calendar-alt"></i> Reportes Programados</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-clock"></i> Automatización de Reportes</h4>
                
                <div class="step-box">
                    <p><strong>Para programar reportes automáticos:</strong></p>
                    <ol>
                        <li>Ve a <strong>"Reportes Programados"</strong></li>
                        <li>Haz clic en <strong>"Nuevo Reporte Programado"</strong></li>
                        <li>Selecciona el tipo de reporte</li>
                        <li>Configura la frecuencia:</li>
                        <ul>
                            <li>Diario</li>
                            <li>Semanal</li>
                            <li>Mensual</li>
                            <li>Trimestral</li>
                        </ul>
                        <li>Define los destinatarios</li>
                        <li>Activa el reporte programado</li>
                    </ol>
                </div>
                
                <h4>Reportes automáticos comunes:</h4>
                <div class="warning-box">
                    <ul>
                        <li><strong>Boletines mensuales:</strong> Envío automático a padres</li>
                        <li><strong>Reportes de asistencia:</strong> Semanales para preceptores</li>
                        <li><strong>Estadísticas trimestrales:</strong> Para dirección</li>
                        <li><strong>Listas de estudiantes:</strong> Actualización automática</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-cog"></i> Configuración de Reportes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-sliders-h"></i> Personalización</h4>
                
                <div class="step-box">
                    <p><strong>Opciones de configuración:</strong></p>
                    <ul>
                        <li><strong>Encabezados:</strong> Personalizar logos y datos de la institución</li>
                        <li><strong>Filtros predeterminados:</strong> Configurar filtros por defecto</li>
                        <li><strong>Formato de fechas:</strong> Estilo de fecha en reportes</li>
                        <li><strong>Idioma:</strong> Español o inglés</li>
                        <li><strong>Unidades de medida:</strong> Sistema métrico o imperial</li>
                    </ul>
                </div>
                
                <h4>Plantillas personalizadas:</h4>
                <div class="info-box">
                    <ul>
                        <li>Crear plantillas para reportes frecuentes</li>
                        <li>Guardar configuraciones de filtros</li>
                        <li>Definir formatos de salida por defecto</li>
                        <li>Configurar destinatarios automáticos</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Seguridad y Permisos</h2>
            
            <div class="warning-box">
                <p><i class="fas fa-lock"></i> <strong>Control de acceso:</strong></p>
                <ul>
                    <li>Cada tipo de reporte tiene permisos específicos</li>
                    <li>Los administradores pueden ver todos los reportes</li>
                    <li>Los profesores solo ven reportes de sus materias</li>
                    <li>Los secretarios tienen acceso a reportes administrativos</li>
                    <li>Todos los accesos son registrados en logs</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Reportes</h4>
                
                <p><strong>¿Cómo genero un boletín de calificaciones?</strong><br>
                Ve a "Reportes de Calificaciones" → "Boletín Individual" → Selecciona el estudiante y período.</p>
                
                <p><strong>¿Puedo exportar reportes a Excel?</strong><br>
                Sí, todos los reportes se pueden exportar en formato Excel (.xlsx).</p>
                
                <p><strong>¿Cómo programo un reporte automático?</strong><br>
                Ve a "Reportes Programados" y configura la frecuencia y destinatarios.</p>
                
                <p><strong>¿Qué reportes puedo generar como profesor?</strong><br>
                Puedes generar reportes de tus materias, listas de estudiantes y calificaciones.</p>
                
                <p><strong>¿Cómo personalizo el encabezado de los reportes?</strong><br>
                Ve a "Configuración" → "Personalización" → "Encabezados de Reportes".</p>
            </div>
            
            <h2><i class="fas fa-tools"></i> Consejos y Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-lightbulb"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Genera reportes regularmente para mantener datos actualizados</li>
                    <li>Usa filtros específicos para reportes más precisos</li>
                    <li>Programa reportes automáticos para ahorrar tiempo</li>
                    <li>Guarda configuraciones frecuentes como plantillas</li>
                    <li>Verifica los datos antes de generar reportes oficiales</li>
                    <li>Mantén backups de reportes importantes</li>
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
