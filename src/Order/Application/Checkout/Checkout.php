<?php

declare(strict_types=1);

namespace Siroko\Order\Application\Checkout;

/**
 * Comando: confirmar el carrito y generar una orden persistente.
 * DTO con el cartId tal como llega del controller.
 */
final class Checkout
{
    public function __construct(
        public readonly string $cartId,
    ) {
    }
}
