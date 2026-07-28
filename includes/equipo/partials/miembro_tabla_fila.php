<?php
/** @var array<string, mixed> $m */
/** @var string $csrfToken */
/** @var bool $operador_es_admin */
$cargoN = (string) ($m['cargo_normalizado'] ?? '');
?>
<tr>
    <td>
        <div class="equipo-tabla-nombre">
            <?php $tam = 'sm'; include __DIR__ . '/miembro_foto.php'; ?>
            <strong><?php echo htmlspecialchars(trim((string) (($m['apellido'] ?? '') . ', ' . ($m['nombre'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
    </td>
    <td>
        <span class="status status-primary"><?php echo htmlspecialchars(ucwords($cargoN), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($cargoN === 'preceptor' && !empty($m['curso_id']) && isset($m['curso_anio'])): ?>
        <br><small><?php echo htmlspecialchars(
            (string) $m['curso_anio'] . '° ' . (string) ($m['curso_division'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        ); ?></small>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($m['telefono'])): ?>
            <i class="fas fa-phone"></i> <?php echo htmlspecialchars((string) $m['telefono'], ENT_QUOTES, 'UTF-8'); ?>
        <?php else: ?>
            <span class="equipo-tabla-muted">No registrado</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($m['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars((string) $m['email'], ENT_QUOTES, 'UTF-8'); ?>" class="equipo-tabla-mail">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string) $m['email'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php else: ?>
            <span class="equipo-tabla-muted">No registrado</span>
        <?php endif; ?>
    </td>
    <td>
        <span class="status status-success">Activo</span>
    </td>
    <td>
        <div class="equipo-tabla-acciones">
            <?php
            $layout = 'table';
            include __DIR__ . '/miembro_credenciales.php';
            ?>
            <?php $compact = false; include __DIR__ . '/miembro_eliminar.php'; ?>
        </div>
    </td>
</tr>
