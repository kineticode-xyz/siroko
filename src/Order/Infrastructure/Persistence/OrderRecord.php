<?php

declare(strict_types=1);

namespace Siroko\Order\Infrastructure\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Record de persistencia de la orden. Tabla `orders` (order es palabra reservada).
 * El total va congelado (céntimos + moneda); el contenido no cambia, solo el estado.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class OrderRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    public string $id;

    #[ORM\Column(type: 'string', length: 16)]
    public string $status;

    #[ORM\Column(name: 'total_amount', type: 'integer')]
    public int $totalAmount;

    #[ORM\Column(name: 'total_currency', type: 'string', length: 3)]
    public string $totalCurrency;

    /** @var Collection<int, OrderLineRecord> */
    #[ORM\OneToMany(targetEntity: OrderLineRecord::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $lines;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->lines = new ArrayCollection();
    }
}
