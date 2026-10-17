<?php

namespace Arronrees\ContentGovernance\Audit;

use Arronrees\ContentGovernance\Contracts\AuditLogRepository;

/**
 * Statamic saves an entry before writing its revision, so an edit's audit event is held
 * until the matching revision arrives (to carry its id) or the request, command or job ends.
 */
class PendingEdits
{
    /** @var array<string, AuditEvent> */
    private array $pending = [];

    public function __construct(private AuditLogRepository $audit) {}

    public function hold(string $entryId, AuditEvent $event): void
    {
        if (isset($this->pending[$entryId])) {
            $this->audit->append($this->pending[$entryId]);
        }

        $this->pending[$entryId] = $event;
    }

    public function attachRevision(string $entryId, string $revisionId): void
    {
        if (! $event = $this->pending[$entryId] ?? null) {
            return;
        }

        unset($this->pending[$entryId]);

        $this->audit->append(new AuditEvent(
            id: $event->id,
            recordId: $event->recordId,
            governableId: $event->governableId,
            site: $event->site,
            type: $event->type,
            actorId: $event->actorId,
            payload: [...$event->payload, 'revision_id' => $revisionId],
            createdAt: $event->createdAt,
        ));
    }

    public function flush(): void
    {
        foreach ($this->pending as $event) {
            $this->audit->append($event);
        }

        $this->pending = [];
    }
}
