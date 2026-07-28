<?php

namespace SistemaAdmin\DTOs;

/**
 * DTO para respuestas del sistema
 * 
 * Proporciona una estructura consistente para todas las respuestas
 */
class ResponseDTO
{
    private bool $success;
    private ?string $message;
    private ?string $error;
    private ?array $data;
    private ?array $errors;
    private int $statusCode;

    public function __construct(
        bool $success = true,
        ?string $message = null,
        ?string $error = null,
        ?array $data = null,
        ?array $errors = null,
        int $statusCode = 200
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->error = $error;
        $this->data = $data;
        $this->errors = $errors;
        $this->statusCode = $statusCode;
    }

    // Getters
    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // Métodos estáticos para crear respuestas comunes
    public static function success(?array $data = null, ?string $message = null): self
    {
        return new self(true, $message, null, $data, null, 200);
    }

    public static function error(string $error, int $statusCode = 400): self
    {
        return new self(false, null, $error, null, null, $statusCode);
    }

    public static function validationError(array $errors, int $statusCode = 422): self
    {
        return new self(false, null, 'Errores de validación', null, $errors, $statusCode);
    }

    public static function notFound(string $resource = 'Recurso'): self
    {
        return new self(false, null, "$resource no encontrado", null, null, 404);
    }

    public static function unauthorized(): self
    {
        return new self(false, null, 'No autorizado', null, null, 401);
    }

    public static function forbidden(): self
    {
        return new self(false, null, 'Acceso denegado', null, null, 403);
    }

    public static function serverError(string $error = 'Error interno del servidor'): self
    {
        return new self(false, null, $error, null, null, 500);
    }

    // Convertir a array para respuestas JSON
    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'status_code' => $this->statusCode
        ];

        if ($this->message !== null) {
            $response['message'] = $this->message;
        }

        if ($this->error !== null) {
            $response['error'] = $this->error;
        }

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }

    // Convertir a JSON
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
