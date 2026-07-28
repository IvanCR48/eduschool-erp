<?php
/**
 * Botón eliminar estudiante (POST + CSRF + confirmación CSP-safe).
 *
 * @var array<string, mixed> $estudiante
 * @var string $csrfToken
 * @var bool $preceptor_sin_curso
 */
if (!empty($preceptor_sin_curso)) {
    return;
}
$eid = (int) ($estudiante['id'] ?? 0);
if ($eid < 1) {
    return;
}
$nomPlano = trim((string) (($estudiante['apellido'] ?? '') . ', ' . ($estudiante['nombre'] ?? '')));
$msg = '¿Estás seguro de que deseas eliminar al estudiante "' . $nomPlano . '"? Esta acción no se puede deshacer.';
?>
<form method="POST" class="estudiantes-inline-form js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="estudiante_id" value="<?php echo $eid; ?>">
    <button type="submit" name="eliminar_estudiante" class="btn btn-sm btn-danger" title="Eliminar estudiante">
        <i class="fas fa-trash"></i>
    </button>
</form>
