<?php
$pageTitle = 'Guía de Desarrollo - Documentación EEST2';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/SistemaAdmin/css/style.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <link rel="stylesheet" href="/SistemaAdmin/css/docs.css">
    
</head>
<body>
    <div class="doc-container">
        <div class="doc-header">
            <h1><i class="fas fa-laptop-code"></i> Guía de Desarrollo</h1>
            <p>Documentación técnica completa para desarrolladores del Sistema EEST2</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-info-circle"></i> Introducción al Desarrollo</h2>
            
            <div class="info-box">
                <p><i class="fas fa-code"></i> <strong>Arquitectura del Sistema:</strong> El Sistema EEST2 está construido con PHP 8+, MySQL 8+, y utiliza un patrón MVC (Model-View-Controller) con arquitectura de servicios.</p>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-sitemap"></i> Estructura del Proyecto</h4>
                
                <div class="step-box">
                    <p><strong>Organización de directorios:</strong></p>
                    <ul>
                        <li><strong>src/:</strong> Código fuente principal</li>
                        <li><strong>public/:</strong> Archivos públicos y punto de entrada</li>
                        <li><strong>config/:</strong> Configuraciones del sistema</li>
                        <li><strong>docs/:</strong> Documentación técnica</li>
                        <li><strong>tests/:</strong> Pruebas unitarias y de integración</li>
                        <li><strong>vendor/:</strong> Dependencias de Composer</li>
                    </ul>
                </div>
                
                <h4>Estructura detallada:</h4>
                <div class="code-block"><code>SistemaAdmin/
├── src/
│   ├── controllers/     # Controladores MVC
│   ├── models/         # Modelos de datos
│   ├── services/       # Lógica de negocio
│   ├── middleware/     # Middleware de seguridad
│   ├── DTOs/          # Data Transfer Objects
│   ├── mappers/       # Mapeo de datos
│   └── exceptions/    # Excepciones personalizadas
├── public/
│   ├── login.php      # Punto de entrada
│   ├── errors/        # Páginas de error
│   └── logs/          # Logs del sistema
├── config/
│   ├── database.php   # Configuración de BD
│   └── production.php # Configuración de producción
└── docs/              # Documentación</code></div>
            </div>
            
            <h2><i class="fas fa-tools"></i> Configuración del Entorno de Desarrollo</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-download"></i> Requisitos del Sistema</h4>
                
                <div class="step-box">
                    <p><strong>Software requerido:</strong></p>
                    <ul>
                        <li><strong>PHP:</strong> 8.0 o superior</li>
                        <li><strong>MySQL:</strong> 8.0 o superior</li>
                        <li><strong>Composer:</strong> Para gestión de dependencias</li>
                        <li><strong>Git:</strong> Para control de versiones</li>
                        <li><strong>Servidor web:</strong> Apache 2.4+ o Nginx 1.18+</li>
                    </ul>
                </div>
                
                <h4>Extensiones PHP requeridas:</h4>
                <div class="success-box checklist-box">
                    <ul>
                        <li>☐ PDO MySQL</li>
                        <li>☐ OpenSSL</li>
                        <li>☐ JSON</li>
                        <li>☐ Mbstring</li>
                        <li>☐ GD</li>
                        <li>☐ Zip</li>
                        <li>☐ XML</li>
                        <li>☐ Curl</li>
                    </ul>
                </div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-cog"></i> Configuración del Entorno</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar el entorno de desarrollo:</strong></p>
                    <ol>
                        <li>Clona el repositorio:
                            <div class="code-block"><code>git clone https://github.com/tu-usuario/sistema-admin-eest2.git
cd sistema-admin-eest2</code></div>
                        </li>
                        <li>Instala las dependencias:
                            <div class="code-block"><code>composer install</code></div>
                        </li>
                        <li>Copia el archivo de configuración:
                            <div class="code-block"><code>cp .env.example .env</code></div>
                        </li>
                        <li>Configura las variables de entorno en .env</li>
                        <li>Importa la base de datos:
                            <div class="code-block"><code>mysql -u root -p < database/sistema_admin_eest2.sql</code></div>
                        </li>
                        <li>Configura los permisos:
                            <div class="code-block"><code>chmod -R 755 .
chmod -R 777 logs/ backups/</code></div>
                        </li>
                    </ol>
                </div>
            </div>
            
            <h2><i class="fas fa-code"></i> Arquitectura y Patrones</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-layer-group"></i> Patrón MVC</h4>
                
                <div class="step-box">
                    <p><strong>Implementación del patrón MVC:</strong></p>
                    <ul>
                        <li><strong>Modelos:</strong> Representan los datos y la lógica de negocio</li>
                        <li><strong>Vistas:</strong> Interfaz de usuario (HTML, CSS, JS)</li>
                        <li><strong>Controladores:</strong> Manejan las peticiones y coordinan</li>
                    </ul>
                </div>
                
                <h4>Ejemplo de controlador:</h4>
                <div class="code-block"><code>&lt;?php
namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\EstudianteService;
use SistemaAdmin\DTOs\ResponseDTO;

class EstudianteController
{
    private EstudianteService $estudianteService;
    
    public function __construct()
    {
        $this->estudianteService = new EstudianteService();
    }
    
    public function index(): ResponseDTO
    {
        try {
            $estudiantes = $this->estudianteService->obtenerTodos();
            return new ResponseDTO(true, 'Estudiantes obtenidos', $estudiantes);
        } catch (Exception $e) {
            return new ResponseDTO(false, $e->getMessage());
        }
    }
}</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-puzzle-piece"></i> Arquitectura de Servicios</h4>
                
                <div class="step-box">
                    <p><strong>Servicios principales:</strong></p>
                    <ul>
                        <li><strong>EstudianteService:</strong> Gestión de estudiantes</li>
                        <li><strong>ProfesorService:</strong> Gestión de profesores</li>
                        <li><strong>NotaService:</strong> Gestión de calificaciones</li>
                        <li><strong>AuthService:</strong> Autenticación y autorización</li>
                        <li><strong>ReporteService:</strong> Generación de reportes</li>
                    </ul>
                </div>
                
                <h4>Ejemplo de servicio:</h4>
                <div class="code-block"><code>&lt;?php
namespace SistemaAdmin\Services;

use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\DTOs\EstudianteDTO;

class EstudianteService
{
    private EstudianteMapper $mapper;
    
    public function __construct()
    {
        $this->mapper = new EstudianteMapper();
    }
    
    public function crear(EstudianteDTO $dto): bool
    {
        $estudiante = $this->mapper->dtoToModel($dto);
        return $estudiante->guardar();
    }
    
    public function obtenerPorId(int $id): ?EstudianteDTO
    {
        $estudiante = Estudiante::buscarPorId($id);
        return $estudiante ? $this->mapper->modelToDto($estudiante) : null;
    }
}</code></div>
            </div>
            
            <h2><i class="fas fa-database"></i> Gestión de Base de Datos</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-table"></i> Migraciones y Esquema</h4>
                
                <div class="step-box">
                    <p><strong>Para crear una nueva migración:</strong></p>
                    <ol>
                        <li>Crea el archivo de migración:
                            <div class="code-block"><code>touch database/migrations/YYYY_MM_DD_HHMMSS_nombre_migracion.sql</code></div>
                        </li>
                        <li>Define la estructura de la tabla</li>
                        <li>Ejecuta la migración:
                            <div class="code-block"><code>mysql -u root -p sistema_admin_eest2 < database/migrations/archivo.sql</code></div>
                        </li>
                    </ol>
                </div>
                
                <h4>Ejemplo de migración:</h4>
                <div class="code-block"><code>-- Migración: Crear tabla de materias
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(10) UNIQUE NOT NULL,
    especialidad_id INT,
    horas_semanales INT DEFAULT 0,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
);</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-search"></i> Consultas y Optimización</h4>
                
                <div class="step-box">
                    <p><strong>Mejores prácticas para consultas:</strong></p>
                    <ul>
                        <li><strong>Usar índices:</strong> En campos de búsqueda frecuente</li>
                        <li><strong>Preparar statements:</strong> Para prevenir SQL injection</li>
                        <li><strong>Limitar resultados:</strong> Usar LIMIT y OFFSET</li>
                        <li><strong>Evitar SELECT *:</strong> Seleccionar solo campos necesarios</li>
                    </ul>
                </div>
                
                <h4>Ejemplo de consulta optimizada:</h4>
                <div class="code-block"><code>&lt;?php
// Consulta optimizada con prepared statement
$stmt = $pdo->prepare("
    SELECT e.id, e.nombre, e.apellido, e.email, c.nombre as curso
    FROM estudiantes e
    INNER JOIN cursos c ON e.curso_id = c.id
    WHERE e.activo = 1 AND c.año = ?
    ORDER BY e.apellido, e.nombre
    LIMIT ? OFFSET ?
");

$stmt->execute([$año, $limit, $offset]);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);</code></div>
            </div>
            
            <h2><i class="fas fa-shield-alt"></i> Seguridad en el Desarrollo</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-lock"></i> Validación y Sanitización</h4>
                
                <div class="step-box">
                    <p><strong>Para validar datos de entrada:</strong></p>
                    <ul>
                        <li><strong>Validación del lado servidor:</strong> Siempre validar en PHP</li>
                        <li><strong>Sanitización:</strong> Limpiar datos antes de procesar</li>
                        <li><strong>Escape de salida:</strong> Escapar datos al mostrar</li>
                        <li><strong>Validación de tipos:</strong> Verificar tipos de datos</li>
                    </ul>
                </div>
                
                <h4>Ejemplo de validación:</h4>
                <div class="code-block"><code>&lt;?php
class Validator
{
    public static function validarEstudiante(array $data): array
    {
        $errores = [];
        
        // Validar nombre
        if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres';
        }
        
        // Validar DNI
        if (!preg_match('/^\d{7,8}$/', $data['dni'])) {
            $errores[] = 'El DNI debe tener 7 u 8 dígitos';
        }
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }
        
        return $errores;
    }
    
    public static function sanitizarString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-key"></i> Autenticación y Autorización</h4>
                
                <div class="step-box">
                    <p><strong>Implementación de seguridad:</strong></p>
                    <ul>
                        <li><strong>Hashing de contraseñas:</strong> Usar password_hash()</li>
                        <li><strong>Tokens CSRF:</strong> Proteger formularios</li>
                        <li><strong>Sesiones seguras:</strong> Configurar cookies seguras</li>
                        <li><strong>Rate limiting:</strong> Limitar intentos de login</li>
                    </ul>
                </div>
                
                <h4>Ejemplo de autenticación:</h4>
                <div class="code-block"><code>&lt;?php
class AuthService
{
    public function login(string $email, string $password): bool
    {
        $usuario = Usuario::buscarPorEmail($email);
        
        if (!$usuario || !password_verify($password, $usuario->password)) {
            $this->registrarIntentoFallido($email);
            return false;
        }
        
        $this->iniciarSesion($usuario);
        return true;
    }
    
    private function iniciarSesion(Usuario $usuario): void
    {
        session_start();
        $_SESSION['usuario_id'] = $usuario->id;
        $_SESSION['nombre'] = $usuario->nombre;
        $_SESSION['rol'] = $usuario->rol;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}</code></div>
            </div>
            
            <h2><i class="fas fa-bug"></i> Testing y Debugging</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-vial"></i> Pruebas Unitarias</h4>
                
                <div class="step-box">
                    <p><strong>Para escribir pruebas unitarias:</strong></p>
                    <ol>
                        <li>Instala PHPUnit:
                            <div class="code-block"><code>composer require --dev phpunit/phpunit</code></div>
                        </li>
                        <li>Crea el archivo de configuración phpunit.xml</li>
                        <li>Escribe las pruebas en tests/</li>
                        <li>Ejecuta las pruebas:
                            <div class="code-block"><code>./vendor/bin/phpunit</code></div>
                        </li>
                    </ol>
                </div>
                
                <h4>Ejemplo de prueba unitaria:</h4>
                <div class="code-block"><code>&lt;?php
use PHPUnit\Framework\TestCase;
use SistemaAdmin\Services\EstudianteService;

class EstudianteServiceTest extends TestCase
{
    private EstudianteService $service;
    
    protected function setUp(): void
    {
        $this->service = new EstudianteService();
    }
    
    public function testCrearEstudianteValido(): void
    {
        $dto = new EstudianteDTO();
        $dto->nombre = 'Juan';
        $dto->apellido = 'Pérez';
        $dto->dni = '12345678';
        $dto->email = 'juan@ejemplo.com';
        
        $resultado = $this->service->crear($dto);
        
        $this->assertTrue($resultado);
    }
}</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-search"></i> Debugging y Logging</h4>
                
                <div class="step-box">
                    <p><strong>Para debugging efectivo:</strong></p>
                    <ul>
                        <li><strong>Logs estructurados:</strong> Usar formato JSON</li>
                        <li><strong>Niveles de log:</strong> DEBUG, INFO, WARNING, ERROR</li>
                        <li><strong>Contexto:</strong> Incluir información relevante</li>
                        <li><strong>Rotación:</strong> Evitar logs muy grandes</li>
                    </ul>
                </div>
                
                <h4>Ejemplo de logging:</h4>
                <div class="code-block"><code>&lt;?php
class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['usuario_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        file_put_contents(
            'logs/app.log',
            json_encode($log) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }
}</code></div>
            </div>
            
            <h2><i class="fas fa-rocket"></i> Despliegue y Producción</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-server"></i> Configuración de Producción</h4>
                
                <div class="step-box">
                    <p><strong>Para configurar producción:</strong></p>
                    <ol>
                        <li>Configura variables de entorno específicas</li>
                        <li>Optimiza PHP para producción</li>
                        <li>Configura el servidor web</li>
                        <li>Implementa SSL/TLS</li>
                        <li>Configura monitoreo</li>
                    </ol>
                </div>
                
                <h4>Configuración PHP para producción:</h4>
                <div class="code-block"><code>; php.ini para producción
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
max_execution_time = 30
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1</code></div>
            </div>
            
            <div class="feature-item">
                <h4><i class="fas fa-docker"></i> Containerización</h4>
                
                <div class="step-box">
                    <p><strong>Para usar Docker:</strong></p>
                    <ol>
                        <li>Crea un Dockerfile</li>
                        <li>Configura docker-compose.yml</li>
                        <li>Construye las imágenes</li>
                        <li>Ejecuta los contenedores</li>
                    </ol>
                </div>
                
                <h4>Ejemplo de Dockerfile:</h4>
                <div class="code-block"><code>FROM php:8.1-apache

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar código
COPY . /var/www/html/

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Configurar Apache
RUN a2enmod rewrite

EXPOSE 80</code></div>
            </div>
            
            <h2><i class="fas fa-code-branch"></i> Control de Versiones</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-git"></i> Flujo de Trabajo Git</h4>
                
                <div class="step-box">
                    <p><strong>Flujo de trabajo recomendado:</strong></p>
                    <ul>
                        <li><strong>main:</strong> Rama principal estable</li>
                        <li><strong>develop:</strong> Rama de desarrollo</li>
                        <li><strong>feature/:</strong> Nuevas funcionalidades</li>
                        <li><strong>hotfix/:</strong> Correcciones urgentes</li>
                    </ul>
                </div>
                
                <h4>Comandos Git esenciales:</h4>
                <div class="code-block"><code># Crear nueva funcionalidad
git checkout -b feature/nueva-funcionalidad
git add .
git commit -m "feat: agregar nueva funcionalidad"
git push origin feature/nueva-funcionalidad

# Merge a develop
git checkout develop
git merge feature/nueva-funcionalidad
git push origin develop

# Crear release
git checkout -b release/v1.2.0
git checkout main
git merge release/v1.2.0
git tag v1.2.0</code></div>
            </div>
            
            <h2><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h2>
            
            <div class="feature-item">
                <h4><i class="fas fa-question"></i> FAQ - Desarrollo</h4>
                
                <p><strong>¿Cómo agrego una nueva funcionalidad?</strong><br>
                Crea una rama feature/, implementa la funcionalidad, escribe pruebas y haz merge a develop.</p>
                
                <p><strong>¿Cómo manejo las dependencias?</strong><br>
                Usa Composer para gestionar dependencias PHP. Nunca commits vendor/ al repositorio.</p>
                
                <p><strong>¿Cómo optimizo el rendimiento?</strong><br>
                Usa índices en BD, implementa caché, optimiza consultas y usa CDN para assets estáticos.</p>
                
                <p><strong>¿Cómo implemento nuevas APIs?</strong><br>
                Sigue el patrón REST, usa DTOs para transferencia de datos y documenta los endpoints.</p>
                
                <p><strong>¿Cómo manejo errores?</strong><br>
                Usa excepciones personalizadas, implementa logging estructurado y maneja errores gracefully.</p>
            </div>
            
            <h2><i class="fas fa-lightbulb"></i> Mejores Prácticas</h2>
            
            <div class="success-box">
                <p><i class="fas fa-star"></i> <strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Sigue los estándares PSR de PHP</li>
                    <li>Escribe código autodocumentado</li>
                    <li>Implementa pruebas unitarias</li>
                    <li>Usa control de versiones efectivamente</li>
                    <li>Documenta tu código</li>
                    <li>Mantén la seguridad como prioridad</li>
                    <li>Optimiza para rendimiento</li>
                    <li>Revisa código regularmente</li>
                </ul>
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
