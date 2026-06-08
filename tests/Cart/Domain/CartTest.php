<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\Exception\CartItemNotFound;
use Siroko\Cart\Domain\Exception\MissingPrice;
use Siroko\Cart\Domain\PriceList;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

final class CartTest extends TestCase
{
    #[Test]
    public function un_carrito_nuevo_esta_vacio(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        self::assertTrue($cart->isEmpty());
        self::assertSame([], $cart->items());
    }

    #[Test]
    public function expone_su_identificador(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        self::assertTrue($cart->id()->equals(new CartId('cart-1')));
    }

    #[Test]
    public function anadir_un_producto_crea_una_linea(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        $cart->addItem(new ProductId('SKU-1'), new Quantity(2));

        self::assertFalse($cart->isEmpty());
        self::assertCount(1, $cart->items());
        self::assertSame(2, $cart->items()[0]->quantity()->value());
    }

    #[Test]
    public function anadir_un_producto_ya_presente_fusiona_sumando_cantidad(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        $cart->addItem(new ProductId('SKU-1'), new Quantity(2));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(3));

        self::assertCount(1, $cart->items());
        self::assertSame(5, $cart->items()[0]->quantity()->value());
    }

    #[Test]
    public function anadir_productos_distintos_crea_lineas_distintas(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        $cart->addItem(new ProductId('SKU-1'), new Quantity(1));
        $cart->addItem(new ProductId('SKU-2'), new Quantity(1));

        self::assertCount(2, $cart->items());
    }

    #[Test]
    public function actualizar_una_linea_fija_la_cantidad_por_valor_absoluto(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(5));

        $cart->updateItem(new ProductId('SKU-1'), new Quantity(2));

        self::assertSame(2, $cart->items()[0]->quantity()->value());
    }

    #[Test]
    public function actualizar_una_linea_inexistente_lanza_excepcion(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        $this->expectException(CartItemNotFound::class);

        $cart->updateItem(new ProductId('SKU-1'), new Quantity(2));
    }

    #[Test]
    public function eliminar_una_linea_la_quita_del_carrito(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(1));

        $cart->removeItem(new ProductId('SKU-1'));

        self::assertTrue($cart->isEmpty());
    }

    #[Test]
    public function eliminar_una_linea_inexistente_lanza_excepcion(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        $this->expectException(CartItemNotFound::class);

        $cart->removeItem(new ProductId('SKU-1'));
    }

    #[Test]
    public function el_total_suma_los_subtotales_de_cada_linea(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(2)); // 2 x 1000 = 2000
        $cart->addItem(new ProductId('SKU-2'), new Quantity(3)); // 3 x  500 = 1500

        $prices = (new PriceList('EUR'))
            ->withPrice(new ProductId('SKU-1'), new Money(1000, 'EUR'))
            ->withPrice(new ProductId('SKU-2'), new Money(500, 'EUR'));

        self::assertTrue($cart->total($prices)->equals(new Money(3500, 'EUR')));
    }

    #[Test]
    public function el_total_de_un_carrito_vacio_es_cero_en_la_moneda_dada(): void
    {
        $cart = new Cart(new CartId('cart-1'));

        self::assertTrue($cart->total(new PriceList('EUR'))->equals(new Money(0, 'EUR')));
    }

    #[Test]
    public function el_total_aplica_suelo_cero_y_nunca_es_negativo(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(1));

        // Precio negativo (descuento contrived) para ejercitar el suelo 0.
        $prices = (new PriceList('EUR'))
            ->withPrice(new ProductId('SKU-1'), new Money(-500, 'EUR'));

        self::assertTrue($cart->total($prices)->equals(new Money(0, 'EUR')));
    }

    #[Test]
    public function el_total_falla_si_no_se_entrega_el_precio_de_una_linea(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(1));

        $this->expectException(MissingPrice::class);

        $cart->total(new PriceList('EUR')); // sin precio para SKU-1
    }
}
