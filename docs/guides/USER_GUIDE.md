# 📚 Guía de Usuario - Sistema Administrativo E.E.S.T N°2

> **✅ SISTEMA COMPLETAMENTE FUNCIONAL** - Guía actualizada para el sistema limpio y optimizado

## 🎯 **Índice General**

1. [**Introducción al Sistema**](#introducción)
2. [**Acceso y Autenticación**](#acceso-y-autenticación)
3. [**Guía por Roles**](#guía-por-roles)
   - [👑 Administrador](#administrador)
   - [🎓 Directivo](#directivo)
   - [👨‍🏫 Profesor](#profesor)
   - [📋 Preceptor](#preceptor)
   - [📝 Secretario](#secretario)
4. [**Funcionalidades Comunes**](#funcionalidades-comunes)
5. [**Seguridad y Autenticación 2FA**](#seguridad-y-autenticación-2fa)
6. [**Resolución de Problemas**](#resolución-de-problemas)

---

## 📖 **Introducción** {#introducción}

El **Sistema Administrativo E.E.S.T N°2** es una plataforma integral diseñada para la gestión completa de la Escuela de Educación Secundaria Técnica N°2 "Educación y Trabajo". 

### **🏫 Características Principales:**
- ✅ **Gestión de Estudiantes** - Registro, seguimiento y administración
- ✅ **Gestión de Profesores** - Control de personal docente
- ✅ **Gestión Académica** - Cursos, materias, horarios y especialidades
- ✅ **Sistema de Llamados** - Control disciplinario
- ✅ **Sistema de Notas** - Calificaciones y evaluaciones
- ✅ **Seguridad Avanzada** - Autenticación de dos factores (2FA)
- ✅ **Reportes y Exportación** - Análisis y documentación

---

## 🔐 **Acceso y Autenticación** {#acceso-y-autenticación}

### **🌐 Acceso al Sistema**
1. **URL**: `http://tu-servidor/sistema-admin/`
2. **Navegador recomendado**: Chrome, Firefox, Safari o Edge (versión actualizada)

### **🔑 Proceso de Login**

#### **Paso 1: Credenciales Básicas**
```
Usuario: [tu_usuario]
Contraseña: [tu_contraseña]
```

#### **Paso 2: CAPTCHA de Seguridad**
```
Pregunta: ¿Cuánto es 7 + 3? = ?
Respuesta: [ingrese el resultado]
```

#### **Paso 3: Autenticación de Dos Factores (2FA)** *(Si está activado)*
```
Código de 6 dígitos: [123456]
```
- Ingrese el código de su aplicación autenticadora (Google Authenticator, Authy, etc.)
- O use uno de sus códigos de respaldo

### **⚠️ Problemas Comunes de Login**

| **Problema** | **Solución** |
|--------------|--------------|
| **"Usuario o contraseña incorrectos"** | Verificar credenciales con administrador |
| **"Demasiados intentos"** | Esperar 5 minutos y volver a intentar |
| **"Sesión expirada"** | La sesión dura 30 minutos de inactividad |
| **"Código 2FA incorrecto"** | Verificar sincronización del autenticador |
| **"CAPTCHA incorrecto"** | Resolver la operación matemática simple |

---

## 👑 **Administrador** {#administrador}

### **🎯 Perfil del Administrador**
El **Administrador** tiene **acceso completo** a todas las funcionalidades del sistema.

### **📋 Funcionalidades Disponibles**

#### **🏠 Dashboard Principal**
- **Vista general** del sistema
- **Estadísticas** de estudiantes, profesores y cursos
- **Métricas** de uso del sistema
- **Alertas** y notificaciones importantes

#### **👥 Gestión de Estudiantes**
- ✅ **Ver** todos los estudiantes
- ✅ **Crear** nuevos estudiantes
- ✅ **Editar** información de estudiantes
- ✅ **Eliminar** estudiantes
- ✅ **Buscar** por DNI, nombre, curso
- ✅ **Exportar** listados
- ✅ **Imprimir** fichas individuales

#### **👨‍🏫 Gestión de Profesores**
- ✅ **Ver** todos los profesores
- ✅ **Crear** nuevos profesores
- ✅ **Editar** información de profesores
- ✅ **Eliminar** profesores
- ✅ **Asignar** materias y cursos
- ✅ **Ver** horarios de cada profesor

#### **🎓 Gestión Académica**
- ✅ **Cursos** - Crear, editar, eliminar
- ✅ **Materias** - Administrar plan de estudios
- ✅ **Especialidades** - Configurar orientaciones
- ✅ **Horarios** - Crear y modificar horarios
- ✅ **Materias Previas** - Gestionar correlatividades

#### **📊 Sistema de Notas**
- ✅ **Ver** todas las notas
- ✅ **Crear** nuevas calificaciones
- ✅ **Editar** notas existentes
- ✅ **Eliminar** calificaciones
- ✅ **Imprimir** boletines
- ✅ **Exportar** calificaciones

#### **⚠️ Sistema de Llamados**
- ✅ **Ver** todos los llamados
- ✅ **Crear** nuevos llamados
- ✅ **Editar** llamados existentes
- ✅ **Eliminar** llamados
- ✅ **Exportar** reportes de disciplina

#### **👔 Equipo Directivo**
- ✅ **Ver** equipo directivo
- ✅ **Editar** información del equipo
- ✅ **Asignar** roles y responsabilidades

#### **🛡️ Seguridad y Usuarios**
- ✅ **Gestionar** usuarios del sistema
- ✅ **Configurar** autenticación 2FA
- ✅ **Ver** logs de seguridad
- ✅ **Configurar** permisos y roles

#### **📈 Reportes y Análisis**
- ✅ **Ver** todos los reportes
- ✅ **Exportar** datos en Excel/PDF
- ✅ **Generar** estadísticas
- ✅ **Monitorear** métricas del sistema

---

## 🎓 **Directivo** {#directivo}

### **🎯 Perfil del Directivo**
El **Directivo** tiene acceso a **todas las funcionalidades administrativas** excepto la gestión de usuarios del sistema.

### **📋 Funcionalidades Disponibles**

#### **🏠 Dashboard Principal**
- **Vista general** del sistema
- **Estadísticas** de estudiantes, profesores y cursos
- **Métricas** de uso del sistema
- **Alertas** y notificaciones importantes

#### **👥 Gestión de Estudiantes**
- ✅ **Ver** todos los estudiantes
- ✅ **Crear** nuevos estudiantes
- ✅ **Editar** información de estudiantes
- ✅ **Eliminar** estudiantes
- ✅ **Buscar** por DNI, nombre, curso
- ✅ **Exportar** listados

#### **👨‍🏫 Gestión de Profesores**
- ✅ **Ver** todos los profesores
- ✅ **Crear** nuevos profesores
- ✅ **Editar** información de profesores
- ✅ **Eliminar** profesores
- ✅ **Asignar** materias y cursos

#### **🎓 Gestión Académica**
- ✅ **Cursos** - Crear, editar, eliminar
- ✅ **Materias** - Administrar plan de estudios
- ✅ **Especialidades** - Configurar orientaciones
- ✅ **Horarios** - Crear y modificar horarios
- ✅ **Materias Previas** - Gestionar correlatividades

#### **📊 Sistema de Notas**
- ✅ **Ver** todas las notas
- ✅ **Crear** nuevas calificaciones
- ✅ **Editar** notas existentes
- ✅ **Eliminar** calificaciones
- ✅ **Imprimir** boletines
- ✅ **Exportar** calificaciones

#### **⚠️ Sistema de Llamados**
- ✅ **Ver** todos los llamados
- ✅ **Crear** nuevos llamados
- ✅ **Editar** llamados existentes
- ✅ **Eliminar** llamados
- ✅ **Exportar** reportes de disciplina

#### **👔 Equipo Directivo**
- ✅ **Ver** equipo directivo
- ✅ **Editar** información del equipo

#### **🛡️ Seguridad**
- ✅ **Configurar** autenticación 2FA personal
- ✅ **Ver** logs de seguridad

#### **📈 Reportes y Análisis**
- ✅ **Ver** todos los reportes
- ✅ **Exportar** datos en Excel/PDF
- ✅ **Generar** estadísticas

---


## 📋 **Preceptor** {#preceptor}

### **🎯 Perfil del Preceptor**
El **Preceptor** tiene acceso **limitado** a las funcionalidades relacionadas con el seguimiento estudiantil.

### **📋 Funcionalidades Disponibles**

#### **🏠 Dashboard Principal**
- **Vista general** de estudiantes asignados
- **Estadísticas** de asistencia y disciplina
- **Notificaciones** de llamados pendientes

#### **👥 Gestión de Estudiantes**
- ✅ **Ver** estudiantes asignados
- ✅ **Editar** información básica de estudiantes
- ✅ **Buscar** estudiantes específicos
- ❌ **No puede** crear o eliminar estudiantes

#### **⚠️ Sistema de Llamados**
- ✅ **Ver** llamados de sus estudiantes
- ✅ **Crear** nuevos llamados
- ✅ **Editar** llamados existentes
- ✅ **Ver** historial de disciplina

#### **📚 Información Académica**
- ✅ **Ver** cursos asignados
- ✅ **Ver** horarios
- ✅ **Ver** asistencia *(si está implementado)*

#### **❌ Funcionalidades NO Disponibles**
- ❌ Gestión de profesores
- ❌ Sistema de notas
- ❌ Gestión de cursos, materias o especialidades
- ❌ Gestión de horarios
- ❌ Gestión de equipo directivo
- ❌ Reportes administrativos

---

## 📝 **Secretario** {#secretario}

### **🎯 Perfil del Secretario**
El **Secretario** tiene acceso **limitado** a las funcionalidades relacionadas con la gestión administrativa básica.

### **📋 Funcionalidades Disponibles**

#### **🏠 Dashboard Principal**
- **Vista general** de estudiantes y profesores
- **Estadísticas** básicas del sistema
- **Notificaciones** de registros pendientes

#### **👥 Gestión de Estudiantes**
- ✅ **Ver** todos los estudiantes
- ✅ **Crear** nuevos estudiantes
- ✅ **Editar** información de estudiantes
- ❌ **No puede** eliminar estudiantes

#### **👨‍🏫 Gestión de Profesores**
- ✅ **Ver** todos los profesores
- ✅ **Crear** nuevos profesores
- ✅ **Editar** información de profesores
- ❌ **No puede** eliminar profesores

#### **📚 Información Académica**
- ✅ **Ver** cursos
- ✅ **Ver** materias
- ✅ **Ver** especialidades
- ❌ **No puede** crear, editar o eliminar

#### **📈 Reportes Básicos**
- ✅ **Ver** reportes básicos
- ✅ **Exportar** datos en Excel/PDF

#### **❌ Funcionalidades NO Disponibles**
- ❌ Sistema de notas
- ❌ Sistema de llamados
- ❌ Gestión de horarios
- ❌ Gestión de equipo directivo
- ❌ Gestión de usuarios
- ❌ Reportes avanzados

---

## 🔧 **Funcionalidades Comunes** {#funcionalidades-comunes}

### **🔍 Búsqueda y Filtros**
Todos los usuarios pueden utilizar:
- **Búsqueda por texto** - Buscar por nombre, apellido o DNI
- **Filtros por curso** - Filtrar estudiantes por curso
- **Filtros por fecha** - Filtrar por rangos de fechas
- **Ordenamiento** - Ordenar por cualquier columna

### **📱 Interfaz Responsiva**
- **Desktop** - Experiencia completa
- **Tablet** - Interfaz adaptada
- **Móvil** - Navegación optimizada

### **⌨️ Atajos de Teclado**
- **Ctrl + F** - Buscar en la página
- **Ctrl + S** - Guardar formulario
- **Escape** - Cerrar modales
- **Enter** - Confirmar acciones

### **🔄 Navegación**
- **Menú lateral** - Acceso rápido a todas las secciones
- **Breadcrumbs** - Ubicación actual en el sistema
- **Botones de acción** - Acciones rápidas en cada página

---

## 🛡️ **Seguridad y Autenticación 2FA** {#seguridad-y-autenticación-2fa}

### **🔐 ¿Qué es la Autenticación de Dos Factores (2FA)?**
La **2FA** es una capa adicional de seguridad que requiere **dos formas** de verificación para acceder al sistema:
1. **Algo que sabes** - Tu contraseña
2. **Algo que tienes** - Tu teléfono con aplicación autenticadora

### **📱 Configuración Inicial de 2FA**

#### **Paso 1: Acceder a la Configuración**
1. Inicia sesión en el sistema
2. Ve a **"Seguridad" → "Autenticación 2FA"**
3. Haz clic en **"Activar 2FA"**

#### **Paso 2: Configurar Aplicación Autenticadora**
1. **Instala** una aplicación autenticadora:
   - **Google Authenticator** (Android/iOS)
   - **Authy** (Android/iOS/Desktop)
   - **Microsoft Authenticator** (Android/iOS)

2. **Escanea** el código QR que aparece en pantalla
3. **Ingresa** el código de 6 dígitos que aparece en la app
4. **Confirma** la activación

#### **Paso 3: Guardar Códigos de Respaldo**
1. **Descarga** los códigos de respaldo
2. **Guárdalos** en un lugar seguro (no en el teléfono)
3. **Úsalos** si pierdes el acceso a tu aplicación autenticadora

### **🔄 Uso Diario de 2FA**

#### **Login con 2FA:**
```
1. Usuario: [tu_usuario]
2. Contraseña: [tu_contraseña]
3. CAPTCHA: [resolver operación]
4. Código 2FA: [123456] ← Código de 6 dígitos de tu app
```

#### **Códigos de Respaldo:**
```
Si no tienes tu teléfono:
1. Haz clic en "Usar código de respaldo"
2. Ingresa uno de tus códigos de respaldo
3. El código usado se desactiva automáticamente
```

### **⚠️ Problemas Comunes con 2FA**

| **Problema** | **Solución** |
|--------------|--------------|
| **"Código incorrecto"** | Verificar que el reloj del teléfono esté sincronizado |
| **"Código expirado"** | Los códigos cambian cada 30 segundos, usar el más reciente |
| **"Perdí mi teléfono"** | Usar códigos de respaldo para desactivar 2FA |
| **"App no funciona"** | Verificar que la app esté actualizada |
| **"QR no se escanea"** | Ingresar manualmente la clave secreta |

### **🔧 Gestión de 2FA**

#### **Desactivar 2FA:**
1. Ve a **"Seguridad" → "Autenticación 2FA"**
2. Haz clic en **"Desactivar 2FA"**
3. **Confirma** con tu contraseña

#### **Regenerar Códigos de Respaldo:**
1. Ve a **"Seguridad" → "Autenticación 2FA"**
2. Haz clic en **"Regenerar códigos"**
3. **Descarga** los nuevos códigos
4. **Guárdalos** en un lugar seguro

---

## 🔧 **Resolución de Problemas** {#resolución-de-problemas}

### **🚨 Problemas de Acceso**

#### **"Usuario o contraseña incorrectos"**
- ✅ Verificar que las credenciales sean correctas
- ✅ Verificar que no haya espacios extra
- ✅ Contactar al administrador si persiste

#### **"Demasiados intentos"**
- ✅ Esperar 5 minutos antes de volver a intentar
- ✅ Verificar que no haya alguien más intentando acceder
- ✅ Contactar al administrador si es urgente

#### **"Sesión expirada"**
- ✅ La sesión expira después de 30 minutos de inactividad
- ✅ Iniciar sesión nuevamente
- ✅ Usar "Recordar sesión" si está disponible

### **🔄 Problemas de Funcionalidad**

#### **"No puedo ver ciertas secciones"**
- ✅ Verificar que tu rol tenga permisos para esa sección
- ✅ Contactar al administrador para verificar permisos
- ✅ Refrescar la página (F5)

#### **"Los datos no se guardan"**
- ✅ Verificar que todos los campos requeridos estén completos
- ✅ Verificar la conexión a internet
- ✅ Intentar guardar nuevamente
- ✅ Contactar al administrador si persiste

#### **"La página carga lentamente"**
- ✅ Verificar la conexión a internet
- ✅ Cerrar otras pestañas del navegador
- ✅ Refrescar la página (F5)
- ✅ Contactar al administrador si persiste

### **📱 Problemas con 2FA**

#### **"Código 2FA incorrecto"**
- ✅ Verificar que el reloj del teléfono esté sincronizado
- ✅ Usar el código más reciente (cambia cada 30 segundos)
- ✅ Verificar que la app esté actualizada

#### **"Perdí mi teléfono"**
- ✅ Usar códigos de respaldo
- ✅ Contactar al administrador para desactivar 2FA
- ✅ Configurar 2FA en un nuevo dispositivo

### **🆘 Contacto de Soporte**

#### **Para Problemas Técnicos:**
- **Administrador del Sistema**: [contacto_admin]
- **Email**: [email_soporte]
- **Teléfono**: [telefono_soporte]

#### **Información a Incluir en Reportes:**
- **Usuario afectado**: [tu_usuario]
- **Rol**: [tu_rol]
- **Descripción del problema**: [detalles]
- **Pasos para reproducir**: [qué hiciste antes del error]
- **Mensaje de error**: [texto exacto del error]
- **Navegador**: [Chrome, Firefox, etc.]
- **Fecha y hora**: [cuándo ocurrió]

---

## 📞 **Contacto y Soporte**

### **🏫 Escuela E.E.S.T N°2**
- **Dirección**: [dirección_escuela]
- **Teléfono**: [telefono_escuela]
- **Email**: [email_escuela]
- **Horario**: [horario_atencion]

### **💻 Soporte Técnico**
- **Administrador**: [nombre_admin]
- **Email**: [email_admin]
- **Teléfono**: [telefono_admin]
- **Horario**: [horario_soporte]

### **📚 Documentación Adicional**
- **Manual de Administrador**: `docs/admin/ADMIN_GUIDE.md`
- **Guía de Seguridad**: `docs/security/SECURITY_GUIDE.md`
- **Documentación Técnica**: `docs/technical/`

---

## 🎯 **Conclusión**

Esta guía te ayudará a utilizar eficientemente el **Sistema Administrativo E.E.S.T N°2**. 

### **📋 Puntos Clave a Recordar:**
- ✅ **Cada rol** tiene permisos específicos
- ✅ **2FA** es obligatorio para mayor seguridad
- ✅ **La sesión** expira después de 30 minutos de inactividad
- ✅ **Los datos** se guardan automáticamente
- ✅ **El soporte** está disponible para ayudarte

### **🚀 Próximos Pasos:**
1. **Configura** tu autenticación 2FA
2. **Explora** las funcionalidades de tu rol
3. **Contacta** al soporte si necesitas ayuda
4. **Mantén** actualizada esta guía

---

**📅 Última actualización**: Diciembre 2024  
**🔄 Versión**: 2.0  
**👥 Para**: Todos los usuarios del Sistema Administrativo E.E.S.T N°2  
**🏆 Estado**: **Completo y Actualizado**

---

*¡Gracias por usar el Sistema Administrativo E.E.S.T N°2!* 🎉
