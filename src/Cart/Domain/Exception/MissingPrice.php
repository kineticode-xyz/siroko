<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\ProductId;

/**
 * Se lanza al calcular un total cuando la PriceList no contiene el precio de un producto del carrito.
 */
final class MissingPrice extends DomainException
{
    public static function forProduct(ProductId $productId): self
    {
        return new self(sprintf('No se ha entregado precio para el producto %s.', $productId->value()));
    }
}
