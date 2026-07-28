<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

/**
 * Servicio para procesar plantillas Excel (.xlsm) para la importación de estudiantes.
 */
class ExcelImportService
{
    /**
     * Procesa un archivo .xlsm y devuelve los datos estructurados del curso y los estudiantes.
     *
     * @param string $filePath Ruta absoluta al archivo .xlsm
     * @return array{
     *   curso: array{
     *     anio: ?int,
     *     division: ?string,
     *     turno: ?string,
     *     especialidad: ?string,
     *     ciclo_lectivo: ?int
     *   },
     *   estudiantes: list<array{
     *     apellido: string,
     *     nombre: string,
     *     dni: string,
     *     grupo_taller: ?string
     *   }>
     * }
     * @throws \Exception
     */
    public function parseXlsm(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("El archivo no existe en la ruta especificada.");
        }

        $tempDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'temp_excel_import_' . uniqid();
        $useFallback = !class_exists('ZipArchive');

        if (!$useFallback) {
            try {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    $sharedStringsContent = $zip->getFromName('xl/sharedStrings.xml');
                    $sheet1Content = $zip->getFromName('xl/worksheets/sheet1.xml');
                    $zip->close();

                    if ($sheet1Content === false) {
                        throw new \Exception("La hoja 1 (sheet1.xml) no pudo encontrarse en el archivo.");
                    }
                    
                    return $this->parseXmlData($sheet1Content, $sharedStringsContent !== false ? $sharedStringsContent : null);
                } else {
                    $useFallback = true;
                }
            } catch (\Throwable $e) {
                $useFallback = true;
            }
        }

        if ($useFallback) {
            // Fallback usando tar y comando del sistema (seguro en Windows)
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            try {
                $output = [];
                $returnCode = 0;
                $cmd = sprintf('tar -xf %s -C %s', escapeshellarg($filePath), escapeshellarg($tempDir));
                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    throw new \Exception("Error al extraer el archivo Excel mediante el descompresor del sistema.");
                }

                $ssPath = $tempDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'sharedStrings.xml';
                $sheetPath = $tempDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets' . DIRECTORY_SEPARATOR . 'sheet1.xml';

                if (!file_exists($sheetPath)) {
                    throw new \Exception("La plantilla de Excel no tiene un formato válido (falta hoja de cálculo principal).");
                }

                $sharedStringsContent = file_exists($ssPath) ? file_get_contents($ssPath) : null;
                $sheet1Content = file_get_contents($sheetPath);

                return $this->parseXmlData($sheet1Content, $sharedStringsContent);
            } finally {
                $this->deleteDirectory($tempDir);
            }
        }

        throw new \Exception("No fue posible procesar el archivo Excel.");
    }

    /**
     * Procesa los contenidos XML de la hoja de cálculo.
     */
    private function parseXmlData(string $sheetXmlContent, ?string $sharedStringsXmlContent): array
    {
        $sharedStrings = [];
        if ($sharedStringsXmlContent !== null) {
            $xml = @simplexml_load_string($sharedStringsXmlContent);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } else {
                        $text = '';
                        if (isset($si->r)) {
                            foreach ($si->r as $r) {
                                $text .= (string)$r->t;
                            }
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        $xmlSheet = @simplexml_load_string($sheetXmlContent);
        if (!$xmlSheet) {
            throw new \Exception("Error al interpretar los datos XML del archivo Excel.");
        }

        $rawCells = [];
        foreach ($xmlSheet->sheetData->row as $row) {
            foreach ($row->c as $c) {
                $cellRef = (string)$c['r']; // e.g. A1, B3
                $type = (string)$c['t']; // s = shared string
                $v = (string)$c->v;
                
                $val = $v;
                if ($type === 's' && isset($sharedStrings[(int)$v])) {
                    $val = $sharedStrings[(int)$v];
                }
                $rawCells[$cellRef] = trim($val);
            }
        }

        // 1. Extraer metadatos del curso en fila 1
        $courseStr = $rawCells['A1'] ?? '';
        $shiftStr = $rawCells['C1'] ?? '';
        $specialtyStr = $rawCells['D1'] ?? '';
        $schoolYearStr = $rawCells['Q1'] ?? '';

        $anio = null;
        $division = null;
        if (preg_match('/(\d+)\D+(\d+)/', $courseStr, $matches)) {
            $anio = (int)$matches[1];
            $division = (string)$matches[2];
        }

        $schoolYear = null;
        if ($schoolYearStr !== '') {
            $schoolYear = (int)preg_replace('/\D/', '', $schoolYearStr);
        }

        $cursoMeta = [
            'anio' => $anio,
            'division' => $division,
            'turno' => $shiftStr !== '' ? $shiftStr : null,
            'especialidad' => $specialtyStr !== '' ? $specialtyStr : null,
            'ciclo_lectivo' => $schoolYear
        ];

        // 2. Extraer estudiantes de fila 3 en adelante
        $estudiantes = [];
        // Para iterar, detectamos filas en base a las celdas disponibles
        // Un estudiante necesita al menos un DNI o un Nombre para ser considerado
        for ($rowNum = 3; $rowNum <= 200; $rowNum++) {
            $fullName = $rawCells['B' . $rowNum] ?? '';
            $dni = preg_replace('/\D/', '', $rawCells['C' . $rowNum] ?? '');
            $grupo = strtoupper(trim($rawCells['D' . $rowNum] ?? ''));

            if ($fullName === '' && $dni === '') {
                continue;
            }

            // Normalizar grupo
            if (!in_array($grupo, ['A', 'B', 'C', 'D', 'E'], true)) {
                $grupo = null;
            }

            // Separar nombre y apellido
            [$apellido, $nombre] = $this->splitFullName($fullName);

            if ($dni !== '' && $apellido !== '' && $nombre !== '') {
                $estudiantes[] = [
                    'apellido' => $apellido,
                    'nombre' => $nombre,
                    'dni' => $dni,
                    'grupo_taller' => $grupo
                ];
            }
        }

        return [
            'curso' => $cursoMeta,
            'estudiantes' => $estudiantes
        ];
    }

    /**
     * Divide un nombre completo en apellido y nombre aplicando una heurística en español.
     *
     * @return array{0: string, 1: string} [apellido, nombre]
     */
    public function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', ''];
        }

        // Si tiene coma: Apellido, Nombre
        if (strpos($fullName, ',') !== false) {
            $parts = explode(',', $fullName, 2);
            return [
                trim($parts[0]),
                trim($parts[1])
            ];
        }

        // Sin coma: Heurística basada en número de palabras
        $words = array_values(array_filter(explode(' ', $fullName)));
        $count = count($words);

        if ($count === 0) {
            return ['', ''];
        }
        if ($count === 1) {
            return [$words[0], ''];
        }
        if ($count === 2) {
            return [$words[0], $words[1]];
        }
        if ($count === 3) {
            // Ejemplo: Barrios Josias Simón -> Apellido: Barrios, Nombre: Josias Simón
            return [
                $words[0],
                implode(' ', array_slice($words, 1))
            ];
        }

        // 4 o más palabras: Ejemplo: Belasin Caroleo Sashenka Zoe -> Apellido: Belasin Caroleo, Nombre: Sashenka Zoe
        return [
            implode(' ', array_slice($words, 0, 2)),
            implode(' ', array_slice($words, 2))
        ];
    }

    /**
     * Elimina recursivamente un directorio y su contenido.
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
