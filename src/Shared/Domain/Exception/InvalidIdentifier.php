<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain\Exception;

use DomainException;

/**
 * Se lanza al intentar construir un identificador con un valor inválido (vacío).
 */
final class InvalidIdentifier extends DomainException
{
    public static function empty(string $type): self
    {
        return new self(sprintf('El identificador %s no puede estar vacío.', $type));
    }
}
