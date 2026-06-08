<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Http;

use Siroko\Cart\Application\GetCart\GetCart;
use Siroko\Cart\Application\GetCart\GetCartHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetCartController
{
    public function __construct(
        private readonly GetCartHandler $handler,
    ) {
    }

    #[Route('/cart/{cartId}', name: 'cart_get', methods: ['GET'])]
    public function __invoke(string $cartId): JsonResponse
    {
        $view = ($this->handler)(new GetCart($cartId));

        return new JsonResponse([
            'cartId' => $view->cartId,
            'currency' => $view->currency,
            'lines' => array_map(static fn ($line): array => [
                'productId' => $line->productId,
                'quantity' => $line->quantity,
                'unitPriceAmount' => $line->unitPriceAmount,
                'subtotalAmount' => $line->subtotalAmount,
            ], $view->lines),
            'totalAmount' => $view->totalAmount,
        ]);
    }
}
