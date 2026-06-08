<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Catalog;

use Doctrine\ORM\Mapping as ORM;

/**
 * Record de persistencia de un producto del catálogo (precio en céntimos + stock).
 */
#[ORM\Entity]
#[ORM\Table(name: 'products')]
class ProductRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    public string $id;

    #[ORM\Column(name: 'price_amount', type: 'integer')]
    public int $priceAmount;

    #[ORM\Column(name: 'price_currency', type: 'string', length: 3)]
    public string $priceCurrency;

    #[ORM\Column(type: 'integer')]
    public int $stock;

    public function __construct(string $id, int $priceAmount, string $priceCurrency, int $stock)
    {
        $this->id = $id;
        $this->priceAmount = $priceAmount;
        $this->priceCurrency = $priceCurrency;
        $this->stock = $stock;
    }
}
