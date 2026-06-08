<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\ProductId;

/**
 * Se lanza al intentar actualizar o eliminar una línea que no existe en el carrito.
 */
final class CartItemNotFound extends DomainException
{
    public static function forProduct(ProductId $productId): self
    {
        return new self(sprintf('El carrito no contiene el producto %s.', $productId->value()));
    }
}
