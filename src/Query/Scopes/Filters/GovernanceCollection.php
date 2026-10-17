<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Statamic\Facades\Collection;

class GovernanceCollection extends GovernanceFilter
{
    protected static $handle = 'governance_collection';

    public static function title()
    {
        return __('Collection');
    }

    public function fieldItems()
    {
        return [
            'collections' => [
                'type' => 'checkboxes',
                'options' => Collection::all()->mapWithKeys(fn ($collection) => [$collection->handle() => $collection->title()])->all(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        $query->whereIn('collection', $values['collections'] ?? []);
    }

    public function badge($values)
    {
        return __('Collection').': '.implode(', ', $values['collections'] ?? []);
    }
}
