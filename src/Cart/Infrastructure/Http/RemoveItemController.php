<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Http;

use Siroko\Cart\Application\RemoveItem\RemoveCartItem;
use Siroko\Cart\Application\RemoveItem\RemoveCartItemHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RemoveItemController
{
    public function __construct(
        private readonly RemoveCartItemHandler $handler,
    ) {
    }

    #[Route('/cart/{cartId}/items/{productId}', name: 'cart_remove_item', methods: ['DELETE'])]
    public function __invoke(string $cartId, string $productId): JsonResponse
    {
        ($this->handler)(new RemoveCartItem($cartId, $productId));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
