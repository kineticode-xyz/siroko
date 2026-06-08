<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Domain\Exception\MissingStock;
use Siroko\Cart\Domain\StockLevels;
use Siroko\Shared\Domain\ProductId;

final class StockLevelsTest extends TestCase
{
    #[Test]
    public function devuelve_las_unidades_disponibles_de_un_producto(): void
    {
        $stock = (new StockLevels())->withStock(new ProductId('SKU-1'), 7);

        self::assertSame(7, $stock->availableFor(new ProductId('SKU-1')));
    }

    #[Test]
    public function admite_stock_cero(): void
    {
        $stock = (new StockLevels())->withStock(new ProductId('SKU-1'), 0);

        self::assertSame(0, $stock->availableFor(new ProductId('SKU-1')));
    }

    #[Test]
    public function lanza_excepcion_si_no_se_entrego_stock_del_producto(): void
    {
        $this->expectException(MissingStock::class);

        (new StockLevels())->availableFor(new ProductId('SKU-1'));
    }

    #[Test]
    public function with_stock_es_inmutable_y_no_afecta_a_la_instancia_original(): void
    {
        $original = new StockLevels();

        $extended = $original->withStock(new ProductId('SKU-1'), 5);

        self::assertNotSame($original, $extended);

        $this->expectException(MissingStock::class);
        $original->availableFor(new ProductId('SKU-1'));
    }
}
