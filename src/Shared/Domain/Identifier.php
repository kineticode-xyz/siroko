<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain;

use Siroko\Shared\Domain\Exception\InvalidIdentifier;

/**
 * Base de los identificadores del dominio (CartId, OrderId, ProductId).
 *
 * Reglas (ver ai/PLAN.md §4.1):
 *  - Value object inmutable que envuelve un identificador.
 *  - El valor es un string no vacío. No se fuerza formato UUID a propósito:
 *    así un ProductId puede ser un SKU del catálogo.
 *  - La igualdad distingue por valor Y por tipo concreto: un CartId nunca es
 *    igual a un ProductId aunque coincida la cadena (son identidades distintas).
 */
abstract class Identifier
{
    private readonly string $value;

    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw InvalidIdentifier::empty(static::class);
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $other::class === static::class
            && $other->value === $this->value;
    }
}
