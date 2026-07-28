<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Models\Especialidad;
use SistemaAdmin\Services\ServicioEspecialidades;
use SistemaAdmin\Services\BaseService;

/**
 * Orquesta HTTP/vista de especialidades (especialidades.php).
 */
class EspecialidadController extends BaseService
{
    private ServicioEspecialidades $servicioEspecialidades;

    public function __construct(DatabaseInterface $database, ServicioEspecialidades $servicioEspecialidades)
    {
        parent::__construct($database);
        $this->servicioEspecialidades = $servicioEspecialidades;
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string}
     */
    public function procesarGuardarDesdePost(array $post): array
    {
        try {
            $this->servicioEspecialidades->crearDesdeFormulario($post);

            return ['success' => true, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Error al crear la especialidad: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al crear la especialidad: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error: string}
     */
    public function procesarDesactivarDesdePost(array $post): array
    {
        try {
            $id = isset($post['especialidad_id']) ? (int) $post['especialidad_id'] : 0;
            $this->servicioEspecialidades->desactivar($id);

            return ['success' => true, 'error' => ''];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Error al desactivar: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error al desactivar: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{especialidades: list<array<string, mixed>>}
     */
    public function datosVista(): array
    {
        $modelos = $this->servicioEspecialidades->obtenerActivasOrdenadas();

        return [
            'especialidades' => array_map(
                static fn (Especialidad $e): array => $e->toArray(),
                $modelos
            ),
        ];
    }
}
