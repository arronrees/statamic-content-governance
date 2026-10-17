<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Arronrees\ContentGovernance\Support\LocalDates;
use Carbon\CarbonImmutable;
use Statamic\Query\Scopes\Filter;
use Statamic\Statamic;

abstract class GovernanceFilter extends Filter
{
    public const LISTING = 'governance';

    public function visibleTo($key)
    {
        return $key === self::LISTING;
    }

    protected function whereWithin($query, string $column, array $range): void
    {
        $timezone = Statamic::displayTimezone();

        // A date that won't parse is ignored rather than failing the whole listing.
        $parse = fn ($value) => is_string($value) ? rescue(fn () => CarbonImmutable::parse($value, $timezone), report: false) : null;

        if ($start = $parse($range['start'] ?? null)) {
            $query->where($column, '>=', $start->startOfDay()->utc());
        }

        if ($end = $parse($range['end'] ?? null)) {
            $query->where($column, '<=', LocalDates::endOfDay($end->toDateString(), $timezone));
        }
    }
}
