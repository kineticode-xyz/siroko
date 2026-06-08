<?php

declare(strict_types=1);

namespace Siroko\Order\Domain\Exception;

use DomainException;
use Siroko\Order\Domain\OrderStatus;

/**
 * Se lanza al intentar una transición de estado no permitida (ver ai/PLAN.md §3.6).
 */
final class IllegalOrderTransition extends DomainException
{
    public static function from(OrderStatus $from, OrderStatus $to): self
    {
        return new self(sprintf('Transición de estado no permitida: %s → %s.', $from->name, $to->name));
    }
}
