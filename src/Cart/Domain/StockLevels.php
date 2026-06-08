<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain;

use Siroko\Cart\Domain\Exception\MissingStock;
use Siroko\Shared\Domain\ProductId;

/**
 * Stock disponible entregado al dominio para la validación dura del checkout.
 *
 * Espejo de PriceList: se construye una vez desde una consulta batch (evita N+1).
 * Inmutable: withStock() devuelve una instancia nueva.
 */
final class StockLevels
{
    /** @var array<string, int> unidades disponibles indexadas por valor de ProductId */
    private array $levels = [];

    public function withStock(ProductId $productId, int $units): self
    {
        $clone = clone $this;
        $clone->levels[$productId->value()] = $units;

        return $clone;
    }

    public function availableFor(ProductId $productId): int
    {
        return $this->levels[$productId->value()] ?? throw MissingStock::forProduct($productId);
    }
}
