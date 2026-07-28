<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioNotas;
use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Controller para manejar las peticiones HTTP relacionadas con notas
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para calificaciones.
 */
class NotaController extends BaseService
{
    private ServicioNotas $servicioNotas;
    private ServicioEstudiantes $servicioEstudiantes;

    public function __construct(DatabaseInterface $database, ServicioNotas $servicioNotas, ServicioEstudiantes $servicioEstudiantes)
    {
        parent::__construct($database);
        $this->servicioNotas = $servicioNotas;
        $this->servicioEstudiantes = $servicioEstudiantes;
    }

    /**
     * Maneja la petición POST para cargar una nueva nota
     */
    public function cargar(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosCarga($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Cargar la nota
            $nota = $this->servicioNotas->cargarNota(
                $datos['estudiante_id'],
                $datos['materia_id'],
                $datos['calificacion'],
                $datos['bimestre'],
                $datos['observaciones'] ?? null
            );
            
            return [
                'success' => true,
                'data' => $nota->toArray(),
                'message' => 'Nota cargada exitosamente'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener notas de un estudiante
     */
    public function obtenerPorEstudiante(int $estudianteId): array
    {
        try {
            $notas = $this->servicioNotas->obtenerNotasEstudiante($estudianteId);
            
            return [
                'success' => true,
                'data' => array_map(fn($nota) => $nota->toArray(), $notas),
                'total' => count($notas)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener notas de una materia específica
     */
    public function obtenerPorMateria(int $estudianteId, int $materiaId): array
    {
        try {
            $notas = $this->servicioNotas->obtenerNotasMateria($estudianteId, $materiaId);
            
            return [
                'success' => true,
                'data' => array_map(fn($nota) => $nota->toArray(), $notas),
                'total' => count($notas)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener notas de un bimestre específico
     */
    public function obtenerPorBimestre(int $estudianteId, string $bimestre): array
    {
        try {
            $notas = $this->servicioNotas->obtenerNotasBimestre($estudianteId, $bimestre);
            
            return [
                'success' => true,
                'data' => array_map(fn($nota) => $nota->toArray(), $notas),
                'total' => count($notas)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener el promedio de un estudiante
     */
    public function promedioEstudiante(int $estudianteId): array
    {
        try {
            $promedio = $this->servicioNotas->obtenerPromedioEstudiante($estudianteId);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'promedio' => $promedio
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
     * Maneja la petición GET para obtener el promedio de una materia
     */
    public function promedioMateria(int $estudianteId, int $materiaId): array
    {
        try {
            $promedio = $this->servicioNotas->obtenerPromedioMateria($estudianteId, $materiaId);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'materia_id' => $materiaId,
                    'promedio' => $promedio
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
     * Maneja la petición GET para obtener el boletín de un estudiante
     */
    public function boletin(int $estudianteId): array
    {
        try {
            $boletin = $this->servicioNotas->obtenerBoletin($estudianteId);
            
            return [
                'success' => true,
                'data' => $boletin
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición PUT para actualizar una nota
     */
    public function actualizar(int $notaId, array $datos): array
    {
        try {
            // Validar datos
            $errores = $this->validarDatosActualizacion($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Actualizar la nota
            $nota = $this->servicioNotas->actualizarNota(
                $notaId,
                $datos['calificacion'],
                $datos['observaciones'] ?? null
            );
            
            return [
                'success' => true,
                'data' => $nota->toArray(),
                'message' => 'Nota actualizada exitosamente'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición DELETE para eliminar una nota
     */
    public function eliminar(int $notaId): array
    {
        try {
            $this->servicioNotas->eliminarNota($notaId);
            
            return [
                'success' => true,
                'message' => 'Nota eliminada exitosamente'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estadísticas de notas
     */
    public function estadisticas(?int $materiaId = null, ?string $bimestre = null): array
    {
        try {
            $estadisticas = $this->servicioNotas->obtenerEstadisticas($materiaId, $bimestre);
            
            return [
                'success' => true,
                'data' => $estadisticas
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar si un estudiante está aprobado en una materia
     */
    public function estaAprobado(int $estudianteId, int $materiaId): array
    {
        try {
            $aprobado = $this->servicioNotas->estaAprobado($estudianteId, $materiaId);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'materia_id' => $materiaId,
                    'aprobado' => $aprobado
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
     * Maneja la petición GET para obtener notas recientes
     */
    public function recientes(int $dias = 30): array
    {
        try {
            $notas = $this->servicioNotas->obtenerNotasRecientes($dias);
            
            return [
                'success' => true,
                'data' => array_map(fn($nota) => $nota->toArray(), $notas),
                'total' => count($notas)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida los datos para cargar una nota
     */
    private function validarDatosCarga(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['estudiante_id'])) {
            $errores[] = 'El ID del estudiante es requerido';
        }
        
        if (empty($datos['materia_id'])) {
            $errores[] = 'El ID de la materia es requerido';
        }
        
        if (!isset($datos['calificacion']) || $datos['calificacion'] === '') {
            $errores[] = 'La calificación es requerida';
        } elseif (!is_numeric($datos['calificacion'])) {
            $errores[] = 'La calificación debe ser un número';
        }
        
        if (empty($datos['bimestre'])) {
            $errores[] = 'El bimestre es requerido';
        }
        
        // Validaciones adicionales de negocio
        if (!empty($datos['estudiante_id']) && !empty($datos['materia_id'])) {
            try {
                // Verificar que el estudiante existe y tiene curso asignado
                $estudiante = $this->servicioEstudiantes->buscarPorId($datos['estudiante_id']);
                if (!$estudiante) {
                    $errores[] = 'El estudiante no existe';
                } elseif (!$estudiante->getCursoId()) {
                    $errores[] = 'El estudiante debe tener un curso asignado para cargar notas';
                } else {
                    // Verificar que la materia está asignada al curso del estudiante
                    $materiaAsignada = $this->verificarMateriaEnCurso($datos['materia_id'], $estudiante->getCursoId());
                    if (!$materiaAsignada) {
                        $errores[] = 'La materia seleccionada no está asignada al curso del estudiante';
                    }
                }
            } catch (\Exception $e) {
                $errores[] = 'Error al validar datos: ' . $e->getMessage();
            }
        }
        
        return $errores;
    }

    /**
     * Valida los datos para actualizar una nota
     */
    private function validarDatosActualizacion(array $datos): array
    {
        $errores = [];
        
        if (!isset($datos['calificacion']) || $datos['calificacion'] === '') {
            $errores[] = 'La calificación es requerida';
        } elseif (!is_numeric($datos['calificacion'])) {
            $errores[] = 'La calificación debe ser un número';
        }
        
        return $errores;
    }

    /**
     * Verifica si una materia está asignada a un curso específico
     */
    private function verificarMateriaEnCurso(int $materiaId, int $cursoId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM materia_curso 
                    WHERE materia_id = ? AND curso_id = ? AND activo = 1";
            $result = $this->database->fetch($sql, [$materiaId, $cursoId]);
            return $result['count'] > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
