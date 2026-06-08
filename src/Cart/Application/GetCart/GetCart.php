<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\GetCart;

/**
 * Query: obtener el contenido del carrito. Devuelve un CartView (no void, no el agregado).
 */
final class GetCart
{
    public function __construct(
        public readonly string $cartId,
    ) {
    }
}
