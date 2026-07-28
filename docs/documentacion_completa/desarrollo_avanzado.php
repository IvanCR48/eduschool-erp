<?php
/**
 * Desarrollo Avanzado - Sistema Admin EEST2
 * 
 * Documentación completa para desarrolladores y programadores
 */

$pageTitle = 'Desarrollo Avanzado - E.E.S.T N°2';
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
            <h1><i class="fas fa-code-branch"></i> Desarrollo Avanzado</h1>
            <p>Sistema Administrativo E.E.S.T N°2 "Educación y Trabajo"</p>
        </div>
        
        <div class="doc-content">
            <h2><i class="fas fa-laptop-code"></i> Introducción al Desarrollo</h2>
            <p>Esta guía está dirigida a desarrolladores que desean contribuir, extender o personalizar el Sistema EEST2. Cubre aspectos técnicos avanzados, arquitectura del sistema, patrones de diseño implementados y mejores prácticas de desarrollo.</p>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Objetivo del Desarrollo</h4>
                <p>Proporcionar una base sólida y extensible para el desarrollo de funcionalidades educativas, manteniendo la calidad del código, seguridad y escalabilidad del sistema.</p>
            </div>

            <h2><i class="fas fa-sitemap"></i> Arquitectura del Sistema</h2>
            
            <div class="dev-grid">
                <div class="dev-card">
                    <h4><i class="fas fa-layer-group"></i> Patrón MVC</h4>
                    <p><strong>Estructura:</strong></p>
                    <ul>
                        <li><strong>Model:</strong> Lógica de negocio y datos</li>
                        <li><strong>View:</strong> Presentación e interfaz</li>
                        <li><strong>Controller:</strong> Control de flujo</li>
                        <li><strong>Services:</strong> Servicios especializados</li>
                        <li><strong>Utilities:</strong> Funciones auxiliares</li>
                    </ul>
                </div>
                
                <div class="dev-card">
                    <h4><i class="fas fa-folder-tree"></i> Estructura de Directorios</h4>
                    <p><strong>Organización:</strong></p>
                    <ul>
                        <li><code>/src/</code> - Código fuente principal</li>
                        <li><code>/config/</code> - Configuraciones</li>
                        <li><code>/public/</code> - Archivos públicos</li>
                        <li><code>/admin/</code> - Panel administrativo</li>
                        <li><code>/docs/</code> - Documentación</li>
                    </ul>
                </div>
                
                <div class="dev-card">
                    <h4><i class="fas fa-database"></i> Capa de Datos</h4>
                    <p><strong>Componentes:</strong></p>
                    <ul>
                        <li><strong>Database:</strong> Singleton para conexiones</li>
                        <li><strong>Models:</strong> Entidades del dominio</li>
                        <li><strong>Repositories:</strong> Acceso a datos</li>
                        <li><strong>Migrations:</strong> Control de versiones</li>
                    </ul>
                </div>
                
                <div class="dev-card">
                    <h4><i class="fas fa-shield-alt"></i> Capa de Seguridad</h4>
                    <p><strong>Implementación:</strong></p>
                    <ul>
                        <li><strong>Authentication:</strong> Autenticación de usuarios</li>
                        <li><strong>Authorization:</strong> Control de acceso</li>
                        <li><strong>Validation:</strong> Validación de datos</li>
                        <li><strong>Sanitization:</strong> Limpieza de entrada</li>
                    </ul>
                </div>
            </div>

            <h2><i class="fas fa-cogs"></i> Configuración del Entorno de Desarrollo</h2>
            
            <h3>Requisitos del Desarrollador</h3>
            <ul>
                <li><strong>PHP 8.0+:</strong> Con extensiones requeridas</li>
                <li><strong>Composer:</strong> Gestión de dependencias</li>
                <li><strong>MySQL/MariaDB:</strong> Base de datos local</li>
                <li><strong>Git:</strong> Control de versiones</li>
                <li><strong>IDE:</strong> VS Code, PhpStorm, o similar</li>
            </ul>

            <h3>Configuración Inicial</h3>
            <div class="code-block">
                <code># Clonar repositorio
git clone https://github.com/tu-repo/sistema-admin-eest2.git
cd sistema-admin-eest2

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
# Editar .env con configuración local

# Configurar base de datos
mysql -u root -p
CREATE DATABASE sistema_admin_eest2_dev;
GRANT ALL ON sistema_admin_eest2_dev.* TO 'dev_user'@'localhost';

# Importar datos de desarrollo
mysql -u dev_user -p sistema_admin_eest2_dev < database/dev_data.sql</code>
            </div>

            <h2><i class="fas fa-code"></i> Patrones de Desarrollo</h2>
            
            <h3>Singleton Pattern (Base de Datos)</h3>
            <div class="code-block">
                <code>class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = new PDO(
            "mysql:host={$host};dbname={$dbname}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}</code>
            </div>

            <h3>Repository Pattern</h3>
            <div class="code-block">
                <code>interface StudentRepositoryInterface {
    public function findById(int $id): ?Student;
    public function findAll(): array;
    public function save(Student $student): bool;
    public function delete(int $id): bool;
}

class StudentRepository implements StudentRepositoryInterface {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }
    
    public function findById(int $id): ?Student {
        $stmt = $this->db->prepare("SELECT * FROM estudiantes WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new Student($data) : null;
    }
}</code>
            </div>

            <h3>Service Layer Pattern</h3>
            <div class="code-block">
                <code>class StudentService {
    private $repository;
    private $validator;
    
    public function __construct(
        StudentRepositoryInterface $repository,
        StudentValidator $validator
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
    }
    
    public function createStudent(array $data): Student {
        // Validar datos
        $this->validator->validate($data);
        
        // Crear entidad
        $student = new Student($data);
        
        // Guardar
        $this->repository->save($student);
        
        return $student;
    }
}</code>
            </div>

            <h2><i class="fas fa-database"></i> Gestión de Base de Datos</h2>
            
            <h3>Migraciones</h3>
            <p>El sistema utiliza un enfoque de migraciones para controlar cambios en la base de datos:</p>
            
            <div class="code-block">
                <code>// Ejemplo de migración
class CreateStudentsTable {
    public function up() {
        $sql = "
            CREATE TABLE estudiantes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                apellido VARCHAR(100) NOT NULL,
                dni VARCHAR(20) UNIQUE NOT NULL,
                email VARCHAR(100),
                telefono VARCHAR(20),
                fecha_nacimiento DATE,
                curso_id INT,
                estado ENUM('activo', 'inactivo', 'egresado') DEFAULT 'activo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (curso_id) REFERENCES cursos(id)
            )
        ";
        
        Database::getInstance()->getConnection()->exec($sql);
    }
    
    public function down() {
        $sql = "DROP TABLE estudiantes";
        Database::getInstance()->getConnection()->exec($sql);
    }
}</code>
            </div>

            <h3>Seeding de Datos</h3>
            <div class="code-block">
                <code>class DatabaseSeeder {
    public function run() {
        $this->seedRoles();
        $this->seedUsers();
        $this->seedCourses();
    }
    
    private function seedRoles() {
        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso completo'],
            ['nombre' => 'Secretario', 'descripcion' => 'Gestión administrativa'],
            ['nombre' => 'Profesor', 'descripcion' => 'Acceso académico'],
            ['nombre' => 'Preceptor', 'descripcion' => 'Seguimiento estudiantil']
        ];
        
        foreach ($roles as $role) {
            $this->db->prepare(
                "INSERT INTO roles (nombre, descripcion) VALUES (?, ?)"
            )->execute([$role['nombre'], $role['descripcion']]);
        }
    }
}</code>
            </div>

            <h2><i class="fas fa-shield-alt"></i> Seguridad en el Desarrollo</h2>
            
            <h3>Validación de Entrada</h3>
            <div class="code-block">
                <code>class InputValidator {
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function sanitizeString(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateDNI(string $dni): bool {
        return preg_match('/^[0-9]{7,8}$/', $dni);
    }
    
    public static function validatePhone(string $phone): bool {
        return preg_match('/^[0-9+\-\s()]{10,20}$/', $phone);
    }
}</code>
            </div>

            <h3>Prevención de Inyección SQL</h3>
            <div class="code-block">
                <code>// ✅ CORRECTO - Prepared Statements
$stmt = $db->prepare("SELECT * FROM estudiantes WHERE curso_id = ?");
$stmt->execute([$cursoId]);
$students = $stmt->fetchAll();

// ❌ INCORRECTO - Concatenación directa
$sql = "SELECT * FROM estudiantes WHERE curso_id = " . $cursoId;
$students = $db->query($sql)->fetchAll();</code>
            </div>

            <h3>Protección CSRF</h3>
            <div class="code-block">
                <code>class CSRFProtection {
    public static function generateToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}

// En formularios
&lt;input type="hidden" name="csrf_token" value="&lt;?= CSRFProtection::generateToken() ?&gt;"&gt;</code>
            </div>

            <h2><i class="fas fa-bug"></i> Testing y Debugging</h2>
            
            <h3>Configuración de Testing</h3>
            <div class="code-block">
                <code>// phpunit.xml
&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;phpunit bootstrap="tests/bootstrap.php"&gt;
    &lt;testsuites&gt;
        &lt;testsuite name="Unit"&gt;
            &lt;directory&gt;tests/Unit&lt;/directory&gt;
        &lt;/testsuite&gt;
        &lt;testsuite name="Integration"&gt;
            &lt;directory&gt;tests/Integration&lt;/directory&gt;
        &lt;/testsuite&gt;
    &lt;/testsuites&gt;
&lt;/phpunit&gt;</code>
            </div>

            <h3>Ejemplo de Test Unitario</h3>
            <div class="code-block">
                <code>class StudentServiceTest extends TestCase {
    private $studentService;
    private $mockRepository;
    
    protected function setUp(): void {
        $this->mockRepository = $this->createMock(StudentRepositoryInterface::class);
        $this->studentService = new StudentService($this->mockRepository);
    }
    
    public function testCreateStudentWithValidData() {
        $data = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '12345678'
        ];
        
        $this->mockRepository
            -&gt;expects($this->once())
            -&gt;method('save')
            -&gt;willReturn(true);
        
        $student = $this->studentService->createStudent($data);
        
        $this->assertInstanceOf(Student::class, $student);
        $this->assertEquals('Juan', $student->getNombre());
    }
}</code>
            </div>

            <h3>Debugging y Logging</h3>
            <div class="code-block">
                <code>class Logger {
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
    
    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }
    
    private static function log(string $level, string $message, array $context): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['usuario_id'] ?? null
        ];
        
        file_put_contents(
            'logs/app.log',
            json_encode($logEntry) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}</code>
            </div>

            <h2><i class="fas fa-rocket"></i> Despliegue y Producción</h2>
            
            <h3>Optimizaciones de Producción</h3>
            <ul>
                <li><strong>OPcache:</strong> Habilitar para mejorar rendimiento</li>
                <li><strong>Compresión:</strong> Gzip para archivos estáticos</li>
                <li><strong>CDN:</strong> Para recursos estáticos</li>
                <li><strong>Caching:</strong> Redis/Memcached para sesiones</li>
                <li><strong>Database:</strong> Índices optimizados</li>
            </ul>

            <h3>Monitoreo de Producción</h3>
            <div class="code-block">
                <code>// Health Check Endpoint
class HealthController {
    public function check(): array {
        $checks = [
            'database' => $this->checkDatabase(),
            'disk_space' => $this->checkDiskSpace(),
            'memory' => $this->checkMemory(),
            'services' => $this->checkServices()
        ];
        
        $status = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'] === 'ok';
        }, true);
        
        return [
            'status' => $status ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => time()
        ];
    }
}</code>
            </div>

            <div class="success-box">
                <h4><i class="fas fa-check-circle"></i> Mejores Prácticas de Desarrollo</h4>
                <ul>
                    <li>Seguir estándares PSR-12 para código PHP</li>
                    <li>Escribir tests para toda funcionalidad nueva</li>
                    <li>Documentar código complejo con PHPDoc</li>
                    <li>Usar versionado semántico para releases</li>
                    <li>Implementar CI/CD para despliegues automáticos</li>
                    <li>Revisar código antes de merge a main</li>
                </ul>
            </div>

            <h2><i class="fas fa-question-circle"></i> Recursos Adicionales</h2>
            
            <h3>Documentación Técnica</h3>
            <ul>
                <li><strong>API Documentation:</strong> <a href="api.php" style="color: #7c2d12; font-weight: 600;">Documentación API REST</a></li>
                <li><strong>Database Schema:</strong> Diagramas de base de datos</li>
                <li><strong>Code Examples:</strong> Ejemplos de implementación</li>
                <li><strong>Testing Guide:</strong> Guía completa de testing</li>
            </ul>

            <h3>Herramientas Recomendadas</h3>
            <ul>
                <li><strong>IDE:</strong> VS Code con extensiones PHP</li>
                <li><strong>Database:</strong> MySQL Workbench o phpMyAdmin</li>
                <li><strong>Testing:</strong> PHPUnit para tests unitarios</li>
                <li><strong>Debugging:</strong> Xdebug para debugging avanzado</li>
                <li><strong>Code Quality:</strong> PHPStan para análisis estático</li>
            </ul>

            <div class="info-box">
                <h4><i class="fas fa-phone"></i> Soporte para Desarrolladores</h4>
                <p>Para consultas técnicas específicas o colaboración en el desarrollo, contacta al equipo técnico a través de la sección de <a href="contacto.php" style="color: #1e40af; font-weight: 600;">Contacto</a>.</p>
            </div>
        </div>
        
        <div class="doc-actions">
            <a href="../documentacion.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver a Documentación
            </a>
            <a href="../../public/login.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Ir al Login
            </a>
        </div>
    </div>
</body>
</html>
