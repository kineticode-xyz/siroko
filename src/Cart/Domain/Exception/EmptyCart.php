<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\CartId;

/**
 * Se lanza al intentar hacer checkout de un carrito vacío. No se crea orden (PLAN §6).
 */
final class EmptyCart extends DomainException
{
    public static function cannotCheckout(CartId $cartId): self
    {
        return new self(sprintf('No se puede hacer checkout de un carrito vacío (%s).', $cartId->value()));
    }
}
