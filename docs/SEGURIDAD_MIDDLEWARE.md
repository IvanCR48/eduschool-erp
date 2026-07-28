# Seguridad en `src/` y Middleware

## Panorama General de `src/`

El directorio `src/` concentra todo el código PHP orientado a dominio y seguridad:

- `controllers/`: orquestan flujos HTTP, delegan a servicios y activan logs/auditoría.
- `services/`: encapsulan reglas de negocio y seguridad (ej. `ServicioSeguridad`, `MFAService`, `ServicioLogging`).
- `middleware/`: filtros reutilizables que endurecen cada request antes de llegar a los controladores.
- `mappers/`, `models/`, `DTOs/`: acceden a datos de manera tipada y consistente.
- `adapters/` y `contracts/`: desacoplan infraestructura (base de datos, caché, etc.).

Los controladores invocan los middlewares (directamente o a través de `SecurityMiddleware`) y se apoyan en los servicios para tareas específicas: rate limiting, CSP, logging, MFA, protección de sesiones, etc.

## Cadena Central: `SecurityMiddleware`

Archivo: `src/middleware/SecurityMiddleware.php`

### Flujo `handle()`

1. **Headers seguros** vía `ServicioSeguridad::configurarHeadersSeguridad()` (X-Frame-Options, CSP, HSTS, Policies, CORS).
2. **Límites del request**: tamaño general, headers, payload (`verificarLimitesRequest`).  
3. **Detección de patrones maliciosos** (`detectarPatronesSospechosos`): path traversal, XSS, SQLi, data URIs.
4. **Rate limiting** mejorado (`verificarRateLimitAvanzado`): combina intentos en sesión con métricas en base.
5. **IP en listas** (`verificarIPListaNegra`).
6. **CSRF** (`validateCSRFToken`) sólo en POST. Recurre a `ServicioSeguridad->verificarTokenCSRF()`.
7. **User-Agent** (`validateUserAgent`): impide clientes vacíos, demasiado largos o conocidos por pentesting.
8. **Uploads** (`validateFileUploads`): tamaño máximo (5 MB), MIME real permitido, extensiones blanqueadas.
9. **Evaluación final** (`isRequestSecure`): si alguna prueba falla, responde 403 (`blockInsecureRequest`) y registra auditoría.

El método `logSecurityViolation` delega en `ServicioLogging::registrarEventoSeguridad` y almacena la telemetría del request (violation list, IP, UA, URI, método).

## Middleware Especializados

### `SecurityHeadersMiddleware`

- Forza **headers de respuesta**: charset, CSP con nonce, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Policies modernas (Permissions/Feature, COEP, COOP, CORP, Expect-CT, Origin-Agent-Cluster).
- Añade `Strict-Transport-Security` en HTTPS.
- Limpia metadatos (`Server`, `X-Powered-By`) para evitar disclosure.
- Gestiona **cache busting** para rutas sensibles.
- Valida parámetros de redirección (`isValidRedirect`) para bloquear **open redirects**. Ejemplo: rutas absolutas se cotejan contra dominios autorizados (`sistema.eest2.edu.ar`, `localhost`, etc.).
- `applyAPIHeaders()` extiende con CORS controlado, pre-flight OPTIONS, y tipo `application/json`.
- `applyDownloadHeaders()` endurece descargas (`Content-Disposition`, `Content-Length`, cache no persistente).

### `SessionSecurityMiddleware`

- **Configura sesión** segura: cookies `HttpOnly`, `Secure`, `SameSite=Strict`, `session.use_strict_mode` y regeneración periódica (cada 15 min o tras login) para mitigar fixation.
- **Integrity checks**: compara IP y User-Agent almacenados; expira sesiones con más de 1 h.
- **CSRF tokens** de un solo uso (colección indexada por hora), limpieza automática de tokens antiguos.
- Métodos utilitarios para *destroy* y detección de actividad sospechosa (intentos de login por IP, cambios bruscos de UA).

### `FileSecurityMiddleware`

- Normaliza rutas (`normalizePath`), detecta traversal (`containsDirectoryTraversal`), verifica pertenencia a directorios permitidos y existencia física.  
- Bloquea nombres con caracteres peligrosos y sanitiza (`sanitizeFileName`).  
- Comprueba tipo real (`finfo`) y NO confía en extensiones. `isExecutableFile` evita subir scripts o binarios (`exe`, `php`, `js`, `sh`, etc.).  
- Crea temporales seguros (`0600`) y limpia artefactos antiguos (`cleanupOldTempFiles`).

### `HTTPParameterPollutionProtectionMiddleware`

- `cleanParameters`: ante valores múltiples en GET/POST/COOKIE toma sólo el primero y sanitiza. Registra en log si detecta posible **HPP** (HTTP Parameter Pollution).
- Limita tamaño total de parámetros (`validateParameterSize` ≤ 10 KB).  
- `cleanURL` y `detectHPPAttempt` analizan query strings duplicados antes de redirecciones.
- Expone limpieza de headers (`cleanHTTPHeaders`) para prevenir inyección vía `$_SERVER`.

### `InjectionProtectionMiddleware`

- Patrones específicos para **SQLi**, **LDAPi** y **Command Injection** (`detectSQLInjection`, `detectLDAPInjection`, `detectCommandInjection`). Comprueba palabras clave, operadores, redirecciones de shell, secuencias hexadecimales, funciones PHP críticas, etc.
- Métodos de sanitización: `sanitizeSQLInput`, `sanitizeLDAPInput`, `sanitizeCommandInput` (USA `escapeshellarg`).  
- `validateInput` y `validateArrayParameters` devuelven amenazas encontradas y versiones higienizadas.

### Otros Middleware

- `OpenRedirectProtectionMiddleware`: utilidades para validar URLs y rutas antes de `header('Location')`.
- `SSRFProtectionMiddleware`: sanitiza endpoints remotos, valida protocolos (`http/https`), limita dominios internos, evalúa DNS/IP contra rangos privados.
- `TimingAttackProtectionMiddleware`: provee comparación constante (`hash_equals`) y retardos aleatorios para respuestas sensibles.
- `XXEProtectionMiddleware`: configura `libxml` (`LIBXML_NONET`, `disableEntityLoader`) antes de parsear XML; valida payload contra DTD externos.
- `UploadSecurityMiddleware`: comprueba contenido binario, renombra con hash, fuerza antivirus (si está disponible), y usa `FileSecurityMiddleware` como backend.
- `MonitoringMiddleware`: centraliza métricas para `SystemMonitoringService` y `ServicioLogging`, anotando headers, latencia y sospechas.

> Todos los middlewares se combinan según la ruta. `SecurityMiddleware` actúa como “macro filtro” y puede delegar en los especializados; los controladores invocan los específicos cuando realizan operaciones críticas (ej. subir archivos, consumir APIs externas, procesar XML).

## Servicios Clave que Respaldan la Seguridad

- `ServicioSeguridad`  
  Gestiona CSRF, rate limiting en sesión, validaciones de entrada, hashing Argon2id, tokens de recuperación, CORS, headers, detección de patrones peligrosos (`$suspiciousPatterns`), tamaño de request (`maxRequestSize`, `maxHeaderSize`), y sanitización de headers.  
  También expone `obtenerIPCliente` con soporte para proxies y Cloudflare, e integra `ServicioLogging` para eventos críticos.

- `ServicioLogging`  
  Recibe eventos de los middleware y los clasifica (`LOGIN_FAILED`, `MFA_FAILED`, `SECURITY_VIOLATION`, etc.), persistiendo en `logs_seguridad` o `logs_auditoria`. Incluye serialización segura (`json_encode` con `JSON_UNESCAPED_UNICODE`) para evitar corrupción de datos.

- `SystemMonitoringService`  
  Usa la data de `logs_seguridad` y las métricas emitidas por middleware para alimentar paneles (`admin/admin_tools.php`) con totales de logins fallidos, IPs destacadas, usuarios bloqueados, amenazas, etc.

- `MFAService`  
  Trabaja junto al middleware y al login para validar TOTP, generar códigos de respaldo y registrar eventos (éxitos/fallos) en logs de seguridad.

- `UtilityService`  
  Ofrece funciones auxiliares (`sanitizeText`, `sanitizeHeaderValue`, generación de tokens aleatorios, regex de validación), utilizadas por `ServicioSeguridad` y varios middleware.

## Buenas Prácticas para Integrar Middleware

- **Siempre** invocar `SecurityHeadersMiddleware::applyHeaders()` al inicio de cada vista pública/protegida.  
- Antes de procesar formularios POST:  
  - Validar CSRF con `SessionSecurityMiddleware::validateCSRFToken` o `SecurityMiddleware::handle()`.  
  - Limpiar parámetros con `HTTPParameterPollutionProtectionMiddleware::cleanParameters`.  
  - Revisar payloads sospechosos (`InjectionProtectionMiddleware::validateArrayParameters`).  
- Para descargas/subidas:  
  - Sanitizar nombres y rutas vía `FileSecurityMiddleware`.  
  - Usar `UploadSecurityMiddleware` para mover archivos a carpetas definitivas.  
- Al consumo de APIs externas: `SSRFProtectionMiddleware::validateURL`.  
- Tras login o acciones sensibles: regenerar sesión (`SessionSecurityMiddleware::configureSecureSession`) y registrar auditorías (`ServicioLogging`).  
- Ante cualquier denegación de seguridad: además de la respuesta 403, loguear con suficiente contexto para análisis (`registrarEventoSeguridad`).

## Referencias Rápidas

- `src/middleware/SecurityMiddleware.php` – orquestador general.  
- `src/services/ServicioSeguridad.php` – utilidades de protección, headers, rate limiting.  
- `src/services/ServicioLogging.php` – persistencia de eventos.  
- `docs/security/SEGURIDAD_UNIFICADA.md` – visión global complementar a este documento.

Con este stack, el proyecto cubre los controles OWASP más habituales: validación de entrada, protección CSRF/XSS/XXE, headers defensivos, sesión reforzada, reducción de superficie para SSRF/open redirect, y registro exhaustivo para auditoría y monitoreo.

