<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PhpSoftBox\Clock\Clock;
use PhpSoftBox\Clock\DatePoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const DATE_ATOM;

#[CoversClass(DatePoint::class)]
#[CoversMethod(DatePoint::class, '__construct')]
#[CoversMethod(DatePoint::class, 'toDateTimeImmutable')]
#[CoversMethod(DatePoint::class, 'fromString')]
final class DatePointTest extends TestCase
{
    protected function tearDown(): void
    {
        Clock::reset();
    }

    /**
     * Проверяет: DatePoint без аргументов берет время из Clock.
     *
     * @see DatePoint::__construct()
     */
    #[Test]
    public function datePointUsesClockNow(): void
    {
        $frozen = new DateTimeImmutable('2026-02-27 11:00:00');

        Clock::freeze($frozen);

        $point = new DatePoint();

        $this->assertSame($frozen->format('Y-m-d H:i:s'), $point->format('Y-m-d H:i:s'));
    }

    /**
     * Проверяет: fromString создает точку из строки.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringParsesValue(): void
    {
        $point = DatePoint::fromString('2026-02-27 12:30:00');

        $this->assertSame('2026-02-27 12:30:00', $point->format('Y-m-d H:i:s'));
    }

    /**
     * Проверяет: именованная таймзона сохраняется, а не превращается в смещение.
     *
     * @see DatePoint::__construct()
     */
    #[Test]
    public function constructorKeepsNamedTimezone(): void
    {
        $source = new DateTimeImmutable('2024-03-30 12:00:00', new DateTimeZone('Europe/Berlin'));

        $point = new DatePoint($source);

        $this->assertSame('Europe/Berlin', $point->getTimezone()->getName());
    }

    /**
     * Проверяет: арифметика через переход на летнее время учитывает правила именованной таймзоны.
     *
     * @see DatePoint::__construct()
     */
    #[Test]
    public function arithmeticAcrossDstUsesNamedTimezone(): void
    {
        $point = new DatePoint(new DateTimeImmutable('2024-03-30 12:00:00', new DateTimeZone('Europe/Berlin')));

        // 31 марта Берлин переходит с +01:00 на +02:00.
        $next = $point->modify('+1 day');

        $this->assertSame('2024-03-31T12:00:00+02:00', $next->format(DATE_ATOM));
    }

    /**
     * Проверяет: момент времени в повторяющемся часе (переход на зимнее время) не теряется.
     *
     * @see DatePoint::__construct()
     */
    #[Test]
    public function constructorKeepsInstantInAmbiguousHour(): void
    {
        // 27 октября 2024 в Берлине час 02:00-03:00 проходит дважды: сначала +02:00, затем +01:00.
        $source = new DateTimeImmutable('2024-10-27 00:30:00 UTC')->setTimezone(new DateTimeZone('Europe/Berlin'));

        $point = new DatePoint($source);

        $this->assertSame($source->getTimestamp(), $point->getTimestamp());
    }

    /**
     * Проверяет: строка, не соответствующая явному формату, приводит к исключению вместо свободного разбора.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringThrowsWhenValueDoesNotMatchFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatePoint::fromString('01/02/2024', 'd/m/Y H:i');
    }

    /**
     * Проверяет: несуществующая дата (переполнение дня) при явном формате не принимается.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringThrowsOnInvalidDateForFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatePoint::fromString('2024-02-30', 'Y-m-d');
    }

    /**
     * Проверяет: некорректная строка без формата приводит к InvalidArgumentException.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringThrowsOnUnparsableValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatePoint::fromString('not a date');
    }

    /**
     * Проверяет: формат без времени и без `!` берёт время из замороженных часов Clock.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringTakesMissingTimeFromClock(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 11:22:33', new DateTimeZone('UTC')));

        $point = DatePoint::fromString('2024-01-15 +00:00', 'Y-m-d P');

        $this->assertSame('2024-01-15 11:22:33', $point->format('Y-m-d H:i:s'));
    }

    /**
     * Проверяет: формат без даты и без `!` берёт дату из замороженных часов Clock.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringTakesMissingDateFromClock(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 11:22:33'));

        $point = DatePoint::fromString('10:15', 'H:i');

        $this->assertSame('2026-02-27 10:15:00', $point->format('Y-m-d H:i:s'));
    }

    /**
     * Проверяет: недостающие поля берутся из Clock в таймзоне, указанной в значении.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringTakesMissingFieldsInParsedTimezone(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 23:30:00', new DateTimeZone('UTC')));

        $point = DatePoint::fromString('10:00 Europe/Moscow', 'H:i e');

        // В Москве в этот момент уже 28 февраля.
        $this->assertSame('2026-02-28 10:00:00 Europe/Moscow', $point->format('Y-m-d H:i:s e'));
    }

    /**
     * Проверяет: формат с `!` не подмешивает текущее время.
     *
     * @see DatePoint::fromString()
     */
    #[Test]
    public function fromStringWithResetFormatIgnoresClock(): void
    {
        Clock::freeze(new DateTimeImmutable('2026-02-27 11:22:33'));

        $point = DatePoint::fromString('2024-01-15', '!Y-m-d');

        $this->assertSame('2024-01-15 00:00:00', $point->format('Y-m-d H:i:s'));
    }

    /**
     * Проверяет: toDateTimeImmutable возвращает тот же момент времени.
     *
     * @see DatePoint::toDateTimeImmutable()
     */
    #[Test]
    public function toDateTimeImmutableReturnsSameInstant(): void
    {
        $point = DatePoint::fromString('2026-02-27 12:30:00');

        $this->assertSame($point->getTimestamp(), $point->toDateTimeImmutable()->getTimestamp());
    }
}
