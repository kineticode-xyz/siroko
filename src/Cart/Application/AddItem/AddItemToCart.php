<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\AddItem;

/**
 * Comando: añadir un producto al carrito (o fusionar si ya está).
 * DTO con primitivos tal como llegan del controller; el handler los convierte a VOs.
 */
final class AddItemToCart
{
    public function __construct(
        public readonly string $cartId,
        public readonly string $productId,
        public readonly int $quantity,
    ) {
    }
}
