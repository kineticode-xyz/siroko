<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

/**
 * Puerto de cobro. Abstrae la pasarela de pago; el adaptador (fake en esta entrega)
 * vive en Infraestructura. El dominio solo conoce esta interfaz.
 */
interface PaymentGateway
{
    public function charge(Order $order): PaymentResult;
}
