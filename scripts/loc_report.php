<?php
// Simple LOC report for the whole project
// Open in browser: /SistemaAdmin/scripts/loc_report.php

declare(strict_types=1);

// Configuración: carpetas a ignorar
$ignoreDirs = [
    'vendor', 'node_modules', '.git', '.idea', '.vscode', 'storage', 'public/logs', 'public/errors', 'scripts'
];

// Mapeo de extensiones a lenguajes
$extToLang = [
    'php' => 'PHP',
    'phtml' => 'PHP',
    'js' => 'JavaScript',
    'ts' => 'TypeScript',
    'css' => 'CSS',
    'scss' => 'CSS',
    'sass' => 'CSS',
    'html' => 'HTML',
    'htm' => 'HTML',
    'vue' => 'HTML',
    'jsx' => 'JavaScript',
    'tsx' => 'TypeScript',
    'sql' => 'SQL',
    'md' => 'Markdown',
    'yml' => 'YAML',
    'yaml' => 'YAML',
    'json' => 'JSON',
    'xml' => 'XML',
    'ini' => 'Config',
    'env' => 'Config',
];

$root = realpath(dirname(__DIR__));

function shouldIgnore(string $path, array $ignoreDirs): bool {
    foreach ($ignoreDirs as $dir) {
        if (strpos($path, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR) !== false) {
            return true;
        }
        // Ignorar si termina exactamente en el directorio ignorado
        if (substr($path, -strlen(DIRECTORY_SEPARATOR . $dir)) === DIRECTORY_SEPARATOR . $dir) {
            return true;
        }
    }
    return false;
}

function countFileLines(string $filePath): int {
    // Evitar contar binarios grandes por accidente
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $binaryExts = ['png','jpg','jpeg','gif','webp','ico','pdf','woff','woff2','ttf','eot'];
    if (in_array($ext, $binaryExts, true)) {
        return 0;
    }
    // Contar líneas de manera eficiente
    $lines = 0;
    $handle = @fopen($filePath, 'rb');
    if ($handle === false) {
        return 0;
    }
    while (!feof($handle)) {
        $chunk = fgets($handle);
        if ($chunk !== false) {
            $lines++;
        }
    }
    fclose($handle);
    return $lines;
}

$byLanguage = [];
$byExtension = [];
$totalFiles = 0;
$totalLines = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $fileInfo) {
    $path = $fileInfo->getPathname();
    // Normalizar separadores
    $norm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    if (shouldIgnore($norm, $ignoreDirs)) {
        // Saltar subárbol completo si el directorio es ignorado
        if ($fileInfo->isDir()) {
            $iterator->next();
        }
        continue;
    }
    if (!$fileInfo->isFile()) {
        continue;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $lang = $extToLang[$ext] ?? strtoupper($ext ?: 'other');

    $lines = countFileLines($path);
    if ($lines === 0) {
        continue;
    }

    $totalFiles++;
    $totalLines += $lines;

    if (!isset($byLanguage[$lang])) {
        $byLanguage[$lang] = ['files' => 0, 'lines' => 0];
    }
    $byLanguage[$lang]['files']++;
    $byLanguage[$lang]['lines'] += $lines;

    if (!isset($byExtension[$ext])) {
        $byExtension[$ext] = ['files' => 0, 'lines' => 0];
    }
    $byExtension[$ext]['files']++;
    $byExtension[$ext]['lines'] += $lines;
}

// Ordenar por líneas desc
uasort($byLanguage, fn($a, $b) => $b['lines'] <=> $a['lines']);
uasort($byExtension, fn($a, $b) => $b['lines'] <=> $a['lines']);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('auto.reporte_loc_sistemaadmin'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/SistemaAdmin/css/style.css">
    <style>
        body { padding: 1.5rem; }
        h1 { margin-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        tfoot td { font-weight: 700; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem; }
    </style>
    <?php $nonce = $GLOBALS['csp_nonce'] ?? ''; ?>
</head>
<body>
    <h1><?php echo htmlspecialchars(__('auto.reporte_de_l_neas_de_c_digo_loc'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="card" style="margin-bottom: 1rem;">
        <div><strong><?php echo htmlspecialchars(__('auto.ra_z_analizada'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars($root); ?></div>
        <div><strong><?php echo htmlspecialchars(__('auto.archivos_totales'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo number_format($totalFiles); ?></div>
        <div><strong><?php echo htmlspecialchars(__('auto.l_neas_totales'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo number_format($totalLines); ?></div>
    </div>
    <div class="grid">
        <div class="card">
            <h3>Por Lenguaje</h3>
            <table>
                <thead>
                    <tr><th>Lenguaje</th><th>Archivos</th><th>Líneas</th></tr>
                </thead>
                <tbody>
                <?php foreach ($byLanguage as $lang => $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lang); ?></td>
                        <td><?php echo number_format($stat['files']); ?></td>
                        <td><?php echo number_format($stat['lines']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><?php echo htmlspecialchars(__('auto.total'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format(array_sum(array_column($byLanguage, 'files'))); ?></td>
                        <td><?php echo number_format(array_sum(array_column($byLanguage, 'lines'))); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card">
            <h3>Por Extensión</h3>
            <table>
                <thead>
                    <tr><th>Extensión</th><th>Archivos</th><th>Líneas</th></tr>
                </thead>
                <tbody>
                <?php foreach ($byExtension as $ext => $stat): ?>
                    <tr>
                        <td>.<?php echo htmlspecialchars($ext ?: ''); ?></td>
                        <td><?php echo number_format($stat['files']); ?></td>
                        <td><?php echo number_format($stat['lines']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td><?php echo number_format(array_sum(array_column($byExtension, 'files'))); ?></td>
                        <td><?php echo number_format(array_sum(array_column($byExtension, 'lines'))); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card" style="margin-top: 1rem;">
        <h3>Notas</h3>
        <ul>
            <li>Se ignoran directorios comunes: <?php echo implode(', ', $ignoreDirs); ?>.</li>
            <li>Archivos binarios (imágenes, fuentes) no se contabilizan.</li>
            <li><?php echo htmlspecialchars(__('auto.el_conteo_es_aproximado_y_se_basa_en_l_neas'), ENT_QUOTES, 'UTF-8'); ?></li>
        </ul>
    </div>
</body>
</html>


