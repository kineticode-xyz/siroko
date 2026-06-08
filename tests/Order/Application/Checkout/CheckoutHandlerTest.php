<?php

declare(strict_types=1);

namespace Siroko\Tests\Order\Application\Checkout;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Cart\Domain\Exception\EmptyCart;
use Siroko\Cart\Domain\Exception\InsufficientStock;
use Siroko\Order\Application\Checkout\Checkout;
use Siroko\Order\Application\Checkout\CheckoutHandler;
use Siroko\Order\Domain\OrderStatus;
use Siroko\Order\Domain\PaymentGateway;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\OrderId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;
use Siroko\Tests\Double\FakePaymentGateway;
use Siroko\Tests\Double\FixedOrderIdGenerator;
use Siroko\Tests\Double\InMemoryCartRepository;
use Siroko\Tests\Double\InMemoryOrderRepository;
use Siroko\Tests\Double\InMemoryProductCatalog;

final class CheckoutHandlerTest extends TestCase
{
    private InMemoryCartRepository $carts;
    private InMemoryProductCatalog $catalog;
    private InMemoryOrderRepository $orders;
    private FixedOrderIdGenerator $orderIds;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->catalog = (new InMemoryProductCatalog('EUR'))
            ->withProduct(new ProductId('SKU-1'), new Money(1000, 'EUR'), 10)
            ->withProduct(new ProductId('SKU-2'), new Money(500, 'EUR'), 10);
        $this->orders = new InMemoryOrderRepository();
        $this->orderIds = new FixedOrderIdGenerator(new OrderId('order-1'));
    }

    #[Test]
    public function checkout_exitoso_paga_la_orden_y_vacia_el_carrito(): void
    {
        $this->seedCart('cart-1', ['SKU-1' => 2, 'SKU-2' => 3]); // 2x1000 + 3x500 = 3500

        $result = ($this->handlerWith(FakePaymentGateway::thatSucceeds()))(new Checkout('cart-1'));

        self::assertSame('order-1', $result->orderId);
        self::assertSame('paid', $result->status);
        self::assertSame(3500, $result->totalAmount);
        self::assertSame('EUR', $result->currency);
        self::assertNull($result->failureReason);

        self::assertSame(OrderStatus::PAID, $this->orders->get(new OrderId('order-1'))->status());
        self::assertFalse($this->carts->has(new CartId('cart-1')), 'el carrito debe eliminarse tras pagar');
    }

    #[Test]
    public function pago_rechazado_deja_la_orden_failed_persistida_y_conserva_el_carrito(): void
    {
        $this->seedCart('cart-1', ['SKU-1' => 1]);

        $result = ($this->handlerWith(FakePaymentGateway::thatFails('tarjeta rechazada')))(new Checkout('cart-1'));

        self::assertSame('failed', $result->status);
        self::assertSame('tarjeta rechazada', $result->failureReason);

        // La orden existe persistida en FAILED (rastro conciliable, D-007).
        self::assertSame(OrderStatus::FAILED, $this->orders->get(new OrderId('order-1'))->status());
        // El carrito NO se elimina: no hubo compra exitosa.
        self::assertTrue($this->carts->has(new CartId('cart-1')));
    }

    #[Test]
    public function no_se_puede_hacer_checkout_de_un_carrito_vacio(): void
    {
        $this->carts->save(new Cart(new CartId('cart-1')));

        try {
            ($this->handlerWith(FakePaymentGateway::thatSucceeds()))(new Checkout('cart-1'));
            self::fail('Se esperaba EmptyCart.');
        } catch (EmptyCart) {
            self::assertFalse($this->orders->has(new OrderId('order-1')), 'no debe crearse orden');
        }
    }

    #[Test]
    public function stock_insuficiente_falla_antes_de_cobrar_y_no_crea_orden(): void
    {
        $this->catalog->withProduct(new ProductId('SKU-1'), new Money(1000, 'EUR'), 1); // solo 1 en stock
        $this->seedCart('cart-1', ['SKU-1' => 5]); // se piden 5

        try {
            ($this->handlerWith(FakePaymentGateway::thatSucceeds()))(new Checkout('cart-1'));
            self::fail('Se esperaba InsufficientStock.');
        } catch (InsufficientStock) {
            self::assertFalse($this->orders->has(new OrderId('order-1')), 'no debe crearse orden');
            self::assertTrue($this->carts->has(new CartId('cart-1')), 'el carrito no debe tocarse');
        }
    }

    #[Test]
    public function checkout_de_un_carrito_inexistente_lanza_cart_not_found(): void
    {
        $this->expectException(CartNotFound::class);

        ($this->handlerWith(FakePaymentGateway::thatSucceeds()))(new Checkout('cart-inexistente'));
    }

    #[Test]
    public function congela_el_precio_en_la_orden_aunque_el_catalogo_cambie_despues(): void
    {
        $this->seedCart('cart-1', ['SKU-1' => 2]); // precio vigente 1000 -> total 2000

        ($this->handlerWith(FakePaymentGateway::thatSucceeds()))(new Checkout('cart-1'));

        // El catálogo sube el precio DESPUÉS del checkout.
        $this->catalog->withProduct(new ProductId('SKU-1'), new Money(9999, 'EUR'), 10);

        $order = $this->orders->get(new OrderId('order-1'));
        self::assertSame(1000, $order->lines()[0]->unitPrice()->amount());
        self::assertTrue($order->total()->equals(new Money(2000, 'EUR')));
    }

    private function handlerWith(PaymentGateway $payments): CheckoutHandler
    {
        return new CheckoutHandler($this->carts, $this->catalog, $this->orders, $payments, $this->orderIds);
    }

    /**
     * @param array<string, int> $lines productId => quantity
     */
    private function seedCart(string $cartId, array $lines): void
    {
        $cart = new Cart(new CartId($cartId));

        foreach ($lines as $productId => $quantity) {
            $cart->addItem(new ProductId($productId), new Quantity($quantity));
        }

        $this->carts->save($cart);
    }
}
