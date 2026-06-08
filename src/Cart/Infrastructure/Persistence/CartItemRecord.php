<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;

/**
 * Record de persistencia de una línea del carrito (producto + cantidad, sin precio).
 */
#[ORM\Entity]
#[ORM\Table(name: 'cart_items')]
class CartItemRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CartRecord::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'cart_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public CartRecord $cart;

    #[ORM\Column(name: 'product_id', type: 'string', length: 64)]
    public string $productId;

    #[ORM\Column(type: 'integer')]
    public int $quantity;

    public function __construct(CartRecord $cart, string $productId, int $quantity)
    {
        $this->cart = $cart;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }
}
