<?php
/**
 * @var array<string, mixed> $m
 * @var string $csrfToken
 * @var bool $compact ancho completo en card
 */
$compact = !empty($compact);
$mid = (int) ($m['id'] ?? 0);
$msg = '¿Estás seguro de que deseas eliminar a ' . trim((string) (($m['apellido'] ?? '') . ', ' . ($m['nombre'] ?? ''))) . ' del equipo directivo?';
$puede = !empty($m['puede_eliminar']);
$codigo = (string) ($m['eliminar_motivo_codigo'] ?? '');
?>
<div class="equipo-eliminar">
    <?php if ($puede): ?>
    <form method="POST" class="js-confirm-submit <?php echo $compact ? 'equipo-eliminar-form--full' : ''; ?>" data-confirm-message="<?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="miembro_id" value="<?php echo $mid; ?>">
        <button type="submit" name="eliminar_miembro" class="btn btn-danger btn-sm <?php echo $compact ? 'equipo-eliminar-btn--full' : ''; ?>">
            <i class="fas fa-trash"></i> <?php echo $compact ? 'Eliminar Miembro' : 'Eliminar'; ?>
        </button>
    </form>
    <?php else: ?>
    <small class="equipo-eliminar-bloqueado">
        <i class="fas fa-shield-alt"></i>
        <?php
        if ($codigo === 'admin') {
            echo 'Administrador - No se puede eliminar';
        } elseif ($codigo === 'director') {
            echo 'Solo el administrador puede eliminar al Director';
        } else {
            echo 'No se puede eliminar este cargo';
        }
        ?>
    </small>
    <?php endif; ?>
</div>
