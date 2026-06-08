<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain;

use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Línea del carrito: producto + cantidad. Entidad interna del agregado Cart.
 *
 * Reglas (ver ai/PLAN.md §4.2):
 *  - Identidad = producto (ProductId, inmutable).
 *  - NO lleva precio: el carrito no guarda precios.
 *  - La cantidad muta, pero solo a través de la raíz (Cart). Métodos de
 *    intención de negocio, no setters anémicos.
 */
final class CartItem
{
    public function __construct(
        private readonly ProductId $productId,
        private Quantity $quantity,
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

    public function isFor(ProductId $productId): bool
    {
        return $this->productId->equals($productId);
    }

    /**
     * Fusiona sumando unidades. Lo usa Cart.addItem cuando el producto ya está en el carrito.
     */
    public function increaseBy(Quantity $quantity): void
    {
        $this->quantity = $this->quantity->add($quantity);
    }

    /**
     * Fija la cantidad por valor absoluto ("pon N"). Lo usa Cart.updateItem.
     */
    public function changeQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }
}
