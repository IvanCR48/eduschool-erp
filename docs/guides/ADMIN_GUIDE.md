# 👑 Guía de Administrador - Sistema Administrativo E.E.S.T N°2

## 🎯 **Índice del Administrador**

1. [**Introducción para Administradores**](#introducción-para-administradores)
2. [**Gestión de Usuarios**](#gestión-de-usuarios)
3. [**Configuración de Seguridad**](#configuración-de-seguridad)
4. [**Monitoreo del Sistema**](#monitoreo-del-sistema)
5. [**Respaldo y Mantenimiento**](#respaldo-y-mantenimiento)
6. [**Troubleshooting Avanzado**](#troubleshooting-avanzado)

---

## 👑 **Introducción para Administradores** {#introducción-para-administradores}

Como **Administrador** del Sistema Administrativo E.E.S.T N°2, tienes **acceso completo** a todas las funcionalidades y configuraciones del sistema.

### **🔑 Permisos del Administrador**
- ✅ **Gestión completa** de usuarios y roles
- ✅ **Configuración** de seguridad y 2FA
- ✅ **Monitoreo** del sistema en tiempo real
- ✅ **Respaldo** y mantenimiento de datos
- ✅ **Configuración** de permisos y accesos
- ✅ **Logs** de seguridad y auditoría
- ✅ **Métricas** de rendimiento y uso

---

## 👥 **Gestión de Usuarios** {#gestión-de-usuarios}

### **🔐 Crear Nuevo Usuario**

#### **Paso 1: Acceder a Gestión de Usuarios**
```
Menú → Configuración → Gestión de Usuarios
```

#### **Paso 2: Crear Usuario**
```php
// Campos requeridos
Usuario: [username]
Contraseña: [password_segura]
Nombre: [nombre]
Apellido: [apellido]
Email: [email@ejemplo.com]
Rol: [admin|directivo|profesor|preceptor|secretario]
```

#### **Paso 3: Configurar Permisos**
```php
// Permisos por rol
Admin: TODOS los permisos
Directivo: Gestión académica completa
Profesor: Solo sus materias y estudiantes
Preceptor: Solo estudiantes asignados
Secretario: Solo gestión básica
```

### **🔧 Gestionar Roles y Permisos**

#### **Roles Disponibles:**
| **Rol** | **Descripción** | **Permisos Principales** |
|---------|-----------------|-------------------------|
| **admin** | Administrador del sistema | Acceso completo a todo |
| **directivo** | Directivo escolar | Gestión académica completa |
| **profesor** | Docente | Solo sus materias y estudiantes |
| **preceptor** | Preceptor | Solo estudiantes asignados |
| **secretario** | Personal administrativo | Gestión básica de datos |

#### **Configurar Permisos Personalizados:**
```php
// Ejemplo: Crear rol personalizado
$permisosPersonalizados = [
    'ver_estudiantes',
    'editar_estudiantes',
    'ver_notas',
    'crear_llamados'
];
```

### **🔒 Gestión de Contraseñas**

#### **Políticas de Contraseñas:**
- ✅ **Mínimo 8 caracteres**
- ✅ **Al menos 1 mayúscula**
- ✅ **Al menos 1 minúscula**
- ✅ **Al menos 1 número**
- ✅ **Al menos 1 carácter especial**

#### **Forzar Cambio de Contraseña:**
```php
// Para un usuario específico
$usuario->forzar_cambio_password = true;
$usuario->password_expirada = true;
```

#### **Resetear Contraseña:**
```php
// Generar nueva contraseña temporal
$nuevaPassword = generarPasswordTemporal();
$usuario->password = password_hash($nuevaPassword, PASSWORD_ARGON2ID);
$usuario->password_temporal = true;
```

---

## 🛡️ **Configuración de Seguridad** {#configuración-de-seguridad}

### **🔐 Autenticación de Dos Factores (2FA)**

#### **Configuración Global de 2FA:**
```php
// Configurar 2FA obligatorio
$configuracion2FA = [
    'obligatorio' => true,
    'roles_requeridos' => ['admin', 'directivo'],
    'tiempo_ventana' => 30, // segundos
    'codigos_respaldo' => 10
];
```

#### **Gestionar 2FA por Usuario:**
```php
// Verificar estado 2FA
$estado2FA = $mfaService->verificarEstadoMFA($usuarioId);

// Forzar activación 2FA
$mfaService->forzarActivacionMFA($usuarioId);

// Desactivar 2FA (solo en casos especiales)
$mfaService->desactivarMFA($usuarioId, $adminId);
```

### **🚨 Rate Limiting y Protección**

#### **Configurar Rate Limiting:**
```php
// Configuración de rate limiting
$rateLimitConfig = [
    'max_intentos' => 5,
    'ventana_tiempo' => 300, // 5 minutos
    'bloqueo_temporal' => 1800, // 30 minutos
    'ip_whitelist' => ['192.168.1.0/24']
];
```

#### **Gestionar IPs Bloqueadas:**
```php
// Ver IPs bloqueadas
$ipsBloqueadas = $seguridadService->obtenerIPsBloqueadas();

// Desbloquear IP
$seguridadService->desbloquearIP('192.168.1.100');

// Agregar IP a whitelist
$seguridadService->agregarIPWhitelist('192.168.1.100');
```

### **🔍 Logs de Seguridad**

#### **Monitorear Eventos de Seguridad:**
```php
// Ver logs de login
$logsLogin = $loggingService->obtenerLogsLogin($fechaInicio, $fechaFin);

// Ver intentos fallidos
$intentosFallidos = $loggingService->obtenerIntentosFallidos($ip);

// Ver eventos de 2FA
$eventos2FA = $loggingService->obtenerEventos2FA($usuarioId);
```

#### **Alertas de Seguridad:**
```php
// Configurar alertas
$alertas = [
    'intentos_fallidos' => 5,
    'ip_sospechosa' => true,
    'acceso_horario_irregular' => true,
    'cambio_permisos' => true
];
```

---

## 📊 **Monitoreo del Sistema** {#monitoreo-del-sistema}

### **📈 Métricas de Rendimiento**

#### **Acceder a Métricas:**
```
Menú → Administración → Métricas del Sistema
```

#### **Métricas Disponibles:**
- **Tiempo de respuesta** - Duración de cada operación
- **Uso de memoria** - Consumo de RAM
- **Consultas de BD** - Tiempo y frecuencia
- **Sesiones activas** - Usuarios conectados
- **Errores del sistema** - Frecuencia y tipos

#### **Interpretar Métricas:**
```php
// Ejemplo de métricas
$metricas = [
    'tiempo_respuesta_promedio' => '2.5 segundos',
    'memoria_peak' => '128 MB',
    'consultas_por_minuto' => 45,
    'sesiones_activas' => 12,
    'errores_ultima_hora' => 3
];
```

### **🔍 Monitoreo en Tiempo Real**

#### **Dashboard de Monitoreo:**
```php
// Métricas en tiempo real
$monitoreo = [
    'cpu_usage' => '45%',
    'memory_usage' => '67%',
    'disk_usage' => '23%',
    'network_usage' => '12%',
    'active_connections' => 8
];
```

#### **Alertas Automáticas:**
```php
// Configurar umbrales
$umbrales = [
    'cpu_max' => 80,
    'memory_max' => 85,
    'disk_max' => 90,
    'response_time_max' => 5,
    'error_rate_max' => 5
];
```

### **📋 Reportes de Uso**

#### **Generar Reportes:**
```php
// Reporte de uso por usuario
$reporteUso = $monitoringService->generarReporteUso($fechaInicio, $fechaFin);

// Reporte de rendimiento
$reporteRendimiento = $monitoringService->generarReporteRendimiento($dias);

// Reporte de seguridad
$reporteSeguridad = $monitoringService->generarReporteSeguridad($mes);
```

---

## 💾 **Respaldo y Mantenimiento** {#respaldo-y-mantenimiento}

### **🗄️ Respaldo de Datos**

#### **Respaldo Automático:**
```php
// Configurar respaldo automático
$respaldoConfig = [
    'frecuencia' => 'diario',
    'hora' => '02:00',
    'retencion' => 30, // días
    'comprimir' => true,
    'encriptar' => true
];
```

#### **Respaldo Manual:**
```bash
# Respaldo completo de la base de datos
mysqldump -u usuario -p sistema_admin > respaldo_$(date +%Y%m%d_%H%M%S).sql

# Respaldo de archivos
tar -czf archivos_$(date +%Y%m%d_%H%M%S).tar.gz /ruta/sistema/
```

### **🧹 Mantenimiento del Sistema**

#### **Limpieza de Logs:**
```php
// Limpiar logs antiguos
$loggingService->limpiarLogsAntiguos(90); // días

// Limpiar métricas antiguas
$monitoringService->limpiarMetricasAntiguas(90); // días
```

#### **Optimización de Base de Datos:**
```sql
-- Optimizar tablas
OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE estudiantes;
OPTIMIZE TABLE notas;
OPTIMIZE TABLE llamados_atencion;

-- Limpiar índices no utilizados
ANALYZE TABLE usuarios;
ANALYZE TABLE estudiantes;
```

#### **Limpieza de Cache:**
```php
// Limpiar cache de autenticación
$authCacheService->clearAuthCache();

// Limpiar cache de configuración
$configService->clearCache();
```

### **🔄 Actualizaciones del Sistema**

#### **Verificar Actualizaciones:**
```php
// Verificar versión actual
$versionActual = obtenerVersionSistema();

// Verificar actualizaciones disponibles
$actualizaciones = verificarActualizaciones();
```

#### **Proceso de Actualización:**
```bash
# 1. Respaldo completo
./scripts/backup.sh

# 2. Descargar actualización
./scripts/update.sh

# 3. Verificar integridad
./scripts/verify.sh

# 4. Aplicar actualización
./scripts/apply.sh
```

---

## 🔧 **Troubleshooting Avanzado** {#troubleshooting-avanzado}

### **🚨 Problemas Críticos**

#### **Sistema No Responde:**
```bash
# Verificar procesos
ps aux | grep php
ps aux | grep mysql

# Verificar logs de error
tail -f /var/log/apache2/error.log
tail -f /var/log/mysql/error.log

# Verificar espacio en disco
df -h
du -sh /ruta/sistema/
```

#### **Base de Datos No Conecta:**
```php
// Verificar conexión
try {
    $pdo = new PDO($dsn, $usuario, $password);
    echo "Conexión exitosa";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
```

#### **Problemas de Permisos:**
```bash
# Verificar permisos de archivos
ls -la /ruta/sistema/

# Corregir permisos
chmod -R 755 /ruta/sistema/
chown -R www-data:www-data /ruta/sistema/
```

### **🔍 Diagnóstico de Rendimiento**

#### **Análisis de Consultas Lentas:**
```sql
-- Ver consultas lentas
SELECT * FROM mysql.slow_log 
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY query_time DESC;

-- Ver procesos activos
SHOW PROCESSLIST;
```

#### **Análisis de Memoria:**
```php
// Verificar uso de memoria
$memoriaActual = memory_get_usage(true);
$memoriaPeak = memory_get_peak_usage(true);

echo "Memoria actual: " . round($memoriaActual / 1024 / 1024, 2) . " MB";
echo "Memoria peak: " . round($memoriaPeak / 1024 / 1024, 2) . " MB";
```

### **🛠️ Herramientas de Diagnóstico**

#### **Script de Diagnóstico:**
```bash
#!/bin/bash
# diagnostic.sh

echo "=== DIAGNÓSTICO DEL SISTEMA ==="
echo "Fecha: $(date)"
echo ""

echo "=== ESPACIO EN DISCO ==="
df -h
echo ""

echo "=== MEMORIA ==="
free -h
echo ""

echo "=== PROCESOS PHP ==="
ps aux | grep php
echo ""

echo "=== PROCESOS MYSQL ==="
ps aux | grep mysql
echo ""

echo "=== LOGS RECIENTES ==="
tail -20 /var/log/apache2/error.log
```

#### **Monitoreo Continuo:**
```php
// Script de monitoreo
$monitor = new SystemMonitor();
$estado = $monitor->verificarEstadoSistema();

if ($estado['critico']) {
    $monitor->enviarAlerta($estado);
}
```

---

## 📞 **Contacto y Soporte Técnico**

### **🆘 Escalación de Problemas**

#### **Nivel 1 - Problemas Básicos:**
- Problemas de login
- Errores de interfaz
- Consultas simples

#### **Nivel 2 - Problemas Intermedios:**
- Problemas de rendimiento
- Errores de base de datos
- Configuraciones complejas

#### **Nivel 3 - Problemas Críticos:**
- Caídas del sistema
- Pérdida de datos
- Problemas de seguridad

### **📋 Información para Reportes**

#### **Información del Sistema:**
```php
$infoSistema = [
    'version' => '2.0',
    'php_version' => PHP_VERSION,
    'mysql_version' => $mysqlVersion,
    'servidor' => $_SERVER['SERVER_SOFTWARE'],
    'timestamp' => date('Y-m-d H:i:s')
];
```

#### **Logs de Error:**
```php
// Recopilar logs de error
$logs = [
    'php_errors' => error_get_last(),
    'mysql_errors' => $mysqlError,
    'application_errors' => $appErrors
];
```

---

## 🎯 **Mejores Prácticas para Administradores**

### **🔒 Seguridad**
- ✅ **Cambiar contraseñas** por defecto inmediatamente
- ✅ **Activar 2FA** para todos los usuarios administrativos
- ✅ **Monitorear logs** de seguridad diariamente
- ✅ **Actualizar** el sistema regularmente
- ✅ **Respaldar** datos diariamente

### **⚡ Rendimiento**
- ✅ **Monitorear métricas** de rendimiento
- ✅ **Optimizar consultas** de base de datos
- ✅ **Limpiar logs** antiguos regularmente
- ✅ **Verificar espacio** en disco semanalmente
- ✅ **Probar respaldos** mensualmente

### **📊 Mantenimiento**
- ✅ **Revisar reportes** de uso semanalmente
- ✅ **Verificar actualizaciones** mensualmente
- ✅ **Probar procedimientos** de respaldo
- ✅ **Documentar cambios** importantes
- ✅ **Capacitar usuarios** en nuevas funcionalidades

---

## 🏆 **Conclusión**

Como **Administrador** del Sistema Administrativo E.E.S.T N°2, tienes la responsabilidad de mantener el sistema funcionando de manera óptima y segura.

### **📋 Checklist Diario:**
- [ ] Verificar logs de seguridad
- [ ] Monitorear métricas de rendimiento
- [ ] Verificar respaldos automáticos
- [ ] Revisar alertas del sistema

### **📋 Checklist Semanal:**
- [ ] Revisar reportes de uso
- [ ] Limpiar logs antiguos
- [ ] Verificar espacio en disco
- [ ] Actualizar documentación

### **📋 Checklist Mensual:**
- [ ] Probar procedimientos de respaldo
- [ ] Revisar actualizaciones disponibles
- [ ] Analizar tendencias de uso
- [ ] Capacitar usuarios

---

**📅 Última actualización**: Diciembre 2024  
**🔄 Versión**: 2.0  
**👑 Para**: Administradores del Sistema  
**🏆 Estado**: **Completo y Actualizado**

---

*¡Gracias por administrar el Sistema Administrativo E.E.S.T N°2!* 🎉
