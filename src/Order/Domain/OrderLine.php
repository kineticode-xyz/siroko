<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Línea de una orden: producto + cantidad + precio unitario CONGELADO.
 *
 * Reglas (ver ai/PLAN.md §4.3):
 *  - Totalmente inmutable: el contenido de la orden no cambia tras crearse.
 *  - A diferencia de CartItem, lleva precio (congelado en el momento del checkout).
 */
final class OrderLine
{
    public function __construct(
        private readonly ProductId $productId,
        private readonly Quantity $quantity,
        private readonly Money $unitPrice,
    ) {
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity->value());
    }
}
