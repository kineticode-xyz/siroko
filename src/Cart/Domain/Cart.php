<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain;

use Siroko\Cart\Domain\Exception\CartItemNotFound;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Carrito de compra. Agregado raíz del contexto Carrito.
 *
 * Reglas (ver ai/PLAN.md §3.3 / §4.2):
 *  - Persistente, identificado por CartId. No guarda precios. No gestiona stock.
 *  - Sin líneas duplicadas: añadir un producto ya presente FUSIONA (suma cantidad).
 *  - Actualizar es set absoluto; eliminar es una operación explícita.
 *  - total() recibe los precios desde fuera y aplica el suelo 0.
 *  - No conoce catálogo ni infraestructura.
 */
final class Cart
{
    /** @var array<string, CartItem> líneas indexadas por valor de ProductId (impide duplicados) */
    private array $items = [];

    public function __construct(private readonly CartId $id)
    {
    }

    public function id(): CartId
    {
        return $this->id;
    }

    public function addItem(ProductId $productId, Quantity $quantity): void
    {
        $key = $productId->value();

        if (isset($this->items[$key])) {
            $this->items[$key]->increaseBy($quantity);

            return;
        }

        $this->items[$key] = new CartItem($productId, $quantity);
    }

    public function updateItem(ProductId $productId, Quantity $quantity): void
    {
        $this->itemFor($productId)->changeQuantity($quantity);
    }

    public function removeItem(ProductId $productId): void
    {
        $this->itemFor($productId); // valida que existe; lanza si no

        unset($this->items[$productId->value()]);
    }

    public function total(PriceList $prices): Money
    {
        $total = new Money(0, $prices->currency());

        foreach ($this->items as $item) {
            $subtotal = $prices->priceOf($item->productId())->multiply($item->quantity()->value());
            $total = $total->add($subtotal);
        }

        // Suelo 0: el precio final a pagar nunca es negativo (PLAN §3.1).
        return $total->isNegative()
            ? new Money(0, $prices->currency())
            : $total;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return list<CartItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    private function itemFor(ProductId $productId): CartItem
    {
        return $this->items[$productId->value()] ?? throw CartItemNotFound::forProduct($productId);
    }
}
