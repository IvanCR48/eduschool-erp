# 🏫 EduSchool ERP — System Overview

## 📋 Descripción General

> **✅ SISTEMA COMPLETAMENTE FUNCIONAL** - Instalado y listo para usar

Sistema administrativo completo para escuela técnica con todos los módulos necesarios para la gestión educativa, incluyendo usuarios, estudiantes, profesores, materias, notas, horarios y llamados de atención.

**Estado actual**: Sistema limpio, optimizado y completamente funcional después de la limpieza de archivos obsoletos.

## 🗄️ Estructura de Base de Datos

### 👥 **Módulo de Usuarios y Autenticación**

#### Tabla `usuarios`
- **Roles disponibles**: admin, director, preceptor, secretaria
- **Campos**: dni, nombre, apellido, email, password_hash, rol, activo
- **Seguridad**: Hash Argon2ID, control de intentos fallidos, bloqueo temporal

#### Tabla `sesiones_usuarios`
- Control de sesiones activas
- Tracking de IP y User Agent
- Timeout automático

### 🎓 **Módulo Académico**

#### Especialidades Técnicas (IMPLEMENTADAS)
- ✅ **TIPP**: Técnico en Informática Personal y Profesional
- ✅ **EMEC**: Técnico en Electromecánica  
- ✅ **CONS**: Técnico en Construcciones
- ✅ **QUIM**: Técnico en Química

#### Cursos
- 4 años por especialidad
- Divisiones A y B
- Turnos: mañana, tarde, contraturno
- Capacidad máxima configurable

### 👨‍🎓 **Módulo de Estudiantes**

#### Campos principales:
- Datos personales completos (DNI, nombre, fecha nacimiento, etc.)
- Vinculación con curso actual
- Estados: activo, egresado, abandono, expulsado
- Historial de ingreso y egreso

### 👨‍🏫 **Módulo de Profesores**

#### Campos principales:
- Datos personales y profesionales
- Títulos y especialidades
- Vinculación con especialidad técnica
- Estados: activo, inactivo, jubilado

### 📚 **Módulo de Materias**

#### Tipos de materias:
- **Generales**: Matemática, Lengua, Historia, Geografía, etc.
- **Técnicas**: Específicas por especialidad
- Configuración de horas semanales
- Año de cursada

### 📅 **Módulo de Horarios**

#### Características:
- Asignación de profesores a materias por curso
- Días de la semana y horarios
- Aulas asignadas
- Control de conflictos

### 📊 **Módulo de Notas**

#### Tipos de evaluación:
- Parciales
- Recuperatorios
- Trabajos prácticos
- Evaluación continua
- Exámenes finales

#### Períodos académicos:
- Cuatrimestres (en UI; el parámetro de filtro puede seguir llamándose `trimestre` por compatibilidad), bimestres en base de datos
- Configuración anual flexible

### ⚠️ **Módulo de Llamados de Atención**

#### Tipos:
- Amonestaciones
- Apercibimientos
- Suspensiones
- Observaciones

#### Estados:
- Pendiente
- Resuelto
- Archivado

## 🔑 Credenciales de Acceso

### Usuarios del Sistema:

| Rol | Usuario | Contraseña | Descripción |
|-----|---------|------------|-------------|
| **Admin** | `admin` | `admin123` | Administrador del sistema |
| **Director** | `12345678` | `admin123` | Director de la institución |
| **Preceptor** | `87654321` | `admin123` | Preceptor general |
| **Secretaria** | `11223344` | `admin123` | Secretaria administrativa |

⚠️ **IMPORTANTE**: Cambiar todas las contraseñas después del primer login.

## 📊 Datos de Ejemplo Incluidos

### Especialidades (4)
- Técnico en Informática Personal y Profesional
- Técnico en Electromecánica
- Técnico en Construcciones
- Técnico en Química

### Cursos (16)
- 4 años × 4 especialidades
- Divisiones A y B
- Turnos mañana y tarde

### Profesores (6)
- Docentes especializados por área
- Con títulos y especialidades asignadas

### Materias (13)
- **Generales**: Matemática, Lengua, Historia, Geografía, Educación Física, Inglés
- **Técnicas TIPP**: Sistemas de Representación, Taller de Programación, Redes, Base de Datos, Desarrollo Web, Sistemas Operativos, Proyecto Final

### Estudiantes (5)
- Estudiantes de 1° año TIPP división A
- Con datos personales completos

### Horarios (7)
- Horarios de ejemplo para 1° año TIPP A
- Asignaciones de profesores y materias

### Notas (8)
- Notas de ejemplo para estudiantes
- Diferentes tipos de evaluación

### Períodos Académicos (3)
- Cuatrimestres del año 2024
- Configuración completa

## 🚀 Instalación (SIMPLIFICADA)

#### **Opción 1: Script Automático (RECOMENDADO)**
```bash
# Ejecutar desde la raíz del proyecto
database\instalar_sistema_completo.bat
```

#### **Opción 2: Manual**
```bash
# Crear base de datos desde cero
mysql -u root -p < database/sistema_completo.sql
```

> **✅ INSTALACIÓN ÚNICA**: Solo necesitas ejecutar el script una vez para tener el sistema completo funcionando.

## 🌐 Acceso al Sistema

**URL**: `http://localhost/SistemaAdmin/public/login.php`

## 🛡️ Características de Seguridad

- ✅ **Autenticación robusta** con hash Argon2ID
- ✅ **Control de sesiones** con timeout automático
- ✅ **Logging completo** de eventos de seguridad
- ✅ **Protección CSRF** en formularios
- ✅ **Rate limiting** para prevenir ataques
- ✅ **MFA opcional** para usuarios administrativos
- ✅ **Headers de seguridad** configurados

## 📈 Funcionalidades Disponibles

### Para Administradores:
- Gestión completa de usuarios
- Configuración del sistema
- Reportes y estadísticas
- Configuración de seguridad

### Para Directores:
- Vista general del sistema
- Reportes académicos
- Gestión de personal
- Configuración académica

### Para Preceptores:
- Gestión de estudiantes
- Llamados de atención
- Seguimiento académico
- Comunicación con familias

### Para Secretarias:
- Gestión administrativa
- Reportes oficiales
- Documentación estudiantil
- Comunicaciones institucionales

## 🔧 Mantenimiento

### Respaldo Regular
```bash
mysqldump -u root -p sistema_admin_eest2 > backup_$(date +%Y%m%d).sql
```

### Limpieza de Sesiones
```sql
-- Limpiar sesiones expiradas
DELETE FROM sesiones_usuarios WHERE ultima_actividad < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

### Limpieza de Cache
```sql
-- Limpiar cache expirado
DELETE FROM cache_configuraciones WHERE expires_at < NOW();
```

## 📞 Soporte

### Logs del Sistema
- **Errores**: `logs/security.log`, `logs/audit.log`
- **Sesiones**: Tabla `sesiones_usuarios`
- **Actividad**: Tabla `logs_errores`

### Verificación de Instalación
```sql
-- Verificar estructura
SHOW TABLES;
DESCRIBE usuarios;
SELECT COUNT(*) as total_usuarios FROM usuarios;
SELECT COUNT(*) as total_estudiantes FROM estudiantes;
SELECT COUNT(*) as total_profesores FROM profesores;
```

## 🎉 ¡Sistema Listo!

El sistema administrativo está completamente instalado y configurado con todos los módulos necesarios para la gestión educativa de la E.E.S.T. N°2 "Educación y Trabajo".
