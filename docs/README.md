# 📚 EduSchool ERP — Technical Documentation

## Overview

**EduSchool ERP** is a comprehensive, production-ready web application for school administration. It provides a complete solution for managing students, teachers, courses, grades, attendance, timetables, disciplinary records, and administrative tools.

> **✅ PRODUCTION READY** — All modules fully functional and tested

## 🚀 Características Principales

### **✅ Funcionalidades Implementadas**

#### **🔐 Autenticación y Seguridad (IMPLEMENTADO)**
- ✅ Sistema de login seguro con hash Argon2ID
- ✅ Autenticación de dos factores (MFA) opcional
- ✅ Rate limiting para prevenir ataques de fuerza bruta
- ✅ Roles: admin, director, preceptor, secretaria
- ✅ Protección CSRF en todos los formularios
- ✅ CAPTCHA anti-bots cuando es necesario
- ✅ Sesiones seguras con timeout automático
- ✅ Logging completo de seguridad y auditoría

#### **👥 Gestión de Usuarios**
- Administración completa de estudiantes
- Gestión de profesores
- Control de acceso basado en roles
- Fichas individuales con información detallada
- Sistema de búsqueda y filtrado avanzado

#### **📚 Gestión Académica**
- Cursos y divisiones por año
- Materias y especialidades
- Notas y calificaciones
- Materias previas y correlatividades
- Horarios de clases
- Turnos (mañana, tarde, noche)

#### **📊 Sistema de Reportes**
- Reportes de estudiantes con filtros avanzados
- Reportes de notas por curso y materia
- Análisis de rendimiento académico
- Exportación a PDF y Excel
- Gráficos y estadísticas visuales

#### **⚠️ Llamados de Atención**
- Registro de incidentes disciplinarios
- Historial por estudiante
- Búsqueda y filtrado
- Exportación de reportes

#### **🛠️ Herramientas Administrativas (IMPLEMENTADO)**
- ✅ **Panel de Monitoreo en Tiempo Real**:
  - Métricas de sistema (memoria, CPU, disco)
  - Estado de base de datos y conexiones
  - Sesiones activas y usuarios conectados
  - Indicador de salud del sistema
- ✅ **Sistema de Backups**:
  - Creación de backups completos (BD + archivos)
  - Descarga de backups
  - Restauración desde backups
  - Limpieza automática de backups antiguos
- ✅ **Configuración del Sistema**:
  - Panel centralizado de configuración
  - Categorías: sistema, seguridad, backup, notificaciones
  - Exportar/importar configuración
- ✅ **Mantenimiento**:
  - Limpiar caché del sistema
  - Optimizar base de datos
  - Verificar integridad del sistema

#### **📱 Diseño Responsive**
- 100% responsive para todos los dispositivos
- Navegación móvil con hamburger menu
- Tablas adaptativas
- Formularios optimizados para touch
- Interfaz moderna y accesible

## 🏗️ Arquitectura del Sistema

### **Patrón: Layered Architecture (Arquitectura en Capas)**

```
┌─────────────────────────────────────────┐
│        PRESENTATION LAYER               │
│    (Controllers, Views, Pages)          │
├─────────────────────────────────────────┤
│         BUSINESS LAYER                  │
│   (Services, Business Logic, DTOs)      │
├─────────────────────────────────────────┤
│       PERSISTENCE LAYER                 │
│      (Mappers, Repositories)            │
├─────────────────────────────────────────┤
│          DATA LAYER                     │
│      (Models, Database)                 │
└─────────────────────────────────────────┘
```

### **Principios de Diseño**

- ✅ **SOLID Principles** aplicados en toda la arquitectura
- ✅ **DRY (Don't Repeat Yourself)**: Reutilización de código
- ✅ **Separation of Concerns**: Responsabilidades claras
- ✅ **Dependency Injection**: Mejor testabilidad
- ✅ **Interface Segregation**: Contratos bien definidos

## 📂 Estructura del Proyecto (ACTUALIZADA)

```
SistemaAdmin/
├── admin/                        # Herramientas administrativas
│   ├── admin_tools.php          # Panel de administración
│   ├── mfa_config.php           # Configuración MFA
│   ├── security_dashboard.php   # Dashboard de seguridad
│   └── reportes_demo.php        # Demo de reportes
├── api/                          # APIs REST
│   └── admin_tools_api.php      # API de herramientas admin
├── bin/                          # Scripts del sistema
│   ├── check_server.bat         # Verificación servidor
│   └── restart_server.bat       # Reinicio servidor
├── config/                       # Configuración
│   ├── database.php             # Conexión a BD
│   └── production.php           # Configuración producción
├── css/                          # Estilos
│   └── style.css                # Estilos principales
├── database/                     # Base de datos
│   ├── sistema_completo.sql     # Sistema completo
│   └── instalar_sistema_completo.bat # Instalador
├── docs/                         # Documentación esencial
│   ├── SISTEMA_COMPLETO.md      # Sistema completo
│   ├── USER_GUIDE.md            # Guía de usuario
│   ├── HERRAMIENTAS_ADMIN.md    # Herramientas admin
│   ├── SEGURIDAD_INFORMATICA.md # Seguridad
│   └── [otros docs esenciales]  # Documentación adicional
├── includes/                     # Componentes compartidos
│   ├── header.php               # Header del sistema
│   ├── footer.php               # Footer del sistema
│   ├── csrf_functions.php       # Funciones CSRF
│   └── character_encoding.php   # Codificación caracteres
├── js/                           # JavaScript
│   ├── admin_tools.js           # JS herramientas admin
│   └── responsive.js            # JS responsive
├── logs/                         # Logs del sistema
│   ├── audit.log                # Log de auditoría
│   └── security.log             # Log de seguridad
├── public/                       # Archivos públicos
│   ├── login.php                # Login del sistema
│   ├── logout.php               # Logout del sistema
│   ├── health-check.php         # Verificación salud
│   ├── errors/                  # Páginas de error
│   └── honeypot/                # Honeypot de seguridad
├── src/                          # Código fuente (PSR-4)
│   ├── adapters/                # Adaptadores
│   ├── contracts/               # Interfaces/Contratos
│   ├── controllers/             # Controladores
│   ├── DTOs/                    # Data Transfer Objects
│   ├── exceptions/              # Excepciones personalizadas
│   ├── interfaces/              # Interfaces de servicios
│   ├── mappers/                 # Mappers (ORM)
│   ├── middleware/              # Middleware de seguridad
│   ├── models/                  # Modelos de dominio
│   ├── services/                # Servicios de negocio
│   └── autoload.php             # Autoloader PSR-4
├── *.php                         # Páginas principales del sistema
└── index.php                    # Dashboard principal
```

## 🚀 Instalación Rápida

### **Requisitos del Sistema**

- ✅ **PHP 8.0+** con extensiones:
  - PDO y PDO_MySQL
  - JSON, OpenSSL, mbstring
  - zip (para backups)
- ✅ **MySQL 5.7+** o **MariaDB 10.3+**
- ✅ **Apache 2.4+** con mod_rewrite
- ✅ **XAMPP** (recomendado para desarrollo)

### **Instalación en 3 Pasos**

#### **1. Instalar Base de Datos**
```bash
# Ejecutar desde la raíz del proyecto
database\instalar_sistema_completo.bat
```

#### **2. Configurar Acceso**
- **URL**: `http://localhost/SistemaAdmin/public/login.php`
- **Admin**: `admin` / `admin123`
- **Director**: `12345678` / `admin123`
- **Preceptor**: `87654321` / `admin123`
- **Secretaria**: `11223344` / `admin123`

#### **3. Cambiar Contraseñas**
⚠️ **IMPORTANTE**: Cambiar todas las contraseñas después del primer login

1. **Clonar el repositorio**
   ```bash
   git clone <repository-url>
   cd SistemaAdmin
   ```

2. **Configurar base de datos**
   ```sql
   CREATE DATABASE sistema_admin_eest2;
   ```

3. **Importar estructura**
   ```bash
   mysql -u root -p sistema_admin_eest2 < database/INSTALL_COMPLETE.sql
   ```

4. **Configurar variables de entorno** (opcional)
   ```php
   // En config/database.php se pueden configurar:
   $_ENV['DB_HOST'] = 'localhost';
   $_ENV['DB_NAME'] = 'sistema_admin_eest2';
   $_ENV['DB_USER'] = 'root';
   $_ENV['DB_PASS'] = '';
   ```

5. **Acceder al sistema**
   ```
   http://localhost/SistemaAdmin/
   ```

6. **Credenciales por defecto**
   ```
   Usuario: admin
   Contraseña: admin123
   ```
   ⚠️ **IMPORTANTE**: Cambiar la contraseña inmediatamente después del primer acceso.

## 📖 Documentación Detallada

### **Para Usuarios**
- [**Guía de Usuario**](guides/USER_GUIDE.md) - Manual completo para usuarios finales
- [**Guía de Administrador**](guides/ADMIN_GUIDE.md) - Guía para administradores del sistema

### **Para Desarrolladores**
- [**Guía de Desarrollo**](development/README.md) - Setup y convenciones de código
- [**Arquitectura del Sistema**](architecture/README.md) - Diseño y patrones
- [**Documentación de API**](api/README.md) - Endpoints y uso de APIs

### **Características Específicas**
- [**Gestión de Asistencia y Flujo Académico**](GUIA_FLUJO_ACADEMICO.md) - Guía sobre asistencia y regularidades
- [**Herramientas Administrativas**](guides/HERRAMIENTAS_ADMIN.md) - Backups, monitoreo y configuración centralizada
- [**Filtros de Interfaz Estándar**](FILTROS_UI_ESTANDAR.md) - Reglas para la UI del sistema

### **Historial y Cambios**
- [**Changelog / Historial de Cambios**](documentacion_completa/changelog.php) - Versiones y actualizaciones del sistema (incluye v2.1.1 de Junio 2026)

## 🔒 Seguridad

### **Características de Seguridad Implementadas**

- ✅ **CSRF Protection**: Tokens únicos para todos los formularios
- ✅ **Password Hashing**: Argon2ID para contraseñas
- ✅ **Rate Limiting**: Límite de intentos de login por IP
- ✅ **Security Headers**: Headers HTTP de seguridad
- ✅ **SQL Injection Prevention**: Prepared statements en todas las consultas
- ✅ **XSS Protection**: Sanitización de todas las salidas
- ✅ **Session Security**: Regeneración de IDs, timeouts configurables
- ✅ **Audit Logging**: Registro completo de eventos de seguridad
- ✅ **Two-Factor Authentication**: 2FA opcional con Google Authenticator
- ✅ **Input Validation**: Validación exhaustiva de todas las entradas

### **Recomendaciones de Seguridad**

1. ✅ Cambiar las credenciales por defecto
2. ✅ Habilitar 2FA para usuarios administradores
3. ✅ Revisar logs de seguridad regularmente
4. ✅ Mantener backups actualizados
5. ✅ Actualizar PHP y MySQL regularmente
6. ✅ Usar HTTPS en producción
7. ✅ Configurar correctamente los permisos de archivos

## 🛠️ Mantenimiento

### **Backups Automáticos**
El sistema incluye un sistema completo de backups que permite:
- Crear backups manuales desde el panel de administración
- Configurar backups automáticos (diarios/semanales)
- Descargar backups
- Restaurar desde backups
- Limpieza automática de backups antiguos

Acceder a: `admin_tools.php` → Pestaña "Backups"

### **Monitoreo del Sistema**
Métricas disponibles en tiempo real:
- Uso de memoria PHP
- Tamaño de base de datos
- Espacio en disco
- Usuarios activos
- Estado de salud del sistema
- Alertas de seguridad

Acceder a: `admin_tools.php` → Pestaña "Monitoreo"

### **Optimización**
Tareas de mantenimiento disponibles:
- Limpiar caché del sistema
- Optimizar tablas de base de datos
- Verificar integridad del sistema
- Ver información del servidor

Acceder a: `admin_tools.php` → Pestaña "Mantenimiento"

## 📊 Métricas de Calidad

- ✅ **Arquitectura**: Layered Architecture con SOLID
- ✅ **Cobertura de Tests**: En desarrollo
- ✅ **Seguridad**: Múltiples capas de protección
- ✅ **Rendimiento**: Optimizado con caché
- ✅ **Responsive**: 100% compatible con móviles
- ✅ **Documentación**: Completa y actualizada

## 🤝 Contribuir

Para contribuir al proyecto:

1. Fork el repositorio
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📞 Soporte

Para problemas o preguntas:
- Revisar la [Guía de Usuario](USER_GUIDE.md)
- Revisar la [Guía de Desarrollo](development/README.md)
- Consultar los logs en `logs/`
- Usar el sistema de verificación de integridad en `admin_tools.php`

## 📝 Licencia

Este proyecto está desarrollado para la E.E.S.T. N°2 "Educación y Trabajo".

---

**Última actualización:** Septiembre 2025  
**Versión del Sistema:** 3.0.0  
**Estado:** Producción