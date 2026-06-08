<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain\Exception;

use DomainException;

/**
 * Se lanza al intentar construir un Money con una moneda inválida (vacía).
 */
final class InvalidCurrency extends DomainException
{
    public static function empty(): self
    {
        return new self('La moneda no puede estar vacía.');
    }
}
