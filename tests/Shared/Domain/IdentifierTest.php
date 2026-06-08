<?php

declare(strict_types=1);

namespace Siroko\Tests\Shared\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Shared\Domain\CartId;
use Siroko\Shared\Domain\Exception\InvalidIdentifier;
use Siroko\Shared\Domain\Identifier;
use Siroko\Shared\Domain\OrderId;
use Siroko\Shared\Domain\ProductId;

final class IdentifierTest extends TestCase
{
    #[Test]
    #[DataProvider('identificadores')]
    public function expone_el_valor_que_envuelve(string $class): void
    {
        /** @var Identifier $id */
        $id = new $class('abc-123');

        self::assertSame('abc-123', $id->value());
    }

    #[Test]
    #[DataProvider('identificadores')]
    public function rechaza_valor_vacio(string $class): void
    {
        $this->expectException(InvalidIdentifier::class);

        new $class('   ');
    }

    /** @return iterable<string, array{class-string<Identifier>}> */
    public static function identificadores(): iterable
    {
        yield 'CartId' => [CartId::class];
        yield 'OrderId' => [OrderId::class];
        yield 'ProductId' => [ProductId::class];
    }

    #[Test]
    public function dos_ids_del_mismo_tipo_y_valor_son_iguales(): void
    {
        self::assertTrue((new ProductId('SKU-1'))->equals(new ProductId('SKU-1')));
    }

    #[Test]
    public function dos_ids_del_mismo_tipo_con_distinto_valor_no_son_iguales(): void
    {
        self::assertFalse((new ProductId('SKU-1'))->equals(new ProductId('SKU-2')));
    }

    #[Test]
    public function ids_de_distinto_tipo_con_la_misma_cadena_no_son_iguales(): void
    {
        $cartId = new CartId('same-value');
        $productId = new ProductId('same-value');

        self::assertFalse($cartId->equals($productId));
    }
}
