<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\EntryRecords;
use Statamic\Events\EntryDeleted;
use Statamic\Facades\User;

class RecordEntryDeleted
{
    public function __construct(
        private EntryRecords $entries,
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private Clock $clock,
    ) {}

    // Runs inside Statamic's own save: a governance fault is reported, never allowed to block the editor.
    public function handle(EntryDeleted $event): void
    {
        rescue(fn () => $this->apply($event));
    }

    private function apply(EntryDeleted $event): void
    {
        if (! $record = $this->entries->find($event->entry)) {
            return;
        }

        $this->audit->append(AuditEvent::for($record, AuditEventType::Deleted, User::current()?->getAuthIdentifier(), $this->clock->now()));
        $this->records->delete($record);
    }
}
