<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Query\Scopes\GovernanceReviewers;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Rules\Reviewer;
use Statamic\Support\Arr;

class ReassignOwner extends GovernanceAction
{
    protected static $handle = 'governance_reassign_owner';

    protected $icon = 'users';

    public static function title()
    {
        return __('Reassign owner');
    }

    public function authorize($user, $item)
    {
        return $this->abilities()->manage($user, $item);
    }

    public function run($items, $values)
    {
        $ownerId = Arr::first(Arr::wrap($values['owner']));

        $items->each(fn (GovernanceRecord $record) => $this->changes()->reassign($record, $ownerId, $this->actorId()));

        return trans_choice('Owner reassigned.|:count owners reassigned.', $items);
    }

    protected function fieldItems()
    {
        return [
            'owner' => [
                'type' => 'users',
                'display' => __('New owner'),
                'max_items' => 1,
                'mode' => 'select',
                'query_scopes' => [GovernanceReviewers::handle()],
                'validate' => ['required', 'new '.Reviewer::class],
            ],
        ];
    }
}
