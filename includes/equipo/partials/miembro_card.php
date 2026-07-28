<?php
/** @var array<string, mixed> $m */
/** @var string $csrfToken */
/** @var bool $operador_es_admin */
?>
<div class="miembro-card">
    <div class="miembro-header">
        <div class="miembro-foto-wrap">
            <?php $tam = 'lg'; include __DIR__ . '/miembro_foto.php'; ?>
        </div>
        <div class="miembro-info">
            <h4 class="miembro-nombre">
                <?php echo htmlspecialchars(trim((string) (($m['apellido'] ?? '') . ', ' . ($m['nombre'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>
            </h4>
            <span class="status status-primary miembro-cargo-badge">
                <?php echo htmlspecialchars(ucwords((string) ($m['cargo_normalizado'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php
            $cargoN = (string) ($m['cargo_normalizado'] ?? '');
            if ($cargoN === 'preceptor' && !empty($m['curso_id']) && isset($m['curso_anio'])):
            ?>
            <p class="miembro-curso-preceptor">
                <i class="fas fa-graduation-cap"></i>Curso<strong><?php echo htmlspecialchars(
                    (string) $m['curso_anio'] . '° ' . (string) ($m['curso_division'] ?? '') . (!empty($m['curso_especialidad']) ? ' — ' . (string) $m['curso_especialidad'] : ''),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?></strong>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="miembro-contacto-wrap">
        <?php include __DIR__ . '/miembro_contacto.php'; ?>
    </div>

    <div class="miembro-creds-wrap">
        <?php
        $layout = 'card';
        include __DIR__ . '/miembro_credenciales.php';
        ?>
    </div>

    <div class="miembro-actions">
        <?php $compact = true; include __DIR__ . '/miembro_eliminar.php'; ?>
    </div>
</div>
