<?php

declare(strict_types=1);

namespace Siroko\Tests\Double;

use Siroko\Order\Domain\OrderIdGenerator;
use Siroko\Shared\Domain\OrderId;

/**
 * Doble determinista del puerto OrderIdGenerator: siempre devuelve el mismo OrderId,
 * para poder afirmar el id resultante en los tests del checkout.
 */
final class FixedOrderIdGenerator implements OrderIdGenerator
{
    public function __construct(
        private readonly OrderId $id,
    ) {
    }

    public function nextIdentity(): OrderId
    {
        return $this->id;
    }
}
