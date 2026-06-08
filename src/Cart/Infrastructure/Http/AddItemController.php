<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Http;

use Siroko\Cart\Application\AddItem\AddItemToCart;
use Siroko\Cart\Application\AddItem\AddItemToCartHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddItemController
{
    public function __construct(
        private readonly AddItemToCartHandler $handler,
    ) {
    }

    #[Route('/cart/{cartId}/items', name: 'cart_add_item', methods: ['POST'])]
    public function __invoke(string $cartId, Request $request): JsonResponse
    {
        $body = (array) (json_decode($request->getContent() ?: '{}', true) ?? []);

        ($this->handler)(new AddItemToCart(
            $cartId,
            (string) ($body['productId'] ?? ''),
            (int) ($body['quantity'] ?? 0),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
