<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Arronrees\ContentGovernance\Records\Status;

class GovernanceStatus extends GovernanceFilter
{
    protected static $handle = 'governance_status';

    public $pinned = true;

    public static function title()
    {
        return __('Status');
    }

    public function fieldItems()
    {
        return [
            'statuses' => [
                'type' => 'checkboxes',
                'options' => collect(Status::cases())->mapWithKeys(fn (Status $status) => [$status->value => $status->label()])->all(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        $query->whereIn('status', $values['statuses'] ?? []);
    }

    public function badge($values)
    {
        return __('Status').': '.collect($values['statuses'] ?? [])->map(fn ($status) => Status::from($status)->label())->join(', ');
    }
}
