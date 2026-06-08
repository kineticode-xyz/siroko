<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\GetCart;

/**
 * Modelo de lectura del carrito: líneas con precios vigentes y total.
 * Importe total en céntimos (int); una sola moneda para todo el carrito (ver DECISIONS D-002).
 */
final class CartView
{
    /**
     * @param list<CartLineView> $lines
     */
    public function __construct(
        public readonly string $cartId,
        public readonly string $currency,
        public readonly array $lines,
        public readonly int $totalAmount,
    ) {
    }
}
