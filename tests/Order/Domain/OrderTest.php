<?php

declare(strict_types=1);

namespace Siroko\Tests\Order\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Order\Domain\Exception\EmptyOrder;
use Siroko\Order\Domain\Exception\IllegalOrderTransition;
use Siroko\Order\Domain\Order;
use Siroko\Order\Domain\OrderLine;
use Siroko\Order\Domain\OrderStatus;
use Siroko\Shared\Domain\Exception\CurrencyMismatch;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\OrderId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

final class OrderTest extends TestCase
{
    #[Test]
    public function se_crea_en_estado_pending(): void
    {
        $order = Order::place(new OrderId('order-1'), [
            new OrderLine(new ProductId('SKU-1'), new Quantity(1), new Money(1000, 'EUR')),
        ]);

        self::assertSame(OrderStatus::PENDING, $order->status());
        self::assertTrue($order->id()->equals(new OrderId('order-1')));
        self::assertCount(1, $order->lines());
    }

    #[Test]
    public function el_total_es_la_suma_de_los_subtotales_congelados(): void
    {
        $order = Order::place(new OrderId('order-1'), [
            new OrderLine(new ProductId('SKU-1'), new Quantity(2), new Money(1000, 'EUR')), // 2000
            new OrderLine(new ProductId('SKU-2'), new Quantity(3), new Money(500, 'EUR')),  // 1500
        ]);

        self::assertTrue($order->total()->equals(new Money(3500, 'EUR')));
    }

    #[Test]
    public function el_total_aplica_suelo_cero_y_nunca_es_negativo(): void
    {
        $order = Order::place(new OrderId('order-1'), [
            new OrderLine(new ProductId('SKU-1'), new Quantity(1), new Money(-500, 'EUR')),
        ]);

        self::assertTrue($order->total()->equals(new Money(0, 'EUR')));
    }

    #[Test]
    public function no_se_puede_crear_una_orden_sin_lineas(): void
    {
        $this->expectException(EmptyOrder::class);

        Order::place(new OrderId('order-1'), []);
    }

    #[Test]
    public function no_se_puede_crear_una_orden_con_lineas_de_distinta_moneda(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Order::place(new OrderId('order-1'), [
            new OrderLine(new ProductId('SKU-1'), new Quantity(1), new Money(1000, 'EUR')),
            new OrderLine(new ProductId('SKU-2'), new Quantity(1), new Money(1000, 'USD')),
        ]);
    }

    #[Test]
    public function una_orden_pendiente_se_puede_marcar_pagada(): void
    {
        $order = $this->pendingOrder();

        $order->markPaid();

        self::assertSame(OrderStatus::PAID, $order->status());
    }

    #[Test]
    public function una_orden_pendiente_se_puede_marcar_fallida(): void
    {
        $order = $this->pendingOrder();

        $order->markFailed();

        self::assertSame(OrderStatus::FAILED, $order->status());
    }

    #[Test]
    public function una_orden_pagada_no_se_puede_volver_a_pagar(): void
    {
        $order = $this->pendingOrder();
        $order->markPaid();

        $this->expectException(IllegalOrderTransition::class);

        $order->markPaid();
    }

    #[Test]
    public function una_orden_pagada_no_puede_pasar_a_fallida(): void
    {
        $order = $this->pendingOrder();
        $order->markPaid();

        $this->expectException(IllegalOrderTransition::class);

        $order->markFailed();
    }

    #[Test]
    public function una_orden_fallida_no_puede_pasar_a_pagada(): void
    {
        $order = $this->pendingOrder();
        $order->markFailed();

        $this->expectException(IllegalOrderTransition::class);

        $order->markPaid();
    }

    #[Test]
    public function el_total_permanece_congelado_tras_un_cambio_de_estado(): void
    {
        $order = $this->pendingOrder();
        $totalAntes = $order->total();

        $order->markPaid();

        self::assertTrue($order->total()->equals($totalAntes));
    }

    private function pendingOrder(): Order
    {
        return Order::place(new OrderId('order-1'), [
            new OrderLine(new ProductId('SKU-1'), new Quantity(1), new Money(1000, 'EUR')),
        ]);
    }
}
