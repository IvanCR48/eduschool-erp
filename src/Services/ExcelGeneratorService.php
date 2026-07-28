<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;

/**
 * Servicio Generador de Excel
 * 
 * Genera reportes en formato Excel (.xlsx) usando PhpSpreadsheet
 * o alternativamente usando funciones nativas de PHP
 */
class ExcelGeneratorService extends BaseService
{
    private array $configuracion;

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
        $this->configuracion = [
            'titulo_fuente' => 'Arial',
            'titulo_tamaño' => 14,
            'titulo_negrita' => true,
            'encabezado_fuente' => 'Arial',
            'encabezado_tamaño' => 12,
            'encabezado_negrita' => true,
            'datos_fuente' => 'Arial',
            'datos_tamaño' => 10,
            'datos_negrita' => false,
            'color_encabezado' => 'E6E6FA',
            'color_alternado' => 'F8F8FF',
            'bordes' => true
        ];
    }

    /**
     * Generar Excel de reporte de estudiantes
     */
    public function generarReporteEstudiantes(array $datos, array $opciones = []): string
    {
        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return $this->generarConPhpSpreadsheet('estudiantes', $datos, $opciones);
            } else {
                return $this->generarConFuncionesNativas('estudiantes', $datos, $opciones);
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel de estudiantes', [
                'error' => $e->getMessage(),
                'datos_count' => count($datos['data'] ?? [])
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel de reporte de profesores
     */
    public function generarReporteProfesores(array $datos, array $opciones = []): string
    {
        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return $this->generarConPhpSpreadsheet('profesores', $datos, $opciones);
            } else {
                return $this->generarConFuncionesNativas('profesores', $datos, $opciones);
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel de profesores', [
                'error' => $e->getMessage(),
                'datos_count' => count($datos['data'] ?? [])
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel de análisis de rendimiento
     */
    public function generarAnalisisRendimiento(array $datos, array $opciones = []): string
    {
        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return $this->generarConPhpSpreadsheet('rendimiento', $datos, $opciones);
            } else {
                return $this->generarConFuncionesNativas('rendimiento', $datos, $opciones);
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel de análisis de rendimiento', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel de análisis de disciplina
     */
    public function generarAnalisisDisciplina(array $datos, array $opciones = []): string
    {
        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return $this->generarConPhpSpreadsheet('disciplina', $datos, $opciones);
            } else {
                return $this->generarConFuncionesNativas('disciplina', $datos, $opciones);
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel de análisis de disciplina', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel de estadísticas generales
     */
    public function generarEstadisticasGenerales(array $datos, array $opciones = []): string
    {
        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return $this->generarConPhpSpreadsheet('estadisticas', $datos, $opciones);
            } else {
                return $this->generarConFuncionesNativas('estadisticas', $datos, $opciones);
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel de estadísticas generales', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel de reporte de llamados de atención
     */
    public function generarReporteLlamados(array $datos, array $opciones = []): string
    {
        try {
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return $this->generarConPhpSpreadsheet('llamados', $datos, $opciones);
            } else {
                return $this->generarConFuncionesNativas('llamados', $datos, $opciones);
            }
        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando Excel de llamados', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar Excel con PhpSpreadsheet
     */
    private function generarConPhpSpreadsheet(string $tipo, array $datos, array $opciones): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Configurar metadatos
        $spreadsheet->getProperties()
            ->setCreator('Sistema Administrativo E.E.S.T N°2')
            ->setLastModifiedBy('Sistema Administrativo')
            ->setTitle($opciones['titulo'] ?? 'Reporte')
            ->setSubject('Reporte Generado')
            ->setDescription('Reporte generado automáticamente por el sistema')
            ->setKeywords('reporte, educacion, estudiantes')
            ->setCategory('Reportes');

        // Configurar según tipo de reporte
        switch ($tipo) {
            case 'estudiantes':
                $this->configurarHojaEstudiantes($worksheet, $datos, $opciones);
                break;
            case 'profesores':
                $this->configurarHojaProfesores($worksheet, $datos, $opciones);
                break;
            case 'rendimiento':
                $this->configurarHojaRendimiento($spreadsheet, $datos, $opciones);
                break;
            case 'disciplina':
                $this->configurarHojaDisciplina($spreadsheet, $datos, $opciones);
                break;
            case 'estadisticas':
                $this->configurarHojaEstadisticas($worksheet, $datos, $opciones);
                break;
            case 'llamados':
                $this->configurarHojaLlamados($worksheet, $datos, $opciones);
                break;
        }

        // Guardar archivo
        $nombreArchivo = 'reporte_' . $tipo . '_' . uniqid() . '.xlsx';
        $rutaArchivo = __DIR__ . '/../../reports/' . $nombreArchivo;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($rutaArchivo);

        return $rutaArchivo;
    }

    /**
     * Configurar hoja para reporte de estudiantes
     */
    private function configurarHojaEstudiantes($worksheet, array $datos, array $opciones): void
    {
        $row = 1;

        // Título
        $worksheet->setCellValue('A1', 'Reporte de Estudiantes');
        $worksheet->mergeCells('A1:H1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row++;

        // Información del reporte
        $worksheet->setCellValue('A3', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
        $worksheet->setCellValue('A4', 'Total de registros: ' . count($datos['data'] ?? []));
        $row += 3;

        // Encabezados
        $encabezados = ['DNI', 'Apellido', 'Nombre', 'Curso', 'Especialidad', 'Promedio', 'Llamados', 'Estado'];
        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $worksheet->setCellValue($col . $row, $encabezado);
            $worksheet->getStyle($col . $row)->getFont()->setBold(true);
            $worksheet->getStyle($col . $row)->getFill()->setFillType('solid')
                ->getStartColor()->setRGB('E6E6FA');
            $col++;
        }
        $row++;

        // Datos
        foreach ($datos['data'] ?? [] as $index => $estudiante) {
            $worksheet->setCellValue('A' . $row, $estudiante['dni'] ?? '');
            $worksheet->setCellValue('B' . $row, $estudiante['apellido'] ?? '');
            $worksheet->setCellValue('C' . $row, $estudiante['nombre'] ?? '');
            $worksheet->setCellValue('D' . $row, $estudiante['curso_nombre'] ?? '');
            $worksheet->setCellValue('E' . $row, $estudiante['especialidad_nombre'] ?? '');
            $worksheet->setCellValue('F' . $row, round($estudiante['promedio_general'] ?? 0, 2));
            $worksheet->setCellValue('G' . $row, $estudiante['total_llamados'] ?? 0);
            $worksheet->setCellValue('H' . $row, ($estudiante['activo'] ?? false) ? 'Activo' : 'Inactivo');

            // Color alternado para filas
            if ($index % 2 == 1) {
                $worksheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F8F8FF');
            }
            $row++;
        }

        // Estadísticas
        if (isset($datos['estadisticas']) && !empty($datos['estadisticas'])) {
            $row += 2;
            $worksheet->setCellValue('A' . $row, 'ESTADÍSTICAS DEL REPORTE');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;

        foreach ($datos['estadisticas'] as $clave => $valor) {
            $worksheet->setCellValue('A' . $row, ucfirst(str_replace('_', ' ', $clave)) . ':');
            if (is_array($valor)) {
                $worksheet->setCellValue('B' . $row, json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $worksheet->setCellValue('B' . $row, $valor);
            }
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            }
        }

        // Ajustar ancho de columnas
        $worksheet->getColumnDimension('A')->setWidth(15);
        $worksheet->getColumnDimension('B')->setWidth(20);
        $worksheet->getColumnDimension('C')->setWidth(20);
        $worksheet->getColumnDimension('D')->setWidth(15);
        $worksheet->getColumnDimension('E')->setWidth(20);
        $worksheet->getColumnDimension('F')->setWidth(12);
        $worksheet->getColumnDimension('G')->setWidth(12);
        $worksheet->getColumnDimension('H')->setWidth(12);
    }

    /**
     * Configurar hoja para reporte de profesores
     */
    private function configurarHojaProfesores($worksheet, array $datos, array $opciones): void
    {
        $row = 1;

        // Título
        $worksheet->setCellValue('A1', 'Reporte de Profesores');
        $worksheet->mergeCells('A1:H1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row++;

        // Información del reporte
        $worksheet->setCellValue('A3', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
        $worksheet->setCellValue('A4', 'Total de registros: ' . count($datos['data'] ?? []));
        $row += 3;

        // Encabezados
        $encabezados = ['DNI', 'Apellido', 'Nombre', 'Email', 'Especialidad', 'Materias', 'Llamados', 'Estado'];
        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $worksheet->setCellValue($col . $row, $encabezado);
            $worksheet->getStyle($col . $row)->getFont()->setBold(true);
            $worksheet->getStyle($col . $row)->getFill()->setFillType('solid')
                ->getStartColor()->setRGB('E6E6FA');
            $col++;
        }
        $row++;

        // Datos
        foreach ($datos['data'] ?? [] as $index => $profesor) {
            $worksheet->setCellValue('A' . $row, $profesor['dni'] ?? '');
            $worksheet->setCellValue('B' . $row, $profesor['apellido'] ?? '');
            $worksheet->setCellValue('C' . $row, $profesor['nombre'] ?? '');
            $worksheet->setCellValue('D' . $row, $profesor['email'] ?? '');
            $worksheet->setCellValue('E' . $row, $profesor['especialidad_nombre'] ?? '');
            $worksheet->setCellValue('F' . $row, $profesor['materias_asignadas'] ?? 0);
            $worksheet->setCellValue('G' . $row, $profesor['llamados_realizados'] ?? 0);
            $worksheet->setCellValue('H' . $row, ($profesor['activo'] ?? false) ? 'Activo' : 'Inactivo');

            // Color alternado para filas
            if ($index % 2 == 1) {
                $worksheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F8F8FF');
            }
            $row++;
        }

        // Estadísticas
        if (isset($datos['estadisticas']) && !empty($datos['estadisticas'])) {
            $row += 2;
            $worksheet->setCellValue('A' . $row, 'ESTADÍSTICAS DEL REPORTE');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;

            foreach ($datos['estadisticas'] as $clave => $valor) {
                $worksheet->setCellValue('A' . $row, ucfirst(str_replace('_', ' ', $clave)) . ':');
                $worksheet->setCellValue('B' . $row, $valor);
                $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
            }
        }

        // Ajustar ancho de columnas
        $worksheet->getColumnDimension('A')->setWidth(15);
        $worksheet->getColumnDimension('B')->setWidth(20);
        $worksheet->getColumnDimension('C')->setWidth(20);
        $worksheet->getColumnDimension('D')->setWidth(25);
        $worksheet->getColumnDimension('E')->setWidth(20);
        $worksheet->getColumnDimension('F')->setWidth(12);
        $worksheet->getColumnDimension('G')->setWidth(12);
        $worksheet->getColumnDimension('H')->setWidth(12);
    }

    /**
     * Configurar hoja para análisis de rendimiento
     */
    private function configurarHojaRendimiento($spreadsheet, array $datos, array $opciones): void
    {
        // Hoja 1: Resumen
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Resumen');

        $row = 1;
        $worksheet->setCellValue('A1', 'Análisis de Rendimiento Académico');
        $worksheet->mergeCells('A1:D1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        if (isset($datos['resumen'])) {
            $row = 3;
            $worksheet->setCellValue('A' . $row, 'RESUMEN EJECUTIVO');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $row++;

            $resumen = $datos['resumen'];
            $resumenData = [
                'Promedio General de Cursos' => $resumen['promedio_general_cursos'] ?? 0,
                'Promedio General de Materias' => $resumen['promedio_general_materias'] ?? 0,
                'Total de Aprobados' => $resumen['total_aprobados'] ?? 0,
                'Total de Notas' => $resumen['total_notas'] ?? 0,
                'Porcentaje de Aprobación General' => ($resumen['porcentaje_aprobacion_general'] ?? 0) . '%'
            ];

            foreach ($resumenData as $clave => $valor) {
                $worksheet->setCellValue('A' . $row, $clave . ':');
                $worksheet->setCellValue('B' . $row, $valor);
                $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
            }
        }

        // Hoja 2: Análisis por Cursos
        if (isset($datos['data']['analisis_cursos'])) {
            $worksheet2 = $spreadsheet->createSheet();
            $worksheet2->setTitle('Análisis por Cursos');
            $this->configurarHojaAnalisisCursos($worksheet2, $datos['data']['analisis_cursos']);
        }

        // Hoja 3: Análisis por Materias
        if (isset($datos['data']['analisis_materias'])) {
            $worksheet3 = $spreadsheet->createSheet();
            $worksheet3->setTitle('Análisis por Materias');
            $this->configurarHojaAnalisisMaterias($worksheet3, $datos['data']['analisis_materias']);
        }
    }

    /**
     * Configurar hoja de análisis por cursos
     */
    private function configurarHojaAnalisisCursos($worksheet, array $cursos): void
    {
        $row = 1;

        // Título
        $worksheet->setCellValue('A1', 'Análisis de Rendimiento por Cursos');
        $worksheet->mergeCells('A1:K1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row += 2;

        // Encabezados
        $encabezados = ['Curso', 'Especialidad', 'Estudiantes', 'Total Notas', 'Promedio', 'Mínima', 'Máxima', 'Aprobados', 'Desaprobados', '% Aprobación'];
        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $worksheet->setCellValue($col . $row, $encabezado);
            $worksheet->getStyle($col . $row)->getFont()->setBold(true);
            $worksheet->getStyle($col . $row)->getFill()->setFillType('solid')
                ->getStartColor()->setRGB('E6E6FA');
            $col++;
        }
        $row++;

        // Datos
        foreach ($cursos as $index => $curso) {
            $worksheet->setCellValue('A' . $row, $curso['curso_nombre'] ?? '');
            $worksheet->setCellValue('B' . $row, $curso['especialidad_nombre'] ?? '');
            $worksheet->setCellValue('C' . $row, $curso['total_estudiantes'] ?? 0);
            $worksheet->setCellValue('D' . $row, $curso['total_notas'] ?? 0);
            $worksheet->setCellValue('E' . $row, round($curso['promedio_general'] ?? 0, 2));
            $worksheet->setCellValue('F' . $row, $curso['nota_minima'] ?? 0);
            $worksheet->setCellValue('G' . $row, $curso['nota_maxima'] ?? 0);
            $worksheet->setCellValue('H' . $row, $curso['aprobados'] ?? 0);
            $worksheet->setCellValue('I' . $row, $curso['desaprobados'] ?? 0);
            $worksheet->setCellValue('J' . $row, ($curso['porcentaje_aprobacion'] ?? 0) . '%');

            // Color alternado
            if ($index % 2 == 1) {
                $worksheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F8F8FF');
            }
            $row++;
        }

        // Ajustar columnas
        foreach (range('A', 'J') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Configurar hoja de análisis por materias
     */
    private function configurarHojaAnalisisMaterias($worksheet, array $materias): void
    {
        $row = 1;

        // Título
        $worksheet->setCellValue('A1', 'Análisis de Rendimiento por Materias');
        $worksheet->mergeCells('A1:J1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row += 2;

        // Encabezados
        $encabezados = ['Materia', 'Estudiantes', 'Total Notas', 'Promedio', 'Mínima', 'Máxima', 'Aprobados', 'Desaprobados', '% Aprobación'];
        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $worksheet->setCellValue($col . $row, $encabezado);
            $worksheet->getStyle($col . $row)->getFont()->setBold(true);
            $worksheet->getStyle($col . $row)->getFill()->setFillType('solid')
                ->getStartColor()->setRGB('E6E6FA');
            $col++;
        }
        $row++;

        // Datos
        foreach ($materias as $index => $materia) {
            $worksheet->setCellValue('A' . $row, $materia['materia_nombre'] ?? '');
            $worksheet->setCellValue('B' . $row, $materia['total_estudiantes'] ?? 0);
            $worksheet->setCellValue('C' . $row, $materia['total_notas'] ?? 0);
            $worksheet->setCellValue('D' . $row, round($materia['promedio_general'] ?? 0, 2));
            $worksheet->setCellValue('E' . $row, $materia['nota_minima'] ?? 0);
            $worksheet->setCellValue('F' . $row, $materia['nota_maxima'] ?? 0);
            $worksheet->setCellValue('G' . $row, $materia['aprobados'] ?? 0);
            $worksheet->setCellValue('H' . $row, $materia['desaprobados'] ?? 0);
            $worksheet->setCellValue('I' . $row, ($materia['porcentaje_aprobacion'] ?? 0) . '%');

            // Color alternado
            if ($index % 2 == 1) {
                $worksheet->getStyle('A' . $row . ':I' . $row)->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F8F8FF');
            }
            $row++;
        }

        // Ajustar columnas
        foreach (range('A', 'I') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Configurar hoja para análisis de disciplina
     */
    private function configurarHojaDisciplina($spreadsheet, array $datos, array $opciones): void
    {
        // Hoja 1: Resumen
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Resumen');

        $row = 1;
        $worksheet->setCellValue('A1', 'Análisis de Disciplina');
        $worksheet->mergeCells('A1:D1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        if (isset($datos['resumen'])) {
            $row = 3;
            $worksheet->setCellValue('A' . $row, 'RESUMEN EJECUTIVO');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $row++;

            $resumen = $datos['resumen'];
            $resumenData = [
                'Total de Llamados' => $resumen['total_llamados'] ?? 0,
                'Total de Estudiantes' => $resumen['total_estudiantes'] ?? 0,
                'Total con Sanción' => $resumen['total_con_sancion'] ?? 0,
                'Promedio de Llamados por Estudiante' => $resumen['promedio_llamados_por_estudiante'] ?? 0,
                'Porcentaje con Sanción' => ($resumen['porcentaje_con_sancion'] ?? 0) . '%',
                'Motivo Más Común' => $resumen['motivo_mas_comun'] ?? 'N/A'
            ];

            foreach ($resumenData as $clave => $valor) {
                $worksheet->setCellValue('A' . $row, $clave . ':');
                $worksheet->setCellValue('B' . $row, $valor);
                $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
            }
        }

        // Hoja 2: Análisis por Motivos
        if (isset($datos['data']['analisis_motivos'])) {
            $worksheet2 = $spreadsheet->createSheet();
            $worksheet2->setTitle('Análisis por Motivos');
            $this->configurarHojaAnalisisMotivos($worksheet2, $datos['data']['analisis_motivos']);
        }
    }

    /**
     * Configurar hoja de análisis por motivos
     */
    private function configurarHojaAnalisisMotivos($worksheet, array $motivos): void
    {
        $row = 1;

        // Título
        $worksheet->setCellValue('A1', 'Análisis de Llamados por Motivos');
        $worksheet->mergeCells('A1:E1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row += 2;

        // Encabezados
        $encabezados = ['Motivo', 'Cantidad', 'Estudiantes Únicos', 'Con Sanción', '% Con Sanción'];
        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $worksheet->setCellValue($col . $row, $encabezado);
            $worksheet->getStyle($col . $row)->getFont()->setBold(true);
            $worksheet->getStyle($col . $row)->getFill()->setFillType('solid')
                ->getStartColor()->setRGB('E6E6FA');
            $col++;
        }
        $row++;

        // Datos
        foreach ($motivos as $index => $motivo) {
            $worksheet->setCellValue('A' . $row, $motivo['motivo'] ?? '');
            $worksheet->setCellValue('B' . $row, $motivo['cantidad'] ?? 0);
            $worksheet->setCellValue('C' . $row, $motivo['estudiantes_unicos'] ?? 0);
            $worksheet->setCellValue('D' . $row, $motivo['con_sancion'] ?? 0);
            
            $porcentajeConSancion = 0;
            if (($motivo['cantidad'] ?? 0) > 0) {
                $porcentajeConSancion = round((($motivo['con_sancion'] ?? 0) * 100) / $motivo['cantidad'], 2);
            }
            $worksheet->setCellValue('E' . $row, $porcentajeConSancion . '%');

            // Color alternado
            if ($index % 2 == 1) {
                $worksheet->getStyle('A' . $row . ':E' . $row)->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F8F8FF');
            }
            $row++;
        }

        // Ajustar columnas
        foreach (range('A', 'E') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Configurar hoja para estadísticas generales
     */
    private function configurarHojaEstadisticas($worksheet, array $datos, array $opciones): void
    {
        $row = 1;

        // Título
        $worksheet->setCellValue('A1', 'Estadísticas Generales del Sistema');
        $worksheet->mergeCells('A1:B1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row += 2;

        // Procesar cada sección de estadísticas
        foreach ($datos as $seccion => $estadisticas) {
            $worksheet->setCellValue('A' . $row, strtoupper(str_replace('_', ' ', $seccion)));
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;

            if (is_array($estadisticas)) {
                foreach ($estadisticas as $clave => $valor) {
                    $worksheet->setCellValue('A' . $row, ucfirst(str_replace('_', ' ', $clave)) . ':');
                    $worksheet->setCellValue('B' . $row, $valor);
                    $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $row++;
                }
            }
            $row++;
        }

        // Ajustar columnas
        $worksheet->getColumnDimension('A')->setWidth(30);
        $worksheet->getColumnDimension('B')->setWidth(15);
    }

    /**
     * Configurar hoja para reporte de llamados
     */
    private function configurarHojaLlamados($worksheet, array $datos, array $opciones): void
    {
        $row = 1;

        $worksheet->setCellValue('A1', 'Reporte de Llamados de Atención');
        $worksheet->mergeCells('A1:H1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $row++;

        $worksheet->setCellValue('A3', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
        $worksheet->setCellValue('A4', 'Total de registros: ' . count($datos['data'] ?? []));
        $row += 3;

        $encabezados = ['Fecha', 'Estudiante', 'Curso', 'Motivo', 'Descripción', 'Responsable', 'Sanción', 'Estado'];
        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $worksheet->setCellValue($col . $row, $encabezado);
            $worksheet->getStyle($col . $row)->getFont()->setBold(true);
            $worksheet->getStyle($col . $row)->getFill()->setFillType('solid')
                ->getStartColor()->setRGB('E6E6FA');
            $col++;
        }
        $row++;

        foreach ($datos['data'] ?? [] as $index => $llamado) {
            $worksheet->setCellValue('A' . $row, $llamado['fecha'] ?? '');
            $worksheet->setCellValue('B' . $row, $llamado['estudiante'] ?? '');
            $worksheet->setCellValue('C' . $row, $llamado['curso'] ?? '');
            $worksheet->setCellValue('D' . $row, $llamado['motivo'] ?? '');
            $worksheet->setCellValue('E' . $row, $llamado['descripcion'] ?? '');
            $worksheet->setCellValue('F' . $row, $llamado['responsable'] ?? '');
            $worksheet->setCellValue('G' . $row, $llamado['sancion'] ?? '');
            $worksheet->setCellValue('H' . $row, $llamado['estado'] ?? '');

            if ($index % 2 == 1) {
                $worksheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F8F8FF');
            }
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Generar Excel con funciones nativas (fallback)
     */
    private function generarConFuncionesNativas(string $tipo, array $datos, array $opciones): string
    {
        // Crear archivo CSV como fallback
        $nombreArchivo = 'reporte_' . $tipo . '_' . uniqid() . '.csv';
        $rutaArchivo = __DIR__ . '/../../reports/' . $nombreArchivo;

        $archivo = fopen($rutaArchivo, 'w');

        // Escribir BOM para UTF-8
        fwrite($archivo, "\xEF\xBB\xBF");

        // Escribir encabezados según tipo
        switch ($tipo) {
            case 'estudiantes':
                fputcsv($archivo, ['DNI', 'Apellido', 'Nombre', 'Curso', 'Especialidad', 'Promedio', 'Llamados', 'Estado']);
                break;
            case 'profesores':
                fputcsv($archivo, ['DNI', 'Apellido', 'Nombre', 'Email', 'Especialidad', 'Materias', 'Llamados', 'Estado']);
                break;
            case 'rendimiento':
                fputcsv($archivo, ['Tipo', 'Nombre', 'Total Estudiantes', 'Total Notas', 'Promedio', 'Aprobados', 'Desaprobados', '% Aprobación']);
                break;
            case 'disciplina':
                fputcsv($archivo, ['Tipo', 'Nombre', 'Total Llamados', 'Estudiantes Únicos', 'Con Sanción']);
                break;
            case 'estadisticas':
                fputcsv($archivo, ['Categoría', 'Métrica', 'Valor']);
                break;
            case 'llamados':
                fputcsv($archivo, ['Fecha', 'Estudiante', 'Curso', 'Motivo', 'Descripción', 'Responsable', 'Sanción', 'Estado']);
                break;
        }

        // Escribir datos
        if (isset($datos['data']) && is_array($datos['data'])) {
            foreach ($datos['data'] as $fila) {
                switch ($tipo) {
                    case 'estudiantes':
                        fputcsv($archivo, [
                            $fila['dni'] ?? '',
                            $fila['apellido'] ?? '',
                            $fila['nombre'] ?? '',
                            $fila['curso_nombre'] ?? '',
                            $fila['especialidad_nombre'] ?? '',
                            round($fila['promedio_general'] ?? 0, 2),
                            $fila['total_llamados'] ?? 0,
                            ($fila['activo'] ?? false) ? 'Activo' : 'Inactivo'
                        ]);
                        break;
                    case 'profesores':
                        fputcsv($archivo, [
                            $fila['dni'] ?? '',
                            $fila['apellido'] ?? '',
                            $fila['nombre'] ?? '',
                            $fila['email'] ?? '',
                            $fila['especialidad_nombre'] ?? '',
                            $fila['materias_asignadas'] ?? 0,
                            $fila['llamados_realizados'] ?? 0,
                            ($fila['activo'] ?? false) ? 'Activo' : 'Inactivo'
                        ]);
                        break;
                    case 'llamados':
                        fputcsv($archivo, [
                            $fila['fecha'] ?? '',
                            $fila['estudiante'] ?? '',
                            $fila['curso'] ?? '',
                            $fila['motivo'] ?? '',
                            $fila['descripcion'] ?? '',
                            $fila['responsable'] ?? '',
                            $fila['sancion'] ?? '',
                            $fila['estado'] ?? ''
                        ]);
                        break;
                }
            }
        }

        // Escribir estadísticas si existen
        if (isset($datos['estadisticas']) && !empty($datos['estadisticas'])) {
            fputcsv($archivo, []); // Línea vacía
            fputcsv($archivo, ['ESTADÍSTICAS']);
            foreach ($datos['estadisticas'] as $clave => $valor) {
                if (is_array($valor)) {
                    fputcsv($archivo, [$clave, json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                } else {
                    fputcsv($archivo, [$clave, $valor]);
                }
            }
        }

        fclose($archivo);

        return $rutaArchivo;
    }
}
