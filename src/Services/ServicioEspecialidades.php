<?php

declare(strict_types=1);

namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Exceptions\EspecialidadNoEncontradaException;
use SistemaAdmin\Mappers\EspecialidadMapper;
use SistemaAdmin\Models\Especialidad;

/**
 * Alta, baja lógica y listado de especialidades para la gestión administrativa.
 */
class ServicioEspecialidades extends BaseService
{
    private EspecialidadMapper $especialidadMapper;

    public function __construct(DatabaseInterface $database, ?EspecialidadMapper $especialidadMapper = null)
    {
        parent::__construct($database);
        $this->especialidadMapper = $especialidadMapper ?? new EspecialidadMapper($database);
    }

    public function obtenerActivasOrdenadas(): array
    {
        return $this->especialidadMapper->findActivasOrdenadasPorNombre();
    }

    public function crearDesdeFormulario(array $post): void
    {
        $nombre = isset($post['nombre']) ? trim((string) $post['nombre']) : '';
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }

        $descripcionRaw = isset($post['descripcion']) ? trim((string) $post['descripcion']) : '';
        $descripcion = $descripcionRaw === '' ? null : $descripcionRaw;

        if ($this->especialidadMapper->countActivasConNombre($nombre) > 0) {
            throw new \InvalidArgumentException('Ya existe una especialidad activa con ese nombre.');
        }

        $especialidad = new Especialidad($nombre, $descripcion, true);
        $this->especialidadMapper->insert($especialidad);
    }

    public function desactivar(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Especialidad inválida.');
        }

        $this->especialidadMapper->desactivar($id);
    }

    public function buscarPorId(int $id): Especialidad
    {
        $especialidad = $this->especialidadMapper->findById($id);
        if ($especialidad === null) {
            throw new EspecialidadNoEncontradaException($id);
        }

        return $especialidad;
    }
}
