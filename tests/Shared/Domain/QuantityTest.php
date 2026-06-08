<?php

declare(strict_types=1);

namespace Siroko\Tests\Shared\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Shared\Domain\Exception\InvalidQuantity;
use Siroko\Shared\Domain\Quantity;

final class QuantityTest extends TestCase
{
    #[Test]
    public function se_construye_con_el_minimo_uno(): void
    {
        self::assertSame(1, (new Quantity(1))->value());
    }

    #[Test]
    public function se_construye_con_un_valor_mayor(): void
    {
        self::assertSame(42, (new Quantity(42))->value());
    }

    #[Test]
    #[DataProvider('cantidadesInvalidas')]
    public function rechaza_valores_menores_que_uno(int $value): void
    {
        $this->expectException(InvalidQuantity::class);

        new Quantity($value);
    }

    /** @return iterable<string, array{int}> */
    public static function cantidadesInvalidas(): iterable
    {
        yield 'cero' => [0];
        yield 'menos uno' => [-1];
        yield 'negativo grande' => [-100];
    }

    #[Test]
    public function add_suma_unidades_y_devuelve_la_cantidad_total(): void
    {
        $result = (new Quantity(2))->add(new Quantity(3));

        self::assertSame(5, $result->value());
    }

    #[Test]
    public function add_no_muta_la_instancia_original_y_devuelve_otra(): void
    {
        $original = new Quantity(2);

        $result = $original->add(new Quantity(3));

        self::assertSame(2, $original->value());
        self::assertNotSame($original, $result);
    }

    #[Test]
    public function equals_distingue_por_valor(): void
    {
        $two = new Quantity(2);

        self::assertTrue($two->equals(new Quantity(2)));
        self::assertFalse($two->equals(new Quantity(3)));
    }
}
