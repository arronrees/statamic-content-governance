<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Events\OwnerChanged;
use Arronrees\ContentGovernance\Events\RecordOrphaned;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Statamic\Events\UserDeleted;
use Statamic\Facades\User;

class OrphanDeletedUsersRecords
{
    public function __construct(
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private GeneralSettings $settings,
        private Clock $clock,
    ) {}

    // Runs inside Statamic's own save: a governance fault is reported, never allowed to block the editor.
    public function handle(UserDeleted $event): void
    {
        rescue(fn () => $this->apply($event));
    }

    private function apply(UserDeleted $event): void
    {
        $deletedId = (string) $event->user->getAuthIdentifier();
        $fallbackId = $this->settings->fallbackOwnerId();
        $newOwnerId = $fallbackId !== $deletedId ? $fallbackId : null;
        $actorId = User::current()?->getAuthIdentifier();

        $this->records->query()->where('owner_id', $deletedId)->get()->each(function (GovernanceRecord $record) use ($deletedId, $newOwnerId, $actorId) {
            $this->records->save($record->ownerId($newOwnerId)->addFlag(Flag::Orphaned));

            $now = $this->clock->now();
            $this->audit->append(AuditEvent::for($record, AuditEventType::OwnerChanged, $actorId, $now, [
                'from' => $deletedId,
                'to' => $newOwnerId,
                'reason' => 'user_deleted',
            ]));
            $this->audit->append(AuditEvent::for($record, AuditEventType::Orphaned, $actorId, $now));

            OwnerChanged::dispatch(clone $record, $actorId, $deletedId, $newOwnerId);
            RecordOrphaned::dispatch(clone $record, $deletedId);
        });
    }
}
