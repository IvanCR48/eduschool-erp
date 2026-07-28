<?php
/**
 * Mensajes flash (éxito / advertencia / error).
 *
 * Variables esperadas: $success_message, $warning_message, $error_message
 */
?>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars((string) $success_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($warning_message)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars((string) $warning_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars((string) $error_message); ?></div>
    <?php endif; ?>
