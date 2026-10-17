<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Records\GovernanceRecord;

class Reschedule extends GovernanceAction
{
    protected static $handle = 'governance_reschedule';

    protected $icon = 'calendar';

    public static function title()
    {
        return __('Reschedule review');
    }

    public function authorize($user, $item)
    {
        return $this->abilities()->manage($user, $item);
    }

    public function run($items, $values)
    {
        $byOneInterval = $values['mode'] === 'interval';

        $items->each(fn (GovernanceRecord $record) => $this->changes()->reschedule($record, $values['date'] ?? null, $byOneInterval, $this->actorId()));

        return trans_choice('Review rescheduled.|:count reviews rescheduled.', $items);
    }

    protected function fieldItems()
    {
        return [
            'mode' => [
                'type' => 'button_group',
                'display' => __('Move the review'),
                'options' => [
                    'interval' => __('One interval later'),
                    'date' => __('To a date'),
                ],
                'default' => 'interval',
                'validate' => 'required',
            ],
            'date' => [
                'type' => 'date',
                'display' => __('Review date'),
                'if' => ['mode' => 'date'],
                'validate' => 'required_if:mode,date',
            ],
        ];
    }
}
