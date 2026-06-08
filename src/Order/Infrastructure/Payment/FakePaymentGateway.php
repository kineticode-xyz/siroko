<?php

declare(strict_types=1);

namespace Siroko\Order\Infrastructure\Payment;

use Siroko\Order\Domain\Order;
use Siroko\Order\Domain\PaymentGateway;
use Siroko\Order\Domain\PaymentResult;

/**
 * Adaptador fake de la pasarela de pago (no hay SDK real en esta entrega).
 * Por defecto acepta el cobro; con PAYMENT_ALWAYS_FAILS=1 lo rechaza (para probar el flujo FAILED).
 */
final class FakePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly bool $paymentAlwaysFails = false,
    ) {
    }

    public function charge(Order $order): PaymentResult
    {
        if ($this->paymentAlwaysFails) {
            return PaymentResult::failed('pago rechazado (gateway fake)');
        }

        return PaymentResult::succeeded('fake-'.$order->id()->value());
    }
}
