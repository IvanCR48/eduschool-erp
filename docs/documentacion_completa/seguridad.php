<?php
$pageTitle = 'Seguridad del Sistema - Documentación EEST2';
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
            <h1><i class="fas fa-shield-alt"></i> Seguridad del Sistema</h1>
            <p>Protecciones y medidas de seguridad implementadas en el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-lock"></i> Resumen de Seguridad</h2>
            
            <p>El Sistema Administrativo EEST2 ha sido desarrollado con un enfoque riguroso en la seguridad, implementando múltiples capas de protección para salvaguardar la información sensible de estudiantes, profesores y personal administrativo.</p>
            
            <div class="danger-box">
                <strong><i class="fas fa-exclamation-triangle"></i> IMPORTANTE:</strong> La seguridad es responsabilidad de todos. Reporta cualquier actividad sospechosa inmediatamente.
            </div>
            
            <h3>Estado de Seguridad</h3>
            <div style="text-align: center; margin: 2rem 0;">
                <span class="security-badge badge-implemented"><i class="fas fa-check-circle"></i> Implementado</span>
                <span class="security-badge badge-recommended"><i class="fas fa-info-circle"></i> Recomendado</span>
                <span class="security-badge badge-critical"><i class="fas fa-shield-alt"></i> Crítico</span>
            </div>
            
            <h2><i class="fas fa-user-lock"></i> Autenticación y Acceso</h2>
            
            <div class="security-feature">
                <h4><i class="fas fa-key"></i> Autenticación Segura y Google OAuth</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Google OAuth 2.0:</strong> Integración de Single Sign-On para docentes.</li>
                    <li><strong>reCAPTCHA v2:</strong> Desafío automatizado en login estándar para prevenir bots.</li>
                    <li><strong>Middleware de Integridad:</strong> Control de consistencia en cookies, IP y User Agent.</li>
                    <li><strong>Password Policy:</strong> Requisitos mínimos de longitud y forzado de cambio.</li>
                </ul>
                <p><strong>Cómo funciona el acceso seguro:</strong></p>
                <ol>
                    <li>El docente utiliza su cuenta institucional o personal registrada mediante "Continuar con Google".</li>
                    <li>Otros usuarios ingresan con DNI y contraseña resolviendo el reCAPTCHA v2.</li>
                    <li>El sistema regenera el identificador de sesión y valida los encabezados de seguridad en cada petición subsiguiente.</li>
                </ol>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-shield-alt"></i> Políticas de Contraseñas</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Longitud mínima:</strong> 8 caracteres (configurable)</li>
                    <li><strong>Complejidad requerida:</strong>
                        <ul>
                            <li>Al menos 1 mayúscula</li>
                            <li>Al menos 1 minúscula</li>
                            <li>Al menos 1 número</li>
                            <li>Al menos 1 carácter especial (!@#$%^&*)</li>
                        </ul>
                    </li>
                    <li><strong>Forzado de Cambio:</strong> Redirección obligatoria al formulario de cambio si se requiere.</li>
                    <li><strong>Hashing:</strong> Argon2id o bcrypt con sal único generado criptográficamente.</li>
                </ul>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-ban"></i> Protección contra Fuerza Bruta y Lockout</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Intentos máximos:</strong> Configurable (por defecto 5 intentos fallidos antes de bloquear).</li>
                    <li><strong>Tiempo de bloqueo:</strong> Configurable (por defecto 30 minutos de bloqueo temporal).</li>
                    <li><strong>reCAPTCHA v2:</strong> Verificación obligatoria contra bots en el formulario de login.</li>
                    <li><strong>Desbloqueo manual:</strong> Los administradores pueden restablecer los intentos fallidos de cualquier cuenta desde el panel de control.</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-network-wired"></i> Seguridad de Red y Sesiones</h2>
            
            <div class="security-feature">
                <h4><i class="fas fa-clock"></i> Gestión de Sesiones</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Timeout de inactividad:</strong> Configurable (por defecto 2 horas)</li>
                    <li><strong>Regeneración de ID:</strong> Nuevo ID de sesión asignado al loguearse con éxito</li>
                    <li><strong>Middleware de Seguridad:</strong> Validaciones dinámicas de IP y User Agent contra secuestro</li>
                    <li><strong>HttpOnly cookies:</strong> Mitigación de robo de sesión mediante ataques XSS</li>
                    <li><strong>SameSite flag:</strong> Cookies de sesión configuradas para mitigar ataques CSRF</li>
                </ul>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-globe"></i> Protección HTTPS</h4>
                <p><span class="security-badge badge-recommended">Recomendado</span></p>
                <ul>
                    <li><strong>TLS 1.2+:</strong> Protocolo mínimo recomendado</li>
                    <li><strong>Certificado SSL:</strong> Let's Encrypt o comercial</li>
                    <li><strong>HSTS:</strong> Forzar HTTPS en todas las conexiones</li>
                    <li><strong>Redirección automática:</strong> HTTP → HTTPS</li>
                </ul>
                <div class="warning-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Producción:</strong> HTTPS es OBLIGATORIO en entornos de producción.
                </div>
            </div>
            
            <h2><i class="fas fa-bug"></i> Protección contra Vulnerabilidades</h2>
            
            <div class="security-feature">
                <h4><i class="fas fa-code"></i> Protección SQL Injection</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>PDO Prepared Statements:</strong> Todas las consultas usan parámetros preparados</li>
                    <li><strong>Validación de entrada:</strong> Sanitización en capa de aplicación</li>
                    <li><strong>ORM seguro:</strong> Abstracción de base de datos</li>
                </ul>
                <div class="code-block"><code>// Ejemplo de consulta segura
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
$stmt->execute(['email' => $email]);
// ✅ Protegido contra SQL Injection</code></div>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-spider"></i> Protección XSS (Cross-Site Scripting)</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Escapado de salida:</strong> htmlspecialchars() en todas las salidas</li>
                    <li><strong>Content Security Policy (CSP):</strong> Headers configurados</li>
                    <li><strong>Validación de entrada:</strong> Strip tags en campos de texto</li>
                    <li><strong>Sanitización HTML:</strong> HTML Purifier para contenido rico</li>
                </ul>
                <div class="code-block"><code>// Ejemplo de salida segura
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
// ✅ Protegido contra XSS</code></div>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-random"></i> Protección CSRF (Cross-Site Request Forgery)</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Tokens CSRF:</strong> Token único por formulario</li>
                    <li><strong>Validación en servidor:</strong> Verificación en cada request POST/PUT/DELETE</li>
                    <li><strong>Expiración de tokens:</strong> 1 hora de validez</li>
                    <li><strong>SameSite cookies:</strong> Protección adicional</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Auditoría y Logging</h2>
            
            <div class="security-feature">
                <h4><i class="fas fa-history"></i> Sistema de Auditoría</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <p>Todos los eventos importantes se registran:</p>
                <ul>
                    <li><strong>Autenticación:</strong> Logins exitosos, logins con Google OAuth y fallas de acceso</li>
                    <li><strong>Cambios críticos:</strong> Modificaciones en usuarios, permisos y configuración</li>
                    <li><strong>Acceso a datos sensibles:</strong> Consultas de información personal</li>
                    <li><strong>Operaciones administrativas:</strong> Backups, configuración</li>
                </ul>
                
                <p><strong>Información registrada:</strong></p>
                <ul>
                    <li>Timestamp exacto</li>
                    <li>Usuario que realizó la acción</li>
                    <li>IP y User-Agent</li>
                    <li>Tipo de operación</li>
                    <li>Datos antes y después (para modificaciones)</li>
                </ul>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-bell"></i> Alertas de Seguridad</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <p>Notificaciones automáticas ante:</p>
                <ul>
                    <li>Múltiples intentos fallidos de login</li>
                    <li>Acceso desde IP nueva o sospechosa</li>
                    <li>Cambios en permisos de administrador</li>
                    <li>Modificación de configuración crítica</li>
                    <li>Errores graves del sistema</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-database"></i> Protección de Datos</h2>
            
            <div class="security-feature">
                <h4><i class="fas fa-lock"></i> Cifrado de Datos</h4>
                <p><span class="security-badge badge-implemented">✓ Implementado</span></p>
                <ul>
                    <li><strong>Contraseñas:</strong> Hashing mediante Argon2id o bcrypt.</li>
                    <li><strong>Datos sensibles:</strong> AES-256 para información sensible.</li>
                    <li><strong>Cookies y Sesiones:</strong> Firmadas y cifradas para evitar manipulaciones.</li>
                    <li><strong>Backups:</strong> Respaldos de base de datos cifrados.</li>
                </ul>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-user-secret"></i> Privacidad y GDPR</h4>
                <p><span class="security-badge badge-recommended">Recomendado</span></p>
                <ul>
                    <li><strong>Minimización de datos:</strong> Solo se almacena lo necesario</li>
                    <li><strong>Derecho al olvido:</strong> Función de eliminación de datos</li>
                    <li><strong>Consentimiento informado:</strong> Política de privacidad clara</li>
                    <li><strong>Exportación de datos:</strong> Usuario puede descargar sus datos</li>
                    <li><strong>Retención limitada:</strong> Datos eliminados según políticas</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-server"></i> Seguridad del Servidor</h2>
            
            <div class="security-feature">
                <h4><i class="fas fa-cog"></i> Configuración del Servidor</h4>
                <p><span class="security-badge badge-critical">Crítico</span></p>
                <ul>
                    <li><strong>PHP:</strong>
                        <ul>
                            <li>display_errors = Off en producción</li>
                            <li>expose_php = Off</li>
                            <li>open_basedir configurado</li>
                            <li>disable_functions para funciones peligrosas</li>
                        </ul>
                    </li>
                    <li><strong>Apache/Nginx:</strong>
                        <ul>
                            <li>ServerTokens Prod</li>
                            <li>ServerSignature Off</li>
                            <li>Límites de request size</li>
                            <li>Timeouts configurados</li>
                        </ul>
                    </li>
                    <li><strong>MySQL:</strong>
                        <ul>
                            <li>Usuario específico con permisos limitados</li>
                            <li>Conexión local solamente</li>
                            <li>root deshabilitado para conexiones remotas</li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-folder"></i> Permisos de Archivos</h4>
                <p><span class="security-badge badge-critical">Crítico</span></p>
                <div class="code-block"><code># Permisos recomendados
Archivos PHP: 644 (rw-r--r--)
Directorios: 755 (rwxr-xr-x)
Logs: 666 (rw-rw-rw-)
Config sensible: 600 (rw-------)
Backups: 600 (rw-------)</code></div>
            </div>
            
            <h2><i class="fas fa-tools"></i> Mantenimiento de Seguridad</h2>
            
            <h3>Lista de Verificación Semanal</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Revisar logs de seguridad</li>
                    <li>☐ Verificar intentos de login fallidos</li>
                    <li>☐ Comprobar sesiones activas</li>
                    <li>☐ Revisar usuarios bloqueados</li>
                </ul>
            </div>
            
            <h3>Lista de Verificación Mensual</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Actualizar dependencias del sistema</li>
                    <li>☐ Revisar permisos de usuarios</li>
                    <li>☐ Auditar cambios en configuración</li>
                    <li>☐ Verificar backups funcionando correctamente</li>
                    <li>☐ Revisar certificado SSL (vigencia)</li>
                </ul>
            </div>
            
            <h3>Lista de Verificación Trimestral</h3>
            <div class="success-box checklist-box">
                <ul style="margin-bottom: 0;">
                    <li>☐ Auditoría de seguridad completa</li>
                    <li>☐ Actualización de políticas de seguridad</li>
                    <li>☐ Revisión de contraseñas de administradores</li>
                    <li>☐ Prueba de restauración de backups</li>
                    <li>☐ Capacitación en seguridad para usuarios</li>
                </ul>
            </div>
            
            <h2><i class="fas fa-exclamation-triangle"></i> Respuesta a Incidentes</h2>
            
            <div class="danger-box">
                <h3 style="margin-top: 0;"><i class="fas fa-fire"></i> En caso de Incidente de Seguridad</h3>
                <ol style="margin-bottom: 0;">
                    <li><strong>Contener:</strong> Aislar el sistema afectado inmediatamente</li>
                    <li><strong>Evaluar:</strong> Determinar el alcance del incidente</li>
                    <li><strong>Notificar:</strong> Informar a administradores y autoridades</li>
                    <li><strong>Documentar:</strong> Registrar todo el proceso</li>
                    <li><strong>Remediar:</strong> Corregir la vulnerabilidad</li>
                    <li><strong>Recuperar:</strong> Restaurar el servicio seguro</li>
                    <li><strong>Revisar:</strong> Análisis post-incidente</li>
                </ol>
            </div>
            
            <h3>Contactos de Emergencia</h3>
            <ul>
                <li><strong>Email:</strong> admin@eest2.edu.ar</li>
                <li><strong>Soporte Técnico:</strong> [Teléfono de emergencia]</li>
                <li><strong>Dirección:</strong> [Email dirección]</li>
            </ul>
            
            <h2><i class="fas fa-book"></i> Recursos Adicionales</h2>
            
            <ul>
                <li><a href="../security/SEGURIDAD_UNIFICADA.md" target="_blank">Documentación de Seguridad Completa</a></li>
                <li><a href="https://owasp.org/www-project-top-ten/" target="_blank">OWASP Top 10</a></li>
                <li><a href="https://www.cisa.gov/cybersecurity" target="_blank">CISA Cybersecurity</a></li>
            </ul>
            
            <div class="success-box">
                <strong><i class="fas fa-shield-alt"></i> Recuerda:</strong> La seguridad es un proceso continuo, no un destino. Mantente actualizado y vigilante.
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
