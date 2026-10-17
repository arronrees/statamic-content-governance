<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Records\EntryRecords;
use Statamic\Entries\Entry;
use Statamic\Events\EntryScheduleReached;

class StartClocksWhenScheduleReached
{
    public function __construct(private EntryRecords $records) {}

    // Fired by Statamic's own per-minute schedule job when a future-dated entry goes live.
    public function handle(EntryScheduleReached $event): void
    {
        rescue(fn () => $this->apply($event));
    }

    private function apply(EntryScheduleReached $event): void
    {
        if ($event->entry instanceof Entry && $this->records->governs($event->entry)) {
            $this->records->sync($event->entry, null);
        }
    }
}
