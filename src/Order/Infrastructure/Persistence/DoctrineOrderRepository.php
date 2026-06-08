<?php

declare(strict_types=1);

namespace Siroko\Order\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Siroko\Order\Domain\Exception\OrderNotFound;
use Siroko\Order\Domain\Order;
use Siroko\Order\Domain\OrderLine;
use Siroko\Order\Domain\OrderRepository;
use Siroko\Order\Domain\OrderStatus;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\OrderId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Adaptador Doctrine del puerto OrderRepository. Traduce dominio <-> record.
 * Reconstituye el agregado vía Order::place() + transiciones legales (D-009):
 * el total sale idéntico porque los precios van congelados en las líneas.
 */
final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(Order $order): void
    {
        $record = $this->em->find(OrderRecord::class, $order->id()->value());

        if ($record === null) {
            $record = new OrderRecord($order->id()->value());
            $this->em->persist($record);

            // El contenido es inmutable: las líneas solo se escriben al crear.
            foreach ($order->lines() as $line) {
                $record->lines->add(new OrderLineRecord(
                    $record,
                    $line->productId()->value(),
                    $line->quantity()->value(),
                    $line->unitPrice()->amount(),
                    $line->unitPrice()->currency(),
                ));
            }
        }

        // Lo único que evoluciona es el estado (y el total, que es congelado pero lo reflejamos).
        $record->status = $order->status()->value;
        $record->totalAmount = $order->total()->amount();
        $record->totalCurrency = $order->total()->currency();

        $this->em->flush();
    }

    public function get(OrderId $orderId): Order
    {
        $record = $this->em->find(OrderRecord::class, $orderId->value());

        if ($record === null) {
            throw OrderNotFound::withId($orderId);
        }

        $lines = [];
        foreach ($record->lines as $line) {
            $lines[] = new OrderLine(
                new ProductId($line->productId),
                new Quantity($line->quantity),
                new Money($line->unitPriceAmount, $line->unitPriceCurrency),
            );
        }

        $order = Order::place($orderId, $lines); // PENDING

        // Aplica el estado persistido mediante transiciones legales.
        match ($record->status) {
            OrderStatus::PAID->value => $order->markPaid(),
            OrderStatus::FAILED->value => $order->markFailed(),
            default => null, // PENDING se queda como está
        };

        return $order;
    }
}
