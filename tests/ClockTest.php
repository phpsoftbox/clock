<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock\Tests;

use DateInterval;
use DateTimeImmutable;
use PhpSoftBox\Clock\Clock;
use PhpSoftBox\Clock\FrozenClock;
use PhpSoftBox\Clock\SystemClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Clock::class)]
#[CoversClass(FrozenClock::class)]
#[CoversClass(SystemClock::class)]
#[CoversMethod(Clock::class, 'freeze')]
#[CoversMethod(Clock::class, 'now')]
#[CoversMethod(Clock::class, 'reset')]
#[CoversMethod(Clock::class, 'get')]
#[CoversMethod(Clock::class, 'travel')]
final class ClockTest extends TestCase
{
    /**
     * Проверяет: freeze фиксирует текущее время.
     */
    #[Test]
    public function freezeFixesNow(): void
    {
        $frozen = new DateTimeImmutable('2026-02-27 10:00:00');

        Clock::freeze($frozen);

        $now = Clock::now();

        $this->assertSame($frozen->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'));

        Clock::reset();
    }

    /**
     * Проверяет: reset возвращает системные часы.
     */
    #[Test]
    public function resetRestoresSystemClock(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 10:00:00'));
        Clock::reset();

        $this->assertInstanceOf(SystemClock::class, Clock::get());
    }

    /**
     * Проверяет: travel двигает зафризенное время.
     */
    #[Test]
    public function travelMovesFrozenTime(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 10:00:00'));

        $moved = Clock::travel(60);

        $this->assertSame('2026-02-27 10:01:00', $moved->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-27 10:01:00', Clock::now()->format('Y-m-d H:i:s'));

        Clock::reset();
    }

    /**
     * Проверяет: travel работает с DateInterval.
     */
    #[Test]
    public function travelAcceptsDateInterval(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 10:00:00'));

        $moved = Clock::travel(new DateInterval('PT2H'));

        $this->assertSame('2026-02-27 12:00:00', $moved->format('Y-m-d H:i:s'));

        Clock::reset();
    }

    /**
     * Проверяет: travel требует frozen clock.
     */
    #[Test]
    public function travelRequiresFrozenClock(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Clock::travel requires a frozen clock.');

        Clock::reset();
        Clock::travel(60);
    }
}
