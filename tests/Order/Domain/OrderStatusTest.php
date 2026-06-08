<?php

declare(strict_types=1);

namespace Siroko\Tests\Order\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Siroko\Order\Domain\OrderStatus;

final class OrderStatusTest extends TestCase
{
    #[Test]
    #[DataProvider('transiciones')]
    public function conoce_sus_transiciones_permitidas(
        OrderStatus $from,
        OrderStatus $to,
        bool $allowed,
    ): void {
        self::assertSame($allowed, $from->canTransitionTo($to));
    }

    /** @return iterable<string, array{OrderStatus, OrderStatus, bool}> */
    public static function transiciones(): iterable
    {
        yield 'PENDING -> PAID permitida'   => [OrderStatus::PENDING, OrderStatus::PAID, true];
        yield 'PENDING -> FAILED permitida' => [OrderStatus::PENDING, OrderStatus::FAILED, true];
        yield 'PENDING -> PENDING no'       => [OrderStatus::PENDING, OrderStatus::PENDING, false];
        yield 'PAID -> FAILED no'           => [OrderStatus::PAID, OrderStatus::FAILED, false];
        yield 'PAID -> PAID no'             => [OrderStatus::PAID, OrderStatus::PAID, false];
        yield 'FAILED -> PAID no (terminal)' => [OrderStatus::FAILED, OrderStatus::PAID, false];
        yield 'FAILED -> FAILED no'         => [OrderStatus::FAILED, OrderStatus::FAILED, false];
    }
}
