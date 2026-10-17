<?php

namespace Arronrees\ContentGovernance\Expiry;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Events\ContentUnpublishedByGovernance;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Support\GovernanceWrites;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;

class UnpublishExpiredEntry
{
    public function __construct(
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private GovernanceWrites $writes,
        private Clock $clock,
    ) {}

    public function __invoke(GovernanceRecord $record): void
    {
        $entry = Entries::find($record->governableId());

        if (! $entry instanceof Entry) {
            return;
        }

        $payload = $entry->published()
            ? $this->unpublish($entry)
            : ['already_unpublished' => true];

        $this->records->save($record->addFlag(Flag::UnpublishedByGovernance));
        $this->audit->append(AuditEvent::for($record, AuditEventType::Unpublished, null, $this->clock->now(), $payload));

        if (! ($payload['already_unpublished'] ?? false)) {
            ContentUnpublishedByGovernance::dispatch(clone $record);
        }
    }

    private function unpublish(Entry $entry): array
    {
        // Unpublish the live entry directly: Entry::unpublish() would promote an unreviewed working copy.
        return $this->writes->during(function () use ($entry) {
            $entry->published(false)->save();

            if (! $entry->revisionsEnabled()) {
                return [];
            }

            $revision = $entry->makeRevision()
                ->user(null)
                ->message(__('Unpublished by Content Governance: expired'))
                ->action('unpublish');
            $revision->save();

            return ['revision_id' => $revision->id()];
        });
    }
}
