<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\CartId;

/**
 * Se lanza cuando se pide un carrito que no existe. Los lookups nunca devuelven null.
 */
final class CartNotFound extends DomainException
{
    public static function withId(CartId $cartId): self
    {
        return new self(sprintf('No existe el carrito %s.', $cartId->value()));
    }
}
