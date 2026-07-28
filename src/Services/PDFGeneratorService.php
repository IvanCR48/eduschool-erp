<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;

/**
 * Servicio Generador de PDF
 * 
 * Genera reportes en formato PDF usando HTML/CSS
 * y la librería DomPDF o similar
 */
class PDFGeneratorService extends BaseService
{
    private string $templatePath;
    private array $configuracion;

    public function __construct(DatabaseInterface $database, ?ErrorHandlerService $errorHandler = null, ?ServicioLogging $logger = null)
    {
        parent::__construct($database, $errorHandler, $logger);
        $this->templatePath = __DIR__ . '/../../templates/pdf/';
        $this->configuracion = [
            'margen_superior' => 20,
            'margen_inferior' => 20,
            'margen_izquierdo' => 20,
            'margen_derecho' => 20,
            'formato_papel' => 'A4',
            'orientacion' => 'portrait',
            'encoding' => 'UTF-8'
        ];
    }

    /**
     * Generar PDF de reporte de estudiantes
     */
    public function generarReporteEstudiantes(array $datos, array $opciones = []): string
    {
        try {
            $plantilla = $this->cargarPlantilla('reporte_estudiantes');
            $html = $this->procesarPlantilla($plantilla, [
                'datos' => $datos,
                'opciones' => $opciones,
                'fecha_generacion' => date('d/m/Y H:i:s'),
                'titulo' => 'Reporte de Estudiantes',
                'total_registros' => count($datos['data'] ?? []),
                'estadisticas' => $datos['estadisticas'] ?? []
            ]);

            return $this->generarPDF($html, $opciones);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF de estudiantes', [
                'error' => $e->getMessage(),
                'datos_count' => count($datos['data'] ?? [])
            ]);
            throw $e;
        }
    }

    /**
     * Generar PDF de reporte de profesores
     */
    public function generarReporteProfesores(array $datos, array $opciones = []): string
    {
        try {
            $plantilla = $this->cargarPlantilla('reporte_profesores');
            $html = $this->procesarPlantilla($plantilla, [
                'datos' => $datos,
                'opciones' => $opciones,
                'fecha_generacion' => date('d/m/Y H:i:s'),
                'titulo' => 'Reporte de Profesores',
                'total_registros' => count($datos['data'] ?? []),
                'estadisticas' => $datos['estadisticas'] ?? []
            ]);

            return $this->generarPDF($html, $opciones);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF de profesores', [
                'error' => $e->getMessage(),
                'datos_count' => count($datos['data'] ?? [])
            ]);
            throw $e;
        }
    }

    /**
     * Generar PDF de análisis de rendimiento
     */
    public function generarAnalisisRendimiento(array $datos, array $opciones = []): string
    {
        try {
            $plantilla = $this->cargarPlantilla('analisis_rendimiento');
            $html = $this->procesarPlantilla($plantilla, [
                'datos' => $datos,
                'opciones' => $opciones,
                'fecha_generacion' => date('d/m/Y H:i:s'),
                'titulo' => 'Análisis de Rendimiento Académico',
                'resumen' => $datos['resumen'] ?? []
            ]);

            return $this->generarPDF($html, $opciones);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF de análisis de rendimiento', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar PDF de análisis de disciplina
     */
    public function generarAnalisisDisciplina(array $datos, array $opciones = []): string
    {
        try {
            $plantilla = $this->cargarPlantilla('analisis_disciplina');
            $html = $this->procesarPlantilla($plantilla, [
                'datos' => $datos,
                'opciones' => $opciones,
                'fecha_generacion' => date('d/m/Y H:i:s'),
                'titulo' => 'Análisis de Disciplina',
                'resumen' => $datos['resumen'] ?? []
            ]);

            return $this->generarPDF($html, $opciones);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF de análisis de disciplina', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar PDF de estadísticas generales
     */
    public function generarEstadisticasGenerales(array $datos, array $opciones = []): string
    {
        try {
            $plantilla = $this->cargarPlantilla('estadisticas_generales');
            $html = $this->procesarPlantilla($plantilla, [
                'datos' => $datos,
                'opciones' => $opciones,
                'fecha_generacion' => date('d/m/Y H:i:s'),
                'titulo' => 'Estadísticas Generales del Sistema'
            ]);

            return $this->generarPDF($html, $opciones);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF de estadísticas generales', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cargar plantilla HTML
     */
    private function cargarPlantilla(string $nombre): string
    {
        $rutaPlantilla = $this->templatePath . $nombre . '.html';
        
        if (!file_exists($rutaPlantilla)) {
            // Usar plantilla por defecto si no existe la específica
            $rutaPlantilla = $this->templatePath . 'default.html';
            
            if (!file_exists($rutaPlantilla)) {
                throw new \Exception("Plantilla no encontrada: {$nombre}");
            }
        }

        return file_get_contents($rutaPlantilla);
    }

    /**
     * Procesar plantilla con datos
     */
    private function procesarPlantilla(string $plantilla, array $datos): string
    {
        foreach ($datos as $clave => $valor) {
            $placeholder = '{{' . $clave . '}}';
            
            if (is_array($valor)) {
                $valor = $this->procesarArrayParaPlantilla($valor);
            } elseif (is_bool($valor)) {
                $valor = $valor ? 'Sí' : 'No';
            }
            
            $plantilla = str_replace($placeholder, $valor, $plantilla);
        }

        // Procesar bucles especiales
        $plantilla = $this->procesarBuclesPlantilla($plantilla, $datos);

        return $plantilla;
    }

    /**
     * Procesar arrays para plantillas
     */
    private function procesarArrayParaPlantilla(array $array): string
    {
        if (empty($array)) {
            return 'Sin datos';
        }

        $html = '<ul>';
        foreach ($array as $clave => $valor) {
            if (is_array($valor)) {
                $valor = json_encode($valor, JSON_PRETTY_PRINT);
            }
            $html .= "<li><strong>{$clave}:</strong> {$valor}</li>";
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Procesar bucles en plantillas (ej: {{#each datos}})
     */
    private function procesarBuclesPlantilla(string $plantilla, array $datos): string
    {
        // Procesar bucles para datos de tabla
        if (isset($datos['datos']['data']) && is_array($datos['datos']['data'])) {
            $plantilla = $this->procesarBucleTabla($plantilla, $datos['datos']['data']);
        }

        // Procesar bucles para estadísticas
        if (isset($datos['estadisticas']) && is_array($datos['estadisticas'])) {
            $plantilla = $this->procesarBucleEstadisticas($plantilla, $datos['estadisticas']);
        }

        return $plantilla;
    }

    /**
     * Procesar bucle de tabla de datos
     */
    private function procesarBucleTabla(string $plantilla, array $filas): string
    {
        $patron = '/\{\{#each tabla\}\}(.*?)\{\{\/each\}\}/s';
        
        if (preg_match($patron, $plantilla, $matches)) {
            $plantillaTabla = $matches[1];
            $htmlTabla = '';
            
            foreach ($filas as $fila) {
                $filaHtml = $plantillaTabla;
                foreach ($fila as $clave => $valor) {
                    $filaHtml = str_replace('{{' . $clave . '}}', $valor, $filaHtml);
                }
                $htmlTabla .= $filaHtml;
            }
            
            $plantilla = str_replace($matches[0], $htmlTabla, $plantilla);
        }

        return $plantilla;
    }

    /**
     * Procesar bucle de estadísticas
     */
    private function procesarBucleEstadisticas(string $plantilla, array $estadisticas): string
    {
        $patron = '/\{\{#each estadisticas\}\}(.*?)\{\{\/each\}\}/s';
        
        if (preg_match($patron, $plantilla, $matches)) {
            $plantillaEstadisticas = $matches[1];
            $htmlEstadisticas = '';
            
            foreach ($estadisticas as $clave => $valor) {
                $estadisticaHtml = $plantillaEstadisticas;
                $estadisticaHtml = str_replace('{{clave}}', $clave, $estadisticaHtml);
                $estadisticaHtml = str_replace('{{valor}}', $valor, $estadisticaHtml);
                $htmlEstadisticas .= $estadisticaHtml;
            }
            
            $plantilla = str_replace($matches[0], $htmlEstadisticas, $plantilla);
        }

        return $plantilla;
    }

    /**
     * Generar PDF desde HTML
     */
    private function generarPDF(string $html, array $opciones = []): string
    {
        try {
            // Si tenemos DomPDF disponible, usarlo
            if (class_exists('\Dompdf\Dompdf')) {
                return $this->generarConDomPDF($html, $opciones);
            }
            
            // Si tenemos TCPDF disponible, usarlo
            if (class_exists('\TCPDF')) {
                return $this->generarConTCPDF($html, $opciones);
            }
            
            // Fallback: usar wkhtmltopdf si está disponible
            if ($this->wkhtmltopdfDisponible()) {
                return $this->generarConWkhtmltopdf($html, $opciones);
            }
            
            // Último recurso: generar HTML que se puede imprimir
            return $this->generarHTMLImprimible($html, $opciones);

        } catch (\Exception $e) {
            $this->logEvent('ERROR', 'Error generando PDF', [
                'error' => $e->getMessage(),
                'opciones' => $opciones
            ]);
            throw $e;
        }
    }

    /**
     * Generar PDF con DomPDF
     */
    private function generarConDomPDF(string $html, array $opciones): string
    {
        $dompdf = new \Dompdf\Dompdf();
        
        // Configurar opciones
        $dompdf->setPaper($opciones['formato_papel'] ?? 'A4', $opciones['orientacion'] ?? 'portrait');
        $dompdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->render();
        
        $output = $dompdf->output();
        
        // Guardar archivo temporal
        $nombreArchivo = 'reporte_' . uniqid() . '.pdf';
        $rutaArchivo = __DIR__ . '/../../reports/' . $nombreArchivo;
        
        file_put_contents($rutaArchivo, $output);
        
        return $rutaArchivo;
    }

    /**
     * Generar PDF con TCPDF
     */
    private function generarConTCPDF(string $html, array $opciones): string
    {
        $pdf = new \TCPDF($opciones['orientacion'] ?? 'P', 'mm', $opciones['formato_papel'] ?? 'A4', true, 'UTF-8', false);
        
        // Configurar documento
        $pdf->SetCreator('Sistema Administrativo E.E.S.T N°2');
        $pdf->SetAuthor('Sistema Administrativo');
        $pdf->SetTitle($opciones['titulo'] ?? 'Reporte');
        $pdf->SetSubject('Reporte Generado');
        
        // Configurar márgenes
        $pdf->SetMargins(
            $opciones['margen_izquierdo'] ?? 20,
            $opciones['margen_superior'] ?? 20,
            $opciones['margen_derecho'] ?? 20
        );
        
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, $opciones['margen_inferior'] ?? 20);
        
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Guardar archivo
        $nombreArchivo = 'reporte_' . uniqid() . '.pdf';
        $rutaArchivo = __DIR__ . '/../../reports/' . $nombreArchivo;
        
        $pdf->Output($rutaArchivo, 'F');
        
        return $rutaArchivo;
    }

    /**
     * Generar PDF con wkhtmltopdf
     */
    private function generarConWkhtmltopdf(string $html, array $opciones): string
    {
        $nombreArchivo = 'reporte_' . uniqid() . '.pdf';
        $rutaArchivo = __DIR__ . '/../../reports/' . $nombreArchivo;
        $rutaHtml = sys_get_temp_dir() . '/reporte_' . uniqid() . '.html';
        
        // Guardar HTML temporal
        file_put_contents($rutaHtml, $html);
        
        // Comando wkhtmltopdf
        $comando = sprintf(
            'wkhtmltopdf --page-size %s --orientation %s --margin-top %d --margin-bottom %d --margin-left %d --margin-right %d "%s" "%s"',
            $opciones['formato_papel'] ?? 'A4',
            $opciones['orientacion'] ?? 'Portrait',
            $opciones['margen_superior'] ?? 20,
            $opciones['margen_inferior'] ?? 20,
            $opciones['margen_izquierdo'] ?? 20,
            $opciones['margen_derecho'] ?? 20,
            $rutaHtml,
            $rutaArchivo
        );
        
        exec($comando, $output, $returnCode);
        
        // Limpiar archivo temporal
        unlink($rutaHtml);
        
        if ($returnCode !== 0) {
            throw new \Exception('Error ejecutando wkhtmltopdf');
        }
        
        return $rutaArchivo;
    }

    /**
     * Generar HTML imprimible (fallback)
     */
    private function generarHTMLImprimible(string $html, array $opciones): string
    {
        $nombreArchivo = 'reporte_' . uniqid() . '.html';
        $rutaArchivo = __DIR__ . '/../../reports/' . $nombreArchivo;
        
        $htmlCompleto = $this->generarHTMLCompleto($html, $opciones);
        
        file_put_contents($rutaArchivo, $htmlCompleto);
        
        return $rutaArchivo;
    }

    /**
     * Generar HTML completo con estilos para impresión
     */
    private function generarHTMLCompleto(string $html, array $opciones): string
    {
        $css = $this->obtenerCSSImpresion();
        
        return "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>" . ($opciones['titulo'] ?? 'Reporte') . "</title>
    <style>{$css}</style>
</head>
<body>
    {$html}
</body>
</html>";
    }

    /**
     * Obtener CSS para impresión
     */
    private function obtenerCSSImpresion(): string
    {
        return "
        @page {
            size: A4;
            margin: 2cm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .titulo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .subtitulo {
            font-size: 14px;
            color: #666;
        }
        
        .info-reporte {
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .estadisticas {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .estadisticas h3 {
            margin-top: 0;
            color: #333;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        ";
    }

    /**
     * Verificar si wkhtmltopdf está disponible
     */
    private function wkhtmltopdfDisponible(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('wkhtmltopdf --version', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Crear plantillas por defecto si no existen
     */
    public function crearPlantillasPorDefecto(): void
    {
        $this->crearDirectorioTemplates();
        $this->crearPlantillaDefault();
        $this->crearPlantillaReporteEstudiantes();
        $this->crearPlantillaReporteProfesores();
        $this->crearPlantillaAnalisisRendimiento();
        $this->crearPlantillaAnalisisDisciplina();
        $this->crearPlantillaEstadisticasGenerales();
    }

    /**
     * Crear directorio de plantillas
     */
    private function crearDirectorioTemplates(): void
    {
        if (!is_dir($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
    }

    /**
     * Crear plantilla por defecto
     */
    private function crearPlantillaDefault(): void
    {
        $plantilla = '
        <div class="header">
            <div class="titulo">{{titulo}}</div>
            <div class="subtitulo">E.E.S.T N°2 "Educación y Trabajo"</div>
        </div>
        
        <div class="info-reporte">
            <p><strong>Fecha de generación:</strong> {{fecha_generacion}}</p>
            <p><strong>Total de registros:</strong> {{total_registros}}</p>
        </div>
        
        <div class="contenido">
            {{contenido}}
        </div>
        
        <div class="footer">
            <p>Sistema Administrativo E.E.S.T N°2 - Reporte generado automáticamente</p>
        </div>
        ';
        
        file_put_contents($this->templatePath . 'default.html', $plantilla);
    }

    /**
     * Crear plantilla para reporte de estudiantes
     */
    private function crearPlantillaReporteEstudiantes(): void
    {
        $plantilla = '
        <div class="header">
            <div class="titulo">{{titulo}}</div>
            <div class="subtitulo">E.E.S.T N°2 "Educación y Trabajo"</div>
        </div>
        
        <div class="info-reporte">
            <p><strong>Fecha de generación:</strong> {{fecha_generacion}}</p>
            <p><strong>Total de estudiantes:</strong> {{total_registros}}</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>DNI:</th>
                    <th>Apellido y Nombre</th>
                    <th>Curso</th>
                    <th>Especialidad</th>
                    <th>Promedio</th>
                    <th>Llamados</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                {{#each tabla}}
                <tr>
                    <td>{{dni}}</td>
                    <td>{{apellido}}, {{nombre}}</td>
                    <td>{{curso_nombre}}</td>
                    <td>{{especialidad_nombre}}</td>
                    <td>{{promedio_general}}</td>
                    <td>{{total_llamados}}</td>
                    <td>{{activo}}</td>
                </tr>
                {{/each}}
            </tbody>
        </table>
        
        {{#if estadisticas}}
        <div class="estadisticas">
            <h3>Estadísticas del Reporte</h3>
            {{#each estadisticas}}
            <p><strong>{{clave}}:</strong> {{valor}}</p>
            {{/each}}
        </div>
        {{/if}}
        
        <div class="footer">
            <p>Sistema Administrativo E.E.S.T N°2 - Reporte generado automáticamente</p>
        </div>
        ';
        
        file_put_contents($this->templatePath . 'reporte_estudiantes.html', $plantilla);
    }

    /**
     * Crear plantilla para reporte de profesores
     */
    private function crearPlantillaReporteProfesores(): void
    {
        $plantilla = '
        <div class="header">
            <div class="titulo">{{titulo}}</div>
            <div class="subtitulo">E.E.S.T N°2 "Educación y Trabajo"</div>
        </div>
        
        <div class="info-reporte">
            <p><strong>Fecha de generación:</strong> {{fecha_generacion}}</p>
            <p><strong>Total de profesores:</strong> {{total_registros}}</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Apellido y Nombre</th>
                    <th>Email</th>
                    <th>Especialidad</th>
                    <th>Materias</th>
                    <th>Llamados</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                {{#each tabla}}
                <tr>
                    <td>{{dni}}</td>
                    <td>{{apellido}}, {{nombre}}</td>
                    <td>{{email}}</td>
                    <td>{{especialidad_nombre}}</td>
                    <td>{{materias_asignadas}}</td>
                    <td>{{llamados_realizados}}</td>
                    <td>{{activo}}</td>
                </tr>
                {{/each}}
            </tbody>
        </table>
        
        {{#if estadisticas}}
        <div class="estadisticas">
            <h3>Estadísticas del Reporte</h3>
            {{#each estadisticas}}
            <p><strong>{{clave}}:</strong> {{valor}}</p>
            {{/each}}
        </div>
        {{/if}}
        
        <div class="footer">
            <p>Sistema Administrativo E.E.S.T N°2 - Reporte generado automáticamente</p>
        </div>
        ';
        
        file_put_contents($this->templatePath . 'reporte_profesores.html', $plantilla);
    }

    /**
     * Crear plantilla para análisis de rendimiento
     */
    private function crearPlantillaAnalisisRendimiento(): void
    {
        $plantilla = '
        <div class="header">
            <div class="titulo">{{titulo}}</div>
            <div class="subtitulo">E.E.S.T N°2 "Educación y Trabajo"</div>
        </div>
        
        <div class="info-reporte">
            <p><strong>Fecha de generación:</strong> {{fecha_generacion}}</p>
        </div>
        
        {{#if resumen}}
        <div class="estadisticas">
            <h3>Resumen Ejecutivo</h3>
            <p><strong>Promedio General de Cursos:</strong> {{resumen.promedio_general_cursos}}</p>
            <p><strong>Promedio General de Materias:</strong> {{resumen.promedio_general_materias}}</p>
            <p><strong>Total de Aprobados:</strong> {{resumen.total_aprobados}}</p>
            <p><strong>Total de Notas:</strong> {{resumen.total_notas}}</p>
            <p><strong>Porcentaje de Aprobación General:</strong> {{resumen.porcentaje_aprobacion_general}}%</p>
        </div>
        {{/if}}
        
        <div class="contenido">
            <h3>Análisis por Cursos</h3>
            {{datos.analisis_cursos}}
            
            <h3>Análisis por Materias</h3>
            {{datos.analisis_materias}}
            
            <h3>Tendencias Bimestrales</h3>
            {{datos.tendencias_bimestrales}}
        </div>
        
        <div class="footer">
            <p>Sistema Administrativo E.E.S.T N°2 - Reporte generado automáticamente</p>
        </div>
        ';
        
        file_put_contents($this->templatePath . 'analisis_rendimiento.html', $plantilla);
    }

    /**
     * Crear plantilla para análisis de disciplina
     */
    private function crearPlantillaAnalisisDisciplina(): void
    {
        $plantilla = '
        <div class="header">
            <div class="titulo">{{titulo}}</div>
            <div class="subtitulo">E.E.S.T N°2 "Educación y Trabajo"</div>
        </div>
        
        <div class="info-reporte">
            <p><strong>Fecha de generación:</strong> {{fecha_generacion}}</p>
        </div>
        
        {{#if resumen}}
        <div class="estadisticas">
            <h3>Resumen Ejecutivo</h3>
            <p><strong>Total de Llamados:</strong> {{resumen.total_llamados}}</p>
            <p><strong>Total de estudiantes:</strong> {{resumen.total_estudiantes}}</p>
            <p><strong>Total con Sanción:</strong> {{resumen.total_con_sancion}}</p>
            <p><strong>Promedio de Llamados por Estudiante:</strong> {{resumen.promedio_llamados_por_estudiante}}</p>
            <p><strong>Porcentaje con Sanción:</strong> {{resumen.porcentaje_con_sancion}}%</p>
            <p><strong>Motivo Más Común:</strong> {{resumen.motivo_mas_comun}}</p>
        </div>
        {{/if}}
        
        <div class="contenido">
            <h3>Análisis por Cursos</h3>
            {{datos.analisis_cursos}}
            
            <h3>Análisis por Motivos</h3>
            {{datos.analisis_motivos}}
            
            <h3>Tendencias Mensuales</h3>
            {{datos.tendencias_mensuales}}
        </div>
        
        <div class="footer">
            <p>Sistema Administrativo E.E.S.T N°2 - Reporte generado automáticamente</p>
        </div>
        ';
        
        file_put_contents($this->templatePath . 'analisis_disciplina.html', $plantilla);
    }

    /**
     * Crear plantilla para estadísticas generales
     */
    private function crearPlantillaEstadisticasGenerales(): void
    {
        $plantilla = '
        <div class="header">
            <div class="titulo">{{titulo}}</div>
            <div class="subtitulo">E.E.S.T N°2 "Educación y Trabajo"</div>
        </div>
        
        <div class="info-reporte">
            <p><strong>Fecha de generación:</strong> {{fecha_generacion}}</p>
        </div>
        
        <div class="contenido">
            <h3>Estadísticas Generales</h3>
            {{datos}}
        </div>
        
        <div class="footer">
            <p>Sistema Administrativo E.E.S.T N°2 - Reporte generado automáticamente</p>
        </div>
        ';
        
        file_put_contents($this->templatePath . 'estadisticas_generales.html', $plantilla);
    }
}
