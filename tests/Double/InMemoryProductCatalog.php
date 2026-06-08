<?php

declare(strict_types=1);

namespace Siroko\Tests\Double;

use Siroko\Cart\Domain\Exception\ProductNotInCatalog;
use Siroko\Cart\Domain\PriceList;
use Siroko\Cart\Domain\ProductCatalog;
use Siroko\Cart\Domain\StockLevels;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;

/**
 * Doble in-memory del puerto ProductCatalog para tests de casos de uso.
 * Se configura con withProduct() (precio + stock) y resuelve solo los ids pedidos.
 */
final class InMemoryProductCatalog implements ProductCatalog
{
    /** @var array<string, array{price: Money, stock: int}> */
    private array $products = [];

    public function __construct(private readonly string $currency = 'EUR')
    {
    }

    public function withProduct(ProductId $productId, Money $price, int $stock): self
    {
        $this->products[$productId->value()] = ['price' => $price, 'stock' => $stock];

        return $this;
    }

    public function exists(ProductId $productId): bool
    {
        return isset($this->products[$productId->value()]);
    }

    public function pricesFor(array $productIds): PriceList
    {
        $prices = new PriceList($this->currency);

        foreach ($productIds as $productId) {
            $prices = $prices->withPrice($productId, $this->entryFor($productId)['price']);
        }

        return $prices;
    }

    public function stockFor(array $productIds): StockLevels
    {
        $stock = new StockLevels();

        foreach ($productIds as $productId) {
            $stock = $stock->withStock($productId, $this->entryFor($productId)['stock']);
        }

        return $stock;
    }

    /**
     * @return array{price: Money, stock: int}
     */
    private function entryFor(ProductId $productId): array
    {
        return $this->products[$productId->value()] ?? throw ProductNotInCatalog::withId($productId);
    }
}
