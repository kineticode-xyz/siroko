<?php

declare(strict_types=1);

namespace Siroko\Order\Infrastructure\Http;

use Siroko\Order\Application\Checkout\Checkout;
use Siroko\Order\Application\Checkout\CheckoutHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController
{
    public function __construct(
        private readonly CheckoutHandler $handler,
    ) {
    }

    #[Route('/cart/{cartId}/checkout', name: 'cart_checkout', methods: ['POST'])]
    public function __invoke(string $cartId): JsonResponse
    {
        $result = ($this->handler)(new Checkout($cartId));

        // Pago OK -> 201 Created; pago rechazado -> 402 Payment Required (no es excepción, D-006).
        $status = 'paid' === $result->status
            ? Response::HTTP_CREATED
            : Response::HTTP_PAYMENT_REQUIRED;

        return new JsonResponse([
            'orderId' => $result->orderId,
            'status' => $result->status,
            'totalAmount' => $result->totalAmount,
            'currency' => $result->currency,
            'failureReason' => $result->failureReason,
        ], $status);
    }
}
