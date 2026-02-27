<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock;

use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use RuntimeException;

use function is_int;
use function sprintf;

final class Clock
{
    private static ?ClockInterface $clock = null;

    public static function now(): DateTimeImmutable
    {
        return self::get()->now();
    }

    public static function get(): ClockInterface
    {
        if (self::$clock === null) {
            self::$clock = new SystemClock();
        }

        return self::$clock;
    }

    public static function set(ClockInterface $clock): void
    {
        self::$clock = $clock;
    }

    public static function freeze(DateTimeImmutable $now): void
    {
        self::$clock = new FrozenClock($now);
    }

    public static function travel(DateInterval|int|string $value): DateTimeImmutable
    {
        $clock = self::get();
        if (!$clock instanceof FrozenClock) {
            throw new RuntimeException('Clock::travel requires a frozen clock.');
        }

        $current = $clock->now();
        if (is_int($value)) {
            $updated = $current->modify(sprintf('%+d seconds', $value));
        } elseif ($value instanceof DateInterval) {
            $updated = $current->add($value);
        } else {
            $updated = $current->modify($value);
        }

        if ($updated === false) {
            $updated = $current;
        }

        self::$clock = $clock->withNow($updated);

        return $updated;
    }

    public static function reset(): void
    {
        self::$clock = null;
    }
}
