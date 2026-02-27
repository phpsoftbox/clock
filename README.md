# Clock

Минимальный PSR-20 совместимый компонент времени.

## Использование

```php
use PhpSoftBox\Clock\Clock;

$now = Clock::now();
```

## Фиксация времени (тесты)

```php
use PhpSoftBox\Clock\Clock;

Clock::freeze(new \DateTimeImmutable('2026-02-27 00:00:00'));
// ... тесты
Clock::reset();
```

## Путешествие во времени (тесты)

```php
use PhpSoftBox\Clock\Clock;

Clock::freeze(new \DateTimeImmutable('2026-02-27 00:00:00'));
Clock::travel(60); // +60 секунд
Clock::travel('+1 hour'); // или строкой
Clock::reset();
```

## DatePoint

`DatePoint` — value object, реализует `DateTimeInterface` и создаётся от `Clock::now()`:

```php
use PhpSoftBox\Clock\DatePoint;

$createdAt = new DatePoint();
```
