<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Academic\SubjectComputedStatus;
use SistemaAdmin\DTO\SubjectStatusResult;

/**
 * Motor de estado: aprueba curso (2 semestres ≥ 7), escudo hasta cierre Feb/Mar, materia previa después.
 *
 * Uso recomendado de fecha de cierre:
 * - Inyectá `\DateTimeImmutable` desde `SchoolYearMilestoneService::getFebruaryMarchClosingDate($schoolYear)`
 *   (tabla `school_year_milestones`), no hardcode en múltiples vistas.
 *
 * Entrada $studentGrades (forma mínima):
 * ```
 * [
 *   'school_year' => 2025,
 *   'semester1_grade' => 6.5|null,  // consolidado REGULAR (sin recuperaciones)
 *   'semester2_grade' => 8.0|null,
 *   'recovery_rows' => [
 *     [
 *       'evaluation_context' => 'intensification_first_semester|intensification_december|intensification_february_march',
 *       'recovery_scope' => 'first_semester|second_semester|both',
 *       'grade' => 7.5,
 *       'evaluated_at' => '2025-12-10', // opcional: Y-m-d; desempata misma ventana
 *     ],
 *   ],
 * ]
 * ```
 *
 * Regla "both": una sola nota que reemplaza ambos consolidados semestrales con el mismo valor;
 * con grade ≥ 7 quedan aprobados los dos semestres a la vez.
 */
final class SubjectStatusService
{
    private const MIN_PASS = 7.0;

    private const CONTEXT_ORDER = [
        'intensification_first_semester' => 10,
        'intensification_december' => 20,
        'intensification_february_march' => 30,
    ];

    /**
     * @param array<string, mixed> $studentGrades
     */
    public function calculateSubjectStatus(array $studentGrades, \DateTimeInterface $febMarchClosingDate): SubjectStatusResult
    {
        $schoolYear = (int) ($studentGrades['school_year'] ?? 0);
        if ($schoolYear < 1) {
            throw new \InvalidArgumentException('studentGrades.school_year es obligatorio');
        }

        $sem1 = $this->toNullableFloat($studentGrades['semester1_grade'] ?? null);
        $sem2 = $this->toNullableFloat($studentGrades['semester2_grade'] ?? null);
        $recoveries = $studentGrades['recovery_rows'] ?? [];
        if (!is_array($recoveries)) {
            throw new \InvalidArgumentException('recovery_rows debe ser un array');
        }

        $sorted = $this->sortRecoveryRows($recoveries);
        $log = [];
        foreach ($sorted as $idx => $row) {
            $ctx = (string) ($row['evaluation_context'] ?? '');
            $scope = (string) ($row['recovery_scope'] ?? '');
            $grade = $this->toFloat($row['grade'] ?? null);

            if (!isset(self::CONTEXT_ORDER[$ctx])) {
                continue;
            }

            if ($ctx === 'intensification_first_semester' && $scope !== 'first_semester') {
                continue;
            }

            if ($scope === 'first_semester') {
                $sem1 = $grade;
                $log[] = sprintf('#%d %s / %s → sem1=%.2f', $idx + 1, $ctx, $scope, $grade);
            } elseif ($scope === 'second_semester') {
                $sem2 = $grade;
                $log[] = sprintf('#%d %s / %s → sem2=%.2f', $idx + 1, $ctx, $scope, $grade);
            } elseif ($scope === 'both') {
                $sem1 = $grade;
                $sem2 = $grade;
                $log[] = sprintf('#%d %s / both → sem1=sem2=%.2f', $idx + 1, $ctx, $grade);
            }
        }

        $bothOk = $this->isPassing($sem1) && $this->isPassing($sem2);
        if ($bothOk) {
            return new SubjectStatusResult(
                SubjectComputedStatus::Passed,
                $sem1,
                $sem2,
                true,
                $log,
            );
        }

        $today = new \DateTimeImmutable('today', $this->timezoneFromClosing($febMarchClosingDate));
        $closing = $this->normalizeToImmutableStartOfDay($febMarchClosingDate);

        if ($this->isCalendarAfterClosingInclusive($today, $closing)) {
            return new SubjectStatusResult(
                SubjectComputedStatus::Prerequisite,
                $sem1,
                $sem2,
                false,
                $log,
            );
        }

        return new SubjectStatusResult(
            SubjectComputedStatus::Intensification,
            $sem1,
            $sem2,
            false,
            $log,
        );
    }

    /**
     * @param list<array<string, mixed>> $recoveries
     * @return list<array<string, mixed>>
     */
    private function sortRecoveryRows(array $recoveries): array
    {
        $withIndex = [];
        foreach ($recoveries as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $ctx = (string) ($row['evaluation_context'] ?? '');
            $prio = self::CONTEXT_ORDER[$ctx] ?? 999;
            $dateStr = (string) ($row['evaluated_at'] ?? '');
            $ts = strtotime($dateStr) ?: 0;
            $withIndex[] = ['row' => $row, 'prio' => $prio, 'ts' => $ts, 'i' => $i];
        }

        usort($withIndex, static function (array $a, array $b): int {
            if ($a['prio'] !== $b['prio']) {
                return $a['prio'] <=> $b['prio'];
            }
            if ($a['ts'] !== $b['ts']) {
                return $a['ts'] <=> $b['ts'];
            }

            return $a['i'] <=> $b['i'];
        });

        return array_values(array_map(static fn (array $x): array => $x['row'], $withIndex));
    }

    private function isPassing(?float $grade): bool
    {
        return $grade !== null && $grade >= self::MIN_PASS - 0.00001;
    }

    private function toNullableFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }

    private function toFloat(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }

        return (float) $v;
    }

    private function normalizeToImmutableStartOfDay(\DateTimeInterface $d): \DateTimeImmutable
    {
        $im = \DateTimeImmutable::createFromInterface($d);

        return $im->setTime(0, 0);
    }

    private function timezoneFromClosing(\DateTimeInterface $febMarchClosingDate): \DateTimeZone
    {
        if ($febMarchClosingDate instanceof \DateTimeImmutable) {
            return $febMarchClosingDate->getTimezone();
        }
        if ($febMarchClosingDate instanceof \DateTime) {
            return $febMarchClosingDate->getTimezone();
        }

        return new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * Escudo vigente hasta el día de cierre inclusive; al día siguiente, período cerrado.
     */
    private function isCalendarAfterClosingInclusive(\DateTimeImmutable $today, \DateTimeImmutable $closing): bool
    {
        $t = $today->setTime(0, 0);
        $c = $closing->setTime(0, 0);

        return $t > $c;
    }
}
