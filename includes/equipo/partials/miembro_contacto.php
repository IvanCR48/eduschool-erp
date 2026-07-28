<?php
/** @var array<string, mixed> $m */
$tel = trim((string) ($m['telefono'] ?? ''));
$mail = trim((string) ($m['email'] ?? ''));
?>
<div class="equipo-contacto">
    <?php if ($tel !== ''): ?>
    <div class="equipo-contacto__linea">
        <i class="fas fa-phone equipo-contacto__icono" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars($tel, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($mail !== ''): ?>
    <div class="equipo-contacto__linea">
        <i class="fas fa-envelope equipo-contacto__icono" aria-hidden="true"></i>
        <a href="mailto:<?php echo htmlspecialchars($mail, ENT_QUOTES, 'UTF-8'); ?>" class="equipo-contacto__mail">
            <?php echo htmlspecialchars($mail, ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </div>
    <?php endif; ?>
    <?php if ($tel === '' && $mail === ''): ?>
    <small class="equipo-contacto__vacio">No hay información de contacto registrada</small>
    <?php endif; ?>
</div>
