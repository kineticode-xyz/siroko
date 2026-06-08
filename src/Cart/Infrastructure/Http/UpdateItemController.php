<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Http;

use Siroko\Cart\Application\UpdateItem\UpdateCartItem;
use Siroko\Cart\Application\UpdateItem\UpdateCartItemHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateItemController
{
    public function __construct(
        private readonly UpdateCartItemHandler $handler,
    ) {
    }

    #[Route('/cart/{cartId}/items/{productId}', name: 'cart_update_item', methods: ['PATCH'])]
    public function __invoke(string $cartId, string $productId, Request $request): JsonResponse
    {
        $body = (array) (json_decode($request->getContent() ?: '{}', true) ?? []);

        ($this->handler)(new UpdateCartItem(
            $cartId,
            $productId,
            (int) ($body['quantity'] ?? 0),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
