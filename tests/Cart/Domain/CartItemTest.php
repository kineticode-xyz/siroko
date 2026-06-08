<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Domain\CartItem;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

final class CartItemTest extends TestCase
{
    #[Test]
    public function expone_el_producto_y_la_cantidad(): void
    {
        $item = new CartItem(new ProductId('SKU-1'), new Quantity(2));

        self::assertTrue($item->productId()->equals(new ProductId('SKU-1')));
        self::assertSame(2, $item->quantity()->value());
    }

    #[Test]
    public function is_for_reconoce_su_propio_producto(): void
    {
        $item = new CartItem(new ProductId('SKU-1'), new Quantity(1));

        self::assertTrue($item->isFor(new ProductId('SKU-1')));
        self::assertFalse($item->isFor(new ProductId('SKU-2')));
    }

    #[Test]
    public function increase_by_suma_unidades_a_la_linea(): void
    {
        $item = new CartItem(new ProductId('SKU-1'), new Quantity(2));

        $item->increaseBy(new Quantity(3));

        self::assertSame(5, $item->quantity()->value());
    }

    #[Test]
    public function increase_by_no_cambia_la_identidad_del_producto(): void
    {
        $item = new CartItem(new ProductId('SKU-1'), new Quantity(2));

        $item->increaseBy(new Quantity(3));

        self::assertTrue($item->isFor(new ProductId('SKU-1')));
    }

    #[Test]
    public function change_quantity_fija_la_cantidad_por_valor_absoluto(): void
    {
        $item = new CartItem(new ProductId('SKU-1'), new Quantity(5));

        $item->changeQuantity(new Quantity(2));

        self::assertSame(2, $item->quantity()->value());
    }

    #[Test]
    public function change_quantity_no_cambia_la_identidad_del_producto(): void
    {
        $item = new CartItem(new ProductId('SKU-1'), new Quantity(5));

        $item->changeQuantity(new Quantity(2));

        self::assertTrue($item->isFor(new ProductId('SKU-1')));
    }
}
