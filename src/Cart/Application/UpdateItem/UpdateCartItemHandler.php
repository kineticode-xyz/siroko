<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\UpdateItem;

use Siroko\Cart\Domain\CartRepository;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Caso de uso UpdateCartItem. Devuelve void (comando).
 *
 * Secuencia:
 *  1. Convierte primitivos a VOs (Quantity valida >= 1 → InvalidQuantity).
 *  2. Recupera el carrito (CartNotFound si no existe; aquí NO se crea, ver DECISIONS D-004).
 *  3. Fija la cantidad por valor absoluto (CartItemNotFound si la línea no existe) y persiste.
 */
final class UpdateCartItemHandler
{
    public function __construct(
        private readonly CartRepository $carts,
    ) {
    }

    public function __invoke(UpdateCartItem $command): void
    {
        $cartId = new CartId($command->cartId);
        $productId = new ProductId($command->productId);
        $quantity = new Quantity($command->quantity);

        $cart = $this->carts->get($cartId);
        $cart->updateItem($productId, $quantity);

        $this->carts->save($cart);
    }
}
