<?php
$pageTitle = 'Documentación API - Sistema EEST2';
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
    <style>
        :root {
            --primary-color: #0ea5a3;
            --primary-dark: #0b7f7e;
            --secondary-color: #4b5563;
            --success-color: #16a34a;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #1f2937;
        }
        
        .doc-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .doc-header {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .doc-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .doc-content {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .doc-content h2 {
            color: #6366f1;
            font-size: 1.8rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #6366f1;
        }
        
        .doc-content h3 {
            color: var(--secondary-color);
            font-size: 1.4rem;
            margin-top: 1.5rem;
        }
        
        .code-block {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            position: relative;
        }
        
        .code-block code {
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .method-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }
        
        .method-get { background: #10b981; color: white; }
        .method-post { background: #3b82f6; color: white; }
        .method-put { background: #f59e0b; color: white; }
        .method-delete { background: #ef4444; color: white; }
        
        .endpoint-box {
            background: #f9fafb;
            border-left: 4px solid #6366f1;
            padding: 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .endpoint-box ul,
        .endpoint-box ol {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 1.5rem 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .info-box ul,
        .info-box ol {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid var(--warning-color);
            padding: 1.5rem 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .warning-box ul,
        .warning-box ol {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .btn-back {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary-color), #374151);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(75, 85, 99, 0.4);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        th {
            background: #6366f1;
            color: white;
            padding: 0.75rem;
            text-align: left;
            font-size: 0.875rem;
        }
        
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }
        
        tr:nth-child(even) {
            background: #f9fafb;
        }
        
        ul { line-height: 2; }
        p { line-height: 1.8; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="doc-container">
        <div class="doc-header">
            <h1><i class="fas fa-code"></i> Documentación API REST</h1>
            <p>Guía completa para integración con el Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-info-circle"></i> Introducción</h2>
            
            <p>El Sistema Administrativo EEST2 proporciona una API RESTful para permitir la integración con sistemas externos y el desarrollo de aplicaciones cliente personalizadas.</p>
            
            <div class="warning-box">
                <strong><i class="fas fa-lock"></i> Importante:</strong> Todos los endpoints requieren autenticación mediante token JWT (JSON Web Token).
            </div>
            
            <h3>Base URL</h3>
            <div class="code-block"><code>https://sistema.eest2.edu.ar/api/v1/</code></div>
            
            <h3>Formato de Respuesta</h3>
            <p>Todas las respuestas son en formato JSON con la siguiente estructura:</p>
            <div class="code-block"><code>{
    "success": true,
    "data": { ... },
    "message": "Operación exitosa",
    "timestamp": "2025-10-26T14:30:00Z"
}</code></div>
            
            <h2><i class="fas fa-key"></i> Autenticación</h2>
            
            <h3>Obtener Token de Acceso</h3>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-post">POST</span> <strong>/auth/login</strong></p>
                <p><strong>Descripción:</strong> Obtiene un token JWT para autenticación.</p>
                
                <p><strong>Parámetros del cuerpo (JSON):</strong></p>
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Tipo</th>
                            <th>Requerido</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>username</td>
                            <td>string</td>
                            <td>Sí</td>
                            <td>Nombre de usuario</td>
                        </tr>
                        <tr>
                            <td>password</td>
                            <td>string</td>
                            <td>Sí</td>
                            <td>Contraseña del usuario</td>
                        </tr>
                    </tbody>
                </table>
                
                <p><strong>Ejemplo de petición:</strong></p>
                <div class="code-block"><code>POST /api/v1/auth/login
Content-Type: application/json

{
    "username": "usuario@eest2.edu.ar",
    "password": "MiPassword123!"
}</code></div>
                
                <p><strong>Respuesta exitosa (200 OK):</strong></p>
                <div class="code-block"><code>{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_in": 3600,
        "user": {
            "id": 123,
            "username": "usuario@eest2.edu.ar",
            "role": "profesor"
        }
    },
    "message": "Autenticación exitosa"
}</code></div>
            </div>
            
            <h3>Uso del Token</h3>
            <p>Una vez obtenido el token, inclúyelo en el header de todas las peticiones:</p>
            <div class="code-block"><code>Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...</code></div>
            
            <h2><i class="fas fa-user-graduate"></i> Endpoints de Estudiantes</h2>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/estudiantes</strong></p>
                <p><strong>Descripción:</strong> Obtiene lista de estudiantes con paginación.</p>
                
                <p><strong>Parámetros de consulta:</strong></p>
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Tipo</th>
                            <th>Default</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>page</td>
                            <td>integer</td>
                            <td>1</td>
                            <td>Número de página</td>
                        </tr>
                        <tr>
                            <td>limit</td>
                            <td>integer</td>
                            <td>20</td>
                            <td>Elementos por página</td>
                        </tr>
                        <tr>
                            <td>curso</td>
                            <td>string</td>
                            <td>-</td>
                            <td>Filtrar por curso</td>
                        </tr>
                        <tr>
                            <td>search</td>
                            <td>string</td>
                            <td>-</td>
                            <td>Buscar por nombre/DNI</td>
                        </tr>
                    </tbody>
                </table>
                
                <p><strong>Ejemplo:</strong></p>
                <div class="code-block"><code>GET /api/v1/estudiantes?page=1&limit=20&curso=4A
Authorization: Bearer {token}</code></div>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/estudiantes/{id}</strong></p>
                <p><strong>Descripción:</strong> Obtiene información detallada de un estudiante.</p>
                
                <p><strong>Respuesta:</strong></p>
                <div class="code-block"><code>{
    "success": true,
    "data": {
        "id": 456,
        "nombre": "Juan",
        "apellido": "Pérez",
        "dni": "12345678",
        "email": "juan.perez@estudiante.eest2.edu.ar",
        "curso": "4A",
        "legajo": "2024-456",
        "fecha_nacimiento": "2008-05-15",
        "domicilio": "Calle Falsa 123",
        "telefono": "1234567890"
    }
}</code></div>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-post">POST</span> <strong>/estudiantes</strong></p>
                <p><strong>Descripción:</strong> Registra un nuevo estudiante.</p>
                <p><strong>Permisos requeridos:</strong> Secretaría o Administrador</p>
                
                <p><strong>Cuerpo de la petición:</strong></p>
                <div class="code-block"><code>{
    "nombre": "María",
    "apellido": "González",
    "dni": "98765432",
    "email": "maria.gonzalez@estudiante.eest2.edu.ar",
    "curso": "1A",
    "fecha_nacimiento": "2011-03-20",
    "domicilio": "Av. Principal 456",
    "telefono": "0987654321",
    "tutor_nombre": "Pedro González",
    "tutor_telefono": "1122334455"
}</code></div>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-put">PUT</span> <strong>/estudiantes/{id}</strong></p>
                <p><strong>Descripción:</strong> Actualiza información de un estudiante.</p>
                <p><strong>Permisos requeridos:</strong> Secretaría o Administrador</p>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-delete">DELETE</span> <strong>/estudiantes/{id}</strong></p>
                <p><strong>Descripción:</strong> Desactiva un estudiante (no elimina, solo marca como inactivo).</p>
                <p><strong>Permisos requeridos:</strong> Administrador</p>
            </div>
            
            <h2><i class="fas fa-clipboard-check"></i> Endpoints de Notas</h2>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/estudiantes/{id}/notas</strong></p>
                <p><strong>Descripción:</strong> Obtiene las notas de un estudiante.</p>
                
                <p><strong>Parámetros de consulta:</strong></p>
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>trimestre</td>
                            <td>integer</td>
                            <td>Filtrar por cuatrimestre (1, 2). Parámetro HTTP: <code>trimestre</code>.</td>
                        </tr>
                        <tr>
                            <td>materia</td>
                            <td>string</td>
                            <td>Filtrar por materia</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-post">POST</span> <strong>/notas</strong></p>
                <p><strong>Descripción:</strong> Carga notas para un estudiante.</p>
                <p><strong>Permisos requeridos:</strong> Profesor (solo sus materias)</p>
                
                <p><strong>Cuerpo de la petición:</strong></p>
                <div class="code-block"><code>{
    "estudiante_id": 456,
    "materia_id": 12,
    "trimestre": 1,
    "calificacion": 8,
    "observaciones": "Buen desempeño"
}</code></div>
            </div>
            
            <h2><i class="fas fa-chalkboard-teacher"></i> Endpoints de Profesores</h2>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/profesores</strong></p>
                <p><strong>Descripción:</strong> Lista todos los profesores.</p>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/profesores/{id}/materias</strong></p>
                <p><strong>Descripción:</strong> Obtiene las materias asignadas a un profesor.</p>
            </div>
            
            <h2><i class="fas fa-book"></i> Endpoints de Cursos y Materias</h2>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/cursos</strong></p>
                <p><strong>Descripción:</strong> Lista todos los cursos activos.</p>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/cursos/{id}/estudiantes</strong></p>
                <p><strong>Descripción:</strong> Obtiene los estudiantes de un curso específico.</p>
            </div>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/materias</strong></p>
                <p><strong>Descripción:</strong> Lista todas las materias.</p>
            </div>
            
            <h2><i class="fas fa-chart-bar"></i> Endpoints de Reportes</h2>
            
            <div class="endpoint-box">
                <p><span class="method-badge method-get">GET</span> <strong>/reportes/estadisticas</strong></p>
                <p><strong>Descripción:</strong> Obtiene estadísticas generales del sistema.</p>
                <p><strong>Permisos requeridos:</strong> Director o Administrador</p>
                
                <p><strong>Respuesta:</strong></p>
                <div class="code-block"><code>{
    "success": true,
    "data": {
        "total_estudiantes": 450,
        "total_profesores": 35,
        "total_cursos": 18,
        "estudiantes_activos": 448,
        "promedio_general": 7.5
    }
}</code></div>
            </div>
            
            <h2><i class="fas fa-exclamation-circle"></i> Códigos de Error</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Solución</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>400</td>
                        <td>Bad Request</td>
                        <td>Verifica los parámetros enviados</td>
                    </tr>
                    <tr>
                        <td>401</td>
                        <td>Unauthorized</td>
                        <td>Token inválido o expirado, vuelve a autenticarte</td>
                    </tr>
                    <tr>
                        <td>403</td>
                        <td>Forbidden</td>
                        <td>No tienes permisos para esta operación</td>
                    </tr>
                    <tr>
                        <td>404</td>
                        <td>Not Found</td>
                        <td>El recurso solicitado no existe</td>
                    </tr>
                    <tr>
                        <td>422</td>
                        <td>Unprocessable Entity</td>
                        <td>Datos de entrada inválidos</td>
                    </tr>
                    <tr>
                        <td>429</td>
                        <td>Too Many Requests</td>
                        <td>Límite de peticiones excedido, espera antes de reintentar</td>
                    </tr>
                    <tr>
                        <td>500</td>
                        <td>Internal Server Error</td>
                        <td>Error del servidor, contacta soporte</td>
                    </tr>
                </tbody>
            </table>
            
            <h2><i class="fas fa-shield-alt"></i> Límites y Rate Limiting</h2>
            
            <div class="info-box">
                <p><strong>Límites de peticiones:</strong></p>
                <ul style="margin-bottom: 0;">
                    <li><strong>General:</strong> 100 peticiones por minuto</li>
                    <li><strong>Login:</strong> 5 intentos por minuto</li>
                    <li><strong>Endpoints de escritura (POST/PUT/DELETE):</strong> 30 por minuto</li>
                </ul>
            </div>
            
            <p>Los headers de respuesta incluyen información sobre límites:</p>
            <div class="code-block"><code>X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1635264000</code></div>
            
            <h2><i class="fas fa-code-branch"></i> Versionado</h2>
            
            <p>La API usa versionado semántico. La versión actual es <strong>v1</strong>.</p>
            <ul>
                <li>Los cambios que rompen compatibilidad incrementan la versión mayor (v2)</li>
                <li>Nuevos endpoints no incrementan versión</li>
                <li>Se notifica con 6 meses de anticipación antes de deprecar una versión</li>
            </ul>
            
            <h2><i class="fas fa-book-open"></i> Recursos Adicionales</h2>
            
            <ul>
                <li><a href="https://postman.com" target="_blank">Postman</a> - Cliente para probar la API</li>
                <li><a href="../development/README.md">Guía de Desarrollo</a></li>
                <li>Colección de Postman: <a href="/api/postman_collection.json">Descargar</a></li>
            </ul>
            
            <div class="info-box">
                <strong><i class="fas fa-envelope"></i> Soporte API:</strong>
                <p style="margin-bottom: 0;">Para consultas sobre la API: api@eest2.edu.ar</p>
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
