<?php

declare(strict_types=1);

namespace Siroko\Cart\Domain;

use Siroko\Cart\Domain\Exception\ProductNotInCatalog;
use Siroko\Shared\Domain\ProductId;

/**
 * Puerto al catálogo/inventario: fuente de verdad de precios vigentes y stock.
 *
 * El dominio NO consulta el catálogo directamente; la capa de aplicación lo invoca
 * y entrega los datos (PriceList/StockLevels) al dominio.
 *
 * Ambos métodos resuelven SOLO los productos que se les pasan, en una única consulta
 * batch (evita el N+1, ver ai/PLAN.md §10). No traen el catálogo entero de la tienda.
 */
interface ProductCatalog
{
    /**
     * ¿Existe el producto en el catálogo? Query pura para validar existencia
     * (p. ej. al añadir una línea al carrito) sin traer precio ni stock.
     */
    public function exists(ProductId $productId): bool;

    /**
     * Precios vigentes de exactamente los productos indicados, en una sola consulta.
     *
     * @param ProductId[] $productIds
     *
     * @throws ProductNotInCatalog si alguno de los productos no existe en el catálogo
     */
    public function pricesFor(array $productIds): PriceList;

    /**
     * Stock disponible de exactamente los productos indicados, en una sola consulta.
     *
     * @param ProductId[] $productIds
     *
     * @throws ProductNotInCatalog si alguno de los productos no existe en el catálogo
     */
    public function stockFor(array $productIds): StockLevels;
}
