<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Alta y baja lógica de equipo_directivo, usuarios vinculados y preceptor_curso.
 * Alta de preceptores: advisors.php. Resto de cargos de gestión: staff.php.
 */
class ServicioEquipoDirectivo extends BaseService
{
    /** @var array<string, array{prefix: string, rol: string, incremental: bool}> */
    private const CREDENCIALES_CONFIG = [
        'preceptor' => ['prefix' => 'preceptor', 'rol' => 'preceptor', 'incremental' => true],
        'secretario' => ['prefix' => 'secretario', 'rol' => 'secretario', 'incremental' => true],
        'vicedirector' => ['prefix' => 'vicedirector', 'rol' => 'directivo', 'incremental' => true],
        'director' => ['prefix' => 'director', 'rol' => 'directivo', 'incremental' => false],
    ];

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarCursosActivosParaPreceptor(): array
    {
        return $this->database->fetchAll(<<<'SQL'
            SELECT c.id, c.anio, c.division, esp.nombre AS especialidad
            FROM cursos c
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            WHERE c.activo = 1
            ORDER BY c.anio, c.division
            SQL,
            []
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarEquipoActivoOrdenado(): array
    {
        return $this->database->fetchAll(
            <<<'SQL'
            SELECT
                ed.*,
                u.dni AS usuario_login,
                u.rol AS usuario_rol,
                c.anio AS curso_anio,
                c.division AS curso_division,
                esp.nombre AS curso_especialidad
            FROM equipo_directivo ed
            LEFT JOIN usuarios u ON ed.usuario_id = u.id
            LEFT JOIN cursos c ON c.id = ed.curso_id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            WHERE ed.activo = 1
            ORDER BY
                CASE LOWER(ed.cargo)
                    WHEN 'admin' THEN 1
                    WHEN 'director' THEN 2
                    WHEN 'vicedirector' THEN 3
                    WHEN 'secretario' THEN 4
                    WHEN 'preceptor' THEN 5
                    ELSE 6
                END,
                ed.apellido, ed.nombre,
                ed.id ASC
            SQL,
            []
        );
    }

    /**
     * @param array<string, mixed> $post
     * @param bool $altaPreceptorForzada Si true, registra siempre como preceptor (desde advisors.php), sin leer `cargo` del POST.
     *
     * @return array{ok: bool, error: string, redirect_query: string|null}
     */
    public function registrarMiembro(array $post, ?int $auditoriaUsuarioId, string $ipAuditoria, bool $altaPreceptorForzada = false): array
    {
        if ($altaPreceptorForzada) {
            $cargo = 'preceptor';
        } else {
            $cargo = strtolower(trim((string) ($post['cargo'] ?? '')));
        }
        $cargosPermitidos = $altaPreceptorForzada
            ? ['preceptor']
            : ['secretario', 'vicedirector', 'director'];
        if (!in_array($cargo, $cargosPermitidos, true)) {
            return ['ok' => false, 'error' => 'El cargo seleccionado no es válido.', 'redirect_query' => null];
        }

        try {
            $credencialesUsuario = null;

            $this->database->transaction(function () use ($post, $cargo, $auditoriaUsuarioId, $ipAuditoria, &$credencialesUsuario): void {
                if ($cargo === 'director') {
                    $directorExistente = $this->database->fetch(<<<'SQL'
                        SELECT id FROM equipo_directivo
                        WHERE LOWER(cargo) = 'director' AND activo = 1
                        LIMIT 1
                        SQL,
                        []
                    );
                    if ($directorExistente !== null) {
                        throw new \InvalidArgumentException('Ya existe un Director activo. Elimine el actual antes de registrar uno nuevo.');
                    }
                }

                $cursoIdPreceptor = null;
                if ($cargo === 'preceptor') {
                    $cursoIdPreceptor = isset($post['curso_id']) ? (int) $post['curso_id'] : 0;
                    if ($cursoIdPreceptor < 1) {
                        throw new \InvalidArgumentException('Debe seleccionar el curso de trabajo del preceptor.');
                    }
                    $cursoOk = $this->database->fetch(
                        'SELECT id FROM cursos WHERE id = ? AND activo = 1',
                        [$cursoIdPreceptor]
                    );
                    if ($cursoOk === null) {
                        throw new \InvalidArgumentException('El curso seleccionado no es válido.');
                    }
                }

                $apellido = trim((string) ($post['apellido'] ?? ''));
                $nombre = trim((string) ($post['nombre'] ?? ''));
                if ($apellido === '' || $nombre === '') {
                    throw new \InvalidArgumentException('Apellido y nombre son obligatorios.');
                }

                $this->database->query(<<<'SQL'
                    INSERT INTO equipo_directivo (apellido, nombre, cargo, telefono, email, foto, curso_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    SQL,
                    [
                        $apellido,
                        $nombre,
                        $cargo,
                        !empty($post['telefono']) ? trim((string) $post['telefono']) : null,
                        !empty($post['email']) ? trim((string) $post['email']) : null,
                        !empty($post['foto']) ? trim((string) $post['foto']) : null,
                        $cursoIdPreceptor,
                    ]
                );
                $equipoId = (int) $this->database->lastInsertId();

                if ($cargo === 'preceptor' && $cursoIdPreceptor !== null && $cursoIdPreceptor > 0) {
                    try {
                        $this->database->query(
                            'INSERT INTO preceptor_curso (equipo_directivo_id, curso_id) VALUES (?, ?)',
                            [$equipoId, $cursoIdPreceptor]
                        );
                    } catch (\Throwable $e) {
                        // Instalaciones sin tabla preceptor_curso: queda solo equipo_directivo.curso_id
                    }
                }

                if (isset(self::CREDENCIALES_CONFIG[$cargo])) {
                    $cfg = self::CREDENCIALES_CONFIG[$cargo];
                    $prefix = $cfg['prefix'];
                    $rolUsuario = $cfg['rol'];
                    $incremental = $cfg['incremental'];

                    if ($incremental) {
                        $existentes = $this->database->fetchAll(
                            'SELECT dni FROM usuarios WHERE LOWER(dni) LIKE ?',
                            [$prefix . '%']
                        );
                        $numerosOcupados = [];
                        foreach ($existentes as $u) {
                            $dniExistente = strtolower(trim((string) ($u['dni'] ?? '')));
                            if (strpos($dniExistente, $prefix) === 0) {
                                $sufijo = substr($dniExistente, strlen($prefix));
                                if ($sufijo !== '' && ctype_digit($sufijo)) {
                                    $numerosOcupados[(int) $sufijo] = true;
                                }
                            }
                        }
                        $numeroDisponible = 1;
                        while (isset($numerosOcupados[$numeroDisponible])) {
                            $numeroDisponible++;
                        }
                        $usuarioLogin = $prefix . $numeroDisponible;
                    } else {
                        $usuarioLogin = $prefix;
                    }

                    $usuarioExistente = $this->database->fetch('SELECT id FROM usuarios WHERE dni = ?', [$usuarioLogin]);
                    if ($usuarioExistente !== null) {
                        throw new \InvalidArgumentException("El identificador {$usuarioLogin} ya existe. Intente nuevamente.");
                    }

                    $passwordTemporalPlano = $this->generarPasswordTemporalSegura();
                    $passwordHash = password_hash($passwordTemporalPlano, PASSWORD_ARGON2ID);
                    $this->database->query(
                        <<<'SQL'
                        INSERT INTO usuarios (dni, password_hash, nombre, apellido, email, rol, activo, must_change_password)
                        VALUES (?, ?, ?, ?, ?, ?, 1, 1)
                        SQL,
                        [
                            $usuarioLogin,
                            $passwordHash,
                            $nombre,
                            $apellido,
                            !empty($post['email']) ? trim((string) $post['email']) : null,
                            $rolUsuario,
                        ]
                    );
                    $usuarioId = (int) $this->database->lastInsertId();
                    $this->database->query(
                        'UPDATE equipo_directivo SET usuario_id = ? WHERE id = ?',
                        [$usuarioId, $equipoId]
                    );
                    $credencialesUsuario = $usuarioLogin . '|' . $passwordTemporalPlano;
                }

                $this->insertarAuditoriaSeguro(
                    'CREAR_MIEMBRO',
                    $equipoId,
                    $ipAuditoria,
                    $auditoriaUsuarioId,
                    [
                        'apellido' => $apellido,
                        'nombre' => $nombre,
                        'cargo' => $cargo,
                        'curso_id' => $cursoIdPreceptor,
                        'usuario_generado' => $credencialesUsuario,
                    ]
                );
            });

            if ($credencialesUsuario !== null) {
                return [
                    'ok' => true,
                    'error' => '',
                    'redirect_query' => http_build_query([
                        'success' => 'credenciales',
                        'username' => explode('|', $credencialesUsuario, 2)[0] ?? '',
                        'temp_password' => explode('|', $credencialesUsuario, 2)[1] ?? '',
                    ]),
                ];
            }

            return [
                'ok' => true,
                'error' => '',
                'redirect_query' => http_build_query(['success' => 'miembro']),
            ];
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'error' => 'Error al registrar miembro: ' . $e->getMessage(), 'redirect_query' => null];
        } catch (\Throwable) {
            return [
                'ok' => false,
                'error' => 'No se pudo registrar el miembro. Verificá los datos o contactá al administrador del sistema.',
                'redirect_query' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, error: string, redirect_query: string|null}
     */
    public function eliminarMiembro(int $miembroId, bool $operadorEsAdmin, ?int $auditoriaUsuarioId, string $ipAuditoria): array
    {
        if ($miembroId < 1) {
            return ['ok' => false, 'error' => 'Miembro no válido.', 'redirect_query' => null];
        }

        try {
            $nombreParaRedirect = '';

            $this->database->transaction(function () use ($miembroId, $operadorEsAdmin, $auditoriaUsuarioId, $ipAuditoria, &$nombreParaRedirect): void {
                $miembro = $this->database->fetch(
                    'SELECT apellido, nombre, cargo, usuario_id FROM equipo_directivo WHERE id = ?',
                    [$miembroId]
                );
                if ($miembro === null) {
                    throw new \InvalidArgumentException('Miembro no encontrado');
                }

                $cargoMiembro = strtolower((string) $miembro['cargo']);
                if ($cargoMiembro === 'admin') {
                    throw new \InvalidArgumentException('No se puede eliminar al Administrador del equipo directivo');
                }
                if ($cargoMiembro === 'director' || $cargoMiembro === 'directivo') {
                    if (!$operadorEsAdmin) {
                        throw new \InvalidArgumentException('Solo el Administrador puede eliminar miembros del equipo directivo con cargo de Director');
                    }
                }

                $this->database->query(
                    'UPDATE equipo_directivo SET activo = 0 WHERE id = ?',
                    [$miembroId]
                );

                if (!empty($miembro['usuario_id'])) {
                    $usuarioAsociado = $this->database->fetch(
                        'SELECT id, dni FROM usuarios WHERE id = ?',
                        [(int) $miembro['usuario_id']]
                    );
                    if ($usuarioAsociado !== null) {
                        $this->database->query(
                            'UPDATE usuarios SET activo = 0 WHERE id = ?',
                            [(int) $usuarioAsociado['id']]
                        );
                        if (isset(self::CREDENCIALES_CONFIG[$cargoMiembro]) && self::CREDENCIALES_CONFIG[$cargoMiembro]['incremental']) {
                            $this->database->query('DELETE FROM usuarios WHERE id = ?', [(int) $usuarioAsociado['id']]);
                        }
                    }
                }

                $nombreParaRedirect = $miembro['apellido'] . ', ' . $miembro['nombre'];

                $this->insertarAuditoriaSeguro(
                    'ELIMINAR_MIEMBRO',
                    $miembroId,
                    $ipAuditoria,
                    $auditoriaUsuarioId,
                    [
                        'apellido' => $miembro['apellido'],
                        'nombre' => $miembro['nombre'],
                        'cargo' => $miembro['cargo'],
                    ]
                );
            });

            return [
                'ok' => true,
                'error' => '',
                'redirect_query' => http_build_query([
                    'success' => 'eliminar',
                    'nombre' => $nombreParaRedirect,
                ]),
            ];
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'error' => 'Error al eliminar miembro: ' . $e->getMessage(), 'redirect_query' => null];
        } catch (\Throwable) {
            return [
                'ok' => false,
                'error' => 'No se pudo eliminar el miembro. Contactá al administrador del sistema si el problema continúa.',
                'redirect_query' => null,
            ];
        }
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function insertarAuditoriaSeguro(
        string $accion,
        int $entidadId,
        string $ip,
        ?int $usuarioId,
        array $datos
    ): void {
        try {
            $this->database->query(
                <<<'SQL'
                INSERT INTO logs_auditoria (timestamp, accion, entidad, entidad_id, ip, usuario_id, datos)
                VALUES (NOW(), ?, 'equipo_directivo', ?, ?, ?, ?)
                SQL,
                [
                    $accion,
                    $entidadId,
                    $ip,
                    $usuarioId,
                    json_encode($datos, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (\Throwable $e) {
            // No interrumpir el flujo principal
        }
    }

    private function generarPasswordTemporalSegura(int $longitud = 12): string
    {
        $longitud = max(8, $longitud);
        $minusculas = 'abcdefghijklmnopqrstuvwxyz';
        $mayusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numeros = '0123456789';
        $simbolos = '!@#$%^&*()-_=+[]{}.,?';

        $password = [
            $minusculas[random_int(0, strlen($minusculas) - 1)],
            $mayusculas[random_int(0, strlen($mayusculas) - 1)],
            $numeros[random_int(0, strlen($numeros) - 1)],
            $simbolos[random_int(0, strlen($simbolos) - 1)],
        ];

        $todos = $minusculas . $mayusculas . $numeros . $simbolos;
        while (count($password) < $longitud) {
            $password[] = $todos[random_int(0, strlen($todos) - 1)];
        }

        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }
}
