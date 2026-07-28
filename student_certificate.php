<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/sistema_admin_autoload.php';
sistema_admin_load_autoload();

$pdo = sistema_admin_pdo();
$estudianteId = (int) ($_GET['estudiante_id'] ?? 0);
$certData = $estudianteId > 0 ? \SistemaAdmin\Services\CertificateService::getCertificateData($pdo, $estudianteId) : null;

$schoolName = $certData['school_name'] ?? \SistemaAdmin\Services\SchoolConfigService::get($pdo, 'school_name', 'Establecimiento Educativo');
$schoolCity = $certData['school_city'] ?? \SistemaAdmin\Services\SchoolConfigService::get($pdo, 'school_city', 'Ciudad');

$pageTitle = 'Certificado de alumno regular - ' . $schoolName;
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/certificado_alumno.css">
    <style nonce="<?php echo $nonce; ?>">
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f0f9ff;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }
        .certificado-page-section {
            width: 100%;
            max-width: 1050px;
        }
        .btn-primary {
            background-color: #2563eb;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<section class="certificado-page-section">
    <div class="actions-bar no-print">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 1.5rem;"><?php echo htmlspecialchars(__('auto.certificado_de_alumno_regular'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p style="margin: 0.5rem 0 0; color: #64748b;"><?php echo htmlspecialchars(__('auto.complete_los_datos_en_la_pantalla_antes_de_im'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="public/portal.php" class="btn-secondary"><i class="fas fa-arrow-left"></i><?php echo htmlspecialchars(__('auto.volver'), ENT_QUOTES, 'UTF-8'); ?></a>
            <button type="button" class="btn-primary" id="btn-imprimir-certificado">
                <i class="fas fa-print"></i><?php echo htmlspecialchars(__('auto.imprimir_certificado'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
    </div>

    <div class="certificado-preview-wrap">
        <div id="certificado-print-area" class="certificado-papel" role="document" aria-label="Constancia de alumno regular">
            <header class="certificado-header">
                <h1><?php echo htmlspecialchars(__('auto.constancia_de_alumno_a_regular'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="certificado-escuela"><?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></p>
            </header>

            <div class="certificado-cuerpo">
                <div class="certificado-row">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.se_hace_constar_que'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" class="cert-input" style="flex: 1;" placeholder="Nombre y Apellido del alumno/a" value="<?php echo htmlspecialchars($certData['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <p class="certificado-par"><?php echo htmlspecialchars(__('auto.es_alumno_a_regular_del_establecimiento_y_est'), ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="certificado-row">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.curso_escolar_en'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" class="cert-input" style="flex: 1;" placeholder="Año y División" value="<?php echo htmlspecialchars($certData['course_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="certificado-row">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.y_concurre_a_clase_en_el_turno'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <select class="cert-input cert-select" style="flex: 1;">
                        <?php $shift = $certData['shift'] ?? 'Mañana'; ?>
                        <option value="Mañana" <?php echo $shift === 'Mañana' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.ma_ana'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Tarde" <?php echo $shift === 'Tarde' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.tarde'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Vespertino" <?php echo $shift === 'Vespertino' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.vespertino'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Doble escolaridad" <?php echo $shift === 'Doble escolaridad' ? 'selected' : ''; ?>><?php echo htmlspecialchars(__('auto.doble_escolaridad'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>

                <p class="certificado-par"><?php echo htmlspecialchars(__('auto.a_pedido_del_interesado_y_al_solo_efecto_de_s'), ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="certificado-row">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.autoridades_de'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" class="cert-input" style="flex: 1;" value="<?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="certificado-row">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.se_le_extiende_la_presente_constancia_en'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" class="cert-input" style="flex: 1;" value="<?php echo htmlspecialchars($schoolCity, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="certificado-row certificado-row-fecha">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.a_los'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" class="cert-input" style="width: 50px; text-align: center;" value="<?php echo date('d'); ?>">
                    <span class="cert-txt"><?php echo htmlspecialchars(__('auto.d_as_del_mes_de'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" class="cert-input" style="width: 120px; text-align: center;" value="<?php 
                        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        echo $meses[(int)date('n') - 1]; 
                    ?>">
                    <span class="cert-txt">de</span>
                    <input type="text" class="cert-input" style="width: 60px; text-align: center;" value="<?php echo date('Y'); ?>">
                </div>
            </div>

            <footer class="certificado-pie">
                <div class="certificado-sello"><?php echo htmlspecialchars(__('auto.sello'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="certificado-firma-bloque">
                    <span class="cert-rule" aria-hidden="true"></span>
                    <p class="cert-firma-label"><?php echo htmlspecialchars(__('auto.firma_registrada'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </footer>

            <div class="certificado-disclaimer" style="margin-top: 0.85rem; border-top: 1px dotted #000; padding-top: 0.4rem; font-size: 7.5pt; color: #555; text-align: center; font-style: italic; line-height: 1.3;">
                Esta constancia es de carácter puramente informativo. Carece de validez legal u oficial si no cuenta con la firma física autógrafa del directivo/secretario y el sello húmedo oficial de la institución.
            </div>
        </div>
    </div>
</section>

<script nonce="<?php echo $nonce; ?>">
document.getElementById('btn-imprimir-certificado').addEventListener('click', function () {
    window.print();
});
</script>
</body>
</html>
