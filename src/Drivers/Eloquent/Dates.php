<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class Dates
{
    public const FORMAT = 'Y-m-d H:i:s';

    public const PRECISE_FORMAT = 'Y-m-d H:i:s.u';

    public static function toDatabase(?DateTimeInterface $date, string $format = self::FORMAT): ?string
    {
        return $date === null ? null : CarbonImmutable::instance($date)->utc()->format($format);
    }

    public static function fromDatabase(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value, 'UTC');
    }
}
