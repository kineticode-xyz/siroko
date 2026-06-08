<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\UpdateItem;

/**
 * Comando: fijar la cantidad de una línea del carrito por valor absoluto ("pon N").
 * DTO con primitivos; el handler los convierte a VOs.
 */
final class UpdateCartItem
{
    public function __construct(
        public readonly string $cartId,
        public readonly string $productId,
        public readonly int $quantity,
    ) {
    }
}
