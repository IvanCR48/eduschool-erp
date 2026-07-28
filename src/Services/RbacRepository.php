<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Lectura de la matriz RBAC en base de datos (tablas rbac_*).
 */
final class RbacRepository
{
    public function __construct(private DatabaseInterface $database)
    {
    }

    public function isInstalled(): bool
    {
        try {
            $row = $this->database->fetch('SELECT COUNT(*) AS c FROM rbac_permissions');

            return $row !== null && (int) ($row['c'] ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    public function getSlugsForRole(string $role): array
    {
        try {
            $rows = $this->database->fetchAll(
                'SELECT rp.permission_slug AS slug FROM rbac_role_permissions rp
                 INNER JOIN rbac_permissions p ON p.slug = rp.permission_slug
                 WHERE rp.role = ?
                 ORDER BY rp.permission_slug',
                [$role]
            );
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $s = (string) ($row['slug'] ?? '');
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function getAllSlugs(): array
    {
        try {
            $rows = $this->database->fetchAll('SELECT slug FROM rbac_permissions ORDER BY slug');
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $s = (string) ($row['slug'] ?? '');
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return $out;
    }
}
