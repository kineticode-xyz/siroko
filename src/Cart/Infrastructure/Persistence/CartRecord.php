<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad de persistencia (Doctrine) del carrito. NO es el dominio: es un record plano.
 * El dominio (Siroko\Cart\Domain\Cart) no la conoce; el repositorio traduce entre ambos.
 */
#[ORM\Entity]
#[ORM\Table(name: 'carts')]
class CartRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    public string $id;

    /** @var Collection<int, CartItemRecord> */
    #[ORM\OneToMany(targetEntity: CartItemRecord::class, mappedBy: 'cart', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $items;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->items = new ArrayCollection();
    }
}
