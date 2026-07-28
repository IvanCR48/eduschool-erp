<?php
/**
 * Arquitectura del Sistema - Sistema Admin EEST2
 * 
 * Documentación completa sobre la arquitectura y diseño del sistema
 */

$pageTitle = 'Arquitectura del Sistema - E.E.S.T N°2';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/SistemaAdmin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/SistemaAdmin/css/docs.css">
</head>
<body>
    <div class="doc-container">
        <div class="doc-header">
            <h1><i class="fas fa-sitemap"></i> Arquitectura del Sistema</h1>
            <p>Sistema Administrativo E.E.S.T N°2 "Educación y Trabajo"</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-building"></i> Visión General de la Arquitectura</h2>
            <p>El Sistema EEST2 está diseñado con una arquitectura modular, escalable y mantenible que sigue los principios de desarrollo de software moderno. La arquitectura está basada en el patrón Model-View-Controller (MVC) con elementos adicionales de servicios y utilidades especializadas.</p>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Principios de Diseño</h4>
                <p>La arquitectura del sistema se basa en principios de separación de responsabilidades, reutilización de código, seguridad por diseño y escalabilidad horizontal.</p>
            </div>

            <h2><i class="fas fa-layer-group"></i> Arquitectura por Capas</h2>
            
            <div class="diagram-container">
                <h3>Diagrama de Arquitectura General</h3>
                <div style="margin: 2rem 0;">
                    <div class="diagram-box">Presentación<br/>(View Layer)</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Controladores<br/>(Controller Layer)</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Servicios<br/>(Service Layer)</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Modelos<br/>(Model Layer)</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Base de Datos<br/>(Data Layer)</div>
                </div>
            </div>

            <div class="architecture-grid">
                <div class="architecture-card">
                    <h4><i class="fas fa-eye"></i> Capa de Presentación</h4>
                    <p><strong>Responsabilidades:</strong></p>
                    <ul>
                        <li>Interfaz de usuario (HTML/CSS/JS)</li>
                        <li>Formularios y validación del lado cliente</li>
                        <li>Interactividad y experiencia de usuario</li>
                        <li>Responsive design</li>
                        <li>Accesibilidad web</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-cogs"></i> Capa de Controladores</h4>
                    <p><strong>Responsabilidades:</strong></p>
                    <ul>
                        <li>Manejo de peticiones HTTP</li>
                        <li>Validación de entrada</li>
                        <li>Coordinación entre capas</li>
                        <li>Manejo de sesiones</li>
                        <li>Respuestas HTTP</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-tools"></i> Capa de Servicios</h4>
                    <p><strong>Responsabilidades:</strong></p>
                    <ul>
                        <li>Lógica de negocio</li>
                        <li>Procesamiento de datos</li>
                        <li>Integración con APIs externas</li>
                        <li>Validaciones complejas</li>
                        <li>Transacciones de negocio</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-database"></i> Capa de Modelos</h4>
                    <p><strong>Responsabilidades:</strong></p>
                    <ul>
                        <li>Representación de entidades</li>
                        <li>Acceso a datos</li>
                        <li>Relaciones entre entidades</li>
                        <li>Validaciones de datos</li>
                        <li>Transformaciones de datos</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-folder-tree"></i> Estructura de Directorios</h2>
            
            <h3>Organización del Proyecto</h3>
            <div class="diagram-container">
                <div style="text-align: left; font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 1.5rem; border-radius: 10px;">
                    SistemaAdmin/
├── admin/                    # Panel administrativo
│   ├── admin_tools.php      # Herramientas de administración
│   └── logs/                # Logs administrativos
├── config/                  # Configuraciones del sistema
│   ├── database.php         # Configuración de BD
│   └── security.php         # Configuración de seguridad
├── css/                     # Hojas de estilo
├── database/                # Scripts de base de datos
│   └── sistema_admin_eest2.sql
├── docs/                    # Documentación
│   ├── documentacion_completa/
│   ├── security/
│   └── deployment/
├── img/                     # Imágenes y recursos
├── public/                  # Archivos públicos
│   ├── login.php           # Página de login
│   ├── errors/             # Páginas de error
│   └── logs/               # Logs públicos
├── src/                     # Código fuente principal
│   ├── EnvLoader.php       # Cargador de variables de entorno
│   └── services/           # Servicios del sistema
├── index.php               # Punto de entrada principal
├── documentacion.php       # Hub de documentación
├── .htaccess              # Configuración Apache
├── .env                   # Variables de entorno
└── composer.json          # Dependencias PHP
                </div>
            </div>

            <h2><i class="fas fa-puzzle-piece"></i> Patrones de Diseño Implementados</h2>
            
            <div class="architecture-grid">
                <div class="architecture-card">
                    <h4><i class="fas fa-cube"></i> Singleton Pattern</h4>
                    <p><strong>Uso:</strong> Gestión de conexiones a base de datos</p>
                    <ul>
                        <li>Una sola instancia de conexión</li>
                        <li>Reutilización de recursos</li>
                        <li>Control centralizado</li>
                        <li>Thread-safe en PHP</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-database"></i> Repository Pattern</h4>
                    <p><strong>Uso:</strong> Abstracción del acceso a datos</p>
                    <ul>
                        <li>Separación de lógica de datos</li>
                        <li>Facilita testing</li>
                        <li>Intercambiabilidad de fuentes</li>
                        <li>Interfaces claras</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-cogs"></i> Service Layer Pattern</h4>
                    <p><strong>Uso:</strong> Lógica de negocio compleja</p>
                    <ul>
                        <li>Encapsulación de reglas de negocio</li>
                        <li>Reutilización de servicios</li>
                        <li>Transacciones complejas</li>
                        <li>Integración con APIs</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-shield-alt"></i> Strategy Pattern</h4>
                    <p><strong>Uso:</strong> Diferentes algoritmos de validación</p>
                    <ul>
                        <li>Validaciones flexibles</li>
                        <li>Algoritmos intercambiables</li>
                        <li>Extensibilidad</li>
                        <li>Mantenibilidad</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-database"></i> Arquitectura de Base de Datos</h2>
            
            <h3>Diseño Relacional</h3>
            <p>La base de datos está diseñada siguiendo principios de normalización y optimización para el dominio educativo:</p>
            
            <div class="diagram-container">
                <h4>Entidades Principales</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
                    <div class="diagram-box">usuarios</div>
                    <div class="diagram-box">roles</div>
                    <div class="diagram-box">estudiantes</div>
                    <div class="diagram-box">profesores</div>
                    <div class="diagram-box">cursos</div>
                    <div class="diagram-box">materias</div>
                    <div class="diagram-box">calificaciones</div>
                    <div class="diagram-box">asistencias</div>
                </div>
            </div>

            <h3>Relaciones Clave</h3>
            <ul>
                <li><strong>Usuarios ↔ Roles:</strong> Relación muchos a muchos para permisos</li>
                <li><strong>Estudiantes ↔ Cursos:</strong> Relación muchos a muchos para inscripciones</li>
                <li><strong>Profesores ↔ Materias:</strong> Relación muchos a muchos para asignaciones</li>
                <li><strong>Estudiantes ↔ Calificaciones:</strong> Relación uno a muchos</li>
                <li><strong>Estudiantes ↔ Asistencias:</strong> Relación uno a muchos</li>
            </ul>

            <h2><i class="fas fa-shield-alt"></i> Arquitectura de Seguridad</h2>
            
            <div class="architecture-grid">
                <div class="architecture-card">
                    <h4><i class="fas fa-user-lock"></i> Autenticación</h4>
                    <p><strong>Componentes:</strong></p>
                    <ul>
                        <li>Login con credenciales</li>
                        <li>Autenticación de dos factores</li>
                        <li>Recuperación de contraseñas</li>
                        <li>Gestión de sesiones</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-key"></i> Autorización</h4>
                    <p><strong>Componentes:</strong></p>
                    <ul>
                        <li>Control de acceso basado en roles</li>
                        <li>Permisos granulares</li>
                        <li>Middleware de autorización</li>
                        <li>Auditoría de accesos</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-shield-virus"></i> Protección</h4>
                    <p><strong>Componentes:</strong></p>
                    <ul>
                        <li>Validación de entrada</li>
                        <li>Sanitización de datos</li>
                        <li>Protección CSRF</li>
                        <li>Headers de seguridad</li>
                    </ul>
                </div>
                
                <div class="architecture-card">
                    <h4><i class="fas fa-search"></i> Auditoría</h4>
                    <p><strong>Componentes:</strong></p>
                    <ul>
                        <li>Logs de acceso</li>
                        <li>Registro de acciones</li>
                        <li>Monitoreo en tiempo real</li>
                        <li>Alertas de seguridad</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-rocket"></i> Escalabilidad y Rendimiento</h2>
            
            <h3>Estrategias de Escalabilidad</h3>
            <ul>
                <li><strong>Escalabilidad Vertical:</strong> Mejora de hardware del servidor</li>
                <li><strong>Escalabilidad Horizontal:</strong> Distribución de carga</li>
                <li><strong>Caching:</strong> Redis/Memcached para sesiones y datos frecuentes</li>
                <li><strong>CDN:</strong> Distribución de contenido estático</li>
                <li><strong>Load Balancing:</strong> Distribución de peticiones</li>
            </ul>

            <h3>Optimizaciones de Rendimiento</h3>
            <ul>
                <li><strong>OPcache:</strong> Cache de código PHP compilado</li>
                <li><strong>Database Indexing:</strong> Índices optimizados para consultas</li>
                <li><strong>Query Optimization:</strong> Consultas SQL eficientes</li>
                <li><strong>Asset Minification:</strong> Compresión de CSS/JS</li>
                <li><strong>Image Optimization:</strong> Optimización de imágenes</li>
            </ul>

            <h2><i class="fas fa-cloud"></i> Arquitectura de Despliegue</h2>
            
            <div class="diagram-container">
                <h3>Arquitectura de Producción</h3>
                <div style="margin: 2rem 0;">
                    <div class="diagram-box">Load Balancer</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Web Servers<br/>(Apache/Nginx)</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Application<br/>(PHP-FPM)</div>
                    <span class="diagram-arrow">↓</span>
                    <div class="diagram-box">Database<br/>(MySQL/MariaDB)</div>
                </div>
                <div style="margin-top: 1rem;">
                    <div class="diagram-box">Cache Layer<br/>(Redis)</div>
                    <div class="diagram-box">File Storage<br/>(NFS/S3)</div>
                </div>
            </div>

            <h3>Componentes de Infraestructura</h3>
            <ul>
                <li><strong>Servidor Web:</strong> Apache/Nginx con SSL/TLS</li>
                <li><strong>Servidor de Aplicación:</strong> PHP-FPM con OPcache</li>
                <li><strong>Base de Datos:</strong> MySQL/MariaDB con replicación</li>
                <li><strong>Cache:</strong> Redis para sesiones y datos temporales</li>
                <li><strong>Almacenamiento:</strong> Sistema de archivos distribuido</li>
                <li><strong>Monitoreo:</strong> Herramientas de monitoreo y alertas</li>
            </ul>

            <div class="success-box">
                <h4><i class="fas fa-check-circle"></i> Beneficios de la Arquitectura</h4>
                <ul>
                    <li><strong>Mantenibilidad:</strong> Código organizado y modular</li>
                    <li><strong>Escalabilidad:</strong> Capacidad de crecimiento</li>
                    <li><strong>Seguridad:</strong> Múltiples capas de protección</li>
                    <li><strong>Rendimiento:</strong> Optimizado para alta concurrencia</li>
                    <li><strong>Flexibilidad:</strong> Fácil extensión y modificación</li>
                </ul>
            </div>

            <h2><i class="fas fa-tools"></i> Herramientas de Desarrollo</h2>
            
            <h3>Stack Tecnológico</h3>
            <ul>
                <li><strong>Backend:</strong> PHP 8.0+, MySQL/MariaDB</li>
                <li><strong>Frontend:</strong> HTML5, CSS3, JavaScript ES6+</li>
                <li><strong>Servidor Web:</strong> Apache/Nginx</li>
                <li><strong>Control de Versiones:</strong> Git</li>
                <li><strong>Gestor de Dependencias:</strong> Composer</li>
                <li><strong>Testing:</strong> PHPUnit</li>
            </ul>

            <h3>Herramientas de Desarrollo</h3>
            <ul>
                <li><strong>IDE:</strong> VS Code, PhpStorm</li>
                <li><strong>Debugging:</strong> Xdebug</li>
                <li><strong>Code Quality:</strong> PHPStan, PHP_CodeSniffer</li>
                <li><strong>Documentación:</strong> PHPDoc</li>
                <li><strong>CI/CD:</strong> GitHub Actions, Jenkins</li>
            </ul>

            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Documentación Técnica</h4>
                <p>Para más detalles técnicos sobre la implementación, consulta la <a href="desarrollo_avanzado.php" style="color: #1e40af; font-weight: 600;">Guía de Desarrollo Avanzado</a> y la <a href="api.php" style="color: #1e40af; font-weight: 600;">Documentación API</a>.</p>
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
