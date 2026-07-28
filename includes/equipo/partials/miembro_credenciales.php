<?php
/**
 * @var array<string, mixed> $m
 * @var bool $operador_es_admin
 * @var 'card'|'table' $layout
 */
if (empty($operador_es_admin)) {
    return;
}
$mid = (int) ($m['id'] ?? 0);
$username = $m['usuario_login'] ?? null;
$sufijoPanel = $layout === 'table' ? 'credenciales-tabla-' : 'credenciales-';
$btnClass = $layout === 'card' ? 'btn btn-secondary btn-sm equipo-creds-btn--full' : 'btn btn-secondary btn-sm';
?>
<div class="equipo-creds">
    <?php if ($username !== null && $username !== ''): ?>
        <button type="button" class="<?php echo htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8'); ?>" data-csp-toggle-credenciales="<?php echo $mid; ?>">
            <i class="fas fa-eye"></i>Ver credenciales</button>
        <div id="<?php echo $sufijoPanel . $mid; ?>" class="equipo-creds-panel credenciales-info">
            <small class="equipo-creds-line"><strong>Usuario</strong> <?php echo htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php if ($layout === 'card'): ?>
            <small class="equipo-creds-hint">La contraseña temporal solo se muestra al crear el usuario.</small>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <small class="equipo-creds-sin"><?php echo $layout === 'card' ? 'No hay credenciales registradas para este miembro.' : 'Sin credenciales registradas.'; ?></small>
    <?php endif; ?>
</div>
