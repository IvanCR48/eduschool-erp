<?php
$pageTitle = 'Sistema de Autenticación - Documentación EEST2';
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
            <h1><i class="fas fa-user-check"></i> Sistema de Autenticación</h1>
            <p>Guía completa sobre autenticación y seguridad de acceso en el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-info-circle"></i> Introducción</h2>
            
            <div class="info-box">
                <p><i class="fas fa-shield-alt"></i> <strong>Propósito:</strong> El sistema de autenticación garantiza que solo usuarios autorizados puedan acceder a la información y funcionalidades del sistema.</p>
                <p><i class="fas fa-lock"></i> <strong>Seguridad:</strong> Implementa múltiples capas de protección para mantener la integridad y confidencialidad de los datos.</p>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Componentes del Sistema</h4>
                
                <div class="step-box">
                    <p><strong>El sistema de autenticación incluye:</strong></p>
                    <ul>
                        <li><span class="auth-method auth-password">Contraseña</span> Autenticación básica con usuario (DNI o email) y contraseña</li>
                        <li><span class="auth-method auth-mfa">Google OAuth</span> Inicio de sesión único (SSO) para docentes registrados</li>
                        <li><span class="auth-method auth-session">Sesión</span> Gestión segura de sesiones y middleware de integridad</li>
                        <li><span class="auth-method auth-biometric">reCAPTCHA v2</span> Protección automatizada contra bots y ataques de fuerza bruta</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-sign-in-alt"></i> Procesos de Inicio de Sesión</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-lock"></i> Autenticación Estándar</h4>
                
                <div class="step-box">
                    <p><strong>Para iniciar sesión de forma básica:</strong></p>
                    <ol>
                        <li>Accede a la URL de login del sistema (<code>public/login.php</code>)</li>
                        <li>Ingresa tu <strong>nombre de usuario</strong> (DNI para la mayoría de los roles)</li>
                        <li>Ingresa tu <strong>contraseña</strong></li>
                        <li>Resuelve el desafío de seguridad **Google reCAPTCHA v2**</li>
                        <li>Haz clic en <strong>"Acceder al sistema"</strong></li>
                    </ol>
                </div>
                
                <h4>Validaciones de seguridad:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ Verificación de credenciales con hashing seguro</li>
                        <li>☐ Validación de formato de datos de entrada</li>
                        <li>☐ Resolución del captcha de reCAPTCHA v2</li>
                        <li>☐ Control de intentos fallidos con bloqueo temporal</li>
                        <li>☐ Verificación de cuenta activa en el sistema</li>
                        <li>☐ Control de caducidad y cambio de contraseña obligatoria</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-google"></i> Continuar con Google (Portal Docentes)</h4>
                
                <div class="auth-card">
                    <h4><i class="fas fa-shield-alt"></i> Acceso SSO con Google OAuth 2.0</h4>
                    <p><strong>Requisitos de acceso para docentes:</strong></p>
                    <ul>
                        <li>El docente debe estar registrado previamente en la base de datos por secretaría.</li>
                        <li>El email del docente cargado en el sistema debe coincidir exactamente con su cuenta de Google.</li>
                        <li>La cuenta de Google seleccionada debe tener su correo electrónico verificado.</li>
                    </ul>
                </div>
                
                <h4>Para iniciar sesión con Google:</h4>
                <div class="step-box">
                    <ol>
                        <li>Ve a la pantalla de login</li>
                        <li>Haz clic en el botón <strong>"Continuar con Google"</strong></li>
                        <li>Si no has iniciado sesión en Google, ingresa tus credenciales en la ventana de Google</li>
                        <li>Selecciona tu cuenta cargada en el sistema</li>
                        <li>El sistema comprobará el correo, validará el DNI del docente e iniciará tu sesión de forma directa.</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-user-shield"></i> Gestión de Contraseñas</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Políticas de Contraseñas</h4>
                
                <div class="step-box">
                    <p><strong>Requisitos de contraseña:</strong></p>
                    <ul>
                        <li><span class="security-level level-high">Alto</span> Mínimo 8 caracteres (configurable mediante `seguridad.password_min_longitud`)</li>
                        <li><span class="security-level level-high">Alto</span> Hashing mediante Argon2id o bcrypt según algoritmos criptográficos del servidor</li>
                        <li><span class="security-level level-high">Alto</span> Forzar cambio en primer inicio de sesión o si es marcado por el administrador</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-edit"></i> Cambio de Contraseña</h4>
                
                <div class="step-box">
                    <p><strong>Para cambiar tu contraseña desde tu perfil:</strong></p>
                    <ol>
                        <li>Inicia sesión en el sistema</li>
                        <li>Ve a <strong>"Configuración de Perfil"</strong> o haz clic en tu nombre en la barra superior</li>
                        <li>Selecciona <strong>"Cambiar Contraseña"</strong></li>
                        <li>Ingresa tu contraseña actual y la nueva contraseña</li>
                        <li>Confirma los cambios haciendo clic en <strong>"Actualizar"</strong></li>
                    </ol>
                </div>
                
                <h4>Cambio de contraseña obligatorio:</h4>
                <div class="warning-box">
                    <p><i class="fas fa-exclamation-triangle"></i> Al crear una cuenta o si el administrador resetea tu contraseña con la opción "Forzar cambio de contraseña", serás redirigido de manera automática al formulario <code>force_password_change.php</code> antes de poder operar en el sistema.</p>
                </div>
            </div>
            
            <h2><i class="fas fa-clock"></i> Gestión de Sesiones</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-stopwatch"></i> Seguridad de Sesiones</h4>
                
                <div class="step-box">
                    <p><strong>Parámetros de sesión y Middleware:</strong></p>
                    <ul>
                        <li><span class="security-level level-medium">Medio</span> Duración predeterminada: 2 horas de inactividad</li>
                        <li><span class="security-level level-high">Alto</span> Middleware de Seguridad de Sesión (`SessionSecurityMiddleware`)</li>
                        <li><span class="security-level level-high">Alto</span> Validación de IP y User Agent del navegador en cada petición</li>
                        <li><span class="security-level level-medium">Medio</span> Regeneración automática del ID de sesión al loguearse para evitar Session Fixation</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-sign-out-alt"></i> Cierre de Sesión</h4>
                
                <div class="step-box">
                    <p><strong>Para cerrar sesión de manera segura:</strong></p>
                    <ul>
                        <li>Haz clic en tu nombre de usuario en la esquina superior del panel</li>
                        <li>Selecciona <strong>"Cerrar Sesión"</strong></li>
                        <li>El sistema invalidará la sesión en el servidor y te redirigirá a <code>login.php</code></li>
                    </ul>
                </div>
                
                <h4>Cierre de sesión automático:</h4>
                <div class="warning-box">
                    <ul>
                        <li>Después del tiempo límite de inactividad de la sesión</li>
                        <li>Si el Middleware detecta cambios sospechosos en el agente de usuario o la dirección IP de origen</li>
                        <li>Al detectar que la integridad de la sesión está comprometida</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-ban"></i> Protección contra Ataques</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-shield-alt"></i> Protección contra Fuerza Bruta</h4>
                
                <div class="step-box">
                    <p><strong>Medidas implementadas:</strong></p>
                    <ul>
                        <li><span class="security-level level-high">Alto</span> Bloqueo temporal tras alcanzar el límite de intentos fallidos</li>
                        <li><span class="security-level level-high">Alto</span> Límite configurable por base de datos (clave `seguridad.max_intentos_login`, por defecto 5 intentos)</li>
                        <li><span class="security-level level-high">Alto</span> Tiempo de bloqueo configurable (clave `seguridad.tiempo_bloqueo`, por defecto 30 minutos)</li>
                        <li><span class="security-level level-medium">Medio</span> Captcha obligatorio (Google reCAPTCHA v2) integrado en el formulario de login</li>
                    </ul>
                </div>
                
                <h4>Comportamiento del bloqueo:</h4>
                <div class="danger-box">
                    <p><i class="fas fa-ban"></i> Si un usuario falla credenciales repetidamente, el sistema registrará los fallos en la base de datos. Al superar el límite configurado, la cuenta quedará inhabilitada temporalmente mostrando los minutos restantes para poder reintentar. Un administrador puede desbloquear la cuenta de forma inmediata si es necesario restableciendo el contador.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-network-wired"></i> Protección CSRF</h4>
                
                <div class="step-box">
                    <p><strong>Medidas CSRF:</strong></p>
                    <ul>
                        <li><span class="security-level level-high">Alto</span> Generación y validación de Tokens CSRF en todos los formularios con acciones POST/PUT/DELETE</li>
                        <li><span class="security-level level-medium">Medio</span> SameSite configurado en cookies de sesión para mitigar cross-site request forgery</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-users-cog"></i> Roles y Permisos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-user-tag"></i> Tipos de Usuario</h4>
                
                <div class="step-box">
                    <p><strong>Roles del sistema:</strong></p>
                    <ul>
                        <li><span class="security-level level-high">Alto</span> <strong>Administrador:</strong> Acceso completo y control de configuraciones</li>
                        <li><span class="security-level level-medium">Medio</span> <strong>Secretario:</strong> Gestión administrativa de estudiantes y cursos</li>
                        <li><span class="security-level level-medium">Medio</span> <strong>Profesor:</strong> Acceso y carga de notas académicas para sus materias asignadas</li>
                        <li><span class="security-level level-basic">Básico</span> <strong>Preceptor:</strong> Gestión y toma de asistencia en cursos bajo su alcance asignado</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Autenticación</h4>
                
                <p><strong>¿Qué hago si olvido mi contraseña?</strong><br>
                Por motivos de seguridad, no se incluye recuperación automática por email. Contacta a un administrador para que resetee tu contraseña y te asigne una credencial temporal.</p>
                
                <p><strong>¿Por qué me da error el login con Google?</strong><br>
                Asegúrate de que estás ingresando con la misma cuenta de correo electrónico que proporcionaste a secretaría y que esta se encuentra debidamente registrada en tu ficha de docente.</p>
                
                <p><strong>¿Por qué mi cuenta se ha bloqueado?</strong><br>
                Porque se han ingresado credenciales incorrectas en varias ocasiones. Espera el tiempo de bloqueo indicado en pantalla o contacta al administrador para que reinicie tus intentos de acceso.</p>
                
                <p><strong>¿Por qué se cierra mi sesión automáticamente?</strong><br>
                Las sesiones caducan por inactividad o si se detecta un cambio en tu red o dispositivo (validación de IP/User Agent del Middleware) para prevenir el secuestro de sesión.</p>
            </div>
            
            <h2><i class="fas fa-lightbulb"></i> Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-star"></i> <strong>Recomendaciones de seguridad:</strong></p>
                <ul>
                    <li>Usa contraseñas seguras y cámbialas de forma regular</li>
                    <li>Utiliza el botón de Google OAuth si eres docente para mayor simplicidad y seguridad</li>
                    <li>No compartas tus credenciales de acceso bajo ningún concepto</li>
                    <li>Cierra la sesión de forma explícita al finalizar tu trabajo, especialmente en computadoras compartidas</li>
                    <li>Mantén actualizado tu navegador web</li>
                    <li>Reporta cualquier actividad o email sospechoso al administrador</li>
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
