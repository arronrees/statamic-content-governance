<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Audit\PendingEdits;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Records\EntryRecords;
use Arronrees\ContentGovernance\Support\GovernanceWrites;
use Statamic\Events\EntrySaved;
use Statamic\Facades\User;

class RecordEntrySaved
{
    public function __construct(
        private EntryRecords $records,
        private PendingEdits $edits,
        private GovernanceWrites $writes,
        private Clock $clock,
    ) {}

    // Runs inside Statamic's own save: a governance fault is reported, never allowed to block the editor.
    public function handle(EntrySaved $event): void
    {
        rescue(fn () => $this->apply($event));
    }

    private function apply(EntrySaved $event): void
    {
        $entry = $event->entry;

        if ($this->writes->active() || ! $this->records->governs($entry)) {
            return;
        }

        $actorId = User::current()?->getAuthIdentifier();
        $existing = $this->records->find($entry);
        $record = $this->records->syncWith($entry, $existing, $actorId);

        // Saving an origin re-saves its localisations; only the save someone made counts as an edit.
        if (! $existing || ! $event->isInitial()) {
            return;
        }

        if ($this->records->wasRepublished($record, $entry)) {
            $this->records->republish($record, $actorId);
        }

        $this->edits->hold($entry->id(), AuditEvent::for($record, AuditEventType::ContentEdited, $actorId, $this->clock->now()));
        $this->records->resetReviewAfterEdit($record, $actorId);
    }
}
