<?php
$pageTitle = 'Guía de Usuario - Documentación EEST2';
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
            <h1><i class="fas fa-user-circle"></i> Guía de Usuario</h1>
            <p>Manual completo para usar el Sistema Administrativo EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-sign-in-alt"></i> Inicio de Sesión</h2>
            
            <h3>Acceder al Sistema</h3>
            <ol>
                <li>Abre tu navegador web</li>
                <li>Ingresa la URL del sistema: <code>http://[servidor]/SistemaAdmin</code></li>
                <li>Serás redirigido automáticamente a la página de login</li>
                <li>Completa el desafío reCAPTCHA v2.</li>
                <li>Alternativamente (si sos docente), podés hacer clic en "Continuar con Google" para ingresar con tu cuenta de correo registrada.</li>
                <li>Haz clic en "Acceder al sistema" (o se iniciará de forma automática con Google).</li>
            </ol>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Nota:</strong> Si olvidaste tu contraseña, contacta al administrador del sistema.
            </div>
            
            <h2><i class="fas fa-th-large"></i> Panel Principal (Dashboard)</h2>
            
            <p>Al iniciar sesión, verás el dashboard con información relevante según tu rol:</p>
            
            <h3>Elementos del Dashboard</h3>
            <ul>
                <li><strong>Estadísticas rápidas:</strong> Total de estudiantes, profesores, cursos activos</li>
                <li><strong>Accesos directos:</strong> Enlaces a las funciones más usadas</li>
                <li><strong>Notificaciones:</strong> Alertas y recordatorios importantes</li>
                <li><strong>Actividad reciente:</strong> Últimas acciones realizadas en el sistema</li>
            </ul>
            
            <h2><i class="fas fa-user-graduate"></i> Gestión de Estudiantes</h2>
            
            <h3>Ver Lista de Estudiantes</h3>
            <ol>
                <li>En el menú principal, haz clic en <strong>"Estudiantes"</strong></li>
                <li>Verás la lista completa de estudiantes registrados</li>
                <li>Usa los filtros para buscar por:
                    <ul>
                        <li>Nombre o apellido</li>
                        <li>DNI</li>
                        <li>Curso</li>
                        <li>Estado (activo/inactivo)</li>
                    </ul>
                </li>
            </ol>
            
            <h3>Registrar Nuevo Estudiante</h3>
            <ol>
                <li>Haz clic en el botón <strong>"Nuevo Estudiante"</strong></li>
                <li>Completa el formulario con los datos:
                    <ul>
                        <li>Datos personales (nombre, apellido, DNI, fecha de nacimiento)</li>
                        <li>Datos de contacto (teléfono, email, domicilio)</li>
                        <li>Información académica (curso, año de ingreso)</li>
                    </ul>
                </li>
                <li>Haz clic en <strong>"Guardar"</strong></li>
            </ol>
            
            <div class="success-box">
                <strong><i class="fas fa-check-circle"></i> Consejo:</strong> Completa todos los campos obligatorios marcados con asterisco (*).
            </div>
            
            <h3>Ver Ficha del Estudiante</h3>
            <p>La ficha del estudiante contiene toda su información completa:</p>
            <ul>
                <li><strong>Datos personales:</strong> Información básica del estudiante</li>
                <li><strong>Información académica:</strong> Curso, especialidad, turno</li>
                <li><strong>Historial de notas:</strong> Calificaciones por cuatrimestre</li>
                <li><strong>Llamados de atención:</strong> Registro disciplinario</li>
                <li><strong>Observaciones:</strong> Notas adicionales sobre el estudiante</li>
            </ul>
            
            <h2><i class="fas fa-calendar-check"></i> Gestión de Asistencia</h2>
            
            <h3>Tomar Asistencia (Preceptores)</h3>
            <ol>
                <li>Ve al <strong>"Dashboard de Asistencia"</strong> o "Asistencia Virtual" en el menú principal.</li>
                <li>Selecciona el curso, materia y fecha.</li>
                <li><strong>En Computadora:</strong> Marca el estado directamente en la grilla de cada estudiante. El sistema guardará automáticamente por AJAX al seleccionar una opción. Si el alumno entregó certificado, haz clic en "Ver notas y justificativo" para desplegar la sección de adjuntos y observaciones.</li>
                <li><strong>En Celular (Diseño Móvil Premium):</strong>
                    <ul>
                        <li>Navega entre las pestañas rápidas de la sección: <strong>Alumnos</strong>, <strong>Resumen</strong> (indicadores estadísticos), y <strong>Filtros</strong>.</li>
                        <li>Toca la fila de cualquier alumno para desplegar el **Panel Inferior (Bottom Sheet)**.</li>
                        <li>Desde este panel interactivo, marca el estado con botones de gran tamaño táctiles, escribe observaciones o sube el certificado justificativo. Los cambios se guardarán automáticamente en segundo plano.</li>
                    </ul>
                </li>
            </ol>

            <h3>Ver Justificativos y Alertas</h3>
            <ol>
                <li>En el <strong>Dashboard de Asistencia</strong> verás un resumen del presentismo del día</li>
                <li>En la sección "Justificados hoy" podrás ver y abrir los certificados subidos ese mismo día</li>
                <li>En la sección de alertas, el sistema listará a los alumnos en riesgo de repitencia por faltas</li>
            </ol>

            <h2><i class="fas fa-clipboard-check"></i> Gestión de Notas</h2>
            
            <h3>Cargar Notas (Para Profesores)</h3>
            <ol>
                <li>Ve a <strong>"Notas"</strong> en el menú principal</li>
                <li>Selecciona:
                    <ul>
                        <li>Curso</li>
                        <li>Materia que dictas</li>
                        <li>Cuatrimestre</li>
                    </ul>
                </li>
                <li>Aparecerá la lista de estudiantes del curso</li>
                <li>Ingresa las calificaciones en los campos correspondientes</li>
                <li>Haz clic en <strong>"Guardar Notas"</strong></li>
            </ol>
            
            <h3>Ver Boletín de Notas</h3>
            <ol>
                <li>Desde la ficha del estudiante, haz clic en <strong>"Ver Notas"</strong></li>
                <li>Verás un resumen completo de todas las materias</li>
                <li>Puedes filtrar por cuatrimestre</li>
                <li>Para imprimir, haz clic en <strong>"Imprimir Boletín"</strong></li>
            </ol>
            
            <h2><i class="fas fa-chalkboard-teacher"></i> Gestión de Profesores</h2>
            
            <h3>Ver Lista de Profesores</h3>
            <p>En <strong>"Profesores"</strong> encontrarás:</p>
            <ul>
                <li>Lista completa del cuerpo docente</li>
                <li>Materias que dicta cada profesor</li>
                <li>Cursos asignados</li>
                <li>Información de contacto</li>
            </ul>
            
            <h3>Registrar Nuevo Profesor (Solo Administradores)</h3>
            <ol>
                <li>Haz clic en <strong>"Nuevo Profesor"</strong></li>
                <li>Completa los datos personales</li>
                <li>Asigna las materias que dictará</li>
                <li>Asigna los cursos correspondientes</li>
                <li>Guarda el registro</li>
            </ol>
            
            <h2><i class="fas fa-book-open"></i> Gestión de Cursos y Materias</h2>
            
            <h3>Ver Cursos</h3>
            <p>En <strong>"Cursos"</strong> puedes:</p>
            <ul>
                <li>Ver todos los cursos activos</li>
                <li>Consultar la lista de estudiantes por curso</li>
                <li>Ver profesores asignados</li>
                <li>Acceder al horario del curso</li>
            </ul>
            
            <h3>Ver Materias</h3>
            <p>En <strong>"Materias"</strong> encontrarás:</p>
            <ul>
                <li>Lista de todas las materias</li>
                <li>Profesores que las dictan</li>
                <li>Cursos donde se imparten</li>
            </ul>
            
            <h2><i class="fas fa-exclamation-triangle"></i> Llamados de Atención</h2>
            
            <h3>Registrar Llamado de Atención (Preceptores/Directivos)</h3>
            <ol>
                <li>Desde la ficha del estudiante, ve a la sección <strong>"Llamados de Atención"</strong></li>
                <li>Haz clic en <strong>"Nuevo Llamado"</strong></li>
                <li>Completa:
                    <ul>
                        <li>Fecha del incidente</li>
                        <li>Motivo (falta de respeto, impuntualidad, etc.)</li>
                        <li>Descripción detallada</li>
                        <li>Gravedad (leve, moderado, grave)</li>
                    </ul>
                </li>
                <li>Guarda el registro</li>
            </ol>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Importante:</strong> Los llamados quedan registrados en el historial del estudiante.
            </div>
            
            <h2><i class="fas fa-print"></i> Impresión de Documentos</h2>
            
            <h3>Imprimir Boletín</h3>
            <ol>
                <li>Abre la ficha del estudiante</li>
                <li>Haz clic en <strong>"Imprimir Boletín"</strong></li>
                <li>Se abrirá una ventana de impresión</li>
                <li>Selecciona la impresora y confirma</li>
            </ol>
            
            <h3>Imprimir Listas de Estudiantes</h3>
            <ol>
                <li>Desde <strong>"Cursos"</strong>, selecciona un curso</li>
                <li>Haz clic en <strong>"Imprimir Lista"</strong></li>
                <li>Se generará una lista en formato PDF</li>
            </ol>
            
            <h2><i class="fas fa-user-cog"></i> Mi Perfil</h2>
            
            <h3>Editar Información Personal</h3>
            <ol>
                <li>Haz clic en tu nombre en la esquina superior derecha</li>
                <li>Selecciona <strong>"Mi Perfil"</strong></li>
                <li>Edita tu información:
                    <ul>
                        <li>Email</li>
                        <li>Teléfono</li>
                        <li>Foto de perfil</li>
                    </ul>
                </li>
                <li>Guarda los cambios</li>
            </ol>
            
            <h3>Cambiar Contraseña</h3>
            <ol>
                <li>Ve a <strong>"Mi Perfil"</strong></li>
                <li>Haz clic en <strong>"Cambiar Contraseña"</strong></li>
                <li>Ingresa:
                    <ul>
                        <li>Contraseña actual</li>
                        <li>Nueva contraseña</li>
                        <li>Confirmar nueva contraseña</li>
                    </ul>
                </li>
                <li>Haz clic en <strong>"Actualizar Contraseña"</strong></li>
            </ol>
            
            <div class="success-box">
                <strong><i class="fas fa-lock"></i> Seguridad:</strong> Usa una contraseña fuerte con al menos 8 caracteres, mayúsculas, minúsculas y números.
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <h3>¿Cómo recupero mi contraseña?</h3>
            <p>Contacta al administrador del sistema para que restablezca tu contraseña.</p>
            
            <h3>¿Puedo acceder desde mi celular?</h3>
            <p>Sí, el sistema es totalmente responsive y funciona en dispositivos móviles.</p>
            
            <h3>¿Qué hago si encuentro un error?</h3>
            <p>Reporta el error al administrador del sistema con una captura de pantalla y descripción del problema.</p>
            
            <h3>¿Puedo exportar datos a Excel?</h3>
            <p>Sí, desde la mayoría de listados puedes exportar los datos a CSV/Excel.</p>
            
            <h2><i class="fas fa-life-ring"></i> Soporte</h2>
            
            <p>Si necesitas ayuda adicional:</p>
            <ul>
                <li><strong>Email:</strong> soporte@eest2.edu.ar</li>
                <li><strong>Teléfono:</strong> [Número de contacto]</li>
                <li><strong>Horario:</strong> Lunes a Viernes, 8:00 - 16:00</li>
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
