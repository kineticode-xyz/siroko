<?php

declare(strict_types=1);

namespace Siroko\Tests\Double;

use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\CartRepository;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Shared\Domain\CartId;

/**
 * Doble in-memory del puerto CartRepository para tests de casos de uso.
 * Verifica efectos observables (qué queda persistido), no llamadas internas.
 *
 * Clona en profundidad en save/get para ser fiel a una BD real: lo guardado es un
 * snapshot desacoplado y lo leído es una copia detached. Así, mutar un Cart sin
 * llamar a save() no altera el almacén (igual que con un ORM sin flush).
 */
final class InMemoryCartRepository implements CartRepository
{
    /** @var array<string, Cart> */
    private array $carts = [];

    public function save(Cart $cart): void
    {
        $this->carts[$cart->id()->value()] = $this->deepCopy($cart);
    }

    public function get(CartId $cartId): Cart
    {
        $cart = $this->carts[$cartId->value()] ?? throw CartNotFound::withId($cartId);

        return $this->deepCopy($cart);
    }

    public function remove(CartId $cartId): void
    {
        unset($this->carts[$cartId->value()]);
    }

    public function has(CartId $cartId): bool
    {
        return isset($this->carts[$cartId->value()]);
    }

    /**
     * Copia profunda (incluye los CartItem mutables) sin tocar el dominio.
     */
    private function deepCopy(Cart $cart): Cart
    {
        return unserialize(serialize($cart));
    }
}
