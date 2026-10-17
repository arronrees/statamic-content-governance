<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\ReviewOutcome;
use Statamic\Facades\User;

class ReviewAllLocalisations extends GovernanceAction
{
    protected static $handle = 'governance_review_all_localisations';

    protected $icon = 'globe-world-wide-web';

    public static function title()
    {
        return __('Review all localisations');
    }

    public function authorize($user, $item)
    {
        return $this->abilities()->review($user, $item);
    }

    public function run($items, $values)
    {
        $user = User::current();
        $outcome = ReviewOutcome::from($values['outcome']);

        $records = $items
            ->flatMap(fn (GovernanceRecord $record) => app(GovernanceRecordRepository::class)->query()->where('origin_id', $record->originId())->get())
            ->unique(fn (GovernanceRecord $record) => $record->id())
            ->filter(fn (GovernanceRecord $record) => $this->abilities()->review($user, $record));

        $records->each(fn (GovernanceRecord $record) => $this->changes()->review($record, $outcome, $values['note'] ?? null, $this->actorId()));

        return trans_choice('Review recorded.|:count reviews recorded.', $records);
    }

    protected function fieldItems()
    {
        return ReviewFields::make();
    }
}
