<?php

declare(strict_types=1);

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Especialidad;

/**
 * Persistencia de especialidades (tabla especialidades).
 */
class EspecialidadMapper extends BaseMapper
{
    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function findById(int $id): ?Especialidad
    {
        $row = $this->database->fetch('SELECT * FROM especialidades WHERE id = ?', [$id]);

        return $row ? $this->mapRowToEspecialidad($row) : null;
    }

    /**
     * @return list<Especialidad>
     */
    public function findActivasOrdenadasPorNombre(): array
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM especialidades WHERE activa = 1 ORDER BY nombre'
        );

        return array_map([$this, 'mapRowToEspecialidad'], $rows);
    }

    public function countActivasConNombre(string $nombre, ?int $excluirId = null): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM especialidades WHERE nombre = ? AND activa = 1';
        $params = [$nombre];
        if ($excluirId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excluirId;
        }
        $row = $this->database->fetch($sql, $params);

        return (int) ($row['c'] ?? 0);
    }

    public function insert(Especialidad $especialidad): Especialidad
    {
        $this->database->query(
            'INSERT INTO especialidades (nombre, descripcion, activa) VALUES (?, ?, ?)',
            [
                $especialidad->getNombre(),
                $especialidad->getDescripcion(),
                $especialidad->estaActiva() ? 1 : 0,
            ]
        );
        $especialidad->setId((int) $this->database->lastInsertId());

        return $especialidad;
    }

    public function desactivar(int $id): void
    {
        $this->validateId($id);
        $this->database->query('UPDATE especialidades SET activa = 0 WHERE id = ?', [$id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToEspecialidad(array $row): Especialidad
    {
        $desc = $row['descripcion'] ?? null;
        $e = new Especialidad(
            (string) $row['nombre'],
            $desc !== null && $desc !== '' ? (string) $desc : null,
            (bool) ($row['activa'] ?? true)
        );
        $e->setId((int) $row['id']);

        return $e;
    }
}
