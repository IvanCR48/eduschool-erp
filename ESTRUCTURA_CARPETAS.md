# ?? Estructura de Carpetas - Sistema Administrativo E.E.S.T N°2

Este documento explica detalladamente cada carpeta del proyecto, sus subcarpetas, funcionalidades y el propósito de su existencia.

---

## ?? **Raíz del Proyecto** (`/`)

La raíz contiene los archivos principales del sistema y las páginas PHP que los usuarios acceden directamente desde el navegador.

### Archivos PHP Principales
- **`index.php`** - Dashboard principal del sistema (página de inicio después del login)
- **`students.php`** - Gestión y listado de estudiantes
- **`student_profile.php`** - Vista detallada de un estudiante individual
- **`teachers.php`** - Gestión y listado de profesores
- **`teacher_profile.php`** - Vista detallada de un profesor individual
- **`courses.php`** - Gestión de cursos académicos
- **`subjects.php`** - Gestión de materias/asignaturas
- **`materias_previas.php`** - Gestión de correlatividades entre materias
- **`grades.php`** - Carga y gestión de calificaciones
- **`schedules.php`** - Gestión de horarios de clases
- **`discipline.php`** - Gestión de llamados de atención a estudiantes
- **`especialidades.php`** - Gestión de especialidades técnicas
- **`staff.php`** - Gestión del equipo directivo y docente
- **`documentacion.php`** - Hub centralizado de documentación del sistema

### Archivos de Configuración
- **`.htaccess`** - Configuración de seguridad y reglas de Apache
- **`.gitignore`** - Archivos y carpetas excluidos del control de versiones
- **`.user.ini`** - Configuración específica de PHP para el directorio
- **`composer.json`** - Definición de dependencias PHP del proyecto
- **`composer.lock`** - Versiones exactas de dependencias instaladas
- **`composer.phar`** - Ejecutable de Composer para gestión de dependencias
- **`env.example`** - Plantilla de variables de entorno (copia a `.env` para usar)

### Scripts de Instalación
- **`install.bat`** - Script de instalación para Windows
- **`install.sh`** - Script de instalación para Linux/Mac

---

## ?? **src/** - Código Fuente PHP

Contiene toda la lógica de negocio del sistema siguiendo principios SOLID y arquitectura en capas (MVC mejorado).

### **Por qué existe:**
- Separación clara entre la presentación (páginas PHP en la raíz) y la lógica de negocio
- Facilita el mantenimiento, testing y escalabilidad
- Permite reutilización de código entre diferentes partes del sistema
- Sigue estándares PSR-4 para autoloading automático

### **src/controllers/** - Controladores
Controladores que manejan las peticiones HTTP y coordinan la lógica entre modelos, servicios y vistas.

**Archivos principales:**
- `LoginController.php` - Maneja autenticación y login
- `EstudianteController.php` - Operaciones CRUD de estudiantes
- `ProfesorController.php` - Operaciones CRUD de profesores
- `HorariosController.php` - Gestión de horarios
- `NotaController.php` - Gestión de calificaciones
- `LlamadoController.php` - Gestión de llamados de atención
- `DashboardController.php` - Datos del dashboard principal
- `DashboardAnalyticsController.php` - Analytics y estadísticas
- `AdminToolsController.php` - Herramientas administrativas
- `ReportesController.php` - Generación de reportes

**Funcionalidad:** Reciben datos de formularios, validan, llaman a servicios correspondientes y retornan respuestas.

### **src/models/** - Modelos de Dominio
Representan las entidades del negocio con sus propiedades, validaciones y reglas.

**Archivos:**
- `Estudiante.php` - Entidad estudiante (DNI, nombre, apellido, curso, etc.)
- `Profesor.php` - Entidad profesor (DNI, especialidad, materias, etc.)
- `Curso.php` - Entidad curso (año, división, turno, especialidad, etc.)
- `Materia.php` - Entidad materia (nombre, especialidad, carga horaria, etc.)
- `Nota.php` - Entidad nota (calificación, tipo de evaluación, fecha, etc.)
- `LlamadoAtencion.php` - Entidad llamado de atención (fecha, motivo, tipo, etc.)

**Funcionalidad:** Encapsulan la lógica de negocio de cada entidad, validaciones y relaciones.

### **src/services/** - Servicios de Aplicación
Contienen la lógica de negocio compleja y coordinan múltiples modelos/mappers.

**Servicios principales:**
- `ServicioEstudiantes.php` - Lógica de negocio para estudiantes
- `ServicioProfesores.php` - Lógica de negocio para profesores
- `ServicioNotas.php` - Lógica de negocio para calificaciones
- `ServicioLlamados.php` - Lógica de negocio para llamados
- `ServicioAutenticacion.php` - Manejo de autenticación y autorización
- `ServicioReportes.php` - Generación de reportes complejos
- `ServicioSeguridad.php` - Funciones de seguridad (CSRF, XSS, etc.)
- `UsuarioRepository.php` - Acceso a datos de usuarios

**Servicios auxiliares:**
- `PDFGeneratorService.php` - Generación de PDFs (boletines, reportes)
- `ExcelGeneratorService.php` - Exportación a Excel
- `QRCodeService.php` - Generación de códigos QR
- `FileUploadService.php` - Manejo de subida de archivos
- `ValidationService.php` - Validaciones reutilizables
- `CacheService.php` - Sistema de caché
- `SessionService.php` - Manejo de sesiones
- `SecurityLoggingService.php` - Logging de eventos de seguridad
- `BackupService.php` - Respaldos automáticos
- `MFAService.php` - Autenticación multifactor

**Por qué existe:** Centraliza la lógica compleja, facilita testing y permite reutilización.

### **src/mappers/** - Mappers de Persistencia
Convierten entre objetos del dominio (models) y datos de la base de datos.

**Funcionalidad:**
- `ProfesorMapper.php` - Mapea entre objetos `Profesor` y tabla `profesores`
- `EstudianteMapper.php` - Mapea entre objetos `Estudiante` y tabla `estudiantes`
- Y otros mappers para cada entidad...

**Por qué existe:** Separa las preocupaciones de persistencia del modelo de dominio, permitiendo cambiar el almacenamiento sin afectar la lógica de negocio.

### **src/DTOs/** - Data Transfer Objects
Objetos simples para transferir datos entre capas sin exponer los modelos completos.

**Por qué existe:** Proporciona una interfaz clara y controlada para transferir datos, evitando exponer detalles internos de los modelos.

### **src/interfaces/** - Interfaces/Contratos
Define contratos que deben cumplir los servicios y clases.

**Por qué existe:** Permite desacoplamiento, facilita testing con mocks y sigue el principio de inversión de dependencias.

### **src/contracts/** - Contratos de Base de Datos
Interfaces para abstraer el acceso a la base de datos.

**Funcionalidad:** Permite cambiar de MySQL a PostgreSQL u otro motor sin modificar el resto del código.

### **src/middleware/** - Middleware
Interceptores que procesan peticiones antes de llegar a los controladores.

**Funcionalidad:**
- Validación de autenticación
- Verificación de permisos (roles)
- Protección CSRF
- Logging de peticiones
- Rate limiting

**Por qué existe:** Permite aplicar lógica transversal (seguridad, logging) sin duplicar código en cada controlador.

### **src/exceptions/** - Excepciones Personalizadas
Excepciones específicas del dominio del negocio.

**Por qué existe:** Permite manejar errores de forma más específica y proporcionar mensajes claros al usuario.

### **src/adapters/** - Adaptadores
Adaptan interfaces externas (APIs, librerías) a las interfaces internas del sistema.

**Por qué existe:** Permite integrar librerías de terceros sin acoplar el código a sus APIs específicas.

### **src/services/advanced/** - Servicios Avanzados
Servicios especializados con funcionalidades más complejas.

**Por qué existe:** Organiza servicios más específicos para mantener la carpeta `services` más ordenada.

---

## ?? **config/** - Configuraciones del Sistema

Contiene todas las configuraciones necesarias para el funcionamiento del sistema.

### **Por qué existe:**
- Centraliza configuraciones en un solo lugar
- Facilita el despliegue en diferentes entornos (desarrollo, producción)
- Permite versionar configuraciones sin exponer datos sensibles

### **config/database.php**
Archivo principal de configuración de base de datos. Define:
- Host, puerto, nombre de BD
- Credenciales de conexión
- Clase `Database` que maneja todas las conexiones

### **config/production.php**
Configuraciones específicas para entorno de producción (optimizaciones, seguridad extra).

### **config/php/** - Configuración de PHP
- `php.ini` - Configuración principal de PHP
- `opcache.ini` - Configuración de OPcache (caché de PHP)
- `www.conf` - Configuración de PHP-FPM

**Por qué existe:** Permite personalizar PHP sin modificar la configuración global del servidor.

### **config/nginx/** - Configuración de Nginx
- `nginx.conf` - Configuración principal
- `default.conf` - Configuración del sitio virtual
- `proxy.conf` - Configuración de proxy reverso

**Por qué existe:** Permite desplegar el sistema con Nginx en lugar de Apache si es necesario.

### **config/mysql/** - Configuración de MySQL
- `my.cnf` - Configuración del servidor MySQL

**Por qué existe:** Permite optimizar MySQL según las necesidades del sistema.

---

## ?? **database/** - Scripts de Base de Datos

Contiene todos los scripts SQL relacionados con la base de datos.

### **Por qué existe:**
- Versiona el esquema de la base de datos
- Facilita instalación en nuevos servidores
- Permite migraciones y actualizaciones controladas

### **database/school_admin.sql**
Script principal que crea toda la estructura de la base de datos:
- Tablas: usuarios, estudiantes, profesores, cursos, materias, notas, etc.
- Índices para optimización
- Foreign keys para integridad referencial
- Datos iniciales (usuarios por defecto, roles, etc.)

### **database/migracion_profesor_curso_es_taller.sql**
Script de migración que agrega nuevas tablas y columnas:
- Tabla `profesor_curso` (relación muchos a muchos)
- Tabla `suplentes` y `suplencias`
- Columna `es_taller` en tabla `materias`

**Por qué existe:** Permite actualizar bases de datos existentes sin perder datos.

### **database/instalar_sistema_completo.bat**
Script automatizado para instalar el sistema completo en Windows.

### **database/.htaccess**
Protege los archivos SQL de acceso directo desde el navegador.

---

## ?? **includes/** - Archivos de Inclusión

Archivos PHP reutilizables que se incluyen en múltiples páginas.

### **Por qué existe:**
- DRY (Don't Repeat Yourself): evita duplicar código
- Facilita mantenimiento: cambios en un solo lugar se reflejan en todo el sistema

### **includes/header.php**
Encabezado común de todas las páginas:
- Navegación principal
- Menús según rol del usuario
- Conexión a base de datos
- Autenticación y verificación de permisos
- Inclusión de CSS y JS comunes

### **includes/footer.php**
Pie de página común:
- Scripts JavaScript finales
- Información de copyright
- Enlaces útiles

### **includes/csrf_functions.php**
Funciones para protección CSRF:
- Generación de tokens
- Validación de tokens
- Prevención de ataques Cross-Site Request Forgery

**Por qué existe:** Seguridad crítica que debe estar disponible en todas las páginas.

### **includes/character_encoding.php**
Configuración de codificación de caracteres UTF-8.

**Por qué existe:** Evita problemas de caracteres especiales (tildes, eñes) en diferentes sistemas.

---

## ?? **css/** - Estilos CSS

Contiene todas las hojas de estilo del sistema.

### **css/style.css**
Estilos principales del sistema:
- Diseño responsive (mobile-first)
- Tema claro/oscuro
- Variables CSS para colores y espaciado
- Estilos para componentes comunes (botones, formularios, tablas, cards)
- Media queries para diferentes tamaños de pantalla

**Por qué existe:** Centraliza todos los estilos para facilitar mantenimiento y asegurar consistencia visual.

---

## ?? **js/** - JavaScript del Cliente

Contiene scripts JavaScript ejecutados en el navegador.

### **js/admin_tools.js**
Funcionalidades JavaScript para herramientas administrativas:
- Validaciones en tiempo real
- Interacciones dinámicas
- AJAX para operaciones sin recargar página

### **js/responsive.js**
Funcionalidades para mejorar la experiencia en dispositivos móviles:
- Menús colapsables
- Ajustes de layout dinámicos

**Por qué existe:** Separa la lógica de presentación (JavaScript) del HTML, facilitando mantenimiento y testing.

---

## ?? **img/** - Imágenes y Recursos Gráficos

Almacena todas las imágenes estáticas del sistema.

### **img/logo-school.png**
Logo de la institución utilizado en header y documentos.

**Por qué existe:** Centraliza recursos gráficos, facilitando su gestión y optimización.

---

## ?? **admin/** - Panel Administrativo

Herramientas exclusivas para administradores del sistema.

### **Por qué existe:**
- Separa funcionalidades administrativas del sistema principal
- Restringe acceso mediante verificación de permisos
- Organiza herramientas avanzadas en un lugar dedicado

### **admin/admin_tools.php**
Panel principal de herramientas administrativas:
- Gestión de usuarios y permisos
- Configuración del sistema
- Monitoreo de logs
- Herramientas de mantenimiento

### **admin/security_dashboard.php**
Dashboard de seguridad:
- Intentos de login fallidos
- Eventos de seguridad registrados
- Estadísticas de acceso
- Alertas de seguridad

### **admin/mfa_config.php**
Configuración de autenticación multifactor (MFA).

### **admin/reportes_demo.php**
Demostración de reportes avanzados.

### **admin/logs/**
Carpeta para almacenar logs específicos del panel administrativo.

---

## ?? **api/** - API REST

Endpoints de API para integraciones externas y comunicación AJAX.

### **Por qué existe:**
- Permite integración con otros sistemas
- Facilita desarrollo de aplicaciones móviles
- Separa la lógica de API del código de presentación

### **api/admin_tools_api.php**
API para herramientas administrativas:
- Endpoints RESTful
- Autenticación mediante tokens
- Respuestas en formato JSON

**Funcionalidad:** Permite que aplicaciones externas o componentes AJAX accedan a funcionalidades del sistema de forma controlada.

---

## ?? **public/** - Archivos Públicos

Archivos accesibles directamente desde el navegador, fuera del control de autenticación.

### **Por qué existe:**
- Contiene archivos que deben ser accesibles públicamente
- Separa contenido público del privado
- Facilita configuración de servidor web

### **public/login.php**
Página de inicio de sesión (accesible sin autenticación).

### **public/logout.php**
Script de cierre de sesión.

### **public/health-check.php**
Endpoint para verificar el estado del sistema (usado por monitoreo).

### **public/errors/** - Páginas de Error Personalizadas
- `403.php` - Error de acceso denegado
- `404.php` - Página no encontrada
- `500.php` - Error interno del servidor

**Por qué existe:** Proporciona páginas de error amigables y consistentes con el diseño del sistema.

### **public/honeypot/** - Honeypot de Seguridad
Carpeta trampa para detectar intentos de acceso maliciosos.

**Por qué existe:** Técnica de seguridad que ayuda a identificar y bloquear ataques automatizados.

### **public/js/**
JavaScript específico para páginas públicas (diferente del de la carpeta `js/` principal).

---

## ?? **logs/** - Logs del Sistema

Registros de eventos, errores y auditoría del sistema.

### **Por qué existe:**
- Permite debugging y diagnóstico de problemas
- Cumple con requisitos de auditoría
- Facilita monitoreo de seguridad

### **logs/audit.log**
Registro de auditoría: todas las acciones importantes realizadas por usuarios.

### **logs/security.log**
Registro de eventos de seguridad: intentos de login, accesos denegados, etc.

**Importante:** Estos archivos contienen información sensible y deben estar protegidos (`.htaccess`).

---

## ?? **backups/** - Respaldos

Carpeta para almacenar respaldos de la base de datos.

### **Por qué existe:**
- Permite restaurar datos en caso de pérdida
- Facilita migraciones entre servidores
- Cumple con políticas de respaldo

**Nota:** Esta carpeta debe tener permisos restrictivos y estar excluida del control de versiones.

---

## ?? **vendor/** - Dependencias de Terceros

Contiene todas las librerías PHP instaladas mediante Composer.

### **Por qué existe:**
- Gestiona dependencias externas de forma organizada
- Facilita actualizaciones y mantenimiento
- Sigue estándar de la comunidad PHP (PSR)

### **Librerías principales incluidas:**
- `bacon/bacon-qr-code` - Generación de códigos QR
- `endroid/qr-code` - Otra librería de códigos QR
- `dasprid/enum` - Manejo de enumeraciones
- `psr/*` - Interfaces PSR estándar

**Importante:** Esta carpeta se genera automáticamente con `composer install` y no debe editarse manualmente.

---

## ?? **docs/** - Documentación

Documentación completa del sistema organizada por categorías.

### **Por qué existe:**
- Centraliza toda la documentación del proyecto
- Facilita onboarding de nuevos desarrolladores
- Mantiene registro de decisiones técnicas

### **docs/guides/** - Guías de Usuario
- `USER_GUIDE.md` - Guía para usuarios finales
- `ADMIN_GUIDE.md` - Guía para administradores
- `SISTEMA_COMPLETO.md` - Documentación completa
- `HERRAMIENTAS_ADMIN.md` - Herramientas administrativas

### **docs/security/** - Documentación de Seguridad
- `SEGURIDAD_INFORMATICA.md` - Conceptos de seguridad
- `PLAN_ENDURECIMIENTO_SEGURIDAD.md` - Plan de hardening
- `PROTECCIONES_IMPLEMENTADAS.md` - Protecciones activas
- `RESUMEN_SEGURIDAD.md` - Resumen ejecutivo
- `SECURITY_CHECKLIST.md` - Lista de verificación

### **docs/deployment/** - Guías de Despliegue
- `DEPLOYMENT_GUIDE.md` - Guía completa de despliegue
- `README_DEPLOYMENT.md` - Instrucciones rápidas

### **docs/api/** - Documentación de API
- `README.md` - Documentación de endpoints REST

### **docs/architecture/** - Arquitectura del Sistema
- `README.md` - Documentación de la arquitectura, patrones de diseño, etc.

### **docs/development/** - Desarrollo
- `README.md` - Guía para desarrolladores, estándares de código, etc.

### **docs/documentacion_completa/** - Documentación Completa
Contiene la base de conocimientos y guías del sistema en formato web (HTML/PHP) interactivo para su visualización desde el hub de documentación (`documentacion.php`).

**Archivos Principales:**
- **`README.md`** - Visión general de la carpeta de documentación interactiva.
- **`sistema_completo.php`** - Consolidado unificado de toda la documentación en una sola página.
- **`autenticacion.php`** - Explicación detallada del módulo de login, MFA y autenticación OAuth de Google.
- **`arquitectura.php`** - Desglose de la arquitectura en capas, patrones de diseño (SOLID) y organización del código.
- **`changelog.php`** - Registro histórico de versiones y actualizaciones del sistema (incluida la versión 2.1.1).
- **`configuracion.php`** - Guía de configuración del entorno (.env) y variables del sistema.
- **`instalacion.php`** - Manual de instalación paso a paso (servidores, XAMPP, dependencias y BD).
- **`seguridad.php`** / **`seguridad_unificada.php`** - Detalle de los mecanismos de seguridad implementados (CSRF, XSS, rate-limiting, honeypots).
- **`reportes.php`** - Documentación del sistema de reportes, exportación (PDF/Excel) y métricas.
- **`students.php`** - Manual sobre el ABM de alumnos, ficha individual y visualización de legajos.
- **`teachers.php`** - Gestión de legajos docentes y su vinculación con materias.
- **`administracion.php`** / **`herramientas_admin.php`** - Manual sobre el panel de control administrativo (backups, restauración y monitoreo).
- **`desarrollo.php`** / **`desarrollo_avanzado.php`** - Guía de desarrollo de nuevas funcionalidades, patrones de diseño, autoloader y testing.
- **`despliegue_unificado.php`** - Instrucciones para el despliegue moderno utilizando Docker y plataformas Cloud (Railway, Render, Fly.io).
- **`troubleshooting.php`** - Guía para la solución de fallos comunes, errores de BD, carga de archivos o sesiones.
- **`mantenimiento.php`** - Instructivo sobre limpieza de caché, purga de logs y optimización de base de datos.
- **`faq.php`** - Preguntas frecuentes con respuestas rápidas para los usuarios y administradores.
- **`contacto.php`** - Datos de contacto de soporte institucional.
- **`licencia.php`** - Licencia de software y términos de uso del sistema.
- **`privacidad.php`** - Políticas de privacidad y resguardo de la información de estudiantes y docentes.

---

## ?? **deployment/** - Archivos de Despliegue

Configuraciones y scripts para desplegar el sistema en diferentes entornos.

### **Por qué existe:**
- Facilita despliegue en producción
- Permite automatización del proceso
- Configuraciones específicas por entorno

### **deployment/docker/** - Configuración Docker
- `Dockerfile` - Imagen Docker del sistema
- `docker-compose.yml` - Configuración para desarrollo local
- `docker-compose.prod.yml` - Configuración para producción
- `docker-entrypoint.sh` - Script de inicialización
- `health-check.sh` - Verificación de salud del contenedor
- `supervisor/` - Configuración de Supervisor para procesos en background

**Por qué existe:** Permite empaquetar y desplegar el sistema de forma consistente en cualquier servidor.

### **deployment/cloud-configs/** - Configuraciones Cloud
- `fly.toml` - Configuración para Fly.io
- `railway.json` - Configuración para Railway
- `render.yaml` - Configuración para Render
- `Procfile` - Configuración para Heroku

**Por qué existe:** Facilita despliegue en diferentes plataformas cloud sin modificar el código base.

### **deployment/scripts/** - Scripts de Despliegue
- `backup.sh` - Script de respaldo de base de datos
- `restore.sh` - Script de restauración
- `deploy.sh` - Script de despliegue general
- `deploy-cloud.sh` - Script de despliegue en cloud
- `setup-windows.bat` - Script de configuración para Windows

**Por qué existe:** Automatiza tareas repetitivas de despliegue y mantenimiento.

---

## ?? **scripts/** - Scripts de Utilidad

Scripts de utilidad para desarrollo y mantenimiento.

### **Por qué existe:**
- Automatiza tareas comunes
- Facilita desarrollo y testing
- Mantiene consistencia en operaciones

**Ejemplos:** Scripts para limpiar caché, regenerar autoload, ejecutar tests, etc.

---

## ?? **bin/** - Binarios Ejecutables

Scripts ejecutables y herramientas del sistema.

### **bin/check_server.bat**
Verifica el estado del servidor (Apache, MySQL, PHP).

### **bin/restart_server.bat**
Reinicia los servicios del servidor.

**Por qué existe:** Proporciona herramientas rápidas para administración del entorno local de desarrollo.

---

## ?? **.github/** - Configuración de GitHub

Configuraciones para GitHub (CI/CD, issues, etc.).

### **.github/workflows/**
Configuraciones de GitHub Actions para integración continua.

**Por qué existe:** Permite automatizar tareas como tests, despliegues y verificaciones de código.

---

## ?? Archivos de Documentación en la Raíz

- **`README.md`** - Documentación principal del proyecto
- **`ESTRUCTURA_PROYECTO.md`** - Estructura general del proyecto
- **`GUIA_CARGA_DATOS.md`** - Guía paso a paso para cargar datos iniciales
- **`PROMPT_CONOCIMIENTO_PROYECTOS.md`** - Prompt para conocimiento del proyecto

---

## ?? Principios de Organización

### **Separación de Responsabilidades**
Cada carpeta tiene un propósito específico y claro, siguiendo el principio de responsabilidad única.

### **Escalabilidad**
La estructura permite crecer sin volverse caótica. Nuevas funcionalidades encuentran su lugar natural.

### **Mantenibilidad**
Código organizado es más fácil de entender, modificar y debuggear.

### **Seguridad**
Separación entre código público y privado, protección de archivos sensibles, logs seguros.

### **Estándares**
Sigue convenciones de la comunidad PHP (PSR-4, estructura MVC mejorada).

---

## ?? Notas Finales

- **No modificar `vendor/`** - Esta carpeta es gestionada por Composer
- **Proteger archivos sensibles** - Logs, backups y configuraciones con datos sensibles
- **Versionar código, no datos** - Los datos de BD y logs no van en Git
- **Mantener documentación actualizada** - La estructura puede evolucionar, actualiza este documento

---

**Última actualización:** 2025
**Versión del documento:** 1.0

