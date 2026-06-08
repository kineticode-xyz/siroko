<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\GetCart;

/**
 * Línea del carrito en el modelo de lectura. Importes en céntimos (int).
 * No expone el agregado ni VOs de dominio: es un DTO plano listo para serializar.
 */
final class CartLineView
{
    public function __construct(
        public readonly string $productId,
        public readonly int $quantity,
        public readonly int $unitPriceAmount,
        public readonly int $subtotalAmount,
    ) {
    }
}
