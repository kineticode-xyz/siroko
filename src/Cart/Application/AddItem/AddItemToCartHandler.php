<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\AddItem;

use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\CartRepository;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Cart\Domain\Exception\ProductNotInCatalog;
use Siroko\Cart\Domain\ProductCatalog;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Caso de uso AddItemToCart. Devuelve void (comando).
 *
 * Secuencia:
 *  1. Convierte primitivos a VOs (Quantity valida >= 1).
 *  2. Valida que el producto existe en catálogo (ProductNotInCatalog si no) ANTES de tocar el carrito.
 *  3. Recupera el carrito o lo crea si no existe (get-or-create, ver DECISIONS D-004).
 *  4. Añade/fusiona la línea y persiste.
 */
final class AddItemToCartHandler
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly ProductCatalog $catalog,
    ) {
    }

    public function __invoke(AddItemToCart $command): void
    {
        $cartId = new CartId($command->cartId);
        $productId = new ProductId($command->productId);
        $quantity = new Quantity($command->quantity);

        // Existencia del producto: si no está en catálogo, lanza y el carrito no se modifica.
        if (!$this->catalog->exists($productId)) {
            throw ProductNotInCatalog::withId($productId);
        }

        $cart = $this->getOrCreate($cartId);
        $cart->addItem($productId, $quantity);

        $this->carts->save($cart);
    }

    private function getOrCreate(CartId $cartId): Cart
    {
        try {
            return $this->carts->get($cartId);
        } catch (CartNotFound) {
            return new Cart($cartId);
        }
    }
}
