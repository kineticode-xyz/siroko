<?php

declare(strict_types=1);

namespace Siroko\Order\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;

/**
 * Record de persistencia de una línea de orden, con el precio unitario CONGELADO.
 */
#[ORM\Entity]
#[ORM\Table(name: 'order_lines')]
class OrderLineRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderRecord::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public OrderRecord $order;

    #[ORM\Column(name: 'product_id', type: 'string', length: 64)]
    public string $productId;

    #[ORM\Column(type: 'integer')]
    public int $quantity;

    #[ORM\Column(name: 'unit_price_amount', type: 'integer')]
    public int $unitPriceAmount;

    #[ORM\Column(name: 'unit_price_currency', type: 'string', length: 3)]
    public string $unitPriceCurrency;

    public function __construct(
        OrderRecord $order,
        string $productId,
        int $quantity,
        int $unitPriceAmount,
        string $unitPriceCurrency,
    ) {
        $this->order = $order;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->unitPriceAmount = $unitPriceAmount;
        $this->unitPriceCurrency = $unitPriceCurrency;
    }
}
