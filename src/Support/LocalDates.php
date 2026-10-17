<?php

namespace Arronrees\ContentGovernance\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class LocalDates
{
    public static function endOfDay(string|DateTimeInterface $date, string $timezone): CarbonImmutable
    {
        $local = is_string($date)
            ? CarbonImmutable::parse($date, $timezone)
            : CarbonImmutable::instance($date)->setTimezone($timezone);

        return $local->endOfDay()->startOfSecond()->utc();
    }

    public static function daysFrom(CarbonImmutable $now, int $days, string $timezone): CarbonImmutable
    {
        return self::endOfDay($now->setTimezone($timezone)->addDays($days)->toDateString(), $timezone);
    }

    public static function addDays(CarbonImmutable $instant, int $days, string $timezone): CarbonImmutable
    {
        return $instant->setTimezone($timezone)->addDays($days)->utc();
    }
}
