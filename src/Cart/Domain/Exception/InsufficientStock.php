<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain\Exception;

use DomainException;
use Siroko\Shared\Domain\ProductId;

/**
 * Se lanza en la validación dura del checkout cuando no hay stock suficiente.
 * Falla ANTES de crear la orden y de cobrar (PLAN §3.5 / §3.7).
 */
final class InsufficientStock extends DomainException
{
    public static function forProduct(ProductId $productId, int $requested, int $available): self
    {
        return new self(sprintf(
            'Stock insuficiente para %s: se piden %d y hay %d.',
            $productId->value(),
            $requested,
            $available,
        ));
    }
}
