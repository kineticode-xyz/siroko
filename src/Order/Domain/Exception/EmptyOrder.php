<?php

declare(strict_types=1);

namespace Siroko\Order\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\OrderId;

/**
 * Se lanza al intentar crear una orden sin líneas. Una orden siempre tiene >= 1 línea.
 */
final class EmptyOrder extends DomainException
{
    public static function cannotBePlaced(OrderId $orderId): self
    {
        return new self(sprintf('No se puede crear la orden %s sin líneas.', $orderId->value()));
    }
}
