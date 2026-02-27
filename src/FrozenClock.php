<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function withNow(DateTimeImmutable $now): self
    {
        $clone      = clone $this;
        $clone->now = $now;

        return $clone;
    }
}
