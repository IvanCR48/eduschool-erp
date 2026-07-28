<?php

namespace SistemaAdmin\DTOs;

/**
 * DTO para datos de estudiante
 */
class EstudianteDTO
{
    private ?int $id;
    private string $dni;
    private string $nombre;
    private string $apellido;
    private ?string $fechaNacimiento;
    private ?string $grupoSanguineo;
    private ?string $obraSocial;
    private ?string $domicilio;
    private ?string $telefonoFijo;
    private ?string $telefonoCelular;
    private ?string $email;
    private ?int $cursoId;
    private ?string $cursoNombre;
    private ?string $especialidadNombre;
    private bool $activo;

    public function __construct(
        ?int $id,
        string $dni,
        string $nombre,
        string $apellido,
        ?string $fechaNacimiento = null,
        ?string $grupoSanguineo = null,
        ?string $obraSocial = null,
        ?string $domicilio = null,
        ?string $telefonoFijo = null,
        ?string $telefonoCelular = null,
        ?string $email = null,
        ?int $cursoId = null,
        ?string $cursoNombre = null,
        ?string $especialidadNombre = null,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->dni = $dni;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->grupoSanguineo = $grupoSanguineo;
        $this->obraSocial = $obraSocial;
        $this->domicilio = $domicilio;
        $this->telefonoFijo = $telefonoFijo;
        $this->telefonoCelular = $telefonoCelular;
        $this->email = $email;
        $this->cursoId = $cursoId;
        $this->cursoNombre = $cursoNombre;
        $this->especialidadNombre = $especialidadNombre;
        $this->activo = $activo;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getFechaNacimiento(): ?string
    {
        return $this->fechaNacimiento;
    }

    public function getGrupoSanguineo(): ?string
    {
        return $this->grupoSanguineo;
    }

    public function getObraSocial(): ?string
    {
        return $this->obraSocial;
    }

    public function getDomicilio(): ?string
    {
        return $this->domicilio;
    }

    public function getTelefonoFijo(): ?string
    {
        return $this->telefonoFijo;
    }

    public function getTelefonoCelular(): ?string
    {
        return $this->telefonoCelular;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCursoId(): ?int
    {
        return $this->cursoId;
    }

    public function getCursoNombre(): ?string
    {
        return $this->cursoNombre;
    }

    public function getEspecialidadNombre(): ?string
    {
        return $this->especialidadNombre;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function getNombreCompleto(): string
    {
        return $this->apellido . ', ' . $this->nombre;
    }

    public function getEdad(): int
    {
        if (!$this->fechaNacimiento) {
            return 0;
        }

        $fechaNacimiento = new \DateTime($this->fechaNacimiento);
        $hoy = new \DateTime();
        return $hoy->diff($fechaNacimiento)->y;
    }

    public function tieneContacto(): bool
    {
        return !empty($this->telefonoFijo) || !empty($this->telefonoCelular) || !empty($this->email);
    }

    // Crear desde array de base de datos
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            $data['dni'],
            $data['nombre'],
            $data['apellido'],
            $data['fecha_nacimiento'] ?? null,
            $data['grupo_sanguineo'] ?? null,
            $data['obra_social'] ?? null,
            $data['domicilio'] ?? null,
            $data['telefono_fijo'] ?? null,
            $data['telefono_celular'] ?? null,
            $data['email'] ?? null,
            isset($data['curso_id']) ? (int) $data['curso_id'] : null,
            $data['curso_nombre'] ?? null,
            $data['especialidad_nombre'] ?? null,
            (bool) ($data['activo'] ?? true)
        );
    }

    // Convertir a array
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'fecha_nacimiento' => $this->fechaNacimiento,
            'edad' => $this->getEdad(),
            'grupo_sanguineo' => $this->grupoSanguineo,
            'obra_social' => $this->obraSocial,
            'domicilio' => $this->domicilio,
            'telefono_fijo' => $this->telefonoFijo,
            'telefono_celular' => $this->telefonoCelular,
            'email' => $this->email,
            'curso_id' => $this->cursoId,
            'curso_nombre' => $this->cursoNombre,
            'especialidad_nombre' => $this->especialidadNombre,
            'activo' => $this->activo,
            'tiene_contacto' => $this->tieneContacto()
        ];
    }

    // Convertir a array para listados (datos básicos)
    public function toListArray(): array
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'nombre_completo' => $this->getNombreCompleto(),
            'curso_nombre' => $this->cursoNombre,
            'especialidad_nombre' => $this->especialidadNombre,
            'activo' => $this->activo
        ];
    }
}
