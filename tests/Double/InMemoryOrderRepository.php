<?php

declare(strict_types=1);

namespace Siroko\Tests\Double;

use Siroko\Order\Domain\Exception\OrderNotFound;
use Siroko\Order\Domain\Order;
use Siroko\Order\Domain\OrderRepository;
use Siroko\Shared\Domain\OrderId;

/**
 * Doble in-memory del puerto OrderRepository. Clona en profundidad en save/get
 * para ser fiel a una BD (lo guardado es un snapshot desacoplado).
 */
final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id()->value()] = $this->deepCopy($order);
    }

    public function get(OrderId $orderId): Order
    {
        $order = $this->orders[$orderId->value()] ?? throw OrderNotFound::withId($orderId);

        return $this->deepCopy($order);
    }

    public function has(OrderId $orderId): bool
    {
        return isset($this->orders[$orderId->value()]);
    }

    private function deepCopy(Order $order): Order
    {
        return unserialize(serialize($order));
    }
}
