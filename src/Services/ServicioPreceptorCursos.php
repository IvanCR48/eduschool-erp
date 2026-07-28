<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Asignación de cursos a preceptores (tabla preceptor_curso + equipo_directivo.curso_id).
 */
class ServicioPreceptorCursos extends BaseService
{
    private ?bool $tablaPreceptorCursoDisponible = null;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    public function mensajeMigracionTablaAusente(): string
    {
        return 'Falta la tabla de relación entre preceptores y cursos en la base de datos. '
            . 'Un administrador debe aplicar la migración incluida en la carpeta database/migrations del proyecto (por ejemplo desde phpMyAdmin: Importar).';
    }

    public function esTablaPreceptorCursoDisponible(): bool
    {
        if ($this->tablaPreceptorCursoDisponible !== null) {
            return $this->tablaPreceptorCursoDisponible;
        }
        try {
            $this->database->query('SELECT 1 FROM preceptor_curso LIMIT 1');
            $this->tablaPreceptorCursoDisponible = true;
        } catch (\Throwable $e) {
            $this->tablaPreceptorCursoDisponible = false;
        }

        return $this->tablaPreceptorCursoDisponible;
    }

    /**
     * Alinea equipo_directivo.curso_id con el primer curso en preceptor_curso (compatibilidad).
     */
    public function sincronizarCursoPrimario(int $equipoDirectivoId): void
    {
        try {
            $r = $this->database->fetch(
                'SELECT curso_id FROM preceptor_curso WHERE equipo_directivo_id = ? ORDER BY curso_id ASC LIMIT 1',
                [$equipoDirectivoId]
            );
            $cid = null;
            if (is_array($r) && isset($r['curso_id']) && (int) $r['curso_id'] > 0) {
                $cid = (int) $r['curso_id'];
            }
            $this->database->query(
                'UPDATE equipo_directivo SET curso_id = ? WHERE id = ?',
                [$cid, $equipoDirectivoId]
            );
        } catch (\Throwable $e) {
            // Tabla ausente u otro error: no interrumpir
        }
    }

    public function agregarCursoAPreceptor(int $miembroId, int $cursoId): void
    {
        if ($miembroId < 1 || $cursoId < 1) {
            throw new \InvalidArgumentException('Datos no válidos.');
        }
        $cargoSql = $this->fragmentoSqlCargoPreceptor('ed');
        $miembro = $this->database->fetch(
            "SELECT ed.id FROM equipo_directivo ed WHERE ed.id = ? AND ed.activo = 1 AND {$cargoSql}",
            [$miembroId]
        );
        if ($miembro === null) {
            throw new \InvalidArgumentException('Preceptor no encontrado.');
        }
        $cursoOk = $this->database->fetch(
            'SELECT id FROM cursos WHERE id = ? AND activo = 1',
            [$cursoId]
        );
        if ($cursoOk === null) {
            throw new \InvalidArgumentException('Curso no válido.');
        }
        $this->database->query(
            'INSERT IGNORE INTO preceptor_curso (equipo_directivo_id, curso_id) VALUES (?, ?)',
            [$miembroId, $cursoId]
        );
        $this->sincronizarCursoPrimario($miembroId);
    }

    public function quitarCursoDePreceptor(int $miembroId, int $cursoId): void
    {
        if ($miembroId < 1 || $cursoId < 1) {
            throw new \InvalidArgumentException('Datos no válidos.');
        }
        $cargoSql = $this->fragmentoSqlCargoPreceptor('ed');
        $miembro = $this->database->fetch(
            "SELECT ed.id FROM equipo_directivo ed WHERE ed.id = ? AND ed.activo = 1 AND {$cargoSql}",
            [$miembroId]
        );
        if ($miembro === null) {
            throw new \InvalidArgumentException('Preceptor no encontrado.');
        }
        $stmt = $this->database->query(
            'DELETE FROM preceptor_curso WHERE equipo_directivo_id = ? AND curso_id = ?',
            [$miembroId, $cursoId]
        );
        $eliminados = (int) $stmt->rowCount();
        if ($eliminados === 0) {
            $this->database->query(
                'UPDATE equipo_directivo SET curso_id = NULL WHERE id = ? AND curso_id = ?',
                [$miembroId, $cursoId]
            );
        }
        $this->sincronizarCursoPrimario($miembroId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarPreceptoresActivos(): array
    {
        return $this->database->fetchAll(<<<'SQL'
            SELECT
                ed.id,
                ed.apellido,
                ed.nombre,
                ed.telefono,
                ed.email,
                ed.usuario_id,
                u.dni AS usuario_login,
                ed.curso_id
            FROM equipo_directivo ed
            LEFT JOIN usuarios u ON u.id = ed.usuario_id
            WHERE LOWER(TRIM(ed.cargo)) IN ('preceptor', 'preceptora') AND ed.activo = 1
            ORDER BY ed.apellido, ed.nombre, ed.id
            SQL,
            []
        );
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    public function mapaCursosPorEquipoDirectivoId(): array
    {
        $cursosPorPreceptor = [];
        try {
            $pcRows = $this->database->fetchAll(
                <<<'SQL'
                SELECT pc.equipo_directivo_id, pc.curso_id, c.anio, c.division, esp.nombre AS especialidad
                FROM preceptor_curso pc
                INNER JOIN cursos c ON c.id = pc.curso_id AND c.activo = 1
                LEFT JOIN especialidades esp ON esp.id = c.especialidad_id
                ORDER BY c.anio, c.division
                SQL,
                []
            );
            foreach ($pcRows as $row) {
                $eid = (int) $row['equipo_directivo_id'];
                $cursosPorPreceptor[$eid][] = $row;
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $cursosPorPreceptor;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarCursosActivos(): array
    {
        return $this->database->fetchAll(
            <<<'SQL'
            SELECT c.id, c.anio, c.division, esp.nombre AS especialidad
            FROM cursos c
            LEFT JOIN especialidades esp ON esp.id = c.especialidad_id
            WHERE c.activo = 1
            ORDER BY c.anio, c.division
            SQL,
            []
        );
    }

    /**
     * Texto corto para selects y badges (ej. "3° A · Informática").
     */
    public function etiquetaCurso(array $row): string
    {
        $lab = (string) ($row['anio'] ?? '?') . '° ' . (string) ($row['division'] ?? '');
        if (!empty($row['especialidad'])) {
            $lab .= ' · ' . (string) $row['especialidad'];
        }

        return $lab;
    }

    /**
     * @param list<array<string, mixed>> $cursosDisponibles cursos activos del sistema (para contar opciones libres)
     *
     * @return list<array{
     *   p: array<string, mixed>,
     *   eid: int,
     *   lista: list<array<string, mixed>>,
     *   idsAsignados: list<int>,
     *   opciones_libres: int
     * }>
     */
    public function construirFilasPreceptorVista(array $preceptores, array $cursosPorPreceptorId, array $cursosDisponibles): array
    {
        $filas = [];
        foreach ($preceptores as $p) {
            $eid = (int) $p['id'];
            $lista = $cursosPorPreceptorId[$eid] ?? [];
            if ($lista === [] && !empty($p['curso_id'])) {
                $legacy = $this->database->fetch(<<<'SQL'
                    SELECT c.id AS curso_id, c.anio, c.division, esp.nombre AS especialidad
                    FROM cursos c
                    LEFT JOIN especialidades esp ON esp.id = c.especialidad_id
                    WHERE c.id = ? AND c.activo = 1
                    SQL,
                    [(int) $p['curso_id']]
                );
                if ($legacy !== null) {
                    $lista = [$legacy];
                }
            }
            foreach ($lista as $k => $citem) {
                $lista[$k]['etiqueta'] = $this->etiquetaCurso($citem);
            }
            $idsAsignados = array_map(static function ($r) {
                return (int) $r['curso_id'];
            }, $lista);
            $opcionesLibres = 0;
            foreach ($cursosDisponibles as $opt) {
                if (!in_array((int) $opt['id'], $idsAsignados, true)) {
                    ++$opcionesLibres;
                }
            }
            $filas[] = [
                'p' => $p,
                'eid' => $eid,
                'lista' => $lista,
                'idsAsignados' => $idsAsignados,
                'opciones_libres' => $opcionesLibres,
            ];
        }

        return $filas;
    }

    /**
     * Filas listas para la vista: opciones de select, botones quitar y nombres ya recortados.
     *
     * @param list<array<string, mixed>> $filas salida de construirFilasPreceptorVista
     * @param list<array<string, mixed>> $cursosDisponibles con clave etiqueta
     *
     * @return list<array<string, mixed>>
     */
    public function enriquecerFilasPreceptorParaVista(array $filas, array $cursosDisponibles): array
    {
        $out = [];
        foreach ($filas as $fp) {
            $idsAsignados = $fp['idsAsignados'];
            $lista = $fp['lista'];
            $cursosParaAgregar = [];
            foreach ($cursosDisponibles as $opt) {
                $oid = (int) $opt['id'];
                if (!in_array($oid, $idsAsignados, true)) {
                    $cursosParaAgregar[] = [
                        'id' => $oid,
                        'etiqueta' => (string) ($opt['etiqueta'] ?? $this->etiquetaCurso($opt)),
                    ];
                }
            }
            $cursosQuitar = [];
            foreach ($lista as $c) {
                $cursosQuitar[] = [
                    'curso_id' => (int) $c['curso_id'],
                    'etiqueta' => (string) ($c['etiqueta'] ?? $this->etiquetaCurso($c)),
                ];
            }
            $p = $fp['p'];
            $out[] = array_merge($fp, [
                'cursos_para_agregar' => $cursosParaAgregar,
                'cursos_quitar' => $cursosQuitar,
                'apellido_display' => trim((string) ($p['apellido'] ?? '')),
                'nombre_display' => trim((string) ($p['nombre'] ?? '')),
            ]);
        }

        return $out;
    }

    private function fragmentoSqlCargoPreceptor(string $alias = 'ed'): string
    {
        return 'LOWER(TRIM(' . $alias . ".cargo)) IN ('preceptor', 'preceptora')";
    }
}
