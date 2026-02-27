<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock\Tests;

use DateTimeImmutable;
use PhpSoftBox\Clock\Clock;
use PhpSoftBox\Clock\DatePoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatePoint::class)]
#[CoversMethod(DatePoint::class, '__construct')]
#[CoversMethod(DatePoint::class, 'toDateTimeImmutable')]
#[CoversMethod(DatePoint::class, 'fromString')]
final class DatePointTest extends TestCase
{
    /**
     * Проверяет: DatePoint без аргументов берет время из Clock.
     */
    #[Test]
    public function datePointUsesClockNow(): void
    {
        $frozen = new DateTimeImmutable('2026-02-27 11:00:00');

        Clock::freeze($frozen);

        $point = new DatePoint();

        $this->assertSame($frozen->format('Y-m-d H:i:s'), $point->format('Y-m-d H:i:s'));

        Clock::reset();
    }

    /**
     * Проверяет: fromString создает точку из строки.
     */
    #[Test]
    public function fromStringParsesValue(): void
    {
        $point = DatePoint::fromString('2026-02-27 12:30:00');

        $this->assertSame('2026-02-27 12:30:00', $point->format('Y-m-d H:i:s'));
    }
}
