<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Application\AddItem;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Application\AddItem\AddItemToCart;
use Siroko\Cart\Application\AddItem\AddItemToCartHandler;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Cart\Domain\Exception\ProductNotInCatalog;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Exception\InvalidQuantity;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;
use Siroko\Tests\Double\InMemoryCartRepository;
use Siroko\Tests\Double\InMemoryProductCatalog;

final class AddItemToCartHandlerTest extends TestCase
{
    private InMemoryCartRepository $carts;
    private InMemoryProductCatalog $catalog;
    private AddItemToCartHandler $handler;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->catalog = (new InMemoryProductCatalog('EUR'))
            ->withProduct(new ProductId('SKU-1'), new Money(1000, 'EUR'), 10)
            ->withProduct(new ProductId('SKU-2'), new Money(500, 'EUR'), 10);

        $this->handler = new AddItemToCartHandler($this->carts, $this->catalog);
    }

    #[Test]
    public function crea_el_carrito_al_vuelo_y_anade_la_linea(): void
    {
        ($this->handler)(new AddItemToCart('cart-1', 'SKU-1', 2));

        $cart = $this->carts->get(new CartId('cart-1'));
        self::assertCount(1, $cart->items());
        self::assertTrue($cart->items()[0]->productId()->equals(new ProductId('SKU-1')));
        self::assertSame(2, $cart->items()[0]->quantity()->value());
    }

    #[Test]
    public function anadir_el_mismo_producto_dos_veces_fusiona_la_cantidad(): void
    {
        ($this->handler)(new AddItemToCart('cart-1', 'SKU-1', 2));
        ($this->handler)(new AddItemToCart('cart-1', 'SKU-1', 3));

        $cart = $this->carts->get(new CartId('cart-1'));
        self::assertCount(1, $cart->items());
        self::assertSame(5, $cart->items()[0]->quantity()->value());
    }

    #[Test]
    public function anadir_un_producto_inexistente_lanza_y_no_persiste_el_carrito(): void
    {
        try {
            ($this->handler)(new AddItemToCart('cart-1', 'SKU-DESCONOCIDO', 1));
            self::fail('Se esperaba ProductNotInCatalog.');
        } catch (ProductNotInCatalog) {
            // El carrito no debe haberse creado ni persistido.
            self::assertFalse($this->carts->has(new CartId('cart-1')));
        }
    }

    #[Test]
    public function una_cantidad_menor_que_uno_lanza_y_no_persiste_el_carrito(): void
    {
        try {
            ($this->handler)(new AddItemToCart('cart-1', 'SKU-1', 0));
            self::fail('Se esperaba InvalidQuantity.');
        } catch (InvalidQuantity) {
            self::assertFalse($this->carts->has(new CartId('cart-1')));
        }
    }

    #[Test]
    public function anadir_un_producto_invalido_a_un_carrito_existente_no_lo_modifica(): void
    {
        ($this->handler)(new AddItemToCart('cart-1', 'SKU-1', 2));

        try {
            ($this->handler)(new AddItemToCart('cart-1', 'SKU-DESCONOCIDO', 1));
            self::fail('Se esperaba ProductNotInCatalog.');
        } catch (ProductNotInCatalog) {
            $cart = $this->carts->get(new CartId('cart-1'));
            self::assertCount(1, $cart->items());
            self::assertTrue($cart->items()[0]->productId()->equals(new ProductId('SKU-1')));
        }
    }
}
