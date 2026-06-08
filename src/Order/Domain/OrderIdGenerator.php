<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

use Siroko\Shared\Domain\OrderId;

/**
 * Puerto de generación de identidad de la orden. El OrderId lo genera el servidor
 * (a diferencia del CartId, que llega del cliente). La implementación vive en
 * Infraestructura (p. ej. con symfony/uid). Ver ai/DECISIONS.md D-005.
 */
interface OrderIdGenerator
{
    public function nextIdentity(): OrderId;
}
