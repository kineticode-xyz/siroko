<?php

declare(strict_types=1);

namespace Siroko\Order\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\OrderId;

/**
 * Se lanza cuando se pide una orden que no existe. Los lookups nunca devuelven null.
 */
final class OrderNotFound extends DomainException
{
    public static function withId(OrderId $orderId): self
    {
        return new self(sprintf('No existe la orden %s.', $orderId->value()));
    }
}
