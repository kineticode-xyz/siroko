<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain\Exception;

use DomainException;

/**
 * Se lanza al intentar construir una Quantity inválida (menor que 1).
 */
final class InvalidQuantity extends DomainException
{
    public static function belowMinimum(int $value): self
    {
        return new self(sprintf('La cantidad debe ser >= 1, se recibió %d.', $value));
    }
}
