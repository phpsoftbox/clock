<?php

declare(strict_types=1);

namespace PhpSoftBox\Clock;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

use function array_merge;
use function date_parse_from_format;
use function implode;
use function is_string;
use function sprintf;
use function str_contains;

final class DatePoint extends DateTimeImmutable implements JsonSerializable, Stringable
{
    public function __construct(?DateTimeInterface $dateTime = null)
    {
        $target = $dateTime ?? Clock::now();

        // Сохраняем исходную таймзону целиком (в т.ч. именованную, например Europe/Berlin),
        // чтобы арифметика через переход на летнее/зимнее время оставалась корректной.
        parent::__construct($target->format('Y-m-d H:i:s.u'), $target->getTimezone());

        if ($this->format('U.u') !== $target->format('U.u')) {
            // Неоднозначное локальное время (повторяющийся час при переходе на зимнее время):
            // по локальному времени момент восстановить нельзя, поэтому фиксируем смещение.
            parent::__construct($target->format('Y-m-d H:i:s.u P'));
        }
    }

    public static function now(): self
    {
        return new self();
    }

    public static function from(DateTimeInterface $dateTime): self
    {
        return new self($dateTime);
    }

    /**
     * Создаёт точку из строки.
     *
     * Без формата строка разбирается свободным парсером PHP (относительные выражения вроде "+1 day"
     * считаются от системного времени, используйте Clock::now()->modify() для работы с замороженным временем).
     * С явным форматом строка обязана ему соответствовать, иначе бросается InvalidArgumentException.
     * Поля, которых нет в формате (без `!`/`|`), берутся из Clock::now(), а не из системных часов.
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value, ?string $format = null): self
    {
        if ($format === null || $format === '') {
            return self::parseFree($value);
        }

        return self::parseFormat($value, $format);
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

    private static function parseFree(string $value): self
    {
        try {
            return new self(new DateTimeImmutable($value));
        } catch (Exception $exception) {
            throw new InvalidArgumentException(sprintf('Invalid date point value "%s".', $value), 0, $exception);
        }
    }

    private static function parseFormat(string $value, string $format): self
    {
        $info = date_parse_from_format($format, $value);

        $problems = array_merge($info['errors'], $info['warnings']);
        if ($problems !== []) {
            throw new InvalidArgumentException(sprintf(
                'Date point value "%s" does not match format "%s": %s.',
                $value,
                $format,
                implode('; ', $problems),
            ));
        }

        if (str_contains($format, '!') || str_contains($format, '|')) {
            return new self(self::createFromFormatOrFail($format, $value));
        }

        // Без `!`/`|` PHP подставляет недостающие поля из системных часов. Подставляем их из Clock::now()
        // явно: дописываем отсутствующие поля в начало строки (поля из значения идут позже и имеют приоритет).
        $zoneSource = self::createFromFormatOrFail('!' . $format, $value);
        $now        = Clock::now()->setTimezone($zoneSource->getTimezone());

        $prefixFormat = [];
        $prefixValue  = [];
        foreach (['year' => 'Y', 'month' => 'm', 'day' => 'd'] as $field => $char) {
            if ($info[$field] === false) {
                $prefixFormat[] = $char;
                $prefixValue[]  = $now->format($char);
            }
        }

        if ($info['hour'] === false && $info['minute'] === false && $info['second'] === false) {
            $prefixFormat[] = 'H:i:s';
            $prefixValue[]  = $now->format('H:i:s');
        }

        if ($prefixFormat === []) {
            return new self($zoneSource);
        }

        $fullFormat = '!' . implode('\\#', $prefixFormat) . '\\#' . $format;
        $fullValue  = implode('#', $prefixValue) . '#' . $value;

        return new self(self::createFromFormatOrFail($fullFormat, $fullValue));
    }

    private static function createFromFormatOrFail(string $format, string $value): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat($format, $value);
        if ($parsed === false) {
            throw new InvalidArgumentException(sprintf(
                'Date point value "%s" does not match format "%s".',
                $value,
                $format,
            ));
        }

        return $parsed;
    }
}
