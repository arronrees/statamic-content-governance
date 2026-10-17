<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\ReviewOutcome;
use Statamic\Entries\Entry;

class MarkEntryReviewed extends GovernanceAction
{
    protected static $handle = 'governance_mark_entry_reviewed';

    protected $icon = 'clipboard-check';

    /** @var array<string, GovernanceRecord|null> */
    private array $found = [];

    public static function title()
    {
        return __('Mark reviewed');
    }

    public function visibleTo($item)
    {
        return $item instanceof Entry && $this->recordFor($item) !== null;
    }

    public function authorize($user, $item)
    {
        $record = $this->recordFor($item);

        return $record !== null && $this->abilities()->review($user, $record);
    }

    public function run($items, $values)
    {
        $outcome = ReviewOutcome::from($values['outcome']);

        $records = $items->map(fn (Entry $entry) => $this->recordFor($entry))->filter();

        $records->each(fn (GovernanceRecord $record) => $this->changes()->review($record, $outcome, $values['note'] ?? null, $this->actorId()));

        return trans_choice('Review recorded.|:count reviews recorded.', $records);
    }

    protected function fieldItems()
    {
        return ReviewFields::make();
    }

    private function recordFor(mixed $entry): ?GovernanceRecord
    {
        if (! $entry instanceof Entry) {
            return null;
        }

        // Statamic asks visibleTo() and then authorize() for every selected entry.
        return $this->found[$entry->id()] ??= app(GovernanceRecordRepository::class)->findByGovernable('entry', $entry->id(), $entry->locale());
    }
}
