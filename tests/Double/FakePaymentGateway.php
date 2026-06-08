<?php

declare(strict_types=1);

namespace Siroko\Tests\Double;

use Siroko\Order\Domain\Order;
use Siroko\Order\Domain\PaymentGateway;
use Siroko\Order\Domain\PaymentResult;

/**
 * Doble del puerto PaymentGateway: configurable para aceptar o rechazar el cobro.
 */
final class FakePaymentGateway implements PaymentGateway
{
    private function __construct(
        private readonly bool $succeeds,
        private readonly ?string $reason,
    ) {
    }

    public static function thatSucceeds(): self
    {
        return new self(true, null);
    }

    public static function thatFails(string $reason = 'pago rechazado'): self
    {
        return new self(false, $reason);
    }

    public function charge(Order $order): PaymentResult
    {
        return $this->succeeds
            ? PaymentResult::succeeded('txn-test')
            : PaymentResult::failed((string) $this->reason);
    }
}
