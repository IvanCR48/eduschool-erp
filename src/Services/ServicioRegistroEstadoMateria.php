<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Contracts\FebruaryMarchClosingProviderInterface;
use SistemaAdmin\DTO\SubjectStatusResult;
use SistemaAdmin\Mappers\NotaMapper;

/**
 * Orquesta lectura de `notas` + armado del payload + {@see SubjectStatusService}.
 */
final class ServicioRegistroEstadoMateria
{
    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly SubjectStatusService $subjectStatusService,
        private readonly FebruaryMarchClosingProviderInterface $calendarProvider,
        private readonly ?NotaMapper $notaMapper = null,
    ) {
    }

    /**
     * @throws \RuntimeException Si no hay fecha de cierre en calendario (desde el provider)
     */
    public function calcularEstadoMateria(int $estudianteId, int $materiaId, int $schoolYear): SubjectStatusResult
    {
        $mapper = $this->notaMapper ?? new NotaMapper($this->database);
        $rows = $mapper->fetchNotasRowsPorEstudianteCicloMaterias($estudianteId, $schoolYear, [$materiaId]);
        $payload = NotasSubjectGradesPayloadBuilder::fromNotasRowsForMateria($rows, $schoolYear);
        $closing = $this->calendarProvider->getFebruaryMarchClosingDate($schoolYear);

        return $this->subjectStatusService->calculateSubjectStatus($payload, $closing);
    }

    /**
     * @param list<int> $materiaIds
     * @return array<int, SubjectStatusResult> materia_id => resultado
     */
    public function calcularEstadosPorEstudiante(int $estudianteId, int $schoolYear, array $materiaIds): array
    {
        $mapper = $this->notaMapper ?? new NotaMapper($this->database);
        $ids = array_values(array_unique(array_filter(array_map(static fn ($v): int => (int) $v, $materiaIds), static fn (int $v): bool => $v > 0)));
        if ($ids === []) {
            return [];
        }

        $rows = $mapper->fetchNotasRowsPorEstudianteCicloMaterias($estudianteId, $schoolYear, $ids);
        $byMateria = [];
        foreach ($rows as $row) {
            $mid = (int) ($row['materia_id'] ?? 0);
            if ($mid < 1) {
                continue;
            }
            $byMateria[$mid][] = $row;
        }

        $closing = $this->calendarProvider->getFebruaryMarchClosingDate($schoolYear);
        $out = [];
        foreach ($ids as $mid) {
            $payload = NotasSubjectGradesPayloadBuilder::fromNotasRowsForMateria($byMateria[$mid] ?? [], $schoolYear);
            $out[$mid] = $this->subjectStatusService->calculateSubjectStatus($payload, $closing);
        }

        return $out;
    }

    /**
     * Igual que {@see calcularEstadoMateria} pero no propaga error de calendario vacío (útil en vistas).
     */
    public function calcularEstadoMateriaONull(int $estudianteId, int $materiaId, int $schoolYear): ?SubjectStatusResult
    {
        try {
            return $this->calcularEstadoMateria($estudianteId, $materiaId, $schoolYear);
        } catch (\Throwable) {
            return null;
        }
    }
}
