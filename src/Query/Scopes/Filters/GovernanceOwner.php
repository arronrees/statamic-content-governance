<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Arronrees\ContentGovernance\Query\Scopes\GovernanceReviewers;
use Statamic\Auth\User as StatamicUser;
use Statamic\Facades\User;
use Statamic\Support\Arr;

class GovernanceOwner extends GovernanceFilter
{
    protected static $handle = 'governance_owner';

    public static function title()
    {
        return __('Owner');
    }

    public function fieldItems()
    {
        return [
            'owner' => [
                'type' => 'users',
                'max_items' => 1,
                'mode' => 'select',
                'query_scopes' => [GovernanceReviewers::handle()],
            ],
        ];
    }

    public function apply($query, $values)
    {
        $query->where('owner_id', (string) Arr::first(Arr::wrap($values['owner'] ?? null)));
    }

    public function badge($values)
    {
        $user = User::find(Arr::first(Arr::wrap($values['owner'] ?? null)));

        return __('Owner').': '.($user instanceof StatamicUser ? $user->name() : __('Unknown'));
    }
}
