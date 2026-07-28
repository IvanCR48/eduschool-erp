<?php
$pageTitle = 'Solución de Problemas - Documentación EEST2';
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
            <h1><i class="fas fa-bug"></i> Solución de Problemas</h1>
            <p>Guía para resolver los problemas más comunes del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-exclamation-circle"></i> Problemas de Acceso y Autenticación</h2>
            
            <div class="problem-item">
                <h4><i class="fas fa-lock"></i> No puedo iniciar sesión - Credenciales incorrectas</h4>
                <p><strong>Síntomas:</strong> El sistema muestra "Usuario o contraseña incorrectos"</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica que estés escribiendo correctamente tu usuario y contraseña</li>
                        <li>Asegúrate de que no tengas activado MAYÚS</li>
                        <li>Intenta copiar y pegar la contraseña si la tienes guardada</li>
                        <li>Contacta al administrador para restablecer tu contraseña</li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-ban"></i> Cuenta bloqueada después de múltiples intentos</h4>
                <p><strong>Síntomas:</strong> Mensaje "Tu cuenta ha sido bloqueada temporalmente"</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Espera 15 minutos - el bloqueo se levanta automáticamente</li>
                        <li>Si es urgente, contacta al administrador para desbloquear manualmente</li>
                        <li>Verifica que no haya otra persona intentando acceder a tu cuenta</li>
                    </ol>
                </div>
                
                <div class="code-block"><code># Administrador: Desbloquear usuario manualmente
UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE email = 'usuario@ejemplo.com';</code></div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fab fa-google"></i> Error en acceso con Google (Docentes)</h4>
                <p><strong>Síntomas:</strong> Se muestra un mensaje de error tras intentar usar "Continuar con Google"</p>
                
                <div class="solution-steps">
                    <p><strong>Causas comunes y soluciones:</strong></p>
                    <ul>
                        <li><strong>«El acceso con Google para docentes no está configurado»:</strong> El administrador del sistema debe copiar y configurar las claves de cliente de Google en <code>config/google_oauth.local.php</code>.</li>
                        <li><strong>«Este correo no está cargado como docente en el sistema»:</strong> El email que estás seleccionando en Google no coincide con el que secretaría ingresó en tu legajo. Contacta a secretaría para verificarlo.</li>
                        <li><strong>«El registro del docente no tiene DNI»:</strong> Tu ficha en la base de datos está incompleta; solicita a secretaría que ingrese tu DNI antes de utilizar el acceso de Google.</li>
                        <li><strong>«Su cuenta de Google no tiene el correo verificado»:</strong> Debes verificar tu correo en las opciones de seguridad de tu cuenta de Google.</li>
                    </ul>
                </div>
            </div>
            
            <h2><i class="fas fa-server"></i> Problemas del Sistema</h2>
            
            <div class="problem-item">
                <h4><i class="fas fa-exclamation-triangle"></i> Error 500 - Internal Server Error</h4>
                <p><strong>Síntomas:</strong> Página en blanco o mensaje "Error 500"</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones para Administradores:</strong></p>
                    <ol>
                        <li>Revisar logs de error PHP:
                            <div class="code-block"><code>tail -f logs/php_errors.log</code></div>
                        </li>
                        <li>Verificar permisos de archivos:
                            <div class="code-block"><code># Establecer permisos correctos
chmod -R 755 .
chmod -R 777 logs/ backups/</code></div>
                        </li>
                        <li>Verificar configuración de .env</li>
                        <li>Comprobar que la base de datos esté accesible</li>
                        <li>Revisar logs de Apache/Nginx</li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-times-circle"></i> Error 403 - Acceso Prohibido</h4>
                <p><strong>Síntomas:</strong> Mensaje "No tienes permiso para acceder a este recurso"</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica que tu usuario tenga los permisos necesarios</li>
                        <li>Administrador: Revisar permisos del rol del usuario</li>
                        <li>Verificar archivo .htaccess no esté bloqueando acceso</li>
                        <li>Comprobar permisos de carpetas en el servidor</li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-database"></i> Error de conexión a base de datos</h4>
                <p><strong>Síntomas:</strong> "Connection failed" o "Unknown database"</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verificar que MySQL esté ejecutándose:
                            <div class="code-block"><code># Windows (XAMPP)
Abrir panel de control XAMPP → Verificar MySQL

# Linux
sudo systemctl status mysql</code></div>
                        </li>
                        <li>Verificar credenciales en .env:
                            <div class="code-block"><code>DB_HOST=localhost
DB_PORT=3306
DB_NAME=sistema_admin_eest2
DB_USER=root
DB_PASS=tu_password</code></div>
                        </li>
                        <li>Verificar que la base de datos existe:
                            <div class="code-block"><code>mysql -u root -p -e "SHOW DATABASES;"</code></div>
                        </li>
                        <li>Restaurar base de datos si es necesario</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-tachometer-alt"></i> Problemas de Rendimiento</h2>
            
            <div class="problem-item">
                <h4><i class="fas fa-hourglass-half"></i> Sistema muy lento</h4>
                <p><strong>Síntomas:</strong> Las páginas tardan mucho en cargar</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Limpiar caché del navegador (Ctrl + Shift + Del)</li>
                        <li>Verificar conexión a internet</li>
                        <li>Administrador: Optimizar base de datos:
                            <div class="code-block"><code>mysql -u root -p sistema_admin_eest2 -e "OPTIMIZE TABLE estudiantes, profesores, notas;"</code></div>
                        </li>
                        <li>Administrador: Revisar uso de recursos del servidor:
                            <div class="code-block"><code>top
htop
df -h</code></div>
                        </li>
                        <li>Limpiar logs antiguos</li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-clock"></i> Timeout / Sesión expirada</h4>
                <p><strong>Síntomas:</strong> "Tu sesión ha expirado" después de un tiempo</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Vuelve a iniciar sesión</li>
                        <li>Guarda tu trabajo frecuentemente</li>
                        <li>Administrador: Ajustar tiempo de sesión en .env:
                            <div class="code-block"><code>SESSION_LIFETIME=120  # minutos</code></div>
                        </li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-file-alt"></i> Problemas con Datos y Archivos</h2>
            
            <div class="problem-item">
                <h4><i class="fas fa-save"></i> Los datos no se guardan</h4>
                <p><strong>Síntomas:</strong> Al hacer clic en "Guardar" no pasa nada o aparece error</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica que todos los campos obligatorios (marcados con *) estén completos</li>
                        <li>Revisa que los datos tengan el formato correcto (email, DNI, fechas)</li>
                        <li>Verifica tu conexión a internet</li>
                        <li>Intenta actualizar la página (F5) e intenta nuevamente</li>
                        <li>Administrador: Revisar logs de errores</li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-upload"></i> No puedo subir archivos</h4>
                <p><strong>Síntomas:</strong> Error al intentar subir documentos o imágenes</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica el tamaño del archivo (máximo 50MB)</li>
                        <li>Verifica el formato del archivo (PDF, JPG, PNG, DOC, DOCX permitidos)</li>
                        <li>Elimina caracteres especiales del nombre del archivo</li>
                        <li>Administrador: Verificar permisos de escritura:
                            <div class="code-block"><code>chmod 777 uploads/
chmod 777 public/uploads/</code></div>
                        </li>
                        <li>Administrador: Verificar configuración PHP:
                            <div class="code-block"><code>upload_max_filesize = 50M
post_max_size = 50M</code></div>
                        </li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-search"></i> No encuentro un estudiante/profesor</h4>
                <p><strong>Síntomas:</strong> El buscador no devuelve resultados</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica la ortografía del nombre</li>
                        <li>Busca por DNI si es posible</li>
                        <li>Quita filtros de curso o estado</li>
                        <li>Verifica que el usuario esté activo (no dado de baja)</li>
                        <li>Administrador: Verificar en base de datos directamente</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-print"></i> Problemas de Impresión y Exportación</h2>
            
            <div class="problem-item">
                <h4><i class="fas fa-file-pdf"></i> Error al generar PDF/Boletín</h4>
                <p><strong>Síntomas:</strong> Página en blanco o error al intentar imprimir</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica que el estudiante tenga notas cargadas</li>
                        <li>Intenta con otro navegador (Chrome recomendado)</li>
                        <li>Desactiva bloqueadores de pop-ups</li>
                        <li>Administrador: Verificar librería PDF instalada:
                            <div class="code-block"><code>composer show | grep pdf</code></div>
                        </li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-file-excel"></i> Error al exportar a Excel</h4>
                <p><strong>Síntomas:</strong> Descarga vacía o archivo corrupto</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Verifica que haya datos para exportar</li>
                        <li>Intenta reducir el rango de fechas o cantidad de registros</li>
                        <li>Descarga como CSV si Excel falla</li>
                        <li>Administrador: Verificar límites de memoria PHP</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-paint-brush"></i> Problemas de Visualización</h2>
            
            <div class="problem-item">
                <h4><i class="fas fa-palette"></i> La página se ve sin estilos (sin CSS)</h4>
                <p><strong>Síntomas:</strong> Texto sin formato, sin colores ni diseño</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Actualiza la página con Ctrl + F5 (recarga forzada)</li>
                        <li>Limpia la caché del navegador</li>
                        <li>Verifica tu conexión a internet</li>
                        <li>Administrador: Verificar rutas de archivos CSS en el código</li>
                    </ol>
                </div>
            </div>
            
            <div class="problem-item">
                <h4><i class="fas fa-mobile-alt"></i> Problemas en dispositivo móvil</h4>
                <p><strong>Síntomas:</strong> Diseño de asistencia o dashboard no adaptado o sin pestañas</p>
                
                <div class="solution-steps">
                    <p><strong>Soluciones:</strong></p>
                    <ol>
                        <li>Los módulos de asistencia virtual y dashboard cuentan con una interfaz premium (App-like UX) con pestañas dedicadas y paneles Bottom Sheet.</li>
                        <li>Si el diseño no se adapta o no se muestran las pestañas en móvil, recarga la página limpiando la caché (Ctrl + F5 o limpiando la caché en la configuración de tu navegador móvil).</li>
                        <li>Asegúrate de utilizar navegadores móviles actualizados (Google Chrome o Safari recomendados).</li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-wrench"></i> Herramientas de Diagnóstico</h2>
            
            <h3>Verificación Rápida del Sistema</h3>
            
            <div class="code-block"><code># Ejecutar script de diagnóstico
php deployment/scripts/system-check.php

# Verificar servicios
systemctl status apache2
systemctl status mysql

# Ver logs recientes
tail -50 logs/error.log
tail -50 logs/system.log</code></div>
            
            <h3>Información del Sistema</h3>
            
            <div class="code-block"><code># Versión de PHP
php -v

# Módulos de PHP cargados
php -m

# Versión de MySQL
mysql --version

# Espacio en disco
df -h</code></div>
            
            <h2><i class="fas fa-phone-alt"></i> Cuándo Contactar Soporte</h2>
            
            <div class="warning-box">
                <p><strong>Contacta al soporte técnico si:</strong></p>
                <ul>
                    <li>El problema persiste después de intentar las soluciones</li>
                    <li>Ves errores críticos del sistema</li>
                    <li>Se perdieron datos importantes</li>
                    <li>El sistema está completamente inaccesible</li>
                    <li>Necesitas recuperar tu contraseña</li>
                </ul>
                
                <p><strong>Información a proporcionar:</strong></p>
                <ul>
                    <li>Descripción detallada del problema</li>
                    <li>Pasos para reproducir el error</li>
                    <li>Captura de pantalla del error</li>
                    <li>Navegador y versión que estás usando</li>
                    <li>Hora aproximada en que ocurrió</li>
                </ul>
            </div>
            
            <h3>Contactos</h3>
            <ul>
                <li><strong>Email:</strong> soporte@eest2.edu.ar</li>
                <li><strong>Teléfono:</strong> [Número de contacto]</li>
                <li><strong>Horario:</strong> Lunes a Viernes, 8:00 - 16:00</li>
            </ul>
            
            <h2><i class="fas fa-book-open"></i> Recursos Adicionales</h2>
            
            <ul>
                <li><a href="faq.php">Preguntas Frecuentes</a></li>
                <li><a href="guia_usuario.php">Guía de Usuario</a></li>
                <li><a href="mantenimiento.php">Guía de Mantenimiento</a></li>
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
