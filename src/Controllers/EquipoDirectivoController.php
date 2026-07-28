<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Services\ServicioEquipoDirectivo;

/**
 * Orquesta POST/PRG y datos de vista para equipo directivo (staff.php).
 */
class EquipoDirectivoController extends BaseService
{
    private ServicioEquipoDirectivo $servicio;

    public function __construct(DatabaseInterface $database, ServicioEquipoDirectivo $servicio)
    {
        parent::__construct($database);
        $this->servicio = $servicio;
    }

    /**
     * @param array<string, mixed> $post
     * @param array{redirect_pagina?: string} $opciones redirect_pagina: staff.php | advisors.php tras PRG
     *
     * @return array{redirect: string|null, error: string}
     */
    public function procesarPost(
        array $post,
        bool $operadorEsAdmin,
        ?int $auditoriaUsuarioId,
        string $ipAuditoria,
        array $opciones = []
    ): array {
        $redirectPagina = (string) ($opciones['redirect_pagina'] ?? 'staff.php');
        if (!in_array($redirectPagina, ['staff.php', 'advisors.php'], true)) {
            $redirectPagina = 'staff.php';
        }

        if (isset($post['guardar_miembro'])) {
            $r = $this->servicio->registrarMiembro($post, $auditoriaUsuarioId, $ipAuditoria, false);
            if ($r['ok'] && $r['redirect_query'] !== null) {
                return ['redirect' => $this->urlRedirectPagina($redirectPagina, $r['redirect_query']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => $r['error']];
        }

        if (isset($post['guardar_preceptor'])) {
            $r = $this->servicio->registrarMiembro($post, $auditoriaUsuarioId, $ipAuditoria, true);
            if ($r['ok'] && $r['redirect_query'] !== null) {
                return ['redirect' => $this->urlRedirectPagina($redirectPagina, $r['redirect_query']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => $r['error']];
        }

        if (isset($post['eliminar_miembro'])) {
            $miembroId = isset($post['miembro_id']) ? (int) $post['miembro_id'] : 0;
            $r = $this->servicio->eliminarMiembro($miembroId, $operadorEsAdmin, $auditoriaUsuarioId, $ipAuditoria);
            if ($r['ok'] && $r['redirect_query'] !== null) {
                return ['redirect' => $this->urlRedirectPagina($redirectPagina, $r['redirect_query']), 'error' => ''];
            }

            return ['redirect' => null, 'error' => $r['error']];
        }

        return ['redirect' => null, 'error' => ''];
    }

    private function urlRedirectPagina(string $pagina, string $query): string
    {
        $path = $pagina . ($query !== '' ? '?' . $query : '');

        return function_exists('app_base_path') ? app_base_path($path) : $path;
    }

    /**
     * @return array{
     *   cursos_para_preceptor: list<array<string, mixed>>,
     *   equipo: list<array<string, mixed>>,
     *   total_miembros: int,
     *   cargos_diferentes_count: int,
     *   cargos_formulario: array<string, string>,
     *   cargos_predefinidos: list<string>,
     *   director_disponible: bool,
     *   operador_es_admin: bool
     * }
     */
    public function datosVista(bool $operadorEsAdmin): array
    {
        $cursos = $this->servicio->listarCursosActivosParaPreceptor();
        $equipoRawCompleto = $this->servicio->listarEquipoActivoOrdenado();
        $directorDisponible = true;
        foreach ($equipoRawCompleto as $fila) {
            if (strtolower((string) ($fila['cargo'] ?? '')) === 'director') {
                $directorDisponible = false;
                break;
            }
        }

        $equipoRaw = array_values(array_filter(
            $equipoRawCompleto,
            static fn (array $f): bool => strtolower(trim((string) ($f['cargo'] ?? ''))) !== 'preceptor'
        ));

        $cargosNormalizados = array_map(
            static fn (array $f): string => strtolower(trim((string) ($f['cargo'] ?? ''))),
            $equipoRaw
        );
        $cargosDiferentes = count(array_unique($cargosNormalizados));

        $cargosFormularioBase = [
            'secretario' => 'Secretario',
            'vicedirector' => 'Vicedirector',
        ];
        $cargosFormulario = $directorDisponible
            ? ['director' => 'Director'] + $cargosFormularioBase
            : $cargosFormularioBase;

        $equipo = $this->equipoConFlagsDeVista($equipoRaw, $operadorEsAdmin);

        return [
            'cursos_para_preceptor' => $cursos,
            'equipo' => $equipo,
            'total_miembros' => count($equipoRaw),
            'cargos_diferentes_count' => $cargosDiferentes,
            'cargos_formulario' => $cargosFormulario,
            'cargos_predefinidos' => [
                'admin',
                'director',
                'vicedirector',
                'secretario',
            ],
            'director_disponible' => $directorDisponible,
            'operador_es_admin' => $operadorEsAdmin,
        ];
    }

    /**
     * @param list<array<string, mixed>> $equipoRaw
     * @return list<array<string, mixed>>
     */
    private function equipoConFlagsDeVista(array $equipoRaw, bool $operadorEsAdmin): array
    {
        $out = [];
        foreach ($equipoRaw as $m) {
            $cargo = strtolower(trim((string) ($m['cargo'] ?? '')));
            $puedeEliminar = $cargo !== 'admin' && ($cargo !== 'director' || $operadorEsAdmin);
            $motivoCodigo = null;
            if (!$puedeEliminar) {
                if ($cargo === 'admin') {
                    $motivoCodigo = 'admin';
                } elseif ($cargo === 'director') {
                    $motivoCodigo = 'director';
                } else {
                    $motivoCodigo = 'otro';
                }
            }

            $out[] = array_merge($m, [
                'cargo_normalizado' => $cargo,
                'puede_eliminar' => $puedeEliminar,
                'eliminar_motivo_codigo' => $motivoCodigo,
            ]);
        }

        return $out;
    }
}
