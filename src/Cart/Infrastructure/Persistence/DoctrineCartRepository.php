<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Siroko\Cart\Domain\Cart;
use Siroko\Cart\Domain\CartRepository;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\ProductId;
use Siroko\Shared\Domain\Quantity;

/**
 * Adaptador Doctrine del puerto CartRepository. Traduce dominio <-> record en la frontera,
 * de modo que el agregado Cart no conoce Doctrine (ver DECISIONS D-009).
 */
final class DoctrineCartRepository implements CartRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(Cart $cart): void
    {
        $record = $this->em->find(CartRecord::class, $cart->id()->value());

        if ($record === null) {
            $record = new CartRecord($cart->id()->value());
            $this->em->persist($record);
        }

        // Resincroniza líneas: vaciar y reconstruir (orphanRemoval borra las viejas).
        $record->items->clear();
        foreach ($cart->items() as $item) {
            $record->items->add(new CartItemRecord(
                $record,
                $item->productId()->value(),
                $item->quantity()->value(),
            ));
        }

        $this->em->flush();
    }

    public function get(CartId $cartId): Cart
    {
        $record = $this->em->find(CartRecord::class, $cartId->value());

        if ($record === null) {
            throw CartNotFound::withId($cartId);
        }

        $cart = new Cart($cartId);
        foreach ($record->items as $item) {
            $cart->addItem(new ProductId($item->productId), new Quantity($item->quantity));
        }

        return $cart;
    }

    public function remove(CartId $cartId): void
    {
        $record = $this->em->find(CartRecord::class, $cartId->value());

        if ($record !== null) {
            $this->em->remove($record);
            $this->em->flush();
        }
    }
}
