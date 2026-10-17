<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Statamic\Facades\User;

class GovernanceAssignedToMe extends GovernanceFilter
{
    protected static $handle = 'governance_assigned_to_me';

    public $pinned = true;

    public static function title()
    {
        return __('Assigned to me');
    }

    public function fieldItems()
    {
        return [
            'mine' => [
                'type' => 'toggle',
                'inline_label' => __('Only content I own'),
            ],
        ];
    }

    public function apply($query, $values)
    {
        if ($values['mine'] ?? false) {
            $query->where('owner_id', (string) User::current()?->getAuthIdentifier());
        }
    }

    public function badge($values)
    {
        return ($values['mine'] ?? false) ? __('Assigned to me') : null;
    }
}
