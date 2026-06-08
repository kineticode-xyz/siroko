<?php

declare(strict_types=1);

namespace Siroko\Cart\Application\GetCart;

use Siroko\Cart\Domain\CartRepository;
use Siroko\Cart\Domain\ProductCatalog;
use Siroko\Shared\Domain\CartId;

/**
 * Caso de uso de lectura GetCart. Devuelve un CartView con precios vigentes y total.
 *
 * Secuencia:
 *  1. Recupera el carrito (CartNotFound si no existe).
 *  2. Pide al catálogo los precios vigentes de sus líneas en UNA consulta batch (evita N+1).
 *  3. Compone el DTO: subtotal por línea y total (con suelo 0, vía Cart::total).
 */
final class GetCartHandler
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly ProductCatalog $catalog,
    ) {
    }

    public function __invoke(GetCart $query): CartView
    {
        $cart = $this->carts->get(new CartId($query->cartId));

        $productIds = array_map(
            static fn ($item) => $item->productId(),
            $cart->items(),
        );
        $prices = $this->catalog->pricesFor($productIds);

        $lines = [];
        foreach ($cart->items() as $item) {
            $unitPrice = $prices->priceOf($item->productId());
            $subtotal = $unitPrice->multiply($item->quantity()->value());

            $lines[] = new CartLineView(
                $item->productId()->value(),
                $item->quantity()->value(),
                $unitPrice->amount(),
                $subtotal->amount(),
            );
        }

        $total = $cart->total($prices);

        return new CartView(
            $cart->id()->value(),
            $total->currency(),
            $lines,
            $total->amount(),
        );
    }
}
