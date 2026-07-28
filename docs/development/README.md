# 👨‍💻 Guía de Desarrollo - Sistema Integral de Gestión Educativa

## 📋 Índice

- [Configuración del Entorno](#configuración-del-entorno)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Convenciones de Código](#convenciones-de-código)
- [Agregar Nuevas Funcionalidades](#agregar-nuevas-funcionalidades)
- [Testing](#testing)
- [Debugging](#debugging)
- [Optimización](#optimización)
- [Troubleshooting](#troubleshooting)

## 🛠️ Configuración del Entorno

### **Requisitos**

- **XAMPP 8.0+** (Apache, MySQL, PHP)
- **PHP 8.0+** con extensiones:
  - PDO
  - PDO_MySQL
  - JSON
  - Session
  - OpenSSL
- **MySQL 5.7+**
- **Composer** (para dependencias de testing)
- **Git** (para control de versiones)

### **Instalación**

1. **Clonar el repositorio**
   ```bash
   git clone <repository-url>
   cd SistemaAdmin
   ```

2. **Configurar XAMPP**
   ```bash
   # Iniciar Apache y MySQL desde XAMPP Control Panel
   # Verificar que los servicios estén corriendo en:
   # - Apache: http://localhost
   # - MySQL: puerto 3306
   ```

3. **Configurar Base de Datos**
   ```sql
   -- Crear base de datos
   CREATE DATABASE sistema_admin_eest2;
   
   -- Importar estructura (si existe archivo SQL)
   mysql -u root -p sistema_admin_eest2 < database_structure.sql
   ```

4. **Instalar Dependencias de Testing**
   ```bash
   composer install
   ```

5. **Verificar Instalación**
   ```bash
   # Ejecutar tests básicos
   php run_tests.php
   
   # Verificar en navegador
   # http://localhost/sys/SistemaAdmin/login.php
   ```

### **Configuración de IDE**

#### **VS Code**
```json
// .vscode/settings.json
{
    "php.validate.executablePath": "C:/xampp/php/php.exe",
    "php.suggest.basic": false,
    "php.format.enable": true,
    "files.associations": {
        "*.php": "php"
    },
    "emmet.includeLanguages": {
        "php": "html"
    }
}
```

#### **PHPStorm**
- Configurar PHP interpreter: `C:\xampp\php\php.exe`
- Configurar servidor web: Apache en `C:\xampp\htdocs`
- Habilitar autocompletado para namespaces

## 📁 Estructura del Proyecto

### **Organización de Archivos**

```
SistemaAdmin/
├── config/                 # Configuración
│   └── database.php       # Conexión a BD
├── css/                   # Estilos
│   └── style.css         # CSS principal
├── docs/                  # Documentación
│   ├── api/              # Documentación de API
│   ├── architecture/     # Documentación de arquitectura
│   └── development/      # Guías de desarrollo
├── img/                   # Imágenes y assets
├── includes/              # Archivos incluidos
│   ├── header.php        # Header común
│   ├── footer.php        # Footer común
│   └── csrf_functions.php # Funciones CSRF
├── logs/                  # Logs del sistema
├── src/                   # Código fuente
│   ├── autoload.php      # Autoloader
│   ├── controllers/      # Controladores
│   ├── services/         # Servicios de negocio
│   ├── models/          # Modelos de dominio
│   ├── mappers/         # Mappers de persistencia
│   ├── interfaces/      # Interfaces
│   ├── DTOs/            # Data Transfer Objects
│   └── exceptions/      # Excepciones personalizadas
├── tests/                 # Tests
│   ├── Unit/            # Tests unitarios
│   ├── Integration/     # Tests de integración
│   └── bootstrap.php    # Bootstrap de tests
├── *.php                 # Páginas principales
├── composer.json         # Dependencias
├── phpunit.xml          # Configuración PHPUnit
└── run_tests.php        # Script de tests
```

### **Convenciones de Nomenclatura**

#### **Archivos y Directorios**
- **Páginas PHP**: `snake_case.php` (ej: `students.php`)
- **Clases**: `PascalCase` (ej: `EstudianteController`)
- **Métodos**: `camelCase` (ej: `obtenerEstudiantes`)
- **Variables**: `camelCase` (ej: `$estudianteId`)
- **Constantes**: `UPPER_SNAKE_CASE` (ej: `DEFAULT_PAGE_SIZE`)

#### **Namespaces**
```php
// Estructura de namespaces
namespace SistemaAdmin\Controllers;     // Controladores
namespace SistemaAdmin\Services;        // Servicios
namespace SistemaAdmin\Models;          // Modelos
namespace SistemaAdmin\Mappers;         // Mappers
namespace SistemaAdmin\Interfaces;      // Interfaces
namespace SistemaAdmin\DTOs;            // DTOs
namespace SistemaAdmin\Exceptions;      // Excepciones
```

## 📝 Convenciones de Código

### **Estilo de Código PHP**

#### **PSR-12 Compliance**
```php
<?php
declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioEstudiantes;
use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Mappers\EstudianteMapper;

/**
 * Servicio de gestión de estudiantes
 * 
 * Implementa la lógica de negocio para la gestión de estudiantes
 * siguiendo los principios SOLID y las mejores prácticas.
 */
class ServicioEstudiantes implements IServicioEstudiantes
{
    private EstudianteMapper $estudianteMapper;
    private CacheService $cacheService;

    public function __construct(
        EstudianteMapper $estudianteMapper,
        CacheService $cacheService
    ) {
        $this->estudianteMapper = $estudianteMapper;
        $this->cacheService = $cacheService;
    }

    /**
     * Crear un nuevo estudiante
     * 
     * @param Estudiante $estudiante Estudiante a crear
     * @return Estudiante Estudiante creado con ID
     * @throws InvalidArgumentException Si los datos son inválidos
     */
    public function crear(Estudiante $estudiante): Estudiante
    {
        // Validar datos
        $errores = $this->validarDatosEstudiante($estudiante);
        if (!empty($errores)) {
            throw new InvalidArgumentException(implode(', ', $errores));
        }

        // Verificar DNI único
        if ($this->dniExiste($estudiante->getDni())) {
            throw new InvalidArgumentException("El DNI ya existe");
        }

        // Crear estudiante
        $estudianteCreado = $this->estudianteMapper->save($estudiante);
        
        // Invalidar cache
        $this->invalidarCache();

        return $estudianteCreado;
    }
}
```

#### **Documentación de Código**
```php
/**
 * Obtiene estudiantes con paginación y filtros
 * 
 * @param int $page Número de página (1-based)
 * @param int $pageSize Tamaño de página (máximo 100)
 * @param array $filtros Filtros a aplicar
 * @return array Array con estudiantes y información de paginación
 * 
 * @example
 * $resultado = $servicio->obtenerConPaginacion(1, 20, ['curso_id' => 1]);
 * echo "Total: " . $resultado['pagination']['total_items'];
 */
public function obtenerConPaginacion(
    int $page = 1, 
    int $pageSize = 20, 
    array $filtros = []
): array {
    // Implementación...
}
```

### **Manejo de Errores**

#### **Excepciones Personalizadas**
```php
<?php
namespace SistemaAdmin\Exceptions;

/**
 * Excepción lanzada cuando un estudiante no es encontrado
 */
class EstudianteNoEncontradoException extends \Exception
{
    public function __construct(int $estudianteId)
    {
        parent::__construct("Estudiante con ID {$estudianteId} no encontrado");
    }
}
```

#### **Manejo de Errores en Controllers**
```php
public function obtenerEstudiante(int $id): array
{
    try {
        $estudiante = $this->servicioEstudiantes->buscarPorId($id);
        
        return [
            'success' => true,
            'data' => $estudiante->toArray()
        ];
        
    } catch (EstudianteNoEncontradoException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => 'ESTUDIANTE_NOT_FOUND'
        ];
        
    } catch (\Exception $e) {
        error_log("Error inesperado: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'Error interno del servidor',
            'code' => 'INTERNAL_ERROR'
        ];
    }
}
```

### **Validación de Datos**

#### **Validaciones en Modelos**
```php
class Estudiante
{
    private string $dni;
    private string $email;

    public function setDni(string $dni): void
    {
        if (empty($dni) || !preg_match('/^\d{7,8}$/', $dni)) {
            throw new InvalidArgumentException('DNI inválido. Debe tener 7 u 8 dígitos');
        }
        
        $this->dni = $dni;
    }

    public function setEmail(?string $email): void
    {
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email inválido');
        }
        
        $this->email = $email;
    }
}
```

#### **Validaciones en Controllers**
```php
public function crearEstudiante(array $data): array
{
    // Validar datos requeridos
    $camposRequeridos = ['dni', 'nombre', 'apellido'];
    foreach ($camposRequeridos as $campo) {
        if (empty($data[$campo])) {
            return [
                'success' => false,
                'error' => "El campo {$campo} es requerido",
                'code' => 'VALIDATION_ERROR'
            ];
        }
    }

    // Validar CSRF token
    if (!$this->servicioSeguridad->verificarTokenCSRF($data['csrf_token'])) {
        return [
            'success' => false,
            'error' => 'Token de seguridad inválido',
            'code' => 'CSRF_ERROR'
        ];
    }

    // Continuar con la lógica...
}
```

## 🚀 Agregar Nuevas Funcionalidades

### **Paso a Paso: Agregar Gestión de Materias**

#### **1. Crear el Modelo**
```php
<?php
// src/models/Materia.php
namespace SistemaAdmin\Models;

class Materia
{
    private int $id;
    private string $nombre;
    private string $codigo;
    private int $especialidadId;
    private int $horasSemanales;
    private bool $activo;

    public function __construct(
        string $nombre,
        string $codigo,
        int $especialidadId,
        int $horasSemanales,
        bool $activo = true
    ) {
        $this->setNombre($nombre);
        $this->setCodigo($codigo);
        $this->especialidadId = $especialidadId;
        $this->horasSemanales = $horasSemanales;
        $this->activo = $activo;
    }

    public function setNombre(string $nombre): void
    {
        if (empty($nombre)) {
            throw new InvalidArgumentException('El nombre es requerido');
        }
        $this->nombre = $nombre;
    }

    public function setCodigo(string $codigo): void
    {
        if (empty($codigo) || !preg_match('/^[A-Z]{3}\d{3}$/', $codigo)) {
            throw new InvalidArgumentException('Código inválido. Formato: ABC123');
        }
        $this->codigo = $codigo;
    }

    // Getters...
}
```

#### **2. Crear la Interfaz del Servicio**
```php
<?php
// src/interfaces/IServicioMaterias.php
namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Materia;

interface IServicioMaterias
{
    public function buscarPorId(int $id): ?Materia;
    public function obtenerTodas(): array;
    public function obtenerPorEspecialidad(int $especialidadId): array;
    public function crear(Materia $materia): Materia;
    public function actualizar(Materia $materia): Materia;
    public function eliminar(int $id): bool;
    public function obtenerConPaginacion(int $page, int $pageSize): array;
}
```

#### **3. Crear el Mapper**
```php
<?php
// src/mappers/MateriaMapper.php
namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Materia;

class MateriaMapper
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?Materia
    {
        $sql = "SELECT * FROM materias WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        return $row ? $this->mapRowToMateria($row) : null;
    }

    public function save(Materia $materia): Materia
    {
        $sql = "INSERT INTO materias (nombre, codigo, especialidad_id, horas_semanales, activo) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $materia->getNombre(),
            $materia->getCodigo(),
            $materia->getEspecialidadId(),
            $materia->getHorasSemanales(),
            $materia->esActiva() ? 1 : 0
        ];
        
        $this->database->query($sql, $params);
        $materia->setId($this->database->lastInsertId());
        
        return $materia;
    }

    private function mapRowToMateria(array $row): Materia
    {
        $materia = new Materia(
            $row['nombre'],
            $row['codigo'],
            $row['especialidad_id'],
            $row['horas_semanales'],
            (bool) $row['activo']
        );
        
        $materia->setId((int) $row['id']);
        return $materia;
    }
}
```

#### **4. Crear el Servicio**
```php
<?php
// src/services/ServicioMaterias.php
namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioMaterias;
use SistemaAdmin\Models\Materia;
use SistemaAdmin\Mappers\MateriaMapper;

class ServicioMaterias implements IServicioMaterias
{
    private MateriaMapper $materiaMapper;
    private CacheService $cacheService;
    private PaginationService $paginationService;

    public function __construct(
        MateriaMapper $materiaMapper,
        CacheService $cacheService,
        PaginationService $paginationService
    ) {
        $this->materiaMapper = $materiaMapper;
        $this->cacheService = $cacheService;
        $this->paginationService = $paginationService;
    }

    public function buscarPorId(int $id): ?Materia
    {
        $cacheKey = "materia:{$id}";
        
        return $this->cacheService->remember($cacheKey, function() use ($id) {
            return $this->materiaMapper->findById($id);
        }, 300);
    }

    public function obtenerConPaginacion(int $page, int $pageSize): array
    {
        $cacheKey = "materias:page:{$page}:size:{$pageSize}";
        
        return $this->cacheService->remember($cacheKey, function() use ($page, $pageSize) {
            $totalItems = $this->materiaMapper->countBy(['activo' => 1]);
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            $materias = $this->materiaMapper->findWithPagination(
                $pagination['offset'], 
                $pagination['page_size']
            );
            
            return [
                'materias' => $materias,
                'pagination' => $pagination
            ];
        }, 300);
    }

    // Otros métodos...
}
```

#### **5. Crear el Controller**
```php
<?php
// src/controllers/MateriaController.php
namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioMaterias;

class MateriaController
{
    private ServicioMaterias $servicioMaterias;

    public function __construct(ServicioMaterias $servicioMaterias)
    {
        $this->servicioMaterias = $servicioMaterias;
    }

    public function listar(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        
        $resultado = $this->servicioMaterias->obtenerConPaginacion($page, $pageSize);
        
        return [
            'success' => true,
            'data' => $resultado
        ];
    }

    public function crear(array $data): array
    {
        try {
            // Validar datos
            $this->validarDatosCreacion($data);
            
            // Crear materia
            $materia = new Materia(
                $data['nombre'],
                $data['codigo'],
                $data['especialidad_id'],
                $data['horas_semanales']
            );
            
            $materiaCreada = $this->servicioMaterias->crear($materia);
            
            return [
                'success' => true,
                'message' => 'Materia creada exitosamente',
                'data' => $materiaCreada->toArray()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function validarDatosCreacion(array $data): void
    {
        $camposRequeridos = ['nombre', 'codigo', 'especialidad_id', 'horas_semanales'];
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo])) {
                throw new InvalidArgumentException("El campo {$campo} es requerido");
            }
        }
    }
}
```

#### **6. Crear la Página PHP**
```php
<?php
// subjects.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar permisos
if (!hasRole(['admin', 'directivo'])) {
    header('Location: index.php');
    exit();
}

require_once 'src/autoload.php';
require_once 'config/database.php';

use SistemaAdmin\Controllers\MateriaController;
use SistemaAdmin\Services\ServicioMaterias;
use SistemaAdmin\Mappers\MateriaMapper;
use SistemaAdmin\Services\CacheService;
use SistemaAdmin\Services\PaginationService;

// Inicializar servicios
$db = Database::getInstance();
$materiaMapper = new MateriaMapper($db);
$cacheService = new CacheService($db);
$paginationService = new PaginationService();
$servicioMaterias = new ServicioMaterias($materiaMapper, $cacheService, $paginationService);
$materiaController = new MateriaController($servicioMaterias);

// Manejar requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $materiaController->crear($_POST);
    if ($resultado['success']) {
        $mensaje = $resultado['message'];
    } else {
        $error = $resultado['error'];
    }
}

// Obtener listado
$params = [
    'page' => $_GET['page'] ?? 1,
    'page_size' => $_GET['page_size'] ?? 20
];

$resultado = $materiaController->listar($params);
$materias = $resultado['data']['materias'] ?? [];
$pagination = $resultado['data']['pagination'] ?? [];

$pageTitle = 'Gestión de Materias (nueva arquitectura)';
require_once 'includes/header.php';
?>

<div class="content">
    <h1>Gestión de Materias</h1>
    
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Formulario de creación -->
    <div class="card">
        <h2>Nueva Materia</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="codigo">Código:</label>
                <input type="text" id="codigo" name="codigo" required 
                       pattern="[A-Z]{3}[0-9]{3}" 
                       title="Formato: ABC123">
            </div>
            
            <div class="form-group">
                <label for="especialidad_id">Especialidad:</label>
                <select id="especialidad_id" name="especialidad_id" required>
                    <option value="">Seleccionar...</option>
                    <!-- Opciones de especialidades -->
                </select>
            </div>
            
            <div class="form-group">
                <label for="horas_semanales">Horas Semanales:</label>
                <input type="number" id="horas_semanales" name="horas_semanales" 
                       min="1" max="20" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Crear Materia</button>
        </form>
    </div>
    
    <!-- Listado de materias -->
    <div class="card">
        <h2>Listado de Materias</h2>
        
        <?php if (empty($materias)): ?>
            <p>No hay materias registradas.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Especialidad</th>
                        <th>Horas/Semana</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias as $materia): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($materia->getCodigo()); ?></td>
                            <td><?php echo htmlspecialchars($materia->getNombre()); ?></td>
                            <td><?php echo htmlspecialchars($materia->getEspecialidadId()); ?></td>
                            <td><?php echo $materia->getHorasSemanales(); ?></td>
                            <td>
                                <a href="materia_ficha.php?id=<?php echo $materia->getId(); ?>" 
                                   class="btn btn-sm btn-info">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php echo $paginationService->generatePaginationHtml($pagination, 'subjects.php'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
```

#### **7. Crear Tests**
```php
<?php
// tests/Unit/Models/MateriaTest.php
namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use SistemaAdmin\Models\Materia;

class MateriaTest extends TestCase
{
    public function testCrearMateriaValida()
    {
        $materia = new Materia('Matemática', 'MAT001', 1, 6);
        
        $this->assertEquals('Matemática', $materia->getNombre());
        $this->assertEquals('MAT001', $materia->getCodigo());
        $this->assertEquals(1, $materia->getEspecialidadId());
        $this->assertEquals(6, $materia->getHorasSemanales());
        $this->assertTrue($materia->esActiva());
    }

    public function testValidarCodigoInvalido()
    {
        $this->expectException(InvalidArgumentException::class);
        new Materia('Matemática', 'INVALID', 1, 6);
    }

    public function testValidarNombreVacio()
    {
        $this->expectException(InvalidArgumentException::class);
        new Materia('', 'MAT001', 1, 6);
    }
}
```

#### **8. Actualizar Autoloader**
```php
// src/autoload.php
spl_autoload_register(function ($class) {
    $prefix = 'SistemaAdmin\\';
    $baseDir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
```

## 🧪 Testing

### **Estrategia de Testing**

#### **1. Tests Unitarios**
```php
<?php
// tests/Unit/Services/ServicioMateriasTest.php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SistemaAdmin\Services\ServicioMaterias;
use SistemaAdmin\Mappers\MateriaMapper;
use SistemaAdmin\Models\Materia;

class ServicioMateriasTest extends TestCase
{
    private MockObject $materiaMapperMock;
    private ServicioMaterias $servicio;

    protected function setUp(): void
    {
        $this->materiaMapperMock = $this->createMock(MateriaMapper::class);
        $this->servicio = new ServicioMaterias($this->materiaMapperMock);
    }

    public function testBuscarPorIdExistente()
    {
        $materia = new Materia('Matemática', 'MAT001', 1, 6);
        $materia->setId(1);
        
        $this->materiaMapperMock
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($materia);
        
        $resultado = $this->servicio->buscarPorId(1);
        
        $this->assertInstanceOf(Materia::class, $resultado);
        $this->assertEquals(1, $resultado->getId());
    }

    public function testBuscarPorIdNoExistente()
    {
        $this->materiaMapperMock
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);
        
        $resultado = $this->servicio->buscarPorId(999);
        
        $this->assertNull($resultado);
    }
}
```

#### **2. Tests de Integración**
```php
<?php
// tests/Integration/MateriaControllerTest.php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use SistemaAdmin\Controllers\MateriaController;
use SistemaAdmin\Services\ServicioMaterias;

class MateriaControllerTest extends TestCase
{
    private MateriaController $controller;

    protected function setUp(): void
    {
        // Configurar entorno de testing
        $this->setupTestDatabase();
        
        $servicioMaterias = new ServicioMaterias(/* mocks */);
        $this->controller = new MateriaController($servicioMaterias);
    }

    public function testListarMaterias()
    {
        $params = ['page' => 1, 'page_size' => 10];
        
        $resultado = $this->controller->listar($params);
        
        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertArrayHasKey('materias', $resultado['data']);
        $this->assertArrayHasKey('pagination', $resultado['data']);
    }

    public function testCrearMateriaValida()
    {
        $data = [
            'nombre' => 'Física',
            'codigo' => 'FIS001',
            'especialidad_id' => 1,
            'horas_semanales' => 4
        ];
        
        $resultado = $this->controller->crear($data);
        
        $this->assertTrue($resultado['success']);
        $this->assertEquals('Materia creada exitosamente', $resultado['message']);
    }
}
```

#### **3. Ejecutar Tests**
```bash
# Ejecutar todos los tests
php run_tests.php

# Ejecutar tests específicos
vendor/bin/phpunit tests/Unit/Models/MateriaTest.php

# Ejecutar con coverage
vendor/bin/phpunit --coverage-html coverage/
```

## 🐛 Debugging

### **Herramientas de Debug**

#### **1. Logging**
```php
// Configurar logging
error_log("DEBUG: Usuario {$userId} accedió al sistema", 0);

// Logging estructurado
$logData = [
    'user_id' => $userId,
    'action' => 'login',
    'ip' => $_SERVER['REMOTE_ADDR'],
    'timestamp' => date('Y-m-d H:i:s')
];
error_log("AUDIT: " . json_encode($logData), 0);
```

#### **2. Debug de Variables**
```php
// Función de debug
function debug($var, $label = 'DEBUG') {
    echo "<pre style='background:#f0f0f0;padding:10px;border:1px solid #ccc;'>";
    echo "<strong>{$label}:</strong>\n";
    print_r($var);
    echo "</pre>";
}

// Uso
debug($estudiantes, 'Lista de estudiantes');
debug($_POST, 'Datos POST');
```

#### **3. Debug de Base de Datos**
```php
// Logging de consultas SQL
class Database {
    public function query($sql, $params = []) {
        // Log de consulta
        error_log("SQL: {$sql} | Params: " . json_encode($params), 0);
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }
}
```

#### **4. Debug de Cache**
```php
// Verificar estado del cache
$cacheStats = $cacheService->getStats();
debug($cacheStats, 'Estadísticas de Cache');

// Verificar contenido del cache
$cachedData = $cacheService->get('estudiantes:page:1');
debug($cachedData, 'Datos cacheados');
```

### **Herramientas de Desarrollo**

#### **1. Xdebug (Opcional)**
```ini
; php.ini
[xdebug]
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

#### **2. Browser DevTools**
- **Network Tab**: Verificar requests/responses
- **Console**: JavaScript errors
- **Application Tab**: Session storage, cookies

#### **3. MySQL Workbench**
- Monitorear consultas en tiempo real
- Analizar performance de queries
- Verificar índices

## ⚡ Optimización

### **Optimización de Consultas**

#### **1. Análisis de Consultas Lentas**
```sql
-- Habilitar log de consultas lentas
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Ver consultas lentas
SHOW VARIABLES LIKE 'slow_query_log%';
```

#### **2. Optimización de Índices**
```sql
-- Analizar uso de índices
EXPLAIN SELECT * FROM estudiantes WHERE curso_id = 1 AND activo = 1;

-- Crear índices compuestos
CREATE INDEX idx_estudiantes_curso_activo ON estudiantes(curso_id, activo);
```

#### **3. Optimización de Cache**
```php
// Configurar TTL apropiado
$cacheService->set('estudiantes:stats', $stats, 600); // 10 minutos
$cacheService->set('usuarios:permissions', $permissions, 3600); // 1 hora

// Invalidar cache selectivamente
$cacheService->invalidatePattern('estudiantes:*');
```

### **Optimización de Código**

#### **1. Lazy Loading**
```php
// Cargar datos solo cuando se necesiten
public function getEstudiantes() {
    if ($this->estudiantes === null) {
        $this->estudiantes = $this->estudianteMapper->findActive();
    }
    return $this->estudiantes;
}
```

#### **2. Memoización**
```php
// Cachear resultados de cálculos costosos
private array $memoizedResults = [];

public function calcularEstadisticas() {
    if (!isset($this->memoizedResults['estadisticas'])) {
        $this->memoizedResults['estadisticas'] = $this->calcularEstadisticasReal();
    }
    return $this->memoizedResults['estadisticas'];
}
```

## 🔧 Troubleshooting

### **Problemas Comunes**

#### **1. Error de Conexión a BD**
```php
// Verificar configuración
$config = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'sistema_admin_eest2',
    'username' => 'root',
    'password' => ''
];

// Test de conexión
try {
    $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", 
                   $config['username'], $config['password']);
    echo "Conexión exitosa";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
```

#### **2. Error de Autoloader**
```php
// Verificar que el autoloader esté incluido
if (!class_exists('SistemaAdmin\\Models\\Estudiante')) {
    require_once 'src/autoload.php';
}

// Verificar rutas de archivos
$classFile = __DIR__ . '/src/Models/Estudiante.php';
if (!file_exists($classFile)) {
    throw new Exception("Archivo de clase no encontrado: {$classFile}");
}
```

#### **3. Error de Permisos**
```php
// Verificar permisos de sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar permisos específicos
if (!hasRole(['admin', 'directivo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Permisos insuficientes']);
    exit();
}
```

#### **4. Error de Cache**
```php
// Verificar que la tabla de cache existe
$sql = "SHOW TABLES LIKE 'cache_data'";
$result = $database->fetch($sql);

if (!$result) {
    // Crear tabla de cache
    $cacheService = new CacheService($database);
    echo "Tabla de cache creada";
}
```

### **Logs de Error**

#### **Ubicación de Logs**
```
logs/
├── error.log          # Errores de PHP
├── access.log         # Logs de acceso
├── security.log       # Eventos de seguridad
└── application.log    # Logs de aplicación
```

#### **Configuración de Logs**
```php
// Configurar logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Log personalizado
function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'file' => __FILE__,
        'line' => __LINE__
    ];
    
    error_log(json_encode($logEntry), 0);
}
```

---

*Guía de Desarrollo v2.0.0 - Sistema Integral de Gestión Educativa*
