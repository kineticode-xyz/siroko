<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Application\UpdateItem;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Application\UpdateItem\UpdateCartItem;
use Siroko\Cart\Application\UpdateItem\UpdateCartItemHandler;
use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\Exception\CartItemNotFound;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Exception\InvalidQuantity;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;
use Siroko\Tests\Double\InMemoryCartRepository;

final class UpdateCartItemHandlerTest extends TestCase
{
    private InMemoryCartRepository $carts;
    private UpdateCartItemHandler $handler;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->handler = new UpdateCartItemHandler($this->carts);
    }

    #[Test]
    public function fija_la_cantidad_de_una_linea_existente_por_valor_absoluto(): void
    {
        $this->seedCartWith('cart-1', 'SKU-1', 5);

        ($this->handler)(new UpdateCartItem('cart-1', 'SKU-1', 2));

        $cart = $this->carts->get(new CartId('cart-1'));
        self::assertSame(2, $cart->items()[0]->quantity()->value());
    }

    #[Test]
    public function actualizar_en_un_carrito_inexistente_lanza_cart_not_found(): void
    {
        $this->expectException(CartNotFound::class);

        ($this->handler)(new UpdateCartItem('cart-inexistente', 'SKU-1', 2));
    }

    #[Test]
    public function actualizar_una_linea_inexistente_lanza_y_no_modifica_el_carrito(): void
    {
        $this->seedCartWith('cart-1', 'SKU-1', 5);

        try {
            ($this->handler)(new UpdateCartItem('cart-1', 'SKU-2', 2));
            self::fail('Se esperaba CartItemNotFound.');
        } catch (CartItemNotFound) {
            $cart = $this->carts->get(new CartId('cart-1'));
            self::assertCount(1, $cart->items());
            self::assertSame(5, $cart->items()[0]->quantity()->value());
        }
    }

    #[Test]
    public function una_cantidad_menor_que_uno_lanza_y_no_modifica_el_carrito(): void
    {
        $this->seedCartWith('cart-1', 'SKU-1', 5);

        try {
            ($this->handler)(new UpdateCartItem('cart-1', 'SKU-1', 0));
            self::fail('Se esperaba InvalidQuantity.');
        } catch (InvalidQuantity) {
            $cart = $this->carts->get(new CartId('cart-1'));
            self::assertSame(5, $cart->items()[0]->quantity()->value());
        }
    }

    private function seedCartWith(string $cartId, string $productId, int $quantity): void
    {
        $cart = new Cart(new CartId($cartId));
        $cart->addItem(new ProductId($productId), new Quantity($quantity));
        $this->carts->save($cart);
    }
}
