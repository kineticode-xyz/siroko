<?php

declare(strict_types=1);

namespace Siroko\Tests\Order\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Order\Domain\OrderLine;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

final class OrderLineTest extends TestCase
{
    #[Test]
    public function expone_producto_cantidad_y_precio_congelado(): void
    {
        $line = new OrderLine(new ProductId('SKU-1'), new Quantity(2), new Money(1000, 'EUR'));

        self::assertTrue($line->productId()->equals(new ProductId('SKU-1')));
        self::assertSame(2, $line->quantity()->value());
        self::assertTrue($line->unitPrice()->equals(new Money(1000, 'EUR')));
    }

    #[Test]
    public function el_subtotal_es_precio_unitario_por_cantidad(): void
    {
        $line = new OrderLine(new ProductId('SKU-1'), new Quantity(3), new Money(1000, 'EUR'));

        self::assertTrue($line->subtotal()->equals(new Money(3000, 'EUR')));
    }
}
