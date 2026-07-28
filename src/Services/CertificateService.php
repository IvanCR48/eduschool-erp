<?php
declare(strict_types=1);

namespace SistemaAdmin\Services;

use PDO;

class CertificateService
{
    /**
     * Fetch complete student & institutional certificate data by student ID
     */
    public static function getCertificateData(PDO $pdo, int $estudianteId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT e.*, c.anio, c.division, c.turno, esp.nombre as especialidad_nombre
            FROM estudiantes e
            LEFT JOIN cursos c ON e.curso_id = c.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            WHERE e.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $estudianteId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            return null;
        }

        // Institutional settings
        $schoolName = SchoolConfigService::get($pdo, 'school_name', 'Establecimiento Educativo');
        $schoolCity = SchoolConfigService::get($pdo, 'school_city', 'Ciudad');
        $authorityTitle = SchoolConfigService::get($pdo, 'authority_title', 'Dirección del Establecimiento');

        $cursoEtiqueta = ($student['anio'] ?? '') ? ($student['anio'] . '° ' . ($student['division'] ?? '')) : 'Sin curso asignado';
        if (!empty($student['especialidad_nombre']) && SchoolConfigService::hasSpecialties($pdo)) {
            $cursoEtiqueta .= ' (' . $student['especialidad_nombre'] . ')';
        }

        return [
            'student_id' => (int) $student['id'],
            'full_name' => trim(($student['apellido'] ?? '') . ', ' . ($student['nombre'] ?? '')),
            'dni' => (string) ($student['dni'] ?? ''),
            'course_label' => $cursoEtiqueta,
            'shift' => (string) ($student['turno'] ?? 'Mañana'),
            'blood_type' => (string) ($student['grupo_sanguineo'] ?? ''),
            'health_insurance' => (string) ($student['obra_social'] ?? ''),
            'school_name' => $schoolName,
            'school_city' => $schoolCity,
            'authority_title' => $authorityTitle,
            'issue_date' => date('Y-m-d'),
            'issue_day' => date('d'),
            'issue_month' => self::spanishMonth((int) date('n')),
            'issue_year' => date('Y'),
        ];
    }

    private static function spanishMonth(int $month): string
    {
        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return $months[$month - 1] ?? '';
    }
}
