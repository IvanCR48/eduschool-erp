<?php
declare(strict_types=1);

namespace SistemaAdmin\Services;

use PDO;

class SchoolConfigService
{
    private static ?array $cachedConfig = null;

    /**
     * Gets a configuration value by key, with default fallback
     */
    public static function get(PDO $pdo, string $key, string $default = ''): string
    {
        self::ensureLoaded($pdo);
        return self::$cachedConfig[$key] ?? $default;
    }

    /**
     * Set/update a configuration key in the database
     */
    public static function set(PDO $pdo, string $key, string $value, string $category = 'academic'): bool
    {
        $stmt = $pdo->prepare("
            INSERT INTO configuracion_sistema (clave, valor, categoria, modificado_en) 
            VALUES (:key, :val, :cat, NOW())
            ON DUPLICATE KEY UPDATE valor = :val_upd, modificado_en = NOW()
        ");
        $result = $stmt->execute([
            'key' => $key,
            'val' => $value,
            'cat' => $category,
            'val_upd' => $value
        ]);
        if (self::$cachedConfig !== null) {
            self::$cachedConfig[$key] = $value;
        }
        return $result;
    }

    /**
     * School profile mode: 'general' (default) | 'technical' | 'custom'
     */
    public static function getSchoolProfile(PDO $pdo): string
    {
        $val = self::get($pdo, 'academico.perfil_escuela', '');
        if ($val !== '') {
            return $val;
        }
        return self::get($pdo, 'school_profile', 'general');
    }

    /**
     * Checks if specialties (especialidades) are enabled
     */
    public static function hasSpecialties(PDO $pdo): bool
    {
        $profile = self::getSchoolProfile($pdo);
        if ($profile === 'technical') {
            return true;
        }
        if ($profile === 'general') {
            return false;
        }
        return self::get($pdo, 'enable_specialties', '0') === '1';
    }

    /**
     * Checks if workshops (talleres) are enabled
     */
    public static function hasWorkshops(PDO $pdo): bool
    {
        $profile = self::getSchoolProfile($pdo);
        if ($profile === 'technical') {
            return true;
        }
        if ($profile === 'general') {
            return false;
        }
        return self::get($pdo, 'enable_workshops', '0') === '1';
    }

    /**
     * Checks if workshop groups (A/B/C/D) are enabled
     */
    public static function hasWorkshopGroups(PDO $pdo): bool
    {
        $profile = self::getSchoolProfile($pdo);
        if ($profile === 'technical') {
            return true;
        }
        if ($profile === 'general') {
            return false;
        }
        return self::get($pdo, 'enable_workshop_groups', '0') === '1';
    }

    /**
     * Active grading scale: 'numeric_10' | 'numeric_100' | 'letter_af' | 'gpa' | 'tea_tep_ted'
     */
    public static function getGradingScale(PDO $pdo): string
    {
        $val = self::get($pdo, 'academico.escala_notas', '');
        if ($val !== '') {
            return $val;
        }
        return self::get($pdo, 'grading_scale', 'numeric_10');
    }

    /**
     * Count of academic terms: 2 (Cuatrimestres), 3 (Trimestres), 4 (Bimestres/Quarters)
     */
    public static function getAcademicTermsCount(PDO $pdo): int
    {
        $valStr = self::get($pdo, 'academico.cantidad_periodos', '');
        if ($valStr === '') {
            $valStr = self::get($pdo, 'academic_terms_count', '2');
        }
        $val = (int) $valStr;
        return ($val >= 2 && $val <= 4) ? $val : 2;
    }

    /**
     * Gets the school name from database configuration or ENV fallback
     */
    public static function getSchoolName(PDO $pdo): string
    {
        $val = self::get($pdo, 'institucion.nombre', '');
        if ($val !== '') {
            return $val;
        }
        $valSys = self::get($pdo, 'sistema.nombre', '');
        if ($valSys !== '') {
            return $valSys;
        }
        return $_ENV['SCHOOL_NAME'] ?? ($GLOBALS['SA_SYSTEM_NAME'] ?? 'EduSchool ERP');
    }

    /**
     * Gets the school slogan/subtitle from database configuration or ENV fallback
     */
    public static function getSchoolSlogan(PDO $pdo): string
    {
        $val = self::get($pdo, 'institucion.slogan', '');
        if ($val !== '') {
            return $val;
        }
        $valSub = self::get($pdo, 'sistema.subtitulo', '');
        if ($valSub !== '') {
            return $valSub;
        }
        return $_ENV['SCHOOL_SLOGAN'] ?? ($GLOBALS['SA_SYSTEM_SUBTITLE'] ?? 'School Management System');
    }

    /**
     * Reset config cache
     */
    public static function clearCache(): void
    {
        self::$cachedConfig = null;
    }

    /**
     * Ensures configs are loaded in memory
     */
    private static function ensureLoaded(PDO $pdo): void
    {
        if (self::$cachedConfig !== null) {
            return;
        }
        self::$cachedConfig = [];
        try {
            $stmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['clave'])) {
                        self::$cachedConfig[(string) $row['clave']] = (string) ($row['valor'] ?? '');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore if table missing during initial setup
        }
    }
}
