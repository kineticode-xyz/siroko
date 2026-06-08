<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\ProductId;

/**
 * Se lanza cuando se piden precio o stock de un producto que no existe en el catálogo.
 */
final class ProductNotInCatalog extends DomainException
{
    public static function withId(ProductId $productId): self
    {
        return new self(sprintf('El producto %s no existe en el catálogo.', $productId->value()));
    }
}
