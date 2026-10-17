<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

class GovernanceExpiryDate extends GovernanceFilter
{
    protected static $handle = 'governance_expiry_date';

    public static function title()
    {
        return __('Expiry date');
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
        $this->whereWithin($query, 'expires_at', $values['range'] ?? []);
    }

    public function badge($values)
    {
        return __('Expiry date').': '.implode(' – ', array_filter([$values['range']['start'] ?? null, $values['range']['end'] ?? null]));
    }
}
