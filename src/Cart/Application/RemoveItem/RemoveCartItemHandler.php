<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\RemoveItem;

use Siroko\Cart\Domain\CartRepository;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\ProductId;

/**
 * Caso de uso RemoveCartItem. Devuelve void (comando).
 *
 * Secuencia:
 *  1. Convierte primitivos a VOs.
 *  2. Recupera el carrito (CartNotFound si no existe; aquí NO se crea, ver DECISIONS D-004).
 *  3. Elimina la línea (CartItemNotFound si no existe) y persiste.
 */
final class RemoveCartItemHandler
{
    public function __construct(
        private readonly CartRepository $carts,
    ) {
    }

    public function __invoke(RemoveCartItem $command): void
    {
        $cartId = new CartId($command->cartId);
        $productId = new ProductId($command->productId);

        $cart = $this->carts->get($cartId);
        $cart->removeItem($productId);

        $this->carts->save($cart);
    }
}
