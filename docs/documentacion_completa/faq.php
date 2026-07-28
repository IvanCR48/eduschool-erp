<?php
$pageTitle = 'Preguntas Frecuentes (FAQ) - Documentación EEST2';
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
            <h1><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h1>
            <p>Respuestas a las consultas más comunes sobre el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Nota:</strong> Si no encuentras la respuesta a tu pregunta aquí, contacta al soporte técnico.
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-sign-in-alt"></i></span> Acceso y Autenticación</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo obtengo mis credenciales de acceso?
                </div>
                <div class="faq-answer">
                    <p>Las credenciales son proporcionadas por el administrador del sistema al momento de tu registro. Recibirás un email con tu nombre de usuario y contraseña temporal. En tu primer inicio de sesión, se te pedirá que cambies la contraseña.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> Olvidé mi contraseña, ¿qué hago?
                </div>
                <div class="faq-answer">
                    <p>Contacta al administrador del sistema o al área de soporte técnico. Por razones de seguridad, no existe un sistema automatizado de recuperación de contraseñas. El administrador podrá resetear tu contraseña y proporcionarte una temporal.</p>
                    <p><strong>Contacto:</strong> admin@eest2.edu.ar o en la secretaría de la escuela.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo funciona el acceso con Google para docentes y qué es reCAPTCHA?
                </div>
                <div class="faq-answer">
                    <p>El sistema permite a los docentes iniciar sesión rápidamente mediante Google OAuth 2.0 (haciendo clic en "Continuar con Google"), siempre que su correo registrado coincida con el de Google. Para los demás usuarios y logins estándar, reCAPTCHA v2 valida que el acceso no proceda de bots automáticos.</p>
                    <p><strong>Ventajas:</strong></p>
                    <ul>
                        <li>Acceso rápido y sin contraseña manual para docentes.</li>
                        <li>Mayor protección contra ataques automatizados de fuerza bruta.</li>
                        <li>Reducción del riesgo de secuestro de cuentas.</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> Mi cuenta está bloqueada, ¿cómo la desbloqueo?
                </div>
                <div class="faq-answer">
                    <p>Las cuentas se bloquean automáticamente después de 5 intentos fallidos de inicio de sesión. Puedes:</p>
                    <ul>
                        <li><strong>Esperar 15 minutos:</strong> El bloqueo se levanta automáticamente</li>
                        <li><strong>Contactar al administrador:</strong> Puede desbloquear tu cuenta manualmente</li>
                    </ul>
                </div>
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-user-graduate"></i></span> Gestión de Estudiantes</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo registro un nuevo estudiante?
                </div>
                <div class="faq-answer">
                    <p>Solo los usuarios con permisos de Secretaría o Administrador pueden registrar estudiantes:</p>
                    <ol>
                        <li>Ve a <strong>Estudiantes → Nuevo Estudiante</strong></li>
                        <li>Completa todos los campos obligatorios (marcados con *)</li>
                        <li>Verifica los datos antes de guardar</li>
                        <li>El sistema generará automáticamente el legajo del estudiante</li>
                    </ol>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Puedo modificar los datos de un estudiante después de registrarlo?
                </div>
                <div class="faq-answer">
                    <p>Sí, puedes modificar los datos en cualquier momento:</p>
                    <ol>
                        <li>Busca al estudiante en la lista</li>
                        <li>Haz clic en su nombre para ver la ficha</li>
                        <li>Haz clic en el botón <strong>"Editar"</strong></li>
                        <li>Modifica los datos necesarios</li>
                        <li>Guarda los cambios</li>
                    </ol>
                    <p><strong>Nota:</strong> Todas las modificaciones quedan registradas en el historial del estudiante.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo busco un estudiante específico?
                </div>
                <div class="faq-answer">
                    <p>El sistema ofrece múltiples formas de búsqueda:</p>
                    <ul>
                        <li><strong>Por nombre o apellido:</strong> Usa el campo de búsqueda principal</li>
                        <li><strong>Por DNI:</strong> Ingresa el número de documento</li>
                        <li><strong>Por curso:</strong> Filtra por año y división</li>
                        <li><strong>Por legajo:</strong> Ingresa el número de legajo</li>
                    </ul>
                    <p>La búsqueda es instantánea y muestra resultados mientras escribes.</p>
                </div>
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-clipboard-check"></i></span> Notas y Calificaciones</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo cargo las notas de mis estudiantes?
                </div>
                <div class="faq-answer">
                    <p>Si eres profesor:</p>
                    <ol>
                        <li>Ve a <strong>Notas → Cargar Notas</strong></li>
                        <li>Selecciona tu materia y el curso</li>
                        <li>Selecciona el cuatrimestre</li>
                        <li>Ingresa las calificaciones para cada estudiante</li>
                        <li>Haz clic en <strong>"Guardar Notas"</strong></li>
                    </ol>
                    <p><strong>Importante:</strong> Solo puedes cargar notas de tus materias asignadas.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Puedo modificar una nota después de cargarla?
                </div>
                <div class="faq-answer">
                    <p>Sí, pero depende del período académico:</p>
                    <ul>
                        <li><strong>Durante el cuatrimestre:</strong> Puedes modificar libremente</li>
                        <li><strong>Después del cierre:</strong> Solo con autorización del director</li>
                        <li><strong>Nota cerrada:</strong> Requiere justificación escrita</li>
                    </ul>
                    <p>Todas las modificaciones quedan registradas con timestamp y usuario.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo imprimo un boletín de calificaciones?
                </div>
                <div class="faq-answer">
                    <p>Proceso para imprimir boletines:</p>
                    <ol>
                        <li>Abre la ficha del estudiante</li>
                        <li>Ve a la sección de <strong>"Notas"</strong></li>
                        <li>Selecciona el cuatrimestre o año completo</li>
                        <li>Haz clic en <strong>"Imprimir Boletín"</strong></li>
                        <li>Se abrirá un PDF listo para imprimir</li>
                    </ol>
                    <p>También puedes generar boletines masivos desde <strong>Reportes → Boletines por Curso</strong>.</p>
                </div>
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-cog"></i></span> Uso del Sistema</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Puedo usar el sistema desde mi celular?
                </div>
                <div class="faq-answer">
                    <p>¡Sí! El sistema es totalmente responsive y funciona perfectamente en dispositivos móviles. Puedes:</p>
                    <ul>
                        <li>Acceder desde cualquier navegador moderno (Chrome, Safari, Firefox).</li>
                        <li>Usar todas las funciones principales de administración.</li>
                        <li>Tomar asistencia utilizando la interfaz móvil premium táctil (con pestañas y panel Bottom Sheet deslizable).</li>
                        <li>Cargar notas desde tu teléfono.</li>
                        <li>Ver fichas de estudiantes y reportes de inasistencia adaptados a tarjetas.</li>
                    </ul>
                    <p><strong>Consejo:</strong> Agrega el sitio a tu pantalla de inicio para un acceso rápido como si fuera una aplicación móvil.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Puedo exportar datos a Excel?
                </div>
                <div class="faq-answer">
                    <p>Sí, la mayoría de listados incluyen opciones de exportación:</p>
                    <ul>
                        <li><strong>CSV:</strong> Compatible con Excel, Google Sheets</li>
                        <li><strong>PDF:</strong> Para reportes y documentos oficiales</li>
                        <li><strong>Impresión directa:</strong> Desde el navegador</li>
                    </ul>
                    <p>Busca el botón <strong>"Exportar"</strong> en la parte superior de las listas.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> El sistema está lento, ¿qué puedo hacer?
                </div>
                <div class="faq-answer">
                    <p>Prueba estas soluciones:</p>
                    <ol>
                        <li><strong>Limpia la caché:</strong> Ctrl+Shift+Del en tu navegador</li>
                        <li><strong>Actualiza la página:</strong> Presiona F5</li>
                        <li><strong>Cierra otras pestañas:</strong> Libera memoria</li>
                        <li><strong>Verifica tu conexión:</strong> Asegúrate de tener buena señal</li>
                        <li><strong>Prueba otro navegador:</strong> Chrome o Firefox recomendados</li>
                    </ol>
                    <p>Si el problema persiste, contacta al soporte técnico.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo cierro mi sesión correctamente?
                </div>
                <div class="faq-answer">
                    <p>Para cerrar sesión de forma segura:</p>
                    <ol>
                        <li>Haz clic en tu nombre en la esquina superior derecha</li>
                        <li>Selecciona <strong>"Cerrar Sesión"</strong></li>
                        <li>Serás redirigido a la página de login</li>
                    </ol>
                    <p><strong>Importante:</strong> Siempre cierra sesión si usas una computadora compartida.</p>
                </div>
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-bug"></i></span> Problemas Comunes</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> Veo un error 403 - Acceso prohibido
                </div>
                <div class="faq-answer">
                    <p>Este error significa que no tienes permisos para acceder a esa función:</p>
                    <ul>
                        <li>Verifica que tengas el rol correcto asignado</li>
                        <li>Algunos módulos son solo para administradores</li>
                        <li>Contacta al administrador si crees que deberías tener acceso</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> No puedo subir un archivo
                </div>
                <div class="faq-answer">
                    <p>Verifica:</p>
                    <ul>
                        <li><strong>Tamaño del archivo:</strong> Máximo 50MB</li>
                        <li><strong>Formato permitido:</strong> PDF, DOC, DOCX, JPG, PNG</li>
                        <li><strong>Nombre del archivo:</strong> Sin caracteres especiales (ñ, acentos, símbolos)</li>
                    </ul>
                    <p>Si cumples todos los requisitos y sigue fallando, contacta soporte.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> Los datos no se guardan
                </div>
                <div class="faq-answer">
                    <p>Posibles causas:</p>
                    <ul>
                        <li><strong>Sesión expirada:</strong> Vuelve a iniciar sesión</li>
                        <li><strong>Campos obligatorios vacíos:</strong> Completa todos los campos con *</li>
                        <li><strong>Formato incorrecto:</strong> Verifica que los datos sean válidos</li>
                        <li><strong>Conexión interrumpida:</strong> Verifica tu internet</li>
                    </ul>
                </div>
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-shield-alt"></i></span> Seguridad y Privacidad</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Mis datos están seguros?
                </div>
                <div class="faq-answer">
                    <ul>
                        <li>Cifrado de contraseñas con algoritmos seguros</li>
                        <li>Conexión segura HTTPS (en producción)</li>
                        <li>Acceso seguro con Google OAuth y reCAPTCHA v2</li>
                        <li>Logs de auditoría completos</li>
                        <li>Backups automáticos diarios</li>
                    </ul>
                    <p>Para más información, consulta la <a href="seguridad.php">Documentación de Seguridad</a>.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Quién puede ver mis datos personales?
                </div>
                <div class="faq-answer">
                    <p>El acceso a información está controlado por roles:</p>
                    <ul>
                        <li><strong>Administradores:</strong> Acceso completo</li>
                        <li><strong>Directivos:</strong> Todos los estudiantes y personal</li>
                        <li><strong>Secretarías:</strong> Información administrativa</li>
                        <li><strong>Profesores:</strong> Solo sus estudiantes</li>
                        <li><strong>Preceptores:</strong> Estudiantes de su turno</li>
                    </ul>
                    <p>Todos los accesos quedan registrados en logs de auditoría.</p>
                </div>
            </div>
            
            <h2><span class="category-icon"><i class="fas fa-life-ring"></i></span> Soporte</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Cómo contacto al soporte técnico?
                </div>
                <div class="faq-answer">
                    <p>Tienes varias opciones:</p>
                    <ul>
                        <li><strong>Email:</strong> soporte@eest2.edu.ar</li>
                        <li><strong>Sistema de tickets:</strong> Panel Admin → Soporte (si tienes acceso)</li>
                        <li><strong>En persona:</strong> Secretaría de la escuela</li>
                        <li><strong>Teléfono:</strong> [Número de contacto] (horario escolar)</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <i class="fas fa-question"></i> ¿Hay tutoriales o videos de ayuda?
                </div>
                <div class="faq-answer">
                    <p>Sí, disponemos de material de capacitación:</p>
                    <ul>
                        <li><strong>Documentación escrita:</strong> Esta sección completa</li>
                        <li><strong>Guías paso a paso:</strong> Para funciones específicas</li>
                        <li><strong>Capacitaciones presenciales:</strong> Al inicio del año escolar</li>
                    </ul>
                    <p>Consulta el <a href="guia_usuario.php">Manual de Usuario</a> para más información.</p>
                </div>
            </div>
            
            <div class="success-box">
                <strong><i class="fas fa-lightbulb"></i> ¿No encuentras tu respuesta?</strong>
                <p style="margin-bottom: 0;">Envía tu pregunta a soporte@eest2.edu.ar y la agregaremos a esta sección.</p>
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
