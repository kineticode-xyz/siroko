<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\RemoveItem;

/**
 * Comando: eliminar una línea del carrito (operación explícita, no "cantidad 0").
 * DTO con primitivos; el handler los convierte a VOs.
 */
final class RemoveCartItem
{
    public function __construct(
        public readonly string $cartId,
        public readonly string $productId,
    ) {
    }
}
