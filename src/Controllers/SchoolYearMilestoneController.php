<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\SchoolYearMilestoneService;

/**
 * Pantalla de calendario escolar: guardar cierre Feb/Mar por año lectivo.
 */
final class SchoolYearMilestoneController
{
    public function __construct(
        private readonly DatabaseInterface $database,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @return array{ok: bool, message: string}
     */
    public function procesarGuardado(array $post): array
    {
        $schoolYear = (int) ($post['school_year'] ?? 0);
        $date = trim((string) ($post['february_march_closure_date'] ?? ''));
        $notesRaw = $post['notes'] ?? null;
        $notes = is_string($notesRaw) ? trim($notesRaw) : '';
        $notesOrNull = $notes === '' ? null : $notes;

        $correctionEnabled = isset($post['grade_correction_enabled']) ? (int) $post['grade_correction_enabled'] : 0;
        $correctionStart = trim((string) ($post['grade_correction_start_date'] ?? ''));
        $correctionStartOrNull = $correctionStart === '' ? null : $correctionStart;
        $correctionEnd = trim((string) ($post['grade_correction_end_date'] ?? ''));
        $correctionEndOrNull = $correctionEnd === '' ? null : $correctionEnd;

        if ($schoolYear < 2000 || $schoolYear > 2100) {
            return ['ok' => false, 'message' => 'Indicá un año lectivo válido (2000–2100).'];
        }

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'message' => 'Indicá la fecha de cierre (formato correcto).'];
        }

        try {
            $service = new SchoolYearMilestoneService($this->database);
            $service->upsertFebruaryMarchClosure(
                $schoolYear,
                $date,
                $correctionEnabled,
                $correctionStartOrNull,
                $correctionEndOrNull,
                $notesOrNull
            );
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'No se pudo guardar. ' . $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Calendario guardado correctamente.'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarMilestones(): array
    {
        $service = new SchoolYearMilestoneService($this->database);

        return $service->listAllMilestones();
    }
}
