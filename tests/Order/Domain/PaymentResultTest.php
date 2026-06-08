<?php

declare(strict_types=1);

namespace Siroko\Tests\Order\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Order\Domain\PaymentResult;

final class PaymentResultTest extends TestCase
{
    #[Test]
    public function un_pago_exitoso_es_exitoso_y_no_lleva_motivo(): void
    {
        $result = PaymentResult::succeeded();

        self::assertTrue($result->isSuccessful());
        self::assertNull($result->reason());
    }

    #[Test]
    public function un_pago_exitoso_puede_llevar_referencia_de_transaccion(): void
    {
        $result = PaymentResult::succeeded('txn-123');

        self::assertTrue($result->isSuccessful());
        self::assertSame('txn-123', $result->reference());
    }

    #[Test]
    public function un_pago_fallido_no_es_exitoso_y_lleva_el_motivo(): void
    {
        $result = PaymentResult::failed('tarjeta rechazada');

        self::assertFalse($result->isSuccessful());
        self::assertSame('tarjeta rechazada', $result->reason());
        self::assertNull($result->reference());
    }
}
