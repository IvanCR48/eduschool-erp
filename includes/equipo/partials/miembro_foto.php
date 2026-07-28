<?php
/**
 * @var array<string, mixed> $m
 * @var 'lg'|'sm' $tam
 */
$tam = $tam ?? 'lg';
$cls = $tam === 'sm' ? 'equipo-foto equipo-foto--sm' : 'equipo-foto equipo-foto--lg';
$placeholderCls = $tam === 'sm' ? 'equipo-foto-placeholder equipo-foto-placeholder--sm' : 'equipo-foto-placeholder equipo-foto-placeholder--lg';
$nombreAlt = htmlspecialchars(trim((string) (($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? ''))), ENT_QUOTES, 'UTF-8');
?>
<?php if (!empty($m['foto'])): ?>
    <img src="<?php echo htmlspecialchars((string) $m['foto'], ENT_QUOTES, 'UTF-8'); ?>"
         alt="Foto de <?php echo $nombreAlt; ?>"
         class="<?php echo htmlspecialchars($cls, ENT_QUOTES, 'UTF-8'); ?>">
<?php else: ?>
    <div class="<?php echo htmlspecialchars($placeholderCls, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <i class="fas fa-user"></i>
    </div>
<?php endif; ?>
