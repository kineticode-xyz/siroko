<?php

declare(strict_types=1);

namespace Siroko\Cart\Infrastructure\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Siroko\Cart\Domain\Exception\ProductNotInCatalog;
use Siroko\Cart\Domain\PriceList;
use Siroko\Cart\Domain\ProductCatalog;
use Siroko\Cart\Domain\StockLevels;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;

/**
 * Adaptador Doctrine del puerto ProductCatalog.
 * pricesFor/stockFor resuelven SOLO los ids pedidos en UNA consulta batch (WHERE id IN ...),
 * evitando el N+1 (ver ai/PLAN.md §10 y DECISIONS D-010).
 */
final class DoctrineProductCatalog implements ProductCatalog
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $catalogCurrency,
    ) {
    }

    public function exists(ProductId $productId): bool
    {
        return $this->em->find(ProductRecord::class, $productId->value()) !== null;
    }

    public function pricesFor(array $productIds): PriceList
    {
        $records = $this->fetch($productIds);

        $prices = new PriceList($this->catalogCurrency);
        foreach ($productIds as $productId) {
            $record = $records[$productId->value()] ?? throw ProductNotInCatalog::withId($productId);
            $prices = $prices->withPrice($productId, new Money($record->priceAmount, $record->priceCurrency));
        }

        return $prices;
    }

    public function stockFor(array $productIds): StockLevels
    {
        $records = $this->fetch($productIds);

        $stock = new StockLevels();
        foreach ($productIds as $productId) {
            $record = $records[$productId->value()] ?? throw ProductNotInCatalog::withId($productId);
            $stock = $stock->withStock($productId, $record->stock);
        }

        return $stock;
    }

    /**
     * @param ProductId[] $productIds
     *
     * @return array<string, ProductRecord> indexado por id
     */
    private function fetch(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $ids = array_map(static fn (ProductId $p): string => $p->value(), $productIds);

        // Una sola consulta: SELECT ... WHERE id IN (...).
        $records = $this->em->getRepository(ProductRecord::class)->findBy(['id' => $ids]);

        $byId = [];
        foreach ($records as $record) {
            $byId[$record->id] = $record;
        }

        return $byId;
    }
}
