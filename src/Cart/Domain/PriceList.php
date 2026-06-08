<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain;

use Siroko\Cart\Domain\Exception\MissingPrice;
use Siroko\Shared\Domain\Exception\CurrencyMismatch;
use Siroko\Shared\Domain\Exception\InvalidCurrency;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;

/**
 * Conjunto de precios vigentes entregado al dominio para calcular totales.
 *
 * Decisiones (ver ai/DECISIONS.md y ai/PLAN.md §3.3 / §10):
 *  - El dominio NO consulta precios: se le entregan. Esta es la forma de entregarlos.
 *  - Lleva una moneda explícita: permite el total de un carrito vacío (Money(0, moneda)).
 *  - Se construye una vez desde una consulta batch (evita el N+1 del checkout/GetCart).
 *  - Inmutable: withPrice() devuelve una instancia nueva.
 *  - Valida que cada precio sea de su moneda (contexto single-currency).
 */
final class PriceList
{
    private readonly string $currency;

    /** @var array<string, Money> precios indexados por valor de ProductId */
    private array $prices = [];

    public function __construct(string $currency)
    {
        $currency = strtoupper(trim($currency));

        if ($currency === '') {
            throw InvalidCurrency::empty();
        }

        $this->currency = $currency;
    }

    public function withPrice(ProductId $productId, Money $price): self
    {
        if ($price->currency() !== $this->currency) {
            throw CurrencyMismatch::between($this->currency, $price->currency());
        }

        $clone = clone $this;
        $clone->prices[$productId->value()] = $price;

        return $clone;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function priceOf(ProductId $productId): Money
    {
        return $this->prices[$productId->value()] ?? throw MissingPrice::forProduct($productId);
    }
}
