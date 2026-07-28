<?php

declare(strict_types=1);

/**
 * Alias de compatibilidad — la clase canónica está en SistemaAdmin\DTOs\SubjectStatusResult.
 * @deprecated Usar SistemaAdmin\DTOs\SubjectStatusResult directamente.
 */

namespace SistemaAdmin\DTO;

class_alias(\SistemaAdmin\DTOs\SubjectStatusResult::class, \SistemaAdmin\DTO\SubjectStatusResult::class);
