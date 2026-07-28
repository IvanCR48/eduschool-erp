<?php

namespace SistemaAdmin\Models;

/**
 * TDC (Tipo de Dato Concreto) - Modelo Profesor
 * 
 * Representa un profesor del sistema con validaciones internas
 * y métodos de negocio encapsulados.
 */
class Profesor
{
    private ?int $id;
    private string $dni;
    private string $nombre;
    private string $apellido;
    private ?\DateTime $fechaNacimiento;
    private ?string $domicilio;
    private ?string $telefonoFijo;
    private ?string $telefonoCelular;
    private ?string $email;
    private ?string $titulo;
    private ?int $especialidadId;
    private ?string $especialidadNombre;
    private ?\DateTime $fechaIngreso;
    private bool $activo;

    public function __construct(
        string $dni,
        string $apellido,
        string $nombre,
        ?\DateTime $fechaNacimiento = null,
        ?string $domicilio = null,
        ?string $telefonoFijo = null,
        ?string $telefonoCelular = null,
        ?string $email = null,
        ?string $titulo = null,
        ?int $especialidadId = null,
        ?string $especialidadNombre = null,
        ?\DateTime $fechaIngreso = null,
        bool $activo = true
    ) {
        $this->id = null;
        $this->setDni($dni);
        $this->setApellido($apellido);
        $this->setNombre($nombre);
        $this->fechaNacimiento = $fechaNacimiento;
        $this->domicilio = $domicilio;
        $this->telefonoFijo = $telefonoFijo;
        $this->telefonoCelular = $telefonoCelular;
        $this->setEmail($email);
        $this->titulo = $titulo;
        $this->especialidadId = $especialidadId;
        $this->especialidadNombre = $especialidadNombre;
        $this->fechaIngreso = $fechaIngreso;
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

    public function getNombreCompleto(): string
    {
        return $this->apellido . ', ' . $this->nombre;
    }

    public function getEspecialidadId(): ?int
    {
        return $this->especialidadId;
    }

    public function getEspecialidadNombre(): ?string
    {
        return $this->especialidadNombre;
    }

    public function getEspecialidad(): ?string
    {
        return $this->especialidadNombre;
    }

    public function getFechaNacimiento(): ?\DateTime
    {
        return $this->fechaNacimiento;
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

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function getFechaIngreso(): ?\DateTime
    {
        return $this->fechaIngreso;
    }

    public function esActivo(): bool
    {
        return $this->activo;
    }

    public function getEdad(): int
    {
        if ($this->fechaNacimiento === null) {
            return 0;
        }
        
        $hoy = new \DateTime();
        $edad = $hoy->diff($this->fechaNacimiento);
        return $edad->y;
    }

    // Setters con validaciones
    public function setDni(string $dni): void
    {
        if (!$this->validarDni($dni)) {
            throw new \InvalidArgumentException("DNI inválido: $dni");
        }
        $this->dni = $dni;
    }

    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if (empty($nombre)) {
            throw new \InvalidArgumentException("El nombre no puede estar vacío");
        }
        if (strlen($nombre) > 100) {
            throw new \InvalidArgumentException("El nombre no puede exceder 100 caracteres");
        }
        $this->nombre = $nombre;
    }

    public function setApellido(string $apellido): void
    {
        $apellido = trim($apellido);
        if (empty($apellido)) {
            throw new \InvalidArgumentException("El apellido no puede estar vacío");
        }
        if (strlen($apellido) > 100) {
            throw new \InvalidArgumentException("El apellido no puede exceder 100 caracteres");
        }
        $this->apellido = $apellido;
    }

    public function setEspecialidadId(?int $especialidadId): void
    {
        $this->especialidadId = $especialidadId;
    }

    public function setEspecialidadNombre(?string $especialidadNombre): void
    {
        if ($especialidadNombre !== null && strlen($especialidadNombre) > 100) {
            throw new \InvalidArgumentException("La especialidad no puede exceder 100 caracteres");
        }
        $this->especialidadNombre = $especialidadNombre;
    }

    public function setEspecialidad(?string $especialidad): void
    {
        $this->setEspecialidadNombre($especialidad);
    }

    public function setFechaNacimiento(?\DateTime $fechaNacimiento): void
    {
        $this->fechaNacimiento = $fechaNacimiento;
    }

    public function setDomicilio(?string $domicilio): void
    {
        $this->domicilio = $domicilio;
    }

    public function setTelefonoFijo(?string $telefonoFijo): void
    {
        if ($telefonoFijo !== null && !$this->validarTelefono($telefonoFijo)) {
            throw new \InvalidArgumentException("Teléfono fijo inválido: $telefonoFijo");
        }
        $this->telefonoFijo = $telefonoFijo;
    }

    public function setTelefonoCelular(?string $telefonoCelular): void
    {
        if ($telefonoCelular !== null && !$this->validarTelefono($telefonoCelular)) {
            throw new \InvalidArgumentException("Teléfono celular inválido: $telefonoCelular");
        }
        $this->telefonoCelular = $telefonoCelular;
    }

    public function setTitulo(?string $titulo): void
    {
        $this->titulo = $titulo;
    }

    public function setFechaIngreso(?\DateTime $fechaIngreso): void
    {
        $this->fechaIngreso = $fechaIngreso;
    }

    public function setEmail(?string $email): void
    {
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
            throw new \InvalidArgumentException("Email inválido: $email");
        }
        $this->email = $email;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    // Métodos de negocio
    public function validarDni(string $dni): bool
    {
        // Validación básica de DNI argentino (7-8 dígitos)
        $dni = preg_replace('/[^0-9]/', '', $dni);
        return strlen($dni) >= 7 && strlen($dni)<= 8;
    }

    public function validarTelefono(string $telefono): bool
    {
        // Validación básica de teléfono argentino
        // Limpiar el teléfono dejando solo dígitos
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        // Debe tener al menos 6 dígitos y máximo 15
        return strlen($telefonoLimpio) >= 6 && strlen($telefonoLimpio)<= 15;
    }

    public function tieneEspecialidad(): bool
    {
        return !empty($this->especialidadNombre);
    }

    public function tieneContacto(): bool
    {
        return !empty($this->telefonoFijo) || !empty($this->telefonoCelular) || !empty($this->email);
    }

    // Método para establecer ID (solo para uso interno de mappers)
    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException("No se puede modificar el ID de un profesor existente");
        }
        $this->id = $id;
    }

    // Método para convertir a array (útil para serialización)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'fecha_nacimiento' => $this->fechaNacimiento?->format('Y-m-d'),
            'edad' => $this->getEdad(),
            'domicilio' => $this->domicilio,
            'telefono_fijo' => $this->telefonoFijo,
            'telefono_celular' => $this->telefonoCelular,
            'email' => $this->email,
            'titulo' => $this->titulo,
            'especialidad' => $this->especialidadNombre,
            'fecha_ingreso' => $this->fechaIngreso?->format('Y-m-d'),
            'activo' => $this->activo,
            'tiene_especialidad' => $this->tieneEspecialidad(),
            'tiene_contacto' => $this->tieneContacto()
        ];
    }
}
