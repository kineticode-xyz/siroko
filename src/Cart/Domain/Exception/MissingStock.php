<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\ProductId;

/**
 * Se lanza al consultar el stock de un producto que no está en el StockLevels entregado.
 */
final class MissingStock extends DomainException
{
    public static function forProduct(ProductId $productId): self
    {
        return new self(sprintf('No se ha entregado stock para el producto %s.', $productId->value()));
    }
}
