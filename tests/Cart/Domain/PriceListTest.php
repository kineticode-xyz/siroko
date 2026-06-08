<?php

declare(strict_types=1);

namespace Siroko\Tests\Cart\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Cart\Domain\Exception\MissingPrice;
use Siroko\Cart\Domain\PriceList;
use Siroko\Shared\Domain\Exception\CurrencyMismatch;
use Siroko\Shared\Domain\Exception\InvalidCurrency;
use Siroko\Shared\Domain\Money;
use Siroko\Shared\Domain\ProductId;

final class PriceListTest extends TestCase
{
    #[Test]
    public function devuelve_el_precio_entregado_para_un_producto(): void
    {
        $prices = (new PriceList('EUR'))
            ->withPrice(new ProductId('SKU-1'), new Money(1999, 'EUR'));

        self::assertTrue($prices->priceOf(new ProductId('SKU-1'))->equals(new Money(1999, 'EUR')));
    }

    #[Test]
    public function lanza_excepcion_si_falta_el_precio_de_un_producto(): void
    {
        $prices = new PriceList('EUR');

        $this->expectException(MissingPrice::class);

        $prices->priceOf(new ProductId('SKU-1'));
    }

    #[Test]
    public function rechaza_construirse_con_moneda_vacia(): void
    {
        $this->expectException(InvalidCurrency::class);

        new PriceList('   ');
    }

    #[Test]
    public function normaliza_la_moneda_a_mayusculas(): void
    {
        self::assertSame('EUR', (new PriceList('eur'))->currency());
    }

    #[Test]
    public function rechaza_un_precio_de_otra_moneda(): void
    {
        $this->expectException(CurrencyMismatch::class);

        (new PriceList('EUR'))->withPrice(new ProductId('SKU-1'), new Money(1000, 'USD'));
    }

    #[Test]
    public function with_price_es_inmutable_y_no_afecta_a_la_instancia_original(): void
    {
        $original = new PriceList('EUR');

        $extended = $original->withPrice(new ProductId('SKU-1'), new Money(1000, 'EUR'));

        self::assertNotSame($original, $extended);

        // La original no quedó modificada: sigue sin tener el precio.
        $this->expectException(MissingPrice::class);
        $original->priceOf(new ProductId('SKU-1'));
    }
}
