<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain;

use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Shared\Domain\CartId;

/**
 * Puerto de persistencia del carrito (Data Mapper: el carrito no se guarda a sí mismo).
 * La implementación vive en Infraestructura.
 */
interface CartRepository
{
    public function save(Cart $cart): void;

    /**
     * @throws CartNotFound si no existe un carrito con ese id (nunca devuelve null)
     */
    public function get(CartId $cartId): Cart;

    public function remove(CartId $cartId): void;
}
