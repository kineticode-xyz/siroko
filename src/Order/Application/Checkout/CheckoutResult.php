<?php

declare(strict_types=1);

namespace Siroko\Order\Application\Checkout;

/**
 * Resultado del checkout para que el controller lo mapee a HTTP en una sola llamada.
 * El pago rechazado viaja como estado 'failed' + motivo, no como excepción (ver DECISIONS D-006).
 * Importe en céntimos (int).
 */
final class CheckoutResult
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $status,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly ?string $failureReason = null,
    ) {
    }
}
