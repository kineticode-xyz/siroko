<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain\Exception;

use DomainException;

/**
 * Se lanza al intentar operar (sumar, restar, comparar) dos Money de monedas distintas.
 */
final class CurrencyMismatch extends DomainException
{
    public static function between(string $a, string $b): self
    {
        return new self(sprintf('No se pueden operar monedas distintas: %s y %s.', $a, $b));
    }
}
