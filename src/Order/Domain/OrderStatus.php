<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

/**
 * Estado de una orden y sus transiciones permitidas (ver ai/PLAN.md §3.6).
 *
 *  - PENDING → PAID  y  PENDING → FAILED.
 *  - PAID y FAILED son terminales (sin transiciones de salida). Reintentar = nueva orden.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';

    /** @return list<OrderStatus> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PAID, self::FAILED],
            self::PAID, self::FAILED => [],
        };
    }

    public function canTransitionTo(OrderStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
