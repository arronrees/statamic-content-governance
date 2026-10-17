<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Statamic\Facades\Site;

class GovernanceSite extends GovernanceFilter
{
    protected static $handle = 'governance_site';

    public static function title()
    {
        return __('Site');
    }

    public function visibleTo($key)
    {
        return parent::visibleTo($key) && Site::multiEnabled();
    }

    public function fieldItems()
    {
        return [
            'sites' => [
                'type' => 'checkboxes',
                'options' => Site::all()->mapWithKeys(fn ($site) => [$site->handle() => $site->name()])->all(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        $query->whereIn('site', $values['sites'] ?? []);
    }

    public function badge($values)
    {
        return __('Site').': '.implode(', ', $values['sites'] ?? []);
    }
}
