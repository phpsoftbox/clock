<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

use function is_string;

final class DatePoint extends DateTimeImmutable implements JsonSerializable, Stringable
{
    public function __construct(?DateTimeInterface $dateTime = null)
    {
        if ($dateTime === null) {
            $target = Clock::now();
        } elseif ($dateTime instanceof DateTimeImmutable) {
            $target = $dateTime;
        } else {
            $target = DateTimeImmutable::createFromInterface($dateTime);
        }

        parent::__construct($target->format('Y-m-d H:i:s.u P'));
    }

    public static function now(): self
    {
        return new self();
    }

    public static function from(DateTimeInterface $dateTime): self
    {
        return new self($dateTime);
    }

    public static function fromString(string $value, ?string $format = null): self
    {
        if ($format !== null && $format !== '') {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt !== false) {
                return new self($dt);
            }
        }

        return new self(new DateTimeImmutable($value));
    }

    public static function fromValue(mixed $value): ?self
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new self($value);
        }

        if (is_string($value) && $value !== '') {
            return self::fromString($value);
        }

        throw new InvalidArgumentException('Unsupported date point value.');
    }

    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return $this;
    }

    public function __toString(): string
    {
        return $this->format(DateTimeInterface::ATOM);
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
