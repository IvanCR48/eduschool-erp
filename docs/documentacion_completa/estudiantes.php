<?php
$pageTitle = 'Gestión de Estudiantes - Documentación EEST2';
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
            <h1><i class="fas fa-user-graduate"></i> Gestión de Estudiantes</h1>
            <p>Guía completa para la administración de estudiantes en el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-list"></i> Acceso al Módulo de Estudiantes</h2>
            
            <div class="step-box">
                <p><strong>Para acceder al módulo de estudiantes:</strong></p>
                <ol>
                    <li>Inicia sesión en el sistema</li>
                    <li>Desde el menú principal, haz clic en <strong>"Estudiantes"</strong></li>
                    <li>Se abrirá la lista completa de estudiantes registrados</li>
                </ol>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> <strong>Permisos requeridos:</strong> Los usuarios con rol de Administrador, Secretario o Profesor pueden acceder al módulo de estudiantes.</p>
            </div>
            
            <h2><i class="fas fa-search"></i> Búsqueda y Filtros</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-filter"></i> Filtros Disponibles</h4>
                <p>El sistema ofrece múltiples opciones para encontrar estudiantes específicos:</p>
                
                <ul>
                    <li><strong>Por nombre:</strong> Busca por nombre completo o apellido</li>
                    <li><strong>Por DNI:</strong> Búsqueda exacta por número de documento</li>
                    <li><strong>Por curso:</strong> Filtra estudiantes de un curso específico</li>
                    <li><strong>Por estado:</strong> Activos, inactivos, egresados</li>
                    <li><strong>Por año:</strong> Estudiantes de un año específico</li>
                </ul>
            </div>
            
            <div class="step-box">
                <p><strong>Cómo usar los filtros:</strong></p>
                <ol>
                    <li>En la barra de búsqueda, escribe el criterio deseado</li>
                    <li>Selecciona el tipo de filtro en el dropdown</li>
                    <li>Los resultados se actualizarán automáticamente</li>
                    <li>Para limpiar filtros, haz clic en "Limpiar"</li>
                </ol>
            </div>
            
            <h2><i class="fas fa-plus-circle"></i> Agregar Nuevo Estudiante</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-plus"></i> Proceso de Registro</h4>
                
                <div class="step-box">
                    <p><strong>Pasos para agregar un estudiante:</strong></p>
                    <ol>
                        <li>Haz clic en el botón <strong>"Agregar Estudiante"</strong></li>
                        <li>Completa el formulario con los datos requeridos:</li>
                    </ol>
                </div>
                
                <h4>Datos Obligatorios:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Nombre completo</li>
                        <li>☐ Apellido completo</li>
                        <li>☐ DNI (sin puntos ni guiones)</li>
                        <li>☐ Fecha de nacimiento</li>
                        <li>☐ Curso asignado</li>
                        <li>☐ Email válido</li>
                        <li>☐ Teléfono de contacto</li>
                    </ul>
                </div>
                
                <h4>Datos Opcionales:</h4>
                <div class="info-box">
                    <ul>
                        <li>Dirección completa</li>
                        <li>Teléfono alternativo</li>
                        <li>Información del tutor/responsable</li>
                        <li>Observaciones médicas</li>
                        <li>Foto del estudiante</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <p><i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> El DNI debe ser único en el sistema. Si intentas registrar un DNI que ya existe, el sistema mostrará un error.</p>
            </div>
            
            <h2><i class="fas fa-edit"></i> Editar Información de Estudiante</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-edit"></i> Modificación de Datos</h4>
                
                <div class="step-box">
                    <p><strong>Para editar un estudiante:</strong></p>
                    <ol>
                        <li>Busca el estudiante en la lista</li>
                        <li>Haz clic en el botón <strong>"Editar"</strong> (ícono de lápiz)</li>
                        <li>Modifica los campos necesarios</li>
                        <li>Haz clic en <strong>"Guardar Cambios"</strong></li>
                    </ol>
                </div>
                
                <h4>Campos que se pueden modificar:</h4>
                <ul>
                    <li>Información personal (nombre, apellido, fecha de nacimiento)</li>
                    <li>Datos de contacto (email, teléfonos)</li>
                    <li>Dirección</li>
                    <li>Curso asignado</li>
                    <li>Estado del estudiante</li>
                    <li>Información del tutor</li>
                    <li>Observaciones</li>
                </ul>
            </div>
            
            <div class="danger-box">
                <p><i class="fas fa-exclamation-circle"></i> <strong>Restricciones:</strong> El DNI no se puede modificar una vez registrado. Si hay un error en el DNI, contacta al administrador.</p>
            </div>
            
            <h2><i class="fas fa-eye"></i> Ver Ficha Completa del Estudiante</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-id-card"></i> Información Detallada</h4>
                
                <div class="step-box">
                    <p><strong>Para ver la ficha completa:</strong></p>
                    <ol>
                        <li>Busca el estudiante en la lista</li>
                        <li>Haz clic en el botón <strong>"Ver Ficha"</strong> (ícono de ojo)</li>
                        <li>Se abrirá una página con toda la información del estudiante</li>
                    </ol>
                </div>
                
                <h4>Información mostrada en la ficha:</h4>
                <ul>
                    <li><strong>Datos personales:</strong> Nombre, apellido, DNI, fecha de nacimiento</li>
                    <li><strong>Información académica:</strong> Curso actual, año de ingreso</li>
                    <li><strong>Contacto:</strong> Email, teléfonos, dirección</li>
                    <li><strong>Historial académico:</strong> Notas por materia, promedios</li>
                    <li><strong>Asistencias:</strong> Registro de faltas y justificaciones</li>
                    <li><strong>Documentos:</strong> Boletines, certificados</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-chart-line"></i> Gestión de Notas</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-graduation-cap"></i> Carga de Calificaciones</h4>
                
                <div class="step-box">
                    <p><strong>Para cargar notas:</strong></p>
                    <ol>
                        <li>Desde la ficha del estudiante, ve a la sección <strong>"Notas"</strong></li>
                        <li>Selecciona la materia y el período</li>
                        <li>Ingresa la calificación (0-10)</li>
                        <li>Agrega observaciones si es necesario</li>
                        <li>Haz clic en <strong>"Guardar Nota"</strong></li>
                    </ol>
                </div>
                
                <h4>Tipos de evaluaciones:</h4>
                <ul>
                    <li><strong>Parciales:</strong> Evaluaciones parciales por trimestre</li>
                    <li><strong>Trabajos prácticos:</strong> TPs y proyectos</li>
                    <li><strong>Exámenes:</strong> Exámenes finales</li>
                    <li><strong>Recuperatorios:</strong> Evaluaciones de recuperación</li>
                </ul>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-calculator"></i> <strong>Cálculo automático:</strong> El sistema calcula automáticamente los promedios por materia y el promedio general del estudiante.</p>
            </div>
            
            <h2><i class="fas fa-calendar-check"></i> Control de Asistencias</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-clipboard-list"></i> Registro de Asistencias</h4>
                
                <div class="step-box">
                    <p><strong>Para registrar asistencias:</strong></p>
                    <ol>
                        <li>Ve a la sección <strong>"Asistencias"</strong> en la ficha del estudiante</li>
                        <li>Selecciona la fecha y materia</li>
                        <li>Marca si el estudiante asistió o faltó</li>
                        <li>Si faltó, especifica el motivo (justificado/injustificado)</li>
                        <li>Guarda el registro</li>
                    </ol>
                </div>
                
                <h4>Tipos de inasistencias:</h4>
                <ul>
                    <li><strong>Justificada:</strong> Con certificado médico o nota de los padres</li>
                    <li><strong>Injustificada:</strong> Sin justificación válida</li>
                    <li><strong>Retiro temprano:</strong> Salida anticipada autorizada</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Generación de Documentos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-print"></i> Boletines y Certificados</h4>
                
                <div class="step-box">
                    <p><strong>Para generar documentos:</strong></p>
                    <ol>
                        <li>Desde la ficha del estudiante, ve a <strong>"Documentos"</strong></li>
                        <li>Selecciona el tipo de documento:</li>
                        <ul>
                            <li>Boletín de calificaciones</li>
                            <li>Certificado de alumno regular</li>
                            <li>Constancia de asistencia</li>
                            <li>Certificado de egreso</li>
                        </ul>
                        <li>Configura el período si es necesario</li>
                        <li>Haz clic en <strong>"Generar PDF"</strong></li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-user-times"></i> Gestión de Estados</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-toggle-on"></i> Cambio de Estado</h4>
                
                <div class="step-box">
                    <p><strong>Estados disponibles:</strong></p>
                    <ul>
                        <li><strong>Activo:</strong> Estudiante cursando normalmente</li>
                        <li><strong>Inactivo:</strong> Temporalmente fuera del sistema</li>
                        <li><strong>Egresado:</strong> Completó sus estudios</li>
                        <li><strong>Retirado:</strong> Abandonó los estudios</li>
                        <li><strong>Suspendido:</strong> Por medidas disciplinarias</li>
                    </ul>
                </div>
                
                <div class="step-box">
                    <p><strong>Para cambiar el estado:</strong></p>
                    <ol>
                        <li>Ve a la ficha del estudiante</li>
                        <li>Haz clic en <strong>"Cambiar Estado"</strong></li>
                        <li>Selecciona el nuevo estado</li>
                        <li>Agrega una observación explicando el cambio</li>
                        <li>Confirma el cambio</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-users"></i> Gestión por Lotes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-layer-group"></i> Operaciones Masivas</h4>
                
                <div class="step-box">
                    <p><strong>Operaciones disponibles:</strong></p>
                    <ul>
                        <li><strong>Cambio de curso:</strong> Mover múltiples estudiantes a otro curso</li>
                        <li><strong>Actualización de datos:</strong> Modificar información común</li>
                        <li><strong>Exportación:</strong> Descargar listas en Excel/PDF</li>
                        <li><strong>Importación:</strong> Cargar estudiantes desde archivo CSV</li>
                    </ul>
                </div>
                
                <div class="step-box">
                    <p><strong>Para operaciones masivas:</strong></p>
                    <ol>
                        <li>Selecciona los estudiantes usando las casillas de verificación</li>
                        <li>Haz clic en <strong>"Acciones Masivas"</strong></li>
                        <li>Selecciona la operación deseada</li>
                        <li>Confirma la acción</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-download"></i> Exportación de Datos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-file-export"></i> Formatos de Exportación</h4>
                
                <div class="step-box">
                    <p><strong>Formatos disponibles:</strong></p>
                    <ul>
                        <li><strong>Excel (.xlsx):</strong> Para análisis y reportes</li>
                        <li><strong>CSV:</strong> Para importar en otros sistemas</li>
                        <li><strong>PDF:</strong> Para impresión y archivo</li>
                    </ul>
                </div>
                
                <div class="step-box">
                    <p><strong>Datos exportables:</strong></p>
                    <ul>
                        <li>Lista completa de estudiantes</li>
                        <li>Estudiantes por curso</li>
                        <li>Estudiantes por estado</li>
                        <li>Datos de contacto</li>
                        <li>Historial académico</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Seguridad y Privacidad</h2>
            
            <div class="warning-box">
                <p><i class="fas fa-lock"></i> <strong>Protección de datos:</strong></p>
                <ul>
                    <li>Todos los datos están protegidos por contraseñas</li>
                    <li>Los accesos son registrados en logs de seguridad</li>
                    <li>Los datos personales están encriptados</li>
                    <li>Solo personal autorizado puede acceder a la información</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Estudiantes</h4>
                
                <p><strong>¿Puedo modificar el DNI de un estudiante?</strong><br>
                No, el DNI no se puede modificar una vez registrado. Si hay un error, contacta al administrador.</p>
                
                <p><strong>¿Cómo cambio a un estudiante de curso?</strong><br>
                Ve a la ficha del estudiante, haz clic en "Editar" y modifica el campo "Curso".</p>
                
                <p><strong>¿Puedo eliminar un estudiante del sistema?</strong><br>
                No se recomienda eliminar estudiantes. En su lugar, cambia su estado a "Retirado" o "Egresado".</p>
                
                <p><strong>¿Cómo genero un boletín de calificaciones?</strong><br>
                Desde la ficha del estudiante, ve a "Documentos" y selecciona "Boletín de calificaciones".</p>
            </div>
            
            <h2><i class="fas fa-tools"></i> Consejos y Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-lightbulb"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Mantén los datos de contacto actualizados</li>
                    <li>Registra las asistencias diariamente</li>
                    <li>Carga las notas en tiempo y forma</li>
                    <li>Genera backups regulares de la información</li>
                    <li>Revisa periódicamente los estados de los estudiantes</li>
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
