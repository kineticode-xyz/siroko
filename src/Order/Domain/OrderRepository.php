<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

use Siroko\Order\Domain\Exception\OrderNotFound;
use Siroko\Shared\Domain\OrderId;

/**
 * Puerto de persistencia de la orden (Data Mapper). La implementación vive en Infraestructura.
 * Requisito de la prueba: la orden es persistente.
 */
interface OrderRepository
{
    public function save(Order $order): void;

    /**
     * @throws OrderNotFound si no existe una orden con ese id (nunca devuelve null)
     */
    public function get(OrderId $orderId): Order;
}
