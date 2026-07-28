<?php
$pageTitle = 'Gestión de Profesores - Documentación EEST2';
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
            <h1><i class="fas fa-chalkboard-teacher"></i> Gestión de Profesores</h1>
            <p>Guía completa para la administración de profesores en el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-list"></i> Acceso al Módulo de Profesores</h2>
            
            <div class="step-box">
                <p><strong>Para acceder al módulo de profesores:</strong></p>
                <ol>
                    <li>Inicia sesión en el sistema</li>
                    <li>Desde el menú principal, haz clic en <strong>"Profesores"</strong></li>
                    <li>Se abrirá la lista completa de profesores registrados</li>
                </ol>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> <strong>Permisos requeridos:</strong> Los usuarios con rol de Administrador o Secretario pueden acceder al módulo de profesores.</p>
            </div>
            
            <h2><i class="fas fa-search"></i> Búsqueda y Filtros</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-filter"></i> Filtros Disponibles</h4>
                <p>El sistema ofrece múltiples opciones para encontrar profesores específicos:</p>
                
                <ul>
                    <li><strong>Por nombre:</strong> Busca por nombre completo o apellido</li>
                    <li><strong>Por DNI:</strong> Búsqueda exacta por número de documento</li>
                    <li><strong>Por materia:</strong> Filtra profesores de una materia específica</li>
                    <li><strong>Por estado:</strong> Activos, inactivos, jubilados</li>
                    <li><strong>Por especialidad:</strong> Profesores de una especialidad técnica</li>
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
            
            <h2><i class="fas fa-plus-circle"></i> Agregar Nuevo Profesor</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-plus"></i> Proceso de Registro</h4>
                
                <div class="step-box">
                    <p><strong>Pasos para agregar un profesor:</strong></p>
                    <ol>
                        <li>Haz clic en el botón <strong>"Agregar Profesor"</strong></li>
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
                        <li>☐ Email válido</li>
                        <li>☐ Teléfono de contacto</li>
                        <li>☐ Materias que dicta</li>
                        <li>☐ Título profesional</li>
                    </ul>
                </div>
                
                <h4>Datos Opcionales:</h4>
                <div class="info-box">
                    <ul>
                        <li>Dirección completa</li>
                        <li>Teléfono alternativo</li>
                        <li>Especialidad técnica</li>
                        <li>Años de experiencia</li>
                        <li>Foto del profesor</li>
                        <li>Observaciones</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <p><i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> El DNI debe ser único en el sistema. Si intentas registrar un DNI que ya existe, el sistema mostrará un error.</p>
            </div>
            
            <h2><i class="fas fa-edit"></i> Editar Información de Profesor</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-edit"></i> Modificación de Datos</h4>
                
                <div class="step-box">
                    <p><strong>Para editar un profesor:</strong></p>
                    <ol>
                        <li>Busca el profesor en la lista</li>
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
                    <li>Materias asignadas</li>
                    <li>Especialidad técnica</li>
                    <li>Estado del profesor</li>
                    <li>Título profesional</li>
                    <li>Observaciones</li>
                </ul>
            </div>
            
            <div class="danger-box">
                <p><i class="fas fa-exclamation-circle"></i> <strong>Restricciones:</strong> El DNI no se puede modificar una vez registrado. Si hay un error en el DNI, contacta al administrador.</p>
            </div>
            
            <h2><i class="fas fa-eye"></i> Ver Ficha Completa del Profesor</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-id-card"></i> Información Detallada</h4>
                
                <div class="step-box">
                    <p><strong>Para ver la ficha completa:</strong></p>
                    <ol>
                        <li>Busca el profesor en la lista</li>
                        <li>Haz clic en el botón <strong>"Ver Ficha"</strong> (ícono de ojo)</li>
                        <li>Se abrirá una página con toda la información del profesor</li>
                    </ol>
                </div>
                
                <h4>Información mostrada en la ficha:</h4>
                <ul>
                    <li><strong>Datos personales:</strong> Nombre, apellido, DNI, fecha de nacimiento</li>
                    <li><strong>Información profesional:</strong> Título, especialidad, años de experiencia</li>
                    <li><strong>Contacto:</strong> Email, teléfonos, dirección</li>
                    <li><strong>Materias:</strong> Lista de materias que dicta</li>
                    <li><strong>Cursos asignados:</strong> Cursos donde enseña</li>
                    <li><strong>Horarios:</strong> Cronograma de clases</li>
                    <li><strong>Documentos:</strong> Certificados, títulos</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-book"></i> Gestión de Materias</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-graduation-cap"></i> Asignación de Materias</h4>
                
                <div class="step-box">
                    <p><strong>Para asignar materias a un profesor:</strong></p>
                    <ol>
                        <li>Desde la ficha del profesor, ve a la sección <strong>"Materias"</strong></li>
                        <li>Haz clic en <strong>"Asignar Materia"</strong></li>
                        <li>Selecciona la materia del dropdown</li>
                        <li>Especifica el curso y división</li>
                        <li>Define los horarios de clase</li>
                        <li>Haz clic en <strong>"Guardar Asignación"</strong></li>
                    </ol>
                </div>
                
                <h4>Tipos de materias:</h4>
                <ul>
                    <li><strong>Materias comunes:</strong> Matemática, Lengua, Historia, etc.</li>
                    <li><strong>Materias técnicas:</strong> Específicas de la especialidad</li>
                    <li><strong>Talleres:</strong> Prácticas profesionales</li>
                    <li><strong>Orientación:</strong> Tutoría y orientación vocacional</li>
                </ul>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-calendar"></i> <strong>Horarios:</strong> El sistema permite asignar múltiples horarios por materia y gestionar conflictos de horarios automáticamente.</p>
            </div>
            
            <h2><i class="fas fa-calendar-alt"></i> Gestión de Horarios</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-clock"></i> Configuración de Horarios</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar horarios:</strong></p>
                    <ol>
                        <li>Ve a la sección <strong>"Horarios"</strong> en la ficha del profesor</li>
                        <li>Selecciona el día de la semana</li>
                        <li>Define la hora de inicio y fin</li>
                        <li>Especifica el aula o taller</li>
                        <li>Asocia la materia correspondiente</li>
                        <li>Guarda el horario</li>
                    </ol>
                </div>
                
                <h4>Configuraciones disponibles:</h4>
                <ul>
                    <li><strong>Horarios fijos:</strong> Mismo horario toda la semana</li>
                    <li><strong>Horarios variables:</strong> Diferentes horarios por día</li>
                    <li><strong>Horarios rotativos:</strong> Cambian por semana</li>
                    <li><strong>Horarios especiales:</strong> Para eventos o actividades</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-users"></i> Gestión de Cursos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-layer-group"></i> Asignación a Cursos</h4>
                
                <div class="step-box">
                    <p><strong>Para asignar profesor a cursos:</strong></p>
                    <ol>
                        <li>Ve a la sección <strong>"Cursos"</strong> en la ficha del profesor</li>
                        <li>Haz clic en <strong>"Asignar Curso"</strong></li>
                        <li>Selecciona el año y división</li>
                        <li>Especifica el rol (Profesor titular, Suplente, etc.)</li>
                        <li>Define el período de asignación</li>
                        <li>Confirma la asignación</li>
                    </ol>
                </div>
                
                <h4>Roles disponibles:</h4>
                <ul>
                    <li><strong>Profesor titular:</strong> Responsable principal de la materia</li>
                    <li><strong>Profesor suplente:</strong> Reemplaza al titular cuando es necesario</li>
                    <li><strong>Profesor auxiliar:</strong> Apoya en actividades específicas</li>
                    <li><strong>Preceptor:</strong> Responsable del curso completo</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Gestión de Documentos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-folder-open"></i> Archivo de Documentos</h4>
                
                <div class="step-box">
                    <p><strong>Para gestionar documentos:</strong></p>
                    <ol>
                        <li>Desde la ficha del profesor, ve a <strong>"Documentos"</strong></li>
                        <li>Haz clic en <strong>"Subir Documento"</strong></li>
                        <li>Selecciona el tipo de documento:</li>
                        <ul>
                            <li>Título profesional</li>
                            <li>Certificado de antecedentes</li>
                            <li>Certificado médico</li>
                            <li>Curriculum vitae</li>
                            <li>Otros documentos</li>
                        </ul>
                        <li>Sube el archivo (PDF, JPG, DOC)</li>
                        <li>Agrega una descripción</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-user-times"></i> Gestión de Estados</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-toggle-on"></i> Cambio de Estado</h4>
                
                <div class="step-box">
                    <p><strong>Estados disponibles:</strong></p>
                    <ul>
                        <li><strong>Activo:</strong> Profesor trabajando normalmente</li>
                        <li><strong>Licencia:</strong> Temporalmente fuera por licencia</li>
                        <li><strong>Jubilado:</strong> Retirado por edad</li>
                        <li><strong>Retirado:</strong> Dejó de trabajar</li>
                        <li><strong>Suspendido:</strong> Por medidas disciplinarias</li>
                    </ul>
                </div>
                
                <div class="step-box">
                    <p><strong>Para cambiar el estado:</strong></p>
                    <ol>
                        <li>Ve a la ficha del profesor</li>
                        <li>Haz clic en <strong>"Cambiar Estado"</strong></li>
                        <li>Selecciona el nuevo estado</li>
                        <li>Agrega una observación explicando el cambio</li>
                        <li>Especifica la fecha de efectividad</li>
                        <li>Confirma el cambio</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-chart-line"></i> Informes Disponibles</h4>
                
                <div class="step-box">
                    <p><strong>Reportes que se pueden generar:</strong></p>
                    <ul>
                        <li><strong>Lista de profesores:</strong> Por materia, curso o estado</li>
                        <li><strong>Horarios de profesores:</strong> Cronograma semanal</li>
                        <li><strong>Distribución de materias:</strong> Carga horaria por profesor</li>
                        <li><strong>Profesores por especialidad:</strong> Distribución técnica</li>
                        <li><strong>Antigüedad:</strong> Años de experiencia</li>
                    </ul>
                </div>
                
                <div class="step-box">
                    <p><strong>Para generar reportes:</strong></p>
                    <ol>
                        <li>Ve a la sección <strong>"Reportes"</strong></li>
                        <li>Selecciona el tipo de reporte</li>
                        <li>Configura los filtros necesarios</li>
                        <li>Elige el formato (PDF, Excel)</li>
                        <li>Haz clic en <strong>"Generar"</strong></li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-download"></i> Exportación de Datos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-file-export"></i> Formatos de Exportación</h4>
                
                <div class="step-box">
                    <p><strong>Formatos disponibles:</strong></p>
                    <ul>
                        <li><strong>Excel (.xlsx):</strong> Para análisis y planillas</li>
                        <li><strong>CSV:</strong> Para importar en otros sistemas</li>
                        <li><strong>PDF:</strong> Para impresión y archivo</li>
                    </ul>
                </div>
                
                <div class="step-box">
                    <p><strong>Datos exportables:</strong></p>
                    <ul>
                        <li>Lista completa de profesores</li>
                        <li>Profesores por materia</li>
                        <li>Profesores por estado</li>
                        <li>Horarios de profesores</li>
                        <li>Distribución de materias</li>
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
                    <li>Los documentos están protegidos con permisos específicos</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Profesores</h4>
                
                <p><strong>¿Puedo asignar un profesor a múltiples materias?</strong><br>
                Sí, un profesor puede dictar varias materias diferentes.</p>
                
                <p><strong>¿Cómo cambio los horarios de un profesor?</strong><br>
                Ve a la ficha del profesor, sección "Horarios" y modifica los horarios necesarios.</p>
                
                <p><strong>¿Puedo eliminar un profesor del sistema?</strong><br>
                No se recomienda eliminar profesores. En su lugar, cambia su estado a "Retirado" o "Jubilado".</p>
                
                <p><strong>¿Cómo genero un reporte de horarios?</strong><br>
                Ve a la sección "Reportes" y selecciona "Horarios de Profesores".</p>
                
                <p><strong>¿Qué documentos debo subir para un profesor?</strong><br>
                Título profesional, certificado de antecedentes, certificado médico y CV son los documentos básicos.</p>
            </div>
            
            <h2><i class="fas fa-tools"></i> Consejos y Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-lightbulb"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Mantén actualizada la información de contacto</li>
                    <li>Sube todos los documentos requeridos</li>
                    <li>Configura los horarios con anticipación</li>
                    <li>Revisa periódicamente los estados de los profesores</li>
                    <li>Genera backups regulares de la información</li>
                    <li>Verifica que no haya conflictos de horarios</li>
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
