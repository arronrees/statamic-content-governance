<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

class GovernanceReviewDate extends GovernanceFilter
{
    protected static $handle = 'governance_review_date';

    public static function title()
    {
        return __('Review date');
    }

    public function fieldItems()
    {
        return [
            'range' => [
                'type' => 'date',
                'mode' => 'range',
            ],
        ];
    }

    public function apply($query, $values)
    {
        $this->whereWithin($query, 'next_review_at', $values['range'] ?? []);
    }

    public function badge($values)
    {
        return __('Review date').': '.implode(' – ', array_filter([$values['range']['start'] ?? null, $values['range']['end'] ?? null]));
    }
}
