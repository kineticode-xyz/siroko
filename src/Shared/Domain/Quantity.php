<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain;

use Siroko\Shared\Domain\Exception\InvalidQuantity;

/**
 * Número de unidades de un producto en una línea, como value object inmutable.
 *
 * Reglas (ver ai/PLAN.md §3.2 / §4.1):
 *  - Es SIEMPRE >= 1. Cero y negativos se rechazan en construcción.
 *  - Eliminar un producto es una operación explícita del Cart, nunca "cantidad 0".
 *  - Inmutable: las operaciones devuelven una instancia nueva.
 */
final class Quantity
{
    private const int MINIMUM = 1;

    private readonly int $value;

    public function __construct(int $value)
    {
        if ($value < self::MINIMUM) {
            throw InvalidQuantity::belowMinimum($value);
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    /**
     * Fusiona con otra cantidad sumando unidades (lo usa Cart al añadir un producto ya presente).
     * El resultado sigue siendo >= 1 por construcción, ya que ambos operandos lo son.
     */
    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }

    public function equals(Quantity $other): bool
    {
        return $this->value === $other->value;
    }
}
