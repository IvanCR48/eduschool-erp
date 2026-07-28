<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Contracts\FebruaryMarchClosingProviderInterface;

/**
 * Lee `school_year_milestones` (tabla de configuración por ciclo).
 * Opción robusta para entorno escolar: secretaría/directivos ajustan fechas sin tocar código.
 */
final class SchoolYearMilestoneService implements FebruaryMarchClosingProviderInterface
{
    public function __construct(
        private readonly DatabaseInterface $database,
    ) {
        $this->checkAndMigrateSchema();
    }

    private function checkAndMigrateSchema(): void
    {
        try {
            $row = $this->database->fetch(
                "SHOW COLUMNS FROM `school_year_milestones` LIKE 'grade_correction_enabled'"
            );
            if ($row === null) {
                $this->database->query(<<<'SQL'
                    ALTER TABLE `school_year_milestones`
                    ADD COLUMN `grade_correction_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Habilitacion manual de correccion de notas' AFTER `february_march_closure_date`,
                    ADD COLUMN `grade_correction_start_date` DATE DEFAULT NULL COMMENT 'Fecha de inicio del periodo de correcciones' AFTER `grade_correction_enabled`,
                    ADD COLUMN `grade_correction_end_date` DATE DEFAULT NULL COMMENT 'Fecha de fin del periodo de correcciones' AFTER `grade_correction_start_date`
                    SQL
                );
            }
        } catch (\Throwable $e) {
            // Silence or log error to avoid crashing application
        }
    }

    public function getFebruaryMarchClosingDate(int $schoolYear): \DateTimeImmutable
    {
        if ($schoolYear < 1990 || $schoolYear > 2100) {
            throw new \InvalidArgumentException('school_year fuera de rango');
        }

        $row = $this->database->fetch(<<<'SQL'
            SELECT february_march_closure_date
            FROM school_year_milestones
            WHERE school_year = ?
            LIMIT 1
            SQL,
            [$schoolYear]
        );

        if ($row === null || empty($row['february_march_closure_date'])) {
            throw new \RuntimeException(
                'No hay february_march_closure_date configurado para school_year=' . $schoolYear
                . '. Insertá una fila en school_year_milestones o usá un fallback explícito en el caller.'
            );
        }

        $raw = (string) $row['february_march_closure_date'];
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $raw, new \DateTimeZone(date_default_timezone_get()));

        if ($dt === false) {
            throw new \RuntimeException('february_march_closure_date inválido en BD: ' . $raw);
        }

        return $dt->setTime(0, 0);
    }

    /**
     * Obtiene la configuración del período de correcciones para un año lectivo.
     *
     * @return array{enabled: int, start_date: string|null, end_date: string|null}
     */
    public function getCorrectionPeriod(int $schoolYear): array
    {
        if ($schoolYear < 1990 || $schoolYear > 2100) {
            return ['enabled' => 0, 'start_date' => null, 'end_date' => null];
        }

        try {
            $row = $this->database->fetch(
                <<<'SQL'
                SELECT grade_correction_enabled, grade_correction_start_date, grade_correction_end_date
                FROM school_year_milestones
                WHERE school_year = ?
                LIMIT 1
                SQL,
                [$schoolYear]
            );

            if ($row === null) {
                return ['enabled' => 0, 'start_date' => null, 'end_date' => null];
            }

            return [
                'enabled' => (int) ($row['grade_correction_enabled'] ?? 0),
                'start_date' => $row['grade_correction_start_date'] ?? null,
                'end_date' => $row['grade_correction_end_date'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['enabled' => 0, 'start_date' => null, 'end_date' => null];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllMilestones(): array
    {
        return $this->database->fetchAll(<<<'SQL'
            SELECT id, school_year, february_march_closure_date, grade_correction_enabled, grade_correction_start_date, grade_correction_end_date, notes, created_at, updated_at
            FROM school_year_milestones
            ORDER BY school_year DESC
            SQL,
            []
        );
    }

    /**
     * Inserta o actualiza el cierre Feb/Mar y el período de corrección para un año lectivo (clave única `school_year`).
     *
     * @throws \InvalidArgumentException Si año o fecha son inválidos
     */
    public function upsertFebruaryMarchClosure(
        int $schoolYear,
        string $closureDateYmd,
        int $correctionEnabled = 0,
        ?string $correctionStartDate = null,
        ?string $correctionEndDate = null,
        ?string $notes = null
    ): void {
        if ($schoolYear < 1990 || $schoolYear > 2100) {
            throw new \InvalidArgumentException('Año lectivo fuera de rango permitido.');
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $closureDateYmd);
        if ($parsed === false || $parsed->format('Y-m-d') !== $closureDateYmd) {
            throw new \InvalidArgumentException('La fecha debe tener formato AAAA-MM-DD.');
        }

        if ($correctionStartDate !== null && $correctionStartDate !== '') {
            $parsedStart = \DateTimeImmutable::createFromFormat('Y-m-d', $correctionStartDate);
            if ($parsedStart === false || $parsedStart->format('Y-m-d') !== $correctionStartDate) {
                throw new \InvalidArgumentException('La fecha de inicio de corrección debe tener formato AAAA-MM-DD.');
            }
        } else {
            $correctionStartDate = null;
        }

        if ($correctionEndDate !== null && $correctionEndDate !== '') {
            $parsedEnd = \DateTimeImmutable::createFromFormat('Y-m-d', $correctionEndDate);
            if ($parsedEnd === false || $parsedEnd->format('Y-m-d') !== $correctionEndDate) {
                throw new \InvalidArgumentException('La fecha de fin de corrección debe tener formato AAAA-MM-DD.');
            }
        } else {
            $correctionEndDate = null;
        }

        if ($correctionStartDate !== null && $correctionEndDate !== null && $correctionStartDate > $correctionEndDate) {
            throw new \InvalidArgumentException('La fecha de inicio de corrección no puede ser posterior a la de fin.');
        }

        $this->database->query(
            <<<'SQL'
            INSERT INTO school_year_milestones (
                school_year,
                february_march_closure_date,
                grade_correction_enabled,
                grade_correction_start_date,
                grade_correction_end_date,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                february_march_closure_date = VALUES(february_march_closure_date),
                grade_correction_enabled = VALUES(grade_correction_enabled),
                grade_correction_start_date = VALUES(grade_correction_start_date),
                grade_correction_end_date = VALUES(grade_correction_end_date),
                notes = VALUES(notes)
            SQL,
            [$schoolYear, $closureDateYmd, $correctionEnabled, $correctionStartDate, $correctionEndDate, $notes]
        );
    }
}
