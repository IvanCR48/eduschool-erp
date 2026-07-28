<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioProfesores;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Controller para manejar las peticiones HTTP relacionadas con el dashboard
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para el panel de control.
 */
class DashboardController extends BaseService
{
    private ServicioEstudiantes $servicioEstudiantes;
    private ServicioProfesores $servicioProfesores;
    private ServicioAutenticacion $servicioAutenticacion;

    public function __construct(
        DatabaseInterface $database,
        ServicioEstudiantes $servicioEstudiantes,
        ServicioProfesores $servicioProfesores,
        ServicioAutenticacion $servicioAutenticacion
    ) {
        parent::__construct($database);
        $this->servicioEstudiantes = $servicioEstudiantes;
        $this->servicioProfesores = $servicioProfesores;
        $this->servicioAutenticacion = $servicioAutenticacion;
    }

    /**
     * Maneja la petición GET para obtener estadísticas del dashboard
     */
    public function obtenerEstadisticas(): array
    {
        try {
            // Obtener estadísticas directamente de la base de datos para mejor rendimiento
            $estadisticasEstudiantes = $this->database->fetch("
                SELECT 
                    COUNT(*) as total_estudiantes,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as estudiantes_activos
                FROM estudiantes
            ");
            
            $estadisticasProfesores = $this->database->fetch("
                SELECT 
                    COUNT(*) as total_profesores,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as profesores_activos
                FROM profesores
            ");
            
            $estadisticasCursos = $this->database->fetch("
                SELECT 
                    COUNT(*) as total_cursos,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as cursos_activos
                FROM cursos
            ");
            
            return [
                'success' => true,
                'data' => [
                    'estudiantes' => [
                        'total' => (int)($estadisticasEstudiantes['total_estudiantes'] ?? 0),
                        'activos' => (int)($estadisticasEstudiantes['estudiantes_activos'] ?? 0)
                    ],
                    'profesores' => [
                        'total' => (int)($estadisticasProfesores['total_profesores'] ?? 0),
                        'activos' => (int)($estadisticasProfesores['profesores_activos'] ?? 0)
                    ],
                    'adicionales' => [
                        'total_cursos' => (int) ($estadisticasCursos['total_cursos'] ?? 0),
                        'cursos_activos' => (int) ($estadisticasCursos['cursos_activos'] ?? 0),
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error obteniendo estadísticas del dashboard', [
                'error' => $e->getMessage(),
                'ip' => $this->obtenerIPCliente()
            ]);
            
            return [
                'success' => true, // Mantener success true para no romper la vista
                'data' => [
                    'estudiantes' => ['total' => 0, 'activos' => 0],
                    'profesores' => ['total' => 0, 'activos' => 0],
                    'adicionales' => ['total_cursos' => 0, 'cursos_activos' => 0]
                ]
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener cumpleañeros del día
     */
    public function obtenerCumpleaneros(): array
    {
        try {
            // Límite de resultados para prevenir consultas masivas
            $maxResults = 1000;
            
            $cumpleaneros = $this->database->fetchAll("
                SELECT e.apellido, e.nombre, c.anio, c.division, esp.nombre as especialidad,
                       YEAR(CURDATE()) - YEAR(e.fecha_nacimiento) as edad
                FROM estudiantes e
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                WHERE e.activo = 1 
                AND DATE_FORMAT(e.fecha_nacimiento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                ORDER BY e.apellido, e.nombre
                LIMIT ?
            ", [$maxResults]);
            
            return [
                'success' => true,
                'data' => $cumpleaneros,
                'total' => count($cumpleaneros)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estudiantes por turno
     */
    public function obtenerEstudiantesPorTurno(): array
    {
        try {
            $estudiantesPorTurno = $this->database->fetchAll("
                SELECT t.nombre as turno, COUNT(e.id) as cantidad
                FROM turnos t
                LEFT JOIN cursos c ON t.id = c.turno_id AND c.activo = 1
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                GROUP BY t.id, t.nombre
                ORDER BY t.id
            ");
            
            return [
                'success' => true,
                'data' => $estudiantesPorTurno
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener resumen del dashboard
     */
    public function obtenerResumen(): array
    {
        try {
            // Establecer límite de tiempo para prevenir timeouts
            $startTime = microtime(true);
            $maxExecutionTime = 30; // segundos
            
            // Obtener todas las estadísticas con manejo individual de errores
            $estadisticas = $this->obtenerEstadisticas();
            $cumpleaneros = $this->obtenerCumpleaneros();
            $estudiantesPorTurno = $this->obtenerEstudiantesPorTurno();
            
            // Verificar tiempo de ejecución
            $executionTime = microtime(true) - $startTime;
            if ($executionTime > $maxExecutionTime) {
                $this->logEvent('WARNING', 'Tiempo de ejecución excedido en dashboard', [
                    'execution_time' => $executionTime,
                    'max_time' => $maxExecutionTime,
                    'ip' => $this->obtenerIPCliente()
                ]);
            }
            
            // Verificar si alguna operación falló
            $errores = [];
            if (!$estadisticas['success']) {
                $errores[] = 'Error obteniendo estadísticas';
            }
            if (!$cumpleaneros['success']) {
                $errores[] = 'Error obteniendo cumpleañeros';
            }
            if (!$estudiantesPorTurno['success']) {
                $errores[] = 'Error obteniendo estudiantes por turno';
            }
            
            if (!empty($errores)) {
                $this->logEvent('WARNING', 'Errores parciales en dashboard', [
                    'errores' => $errores,
                    'ip' => $this->obtenerIPCliente()
                ]);
            }
            
            return [
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas['data'] ?? [],
                    'cumpleaneros' => $cumpleaneros['data'] ?? [],
                    'estudiantes_por_turno' => $estudiantesPorTurno['data'] ?? []
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene estadísticas adicionales de la base de datos
     */
    private function obtenerEstadisticasAdicionales(): array
    {
        try {
            // Total cursos activos con manejo de errores
            $totalCursos = 0;
            try {
                $result = $this->database->fetch("SELECT COUNT(*) as total FROM cursos WHERE activo = 1");
                $totalCursos = $result['total'] ?? 0;
            } catch (\Exception $e) {
                $this->logEvent('WARNING', 'Error obteniendo total de cursos', [
                    'error' => $e->getMessage(),
                    'ip' => $this->obtenerIPCliente()
                ]);
            }
            
            // Cumpleaños de hoy con manejo de errores
            $cumpleanosHoy = 0;
            try {
                $result = $this->database->fetch("
                    SELECT COUNT(*) as total 
                    FROM estudiantes 
                    WHERE activo = 1 
                    AND DATE_FORMAT(fecha_nacimiento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                ");
                $cumpleanosHoy = $result['total'] ?? 0;
            } catch (\Exception $e) {
                $this->logEvent('WARNING', 'Error obteniendo cumpleañeros', [
                    'error' => $e->getMessage(),
                    'ip' => $this->obtenerIPCliente()
                ]);
            }
            
            return [
                'total_cursos' => $totalCursos,
                'cumpleanos_hoy' => $cumpleanosHoy
            ];
            
        } catch (\Exception $e) {
            return [
                'total_cursos' => 0,
                'cumpleanos_hoy' => 0
            ];
        }
    }
}
