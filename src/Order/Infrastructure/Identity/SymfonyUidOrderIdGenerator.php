<?php

declare(strict_types=1);

namespace Siroko\Order\Infrastructure\Identity;

use Siroko\Order\Domain\OrderIdGenerator;
use Siroko\Shared\Domain\OrderId;
use Symfony\Component\Uid\Uuid;

/**
 * Adaptador del puerto OrderIdGenerator usando symfony/uid (UUID v4).
 */
final class SymfonyUidOrderIdGenerator implements OrderIdGenerator
{
    public function nextIdentity(): OrderId
    {
        return new OrderId(Uuid::v4()->toRfc4122());
    }
}
