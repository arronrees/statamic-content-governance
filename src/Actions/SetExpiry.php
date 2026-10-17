<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Records\GovernanceRecord;

class SetExpiry extends GovernanceAction
{
    protected static $handle = 'governance_set_expiry';

    protected $icon = 'time-clock';

    public static function title()
    {
        return __('Set or clear expiry');
    }

    public function authorize($user, $item)
    {
        return $this->abilities()->manage($user, $item);
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
