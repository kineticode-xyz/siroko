<?php

declare(strict_types=1);

namespace Siroko\Shared\Domain;

use Siroko\Shared\Domain\Exception\CurrencyMismatch;
use Siroko\Shared\Domain\Exception\InvalidCurrency;

/**
 * Importe monetario como value object inmutable.
 *
 * Reglas (ver ai/PLAN.md §3.1):
 *  - El importe es SIEMPRE un entero en céntimos, con signo. Nunca float.
 *  - Puede ser negativo: representa un descuento que se resta. Es aritmética pura.
 *  - El suelo 0 del precio final NO vive aquí; vive en el cálculo del total.
 *  - No se opera dinero de monedas distintas: lanza excepción de dominio.
 *  - No formatea a texto: eso es responsabilidad de presentación.
 */
final class Money
{
    private readonly int $amount;
    private readonly string $currency;

    public function __construct(int $amount, string $currency)
    {
        $currency = strtoupper(trim($currency));

        if ($currency === '') {
            throw InvalidCurrency::empty();
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }
    }
}
