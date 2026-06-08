<?php

declare(strict_types=1);

namespace Siroko\Tests\Shared\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Shared\Domain\Exception\CurrencyMismatch;
use Siroko\Shared\Domain\Exception\InvalidCurrency;
use Siroko\Shared\Domain\Money;

final class MoneyTest extends TestCase
{
    #[Test]
    public function expone_importe_en_centimos_y_moneda(): void
    {
        $money = new Money(1999, 'EUR');

        self::assertSame(1999, $money->amount());
        self::assertSame('EUR', $money->currency());
    }

    #[Test]
    public function permite_importe_negativo_para_representar_descuento(): void
    {
        $money = new Money(-500, 'EUR');

        self::assertSame(-500, $money->amount());
        self::assertTrue($money->isNegative());
    }

    #[Test]
    public function permite_importe_cero(): void
    {
        $money = new Money(0, 'EUR');

        self::assertSame(0, $money->amount());
        self::assertFalse($money->isNegative());
    }

    #[Test]
    public function normaliza_la_moneda_a_mayusculas_y_recorta_espacios(): void
    {
        $money = new Money(100, '  eur ');

        self::assertSame('EUR', $money->currency());
    }

    #[Test]
    #[DataProvider('monedasVacias')]
    public function rechaza_construir_con_moneda_vacia(string $currency): void
    {
        $this->expectException(InvalidCurrency::class);

        new Money(100, $currency);
    }

    /** @return iterable<string, array{string}> */
    public static function monedasVacias(): iterable
    {
        yield 'cadena vacía' => [''];
        yield 'solo espacios' => ['   '];
    }

    #[Test]
    public function suma_dos_importes_de_la_misma_moneda(): void
    {
        $result = (new Money(1999, 'EUR'))->add(new Money(1, 'EUR'));

        self::assertTrue($result->equals(new Money(2000, 'EUR')));
    }

    #[Test]
    public function resta_dos_importes_de_la_misma_moneda(): void
    {
        $result = (new Money(2000, 'EUR'))->subtract(new Money(1, 'EUR'));

        self::assertTrue($result->equals(new Money(1999, 'EUR')));
    }

    #[Test]
    public function la_resta_puede_dar_negativo(): void
    {
        $result = (new Money(100, 'EUR'))->subtract(new Money(150, 'EUR'));

        self::assertSame(-50, $result->amount());
    }

    #[Test]
    #[DataProvider('factoresDeMultiplicacion')]
    public function multiplica_por_un_factor_entero(int $factor, int $expected): void
    {
        $result = (new Money(1999, 'EUR'))->multiply($factor);

        self::assertSame($expected, $result->amount());
        self::assertSame('EUR', $result->currency());
    }

    /** @return iterable<string, array{int, int}> */
    public static function factoresDeMultiplicacion(): iterable
    {
        yield 'por 3 unidades' => [3, 5997];
        yield 'por cero' => [0, 0];
        yield 'por factor negativo' => [-2, -3998];
    }

    #[Test]
    public function las_operaciones_no_mutan_la_instancia_original(): void
    {
        $original = new Money(1000, 'EUR');

        $original->add(new Money(500, 'EUR'));
        $original->subtract(new Money(500, 'EUR'));
        $original->multiply(10);

        self::assertSame(1000, $original->amount());
    }

    #[Test]
    public function add_devuelve_una_instancia_distinta(): void
    {
        $original = new Money(1000, 'EUR');

        $result = $original->add(new Money(0, 'EUR'));

        self::assertNotSame($original, $result);
    }

    #[Test]
    public function suma_con_monedas_distintas_lanza_excepcion(): void
    {
        $this->expectException(CurrencyMismatch::class);

        (new Money(1000, 'EUR'))->add(new Money(1000, 'USD'));
    }

    #[Test]
    public function resta_con_monedas_distintas_lanza_excepcion(): void
    {
        $this->expectException(CurrencyMismatch::class);

        (new Money(1000, 'EUR'))->subtract(new Money(1000, 'USD'));
    }

    #[Test]
    public function comparar_con_monedas_distintas_lanza_excepcion(): void
    {
        $this->expectException(CurrencyMismatch::class);

        (new Money(1000, 'EUR'))->greaterThan(new Money(1000, 'USD'));
    }

    #[Test]
    public function equals_distingue_por_importe(): void
    {
        $money = new Money(1000, 'EUR');

        self::assertTrue($money->equals(new Money(1000, 'EUR')));
        self::assertFalse($money->equals(new Money(1001, 'EUR')));
    }

    #[Test]
    public function equals_distingue_por_moneda(): void
    {
        $money = new Money(1000, 'EUR');

        self::assertFalse($money->equals(new Money(1000, 'USD')));
    }

    #[Test]
    public function greater_than_y_less_than_comparan_importes(): void
    {
        $ten = new Money(1000, 'EUR');
        $twenty = new Money(2000, 'EUR');

        self::assertTrue($twenty->greaterThan($ten));
        self::assertFalse($ten->greaterThan($twenty));
        self::assertTrue($ten->lessThan($twenty));
        self::assertFalse($twenty->lessThan($ten));
    }
}
