<?php
/**
 * Botón o formulario de eliminar curso (tabla o vista por turnos).
 *
 * @var array<string, mixed> $curso
 * @var string $csrfToken
 * @var bool $compact clases más chicas para la tarjeta por turno
 */
$puedeEliminar = hasRole('admin') || hasRole('directivo');
if (!$puedeEliminar) {
    return;
}
$sinEstudiantes = (int) ($curso['cantidad_estudiantes'] ?? 0) === 0;
$labelCurso = (string) $curso['anio'] . '° ' . (string) $curso['division'];
$msg = '¿Estás seguro de que quieres eliminar el curso ' . $labelCurso . '? Esta acción no se puede deshacer.';
$btnClass = 'btn btn-sm btn-danger' . (!empty($compact) ? ' cursos-btn-del--compact' : '');
?>
<?php if ($sinEstudiantes): ?>
<form method="POST" class="cursos-inline-form js-confirm-submit" data-confirm-message="<?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="curso_id" value="<?php echo (int) $curso['id']; ?>">
    <button type="submit" name="eliminar_curso" class="<?php echo htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8'); ?>" title="Eliminar curso">
        <i class="fas fa-trash"></i>
    </button>
</form>
<?php else: ?>
<button type="button" class="<?php echo htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8'); ?>" disabled title="No se puede eliminar - Tiene estudiantes asignados">
    <i class="fas fa-trash"></i>
</button>
<?php endif; ?>
