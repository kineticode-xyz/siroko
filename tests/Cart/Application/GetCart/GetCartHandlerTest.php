<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Application\GetCart;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Application\GetCart\GetCart;
use Siroko\Cart\Application\GetCart\GetCartHandler;
use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;
use Siroko\Tests\Double\InMemoryCartRepository;
use Siroko\Tests\Double\InMemoryProductCatalog;

final class GetCartHandlerTest extends TestCase
{
    private InMemoryCartRepository $carts;
    private InMemoryProductCatalog $catalog;
    private GetCartHandler $handler;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->catalog = (new InMemoryProductCatalog('EUR'))
            ->withProduct(new ProductId('SKU-1'), new Money(1000, 'EUR'), 10)
            ->withProduct(new ProductId('SKU-2'), new Money(500, 'EUR'), 10);

        $this->handler = new GetCartHandler($this->carts, $this->catalog);
    }

    #[Test]
    public function devuelve_las_lineas_con_precios_vigentes_subtotales_y_total(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(2)); // 2 x 1000 = 2000
        $cart->addItem(new ProductId('SKU-2'), new Quantity(3)); // 3 x  500 = 1500
        $this->carts->save($cart);

        $view = ($this->handler)(new GetCart('cart-1'));

        self::assertSame('cart-1', $view->cartId);
        self::assertSame('EUR', $view->currency);
        self::assertSame(3500, $view->totalAmount);
        self::assertCount(2, $view->lines);

        self::assertSame('SKU-1', $view->lines[0]->productId);
        self::assertSame(2, $view->lines[0]->quantity);
        self::assertSame(1000, $view->lines[0]->unitPriceAmount);
        self::assertSame(2000, $view->lines[0]->subtotalAmount);

        self::assertSame('SKU-2', $view->lines[1]->productId);
        self::assertSame(1500, $view->lines[1]->subtotalAmount);
    }

    #[Test]
    public function refleja_el_precio_vigente_del_catalogo_no_uno_guardado(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(1));
        $this->carts->save($cart);

        // El catálogo cambia el precio después de añadir al carrito.
        $this->catalog->withProduct(new ProductId('SKU-1'), new Money(1234, 'EUR'), 10);

        $view = ($this->handler)(new GetCart('cart-1'));

        self::assertSame(1234, $view->lines[0]->unitPriceAmount);
        self::assertSame(1234, $view->totalAmount);
    }

    #[Test]
    public function un_carrito_vacio_devuelve_vista_sin_lineas_y_total_cero(): void
    {
        $this->carts->save(new Cart(new CartId('cart-1')));

        $view = ($this->handler)(new GetCart('cart-1'));

        self::assertSame([], $view->lines);
        self::assertSame(0, $view->totalAmount);
        self::assertSame('EUR', $view->currency);
    }

    #[Test]
    public function pedir_un_carrito_inexistente_lanza_cart_not_found(): void
    {
        $this->expectException(CartNotFound::class);

        ($this->handler)(new GetCart('cart-inexistente'));
    }
}
