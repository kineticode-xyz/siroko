<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Application\RemoveItem;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Application\RemoveItem\RemoveCartItem;
use Siroko\Cart\Application\RemoveItem\RemoveCartItemHandler;
use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\Exception\CartItemNotFound;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;
use Siroko\Tests\Double\InMemoryCartRepository;

final class RemoveCartItemHandlerTest extends TestCase
{
    private InMemoryCartRepository $carts;
    private RemoveCartItemHandler $handler;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->handler = new RemoveCartItemHandler($this->carts);
    }

    #[Test]
    public function elimina_una_linea_existente_del_carrito(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(2));
        $cart->addItem(new ProductId('SKU-2'), new Quantity(1));
        $this->carts->save($cart);

        ($this->handler)(new RemoveCartItem('cart-1', 'SKU-1'));

        $cart = $this->carts->get(new CartId('cart-1'));
        self::assertCount(1, $cart->items());
        self::assertTrue($cart->items()[0]->productId()->equals(new ProductId('SKU-2')));
    }

    #[Test]
    public function eliminar_la_ultima_linea_deja_el_carrito_vacio(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(2));
        $this->carts->save($cart);

        ($this->handler)(new RemoveCartItem('cart-1', 'SKU-1'));

        self::assertTrue($this->carts->get(new CartId('cart-1'))->isEmpty());
    }

    #[Test]
    public function eliminar_en_un_carrito_inexistente_lanza_cart_not_found(): void
    {
        $this->expectException(CartNotFound::class);

        ($this->handler)(new RemoveCartItem('cart-inexistente', 'SKU-1'));
    }

    #[Test]
    public function eliminar_una_linea_inexistente_lanza_y_no_modifica_el_carrito(): void
    {
        $cart = new Cart(new CartId('cart-1'));
        $cart->addItem(new ProductId('SKU-1'), new Quantity(2));
        $this->carts->save($cart);

        try {
            ($this->handler)(new RemoveCartItem('cart-1', 'SKU-2'));
            self::fail('Se esperaba CartItemNotFound.');
        } catch (CartItemNotFound) {
            $cart = $this->carts->get(new CartId('cart-1'));
            self::assertCount(1, $cart->items());
            self::assertTrue($cart->items()[0]->productId()->equals(new ProductId('SKU-1')));
        }
    }
}
