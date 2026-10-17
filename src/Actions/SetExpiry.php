<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Rules\CollectionRules;

class SetExpiry extends GovernanceAction
{
    protected static $handle = 'governance_set_expiry';

    protected $icon = 'time-clock';

    public static function title()
    {
        return __('Set or clear expiry');
    }

    // An expiry on a collection with expiry off is stored but ignored, so don't offer to set one.
    public function visibleTo($item)
    {
        return parent::visibleTo($item) && app(CollectionRules::class)->for($item->collection())->expiryEnabled;
    }

    public function authorize($user, $item)
    {
        return $this->visibleTo($item) && $this->abilities()->manage($user, $item);
    }

    public function run($items, $values)
    {
        $items->each(fn (GovernanceRecord $record) => $this->changes()->setExpiry($record, $values['expires_at'] ?? null, $this->actorId()));

        return trans_choice('Expiry updated.|:count expiries updated.', $items);
    }

    protected function fieldItems()
    {
        return [
            'expires_at' => [
                'type' => 'date',
                'display' => __('Expiry date'),
                'instructions' => __('Leave empty to remove the expiry.'),
            ],
        ];
    }
}
