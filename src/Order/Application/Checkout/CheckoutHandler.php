<?php

declare(strict_types=1);

namespace Siroko\Order\Application\Checkout;

use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\CartRepository;
use Siroko\Cart\Domain\Exception\EmptyCart;
use Siroko\Cart\Domain\Exception\InsufficientStock;
use Siroko\Cart\Domain\PriceList;
use Siroko\Cart\Domain\ProductCatalog;
use Siroko\Cart\Domain\StockLevels;
use Siroko\Order\Domain\Order;
use Siroko\Order\Domain\OrderIdGenerator;
use Siroko\Order\Domain\OrderLine;
use Siroko\Order\Domain\OrderRepository;
use Siroko\Order\Domain\PaymentGateway;
use Siroko\Shared\Domain\CartId;

/**
 * Caso de uso Checkout: confirma el carrito y genera una orden persistente.
 *
 * Secuencia (ai/PLAN.md §3.7, con persistencia PENDING antes de cobrar, ver DECISIONS D-007):
 *  1. Recuperar el carrito (CartNotFound). Si está vacío → EmptyCart (no se crea orden).
 *  2. Precios y stock vigentes en una sola consulta batch cada uno (evita N+1).
 *  3. Validación DURA de stock → InsufficientStock antes de crear orden / cobrar.
 *  4. Crear la Order en PENDING con líneas y precios CONGELADOS, y persistirla.
 *  5. Cobrar. Pago OK → PAID + eliminar carrito; pago KO → FAILED (carrito intacto).
 *  6. Persistir el nuevo estado y devolver CheckoutResult.
 */
final class CheckoutHandler
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly ProductCatalog $catalog,
        private readonly OrderRepository $orders,
        private readonly PaymentGateway $payments,
        private readonly OrderIdGenerator $orderIds,
    ) {
    }

    public function __invoke(Checkout $command): CheckoutResult
    {
        $cart = $this->carts->get(new CartId($command->cartId));

        if ($cart->isEmpty()) {
            throw EmptyCart::cannotCheckout($cart->id());
        }

        $productIds = array_map(
            static fn ($item) => $item->productId(),
            $cart->items(),
        );
        $prices = $this->catalog->pricesFor($productIds);
        $stock = $this->catalog->stockFor($productIds);

        $this->assertEnoughStock($cart, $stock);

        $order = Order::place($this->orderIds->nextIdentity(), $this->freezeLines($cart, $prices));

        // Persistir en PENDING antes de cobrar: nunca cobrar sin dejar rastro conciliable (D-007).
        $this->orders->save($order);

        $payment = $this->payments->charge($order);

        if ($payment->isSuccessful()) {
            $order->markPaid();
            $this->orders->save($order);
            $this->carts->remove($cart->id());
        } else {
            $order->markFailed();
            $this->orders->save($order);
        }

        return new CheckoutResult(
            $order->id()->value(),
            $order->status()->value,
            $order->total()->amount(),
            $order->total()->currency(),
            $payment->isSuccessful() ? null : $payment->reason(),
        );
    }

    private function assertEnoughStock(Cart $cart, StockLevels $stock): void
    {
        foreach ($cart->items() as $item) {
            $requested = $item->quantity()->value();
            $available = $stock->availableFor($item->productId());

            if ($available < $requested) {
                throw InsufficientStock::forProduct($item->productId(), $requested, $available);
            }
        }
    }

    /**
     * @return list<OrderLine>
     */
    private function freezeLines(Cart $cart, PriceList $prices): array
    {
        $lines = [];

        foreach ($cart->items() as $item) {
            $lines[] = new OrderLine(
                $item->productId(),
                $item->quantity(),
                $prices->priceOf($item->productId()),
            );
        }

        return $lines;
    }
}
