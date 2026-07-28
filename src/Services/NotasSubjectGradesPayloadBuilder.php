<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

/**
 * Arma el array que consume {@see SubjectStatusService::calculateSubjectStatus()}
 * a partir de filas crudas de `notas` (misma materia, mismo school_year).
 *
 * Semestres base (solo evaluation_context = regular):
 * - Por defecto: **última nota por bimestre** (mayor `id`), alineado al boletín actual donde cada bimestre suele tener una instancia.
 * - Bimestre 1 → semester1_grade, bimestre 2 → semester2_grade (mapeo 1:1 con año lectivo en dos períodos).
 *
 * Si en el futuro cargás varios parciales por bimestre, podés cambiar a promedio
 * habilitando {@see self::fromNotasRowsForMateria()} con `$aggregateMode = self::AGGREGATE_AVERAGE`.
 */
final class NotasSubjectGradesPayloadBuilder
{
    public const AGGREGATE_LATEST_PER_BIMESTRE = 'latest';

    public const AGGREGATE_AVERAGE_PER_BIMESTRE = 'average';

    /**
     * Ciclo lectivo tipo Argentina (mar–feb): marzo–diciembre = mismo año; ene–feb = año anterior.
     */
    public static function inferSchoolYearCicloMarzoArgentina(\DateTimeInterface $reference): int
    {
        $im = \DateTimeImmutable::createFromInterface($reference)->setTime(0, 0);
        $month = (int) $im->format('n');
        $year = (int) $im->format('Y');

        return $month >= 3 ? $year : $year - 1;
    }

    /**
     * @param list<array<string, mixed>> $rows Filas de `notas` para UNA materia y UN school_year
     * @return array<string, mixed> Payload para SubjectStatusService
     */
    public static function fromNotasRowsForMateria(
        array $rows,
        int $schoolYear,
        string $aggregateMode = self::AGGREGATE_LATEST_PER_BIMESTRE,
    ): array {
        $regular = [];
        $recovery = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $ctx = (string) ($row['evaluation_context'] ?? 'regular');
            if ($ctx === 'regular') {
                $regular[] = $row;
                continue;
            }
            if (!in_array($ctx, [
                'intensification_first_semester',
                'intensification_december',
                'intensification_february_march',
            ], true)) {
                continue;
            }

            $scope = $row['recovery_scope'] ?? null;
            if ($scope === null || $scope === '') {
                continue;
            }

            $recovery[] = [
                'evaluation_context' => $ctx,
                'recovery_scope' => (string) $scope,
                'grade' => (float) ($row['calificacion'] ?? 0),
                'evaluated_at' => self::pickEvaluatedAt($row),
            ];
        }

        [$sem1, $sem2] = self::aggregateRegularSemesters($regular, $aggregateMode);

        return [
            'school_year' => $schoolYear,
            'semester1_grade' => $sem1,
            'semester2_grade' => $sem2,
            'recovery_rows' => array_values($recovery),
        ];
    }

    /**
     * @param list<array<string, mixed>> $regularRows
     * @return array{0: ?float, 1: ?float} [semester1, semester2]
     */
    private static function aggregateRegularSemesters(array $regularRows, string $aggregateMode): array
    {
        $byBimestre = [1 => [], 2 => []];
        foreach ($regularRows as $row) {
            $bim = (int) ($row['bimestre'] ?? 0);
            if (!isset($byBimestre[$bim])) {
                continue;
            }
            $byBimestre[$bim][] = $row;
        }

        $sem1 = self::aggregateOneBimestre($byBimestre[1], $aggregateMode);
        $sem2 = self::aggregateOneBimestre($byBimestre[2], $aggregateMode);

        return [$sem1, $sem2];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private static function aggregateOneBimestre(array $rows, string $aggregateMode): ?float
    {
        if ($rows === []) {
            return null;
        }

        if ($aggregateMode === self::AGGREGATE_AVERAGE_PER_BIMESTRE) {
            $sum = 0.0;
            $n = 0;
            foreach ($rows as $row) {
                $sum += (float) ($row['calificacion'] ?? 0);
                ++$n;
            }

            return $n > 0 ? round($sum / $n, 2) : null;
        }

        usort($rows, static function (array $a, array $b): int {
            $idA = (int) ($a['id'] ?? 0);
            $idB = (int) ($b['id'] ?? 0);

            return $idA <=> $idB;
        });
        $last = $rows[array_key_last($rows)];

        return isset($last['calificacion']) ? (float) $last['calificacion'] : null;
    }

    private static function pickEvaluatedAt(array $row): string
    {
        $f = $row['fecha'] ?? null;
        if (is_string($f) && $f !== '') {
            return substr($f, 0, 10);
        }

        $u = $row['actualizado_en'] ?? $row['creado_en'] ?? null;
        if (is_string($u) && $u !== '') {
            return substr($u, 0, 10);
        }

        return '';
    }
}
