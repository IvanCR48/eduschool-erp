# ?? Estructura del Proyecto - Sistema Admin EEST N°2

## ??? Organización de Carpetas

### ?? **deployment/** - Archivos de Despliegue
Contiene todos los archivos relacionados con el despliegue del sistema en diferentes entornos.

#### **deployment/docker/**
- `Dockerfile` - Definición de la imagen Docker
- `docker-compose.yml` - Configuración para desarrollo local
- `docker-compose.prod.yml` - Configuración para producción
- `docker-entrypoint.sh` - Script de inicialización del contenedor
- `health-check.sh` - Script de verificación de salud del contenedor
- `supervisor/` - Configuración de Supervisor para procesos

#### **deployment/cloud-configs/**
- `fly.toml` - Configuración para Fly.io
- `railway.json` - Configuración para Railway
- `render.yaml` - Configuración para Render
- `Procfile` - Configuración para Heroku

#### **deployment/scripts/**
- `backup.sh` - Script de respaldo de base de datos
- `restore.sh` - Script de restauración de base de datos
- `deploy.sh` - Script de despliegue general
- `deploy-cloud.sh` - Script de despliegue en cloud
- `setup-windows.bat` - Script de configuración para Windows

---

### ?? **config/** - Configuraciones del Sistema
Contiene todas las configuraciones del sistema y servicios.

#### **config/php/**
- `php.ini` - Configuración principal de PHP
- `opcache.ini` - Configuración de OPcache
- `www.conf` - Configuración de PHP-FPM

#### **config/nginx/**
- `nginx.conf` - Configuración principal de Nginx
- `default.conf` - Configuración del sitio por defecto
- `proxy.conf` - Configuración de proxy

#### **config/mysql/**
- `my.cnf` - Configuración de MySQL

#### **config/** (root)
- `database.php` - Configuración de conexión a base de datos
- `production.php` - Configuración específica de producción

---

### ?? **docs/** - Documentación del Sistema
Documentación organizada por categorías.

#### **docs/guides/** - Guías de Uso
- `USER_GUIDE.md` - Guía para usuarios finales
- `ADMIN_GUIDE.md` - Guía para administradores
- `SISTEMA_COMPLETO.md` - Documentación completa del sistema
- `HERRAMIENTAS_ADMIN.md` - Herramientas administrativas

#### **docs/security/** - Documentación de Seguridad
- `SEGURIDAD_INFORMATICA.md` - Conceptos de seguridad
- `PLAN_ENDURECIMIENTO_SEGURIDAD.md` - Plan de hardening
- `PROTECCIONES_IMPLEMENTADAS.md` - Protecciones activas
- `RESUMEN_SEGURIDAD.md` - Resumen de seguridad
- `SECURITY_CHECKLIST.md` - Lista de verificación

#### **docs/deployment/** - Guías de Despliegue
- `DEPLOYMENT_GUIDE.md` - Guía de despliegue
- `README_DEPLOYMENT.md` - Instrucciones de despliegue

#### **docs/api/** - Documentación de API
- `README.md` - Documentación de endpoints

#### **docs/architecture/** - Arquitectura del Sistema
- `README.md` - Documentación de arquitectura

#### **docs/development/** - Desarrollo
- `README.md` - Guía de desarrollo

#### **docs/** (root)
- `README.md` - Índice general de documentación

---

### ?? **src/** - Código Fuente PHP
Arquitectura moderna con separación de responsabilidades.

#### **src/controllers/**
Controladores para lógica de negocio y manejo de peticiones.

#### **src/services/**
Servicios para lógica de aplicación compleja.

#### **src/mappers/**
Mappers para transformación entre base de datos y objetos.

#### **src/models/**
Modelos de dominio (Estudiante, Profesor, Curso, etc.).

#### **src/middleware/**
Middleware para seguridad, autenticación, etc.

#### **src/interfaces/**
Interfaces para contratos de servicios.

#### **src/DTOs/**
Data Transfer Objects para transferencia de datos.

#### **src/exceptions/**
Excepciones personalizadas.

#### **src/adapters/**
Adaptadores para diferentes implementaciones.

#### **src/contracts/**
Contratos de base de datos.

---

### ?? **public/** - Archivos Públicos
Archivos accesibles directamente desde el navegador.

#### **public/errors/**
- Páginas de error personalizadas (403, 404, 500)

#### **public/honeypot/**
- Trampas de seguridad para detectar atacantes

#### **public/** (root)
- `login.php` - Página de inicio de sesión
- `logout.php` - Cierre de sesión
- `health-check.php` - Verificación de salud del sistema

---

### ?? **database/** - Base de Datos
Scripts y archivos relacionados con la base de datos.

- `school_admin.sql` - Schema completo de la base de datos
- `instalar_sistema_completo.bat` - Instalador automatizado

---

### ?? **includes/** - Archivos de Inclusión
Archivos PHP incluidos globalmente.

- `header.php` - Encabezado del sistema
- `footer.php` - Pie de página del sistema
- `csrf_functions.php` - Funciones de protección CSRF
- `character_encoding.php` - Codificación de caracteres

---

### ?? **css/** - Estilos CSS
- `style.css` - Estilos principales del sistema (completamente responsive)

---

### ?? **js/** - JavaScript
- `admin_tools.js` - JavaScript para herramientas administrativas
- `responsive.js` - JavaScript para funcionalidad responsive

---

### ?? **img/** - Imágenes
- `logo-school.png` - Logo institucional

---

### ?? **admin/** - Panel Administrativo
Herramientas exclusivas para administradores.

- `admin_tools.php` - Herramientas administrativas
- `security_dashboard.php` - Dashboard de seguridad
- `mfa_config.php` - Configuración de autenticación multifactor
- `reportes_demo.php` - Reportes demostrativos

---

### ?? **api/** - API REST
Endpoints de API para integraciones.

- `admin_tools_api.php` - API de herramientas administrativas

---

### ?? **logs/** - Logs del Sistema
Registros de auditoría y seguridad.

- `audit.log` - Registro de auditoría
- `security.log` - Registro de eventos de seguridad

---

### ?? **backups/** - Respaldos
Carpeta para almacenar respaldos de base de datos.

---

### ?? **bin/** - Binarios y Scripts Ejecutables
Scripts de utilidad para Windows.

- `check_server.bat` - Verificar estado del servidor
- `restart_server.bat` - Reiniciar servidor

---

### ?? **vendor/** - Dependencias de Composer
Librerías de terceros instaladas via Composer.

---

## ?? Archivos Principales en la Raíz

### Páginas del Sistema
- `index.php` - Dashboard principal
- `students.php` - Gestión de estudiantes
- `student_profile.php` - Ficha individual del estudiante
- `teachers.php` - Gestión de profesores
- `teacher_profile.php` - Ficha individual del profesor
- `courses.php` - Gestión de cursos
- `subjects.php` - Gestión de materias
- `materias_previas.php` - Gestión de materias previas
- `grades.php` - Carga y gestión de notas
- `schedules.php` - Gestión de horarios
- `discipline.php` - Llamados de atención
- `especialidades.php` - Gestión de especialidades
- `staff.php` - Gestión del equipo docente

### Páginas de Documentación
- `documentacion.php` - Hub de documentación
- `documentacion_guia_usuario.php` - Guía de usuario
- `documentacion_students.php` - Documentación para estudiantes
- `documentacion_teachers.php` - Documentación para profesores
- `documentacion_administracion.php` - Documentación administrativa
- `documentacion_instalacion.php` - Guía de instalación
- `documentacion_seguridad.php` - Documentación de seguridad

### Funcionalidades Adicionales
- `qr_students.php` - Generación de códigos QR para múltiples estudiantes
- `ver_qr.php` - Visualización individual de código QR
- `print_report_card.php` - Impresión de boletín de notas

### Configuración
- `composer.json` - Dependencias de PHP
- `env.example` - Ejemplo de variables de entorno

---

## ?? Comandos Útiles

### Docker
```bash
# Iniciar en desarrollo
cd deployment/docker
docker-compose up -d

# Iniciar en producción
docker-compose -f docker-compose.prod.yml up -d

# Ver logs
docker-compose logs -f
```

### Respaldos
```bash
# Crear respaldo
cd deployment/scripts
./backup.sh

# Restaurar respaldo
./restore.sh backup_file.sql
```

### Despliegue
```bash
# Despliegue general
cd deployment/scripts
./deploy.sh

# Despliegue en cloud
./deploy-cloud.sh
```

---

## ?? Notas Importantes

1. **Configuraciones PHP**: Ahora en `config/php/` en lugar de `docker/php/`
2. **Configuraciones Nginx**: Ahora en `config/nginx/` en lugar de `docker/nginx/`
3. **Scripts de despliegue**: Ahora en `deployment/scripts/` en lugar de `scripts/`
4. **Documentación**: Organizada en subcarpetas por categoría en `docs/`
5. **Archivos Docker**: Consolidados en `deployment/docker/`
6. **Sistema completamente responsive**: Media queries en todos los archivos principales

---

## ?? Seguridad

- Configuraciones de seguridad en `docs/security/`
- Logs de seguridad en `logs/security.log`
- Honeypots en `public/honeypot/`
- MFA configurado en `admin/mfa_config.php`

---

## ?? Soporte

Para más información, consulta:
- `docs/README.md` - Índice de documentación
- `docs/guides/USER_GUIDE.md` - Guía de usuario
- `docs/guides/ADMIN_GUIDE.md` - Guía de administrador

---

**Sistema Admin EEST N°2 "Educación y Trabajo"**  
*Formando Futuros Profesionales* ??


