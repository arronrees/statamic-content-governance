<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\ReviewOutcome;

class MarkReviewed extends GovernanceAction
{
    protected static $handle = 'governance_mark_reviewed';

    protected $icon = 'checkmark';

    public static function title()
    {
        return __('Mark reviewed');
    }

    public function authorize($user, $item)
    {
        return $this->abilities()->review($user, $item);
    }

    public function run($items, $values)
    {
        $outcome = ReviewOutcome::from($values['outcome']);

        $items->each(fn (GovernanceRecord $record) => $this->changes()->review($record, $outcome, $values['note'] ?? null, $this->actorId()));

        return trans_choice('Review recorded.|:count reviews recorded.', $items);
    }

    protected function fieldItems()
    {
        return ReviewFields::make();
    }
}
