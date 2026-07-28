<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $post
 * @return array{anio: string, division: string, turno_id: string, especialidad_id: string}
 */
function cursos_form_curso_desde_post(array $post): array
{
    return [
        'anio' => (string) ($post['anio'] ?? ''),
        'division' => (string) ($post['division'] ?? ''),
        'turno_id' => (string) ($post['turno_id'] ?? ''),
        'especialidad_id' => (string) ($post['especialidad_id'] ?? ''),
    ];
}
