# 🛠️ Guía de Herramientas Administrativas

> **✅ HERRAMIENTAS COMPLETAMENTE FUNCIONALES** - Sistema optimizado y listo para usar

## 📋 Índice
1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Panel de Monitoreo](#panel-de-monitoreo)
4. [Sistema de Backups](#sistema-de-backups)
5. [Configuración del Sistema](#configuración-del-sistema)
6. [Mantenimiento](#mantenimiento)
7. [API REST](#api-rest)

---

## 🎯 Introducción

El sistema de Herramientas Administrativas proporciona un panel completo para administradores que permite:

- **Monitorear** el estado del sistema en tiempo real
- **Crear y restaurar** backups completos
- **Configurar** parámetros del sistema
- **Realizar tareas** de mantenimiento
- **Ver alertas** y problemas del sistema

## 🔐 Acceso al Sistema

### Requisitos
- Rol: **Administrador** (admin)
- Permiso: `administrar_sistema`

### URL de Acceso
```
https://tu-dominio.com/admin_tools.php
```

El enlace aparece en el menú lateral bajo "Administración" → "Herramientas Admin"

---

## 📊 Panel de Monitoreo

### Métricas Disponibles

#### 1. **Memoria PHP**
- Uso actual de memoria
- Pico máximo de memoria
- Límite configurado

#### 2. **Base de Datos**
- Tamaño total de la base de datos
- Número de tablas
- Conexiones activas

#### 3. **Espacio en Disco**
- Porcentaje de uso
- Espacio libre disponible
- Espacio total

#### 4. **Usuarios Activos**
- Sesiones activas actuales
- Total de usuarios registrados

### Indicador de Salud

El sistema calcula automáticamente la salud general del sistema basándose en:
- Tiempo de respuesta
- Consultas lentas
- Uso de recursos

**Niveles:**
- 🟢 **Excelente** (90-100): Sistema funcionando óptimamente
- 🔵 **Bueno** (75-89): Sistema funcionando bien
- 🟡 **Precaución** (60-74): Revisar configuración
- 🔴 **Crítico** (<60): Requiere atención inmediata

### Métricas de Seguridad

- Logins fallidos en las últimas 24 horas
- Usuarios bloqueados
- Sesiones sospechosas
- Nivel de amenaza (bajo, medio, alto, crítico)

### Actualización Automática

Las métricas se actualizan automáticamente cada **30 segundos** cuando estás en la pestaña de monitoreo.

---

## 💾 Sistema de Backups

### Crear Backup Manual

1. Ir a la pestaña **"Backups"**
2. Clic en **"Crear Backup Ahora"**
3. El sistema creará un backup completo que incluye:
   - Base de datos completa (estructura y datos)
   - Archivos de configuración
   - Logs del sistema

### Formato del Backup

```
backup_completo_YYYY-MM-DD_HH-mm-ss.zip
```

**Contenido:**
```
backup_completo_2025-09-30_12-30-00/
├── database.sql          (Base de datos completa)
├── config/              (Archivos de configuración)
└── logs/                (Logs del sistema)
```

### Descargar Backup

1. En la lista de backups recientes
2. Clic en **"Descargar"**
3. El archivo ZIP se descargará a tu computadora

### Restaurar Backup

⚠️ **ADVERTENCIA:** Esta acción sobrescribirá los datos actuales

1. En la lista de backups
2. Clic en **"Restaurar"**
3. Confirmar la acción
4. El sistema restaurará automáticamente la base de datos

### Configuración de Backups Automáticos

En la pestaña **"Configuración"** → Sección **"Backup"**:

- `backup.automatico`: Activar/desactivar backups automáticos
- `backup.frecuencia`: diario, semanal
- `backup.hora`: Hora de ejecución (formato 24h)
- `backup.max_backups`: Número máximo de backups a mantener (default: 30)

### Limpieza Automática

El sistema mantiene automáticamente los últimos **30 backups** y elimina los más antiguos.

---

## ⚙️ Configuración del Sistema

### Categorías de Configuración

#### 1. **Sistema**
- `sistema.nombre`: Nombre del sistema
- `sistema.timezone`: Zona horaria
- `sistema.mantenimiento`: Modo mantenimiento (0/1)

#### 2. **Seguridad**
- `seguridad.max_intentos_login`: Intentos fallidos permitidos (default: 5)
- `seguridad.tiempo_bloqueo`: Minutos de bloqueo (default: 30)
- `seguridad.sesion_duracion`: Duración de sesión en minutos (default: 480)
- `seguridad.requiere_2fa`: Obligar 2FA para todos (0/1)
- `seguridad.password_min_longitud`: Longitud mínima de contraseña (default: 8)

#### 3. **Backup**
- `backup.automatico`: Backups automáticos (0/1)
- `backup.frecuencia`: Frecuencia (diario/semanal)
- `backup.hora`: Hora de ejecución (HH:MM)
- `backup.max_backups`: Máximo de backups a mantener

#### 4. **Notificaciones**
- `notificaciones.email_activo`: Notificaciones por email (0/1)
- `notificaciones.email_admin`: Email del administrador

#### 5. **Rendimiento**
- `rendimiento.cache_activo`: Sistema de caché (0/1)
- `rendimiento.cache_duracion`: Duración del caché en segundos
- `rendimiento.logs_nivel`: Nivel de logging (DEBUG, INFO, WARNING, ERROR)

#### 6. **Académico**
- `academico.anio_lectivo`: Año lectivo actual
- `academico.periodo_actual`: Período académico

### Guardar Cambios

1. Modificar los valores deseados
2. Clic en **"Guardar Cambios"**
3. Los cambios se aplicarán inmediatamente

### Exportar/Importar Configuración

**Exportar:**
- Clic en "Exportar Configuración"
- Se descarga archivo JSON con toda la configuración

**Importar:**
- Subir archivo JSON de configuración
- La configuración se aplicará al sistema

### Restaurar Valores por Defecto

⚠️ Esto restaurará TODA la configuración a valores por defecto

---

## 🔧 Mantenimiento

### Limpiar Caché

**¿Qué hace?**
- Elimina caché de configuración
- Elimina entradas de caché expiradas
- Libera memoria

**Cuándo usar:**
- Después de cambios importantes en configuración
- Si el sistema se comporta de forma extraña
- Mantenimiento regular

### Optimizar Base de Datos

**¿Qué hace?**
- Ejecuta `OPTIMIZE TABLE` en todas las tablas
- Desfragmenta tablas
- Recupera espacio no utilizado
- Reconstruye índices

**Cuándo usar:**
- Mensualmente como mantenimiento preventivo
- Después de eliminar muchos registros
- Si las consultas son lentas

**Tiempo estimado:** 1-5 minutos dependiendo del tamaño de la base de datos

### Verificar Integridad del Sistema

Verifica:
- ✅ Permisos de directorios críticos
- ✅ Extensiones PHP requeridas
- ✅ Conexión a base de datos
- ✅ Archivos de configuración

---

## 🔌 API REST

### Endpoint Base
```
/api/admin_tools_api.php
```

### Autenticación
Requiere sesión válida con permisos de administrador

### Endpoints Disponibles

#### 1. Obtener Métricas
```http
GET /api/admin_tools_api.php?action=metricas
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "metricas": {
      "sistema": { ... },
      "base_datos": { ... },
      "aplicacion": { ... },
      "seguridad": { ... },
      "rendimiento": { ... }
    }
  }
}
```

#### 2. Obtener Historial
```http
GET /api/admin_tools_api.php?action=historial&horas=24
```

**Parámetros:**
- `horas`: Número de horas de historial (default: 24)

#### 3. Obtener Alertas
```http
GET /api/admin_tools_api.php?action=alertas
```

#### 4. Información del Sistema
```http
GET /api/admin_tools_api.php?action=info_sistema
```

#### 5. Verificar Integridad
```http
GET /api/admin_tools_api.php?action=verificar_integridad
```

### Ejemplo de Uso (JavaScript)

```javascript
// Obtener métricas
fetch('api/admin_tools_api.php?action=metricas')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Métricas:', data.data.metricas);
    }
  });

// Obtener alertas
fetch('api/admin_tools_api.php?action=alertas')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Alertas:', data.data.alertas);
    }
  });
```

---

## 🚨 Alertas del Sistema

### Tipos de Alertas

#### 🟡 **Warning** (Advertencia)
- Uso de memoria cercano al límite (>90%)
- Espacio en disco bajo (>80%)
- Logins fallidos moderados

#### 🔴 **Error** (Error)
- Espacio en disco crítico (>90%)
- Nivel de amenaza crítico
- Múltiples sesiones sospechosas

### Visualización

Las alertas aparecen automáticamente en la parte superior del dashboard cuando:
- Se detecta una condición de alerta
- Las métricas superan umbrales definidos

---

## 📈 Mejores Prácticas

### Backups

1. ✅ Crear backup **antes** de actualizaciones importantes
2. ✅ Verificar backups mensualmente
3. ✅ Mantener backups fuera del servidor (descargar)
4. ✅ Documentar cambios importantes

### Monitoreo

1. ✅ Revisar métricas diariamente
2. ✅ Atender alertas inmediatamente
3. ✅ Monitorear tendencias de uso
4. ✅ Mantener logs organizados

### Mantenimiento

1. ✅ Optimizar base de datos mensualmente
2. ✅ Limpiar caché después de cambios
3. ✅ Revisar configuración regularmente
4. ✅ Actualizar PHP y dependencias

### Seguridad

1. ✅ Revisar logins fallidos diariamente
2. ✅ Investigar sesiones sospechosas
3. ✅ Mantener configuración de seguridad actualizada
4. ✅ Habilitar 2FA para todos los administradores

---

## 🐛 Solución de Problemas

### El sistema está lento

1. Verificar métricas de rendimiento
2. Optimizar base de datos
3. Limpiar caché
4. Revisar consultas lentas

### No puedo crear backups

1. Verificar permisos del directorio `backups/`
2. Verificar espacio en disco
3. Revisar logs de error

### Las métricas no se actualizan

1. Verificar que JavaScript esté habilitado
2. Revisar consola del navegador para errores
3. Verificar que la API esté accesible

### Errores de permisos

1. Verificar que tienes rol de administrador
2. Revisar configuración de permisos en `PermissionService`

---

## 📞 Soporte

Para problemas o dudas sobre las herramientas administrativas:

1. Revisar logs en `logs/audit.log` y `logs/security.log`
2. Verificar integridad del sistema
3. Consultar documentación técnica en `docs/`

---

**Última actualización:** 30 de Septiembre de 2025  
**Versión:** 1.0.0
