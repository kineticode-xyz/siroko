<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

use Siroko\Order\Domain\Exception\EmptyOrder;
use Siroko\Order\Domain\Exception\IllegalOrderTransition;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\OrderId;

/**
 * Orden: registro histórico de una compra. Agregado raíz del contexto Orden.
 *
 * Reglas (ver ai/PLAN.md §3.6 / §4.3):
 *  - Contenido inmutable desde su creación (líneas, precios congelados, total).
 *  - Lo único que evoluciona es el estado.
 *  - No referencia al carrito del que vino.
 *  - Se crea en PENDING; transiciones controladas por OrderStatus.
 */
final class Order
{
    /** @var list<OrderLine> */
    private readonly array $lines;

    private OrderStatus $status;

    private readonly Money $total;

    /**
     * @param list<OrderLine> $lines
     */
    private function __construct(
        private readonly OrderId $id,
        array $lines,
        OrderStatus $status,
        Money $total,
    ) {
        $this->lines = $lines;
        $this->status = $status;
        $this->total = $total;
    }

    /**
     * Crea una orden a partir de líneas con precio ya congelado. Estado inicial PENDING.
     *
     * @param list<OrderLine> $lines
     */
    public static function place(OrderId $id, array $lines): self
    {
        if ($lines === []) {
            throw EmptyOrder::cannotBePlaced($id);
        }

        return new self($id, array_values($lines), OrderStatus::PENDING, self::calculateTotal($lines));
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function total(): Money
    {
        return $this->total;
    }

    /** @return list<OrderLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function markPaid(): void
    {
        $this->transitionTo(OrderStatus::PAID);
    }

    public function markFailed(): void
    {
        $this->transitionTo(OrderStatus::FAILED);
    }

    private function transitionTo(OrderStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw IllegalOrderTransition::from($this->status, $target);
        }

        $this->status = $target;
    }

    /**
     * @param non-empty-list<OrderLine> $lines
     */
    private static function calculateTotal(array $lines): Money
    {
        $total = null;

        foreach ($lines as $line) {
            // add() exige misma moneda: si las líneas mezclan monedas, lanza CurrencyMismatch.
            $total = $total === null ? $line->subtotal() : $total->add($line->subtotal());
        }

        // $total no es null: place() garantiza >= 1 línea.
        // Suelo 0: el precio final a pagar nunca es negativo (PLAN §3.1).
        return $total->isNegative() ? new Money(0, $total->currency()) : $total;
    }
}
