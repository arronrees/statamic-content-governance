<?php

namespace Arronrees\ContentGovernance\Records;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Events\GovernanceRecordCreated;
use Arronrees\ContentGovernance\Events\ReviewRescheduled;
use Arronrees\ContentGovernance\Owners\OwnerResolver;
use Arronrees\ContentGovernance\Rules\CollectionRule;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Arronrees\ContentGovernance\Support\LocalDates;
use Arronrees\ContentGovernance\Support\SiteTimezone;
use Carbon\CarbonImmutable;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;

class EntryRecords
{
    public function __construct(
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private CollectionRules $rules,
        private OwnerResolver $owners,
        private GeneralSettings $settings,
        private SiteTimezone $timezones,
        private Clock $clock,
    ) {}

    public function governs(Entry $entry): bool
    {
        return $this->rules->for($entry->collectionHandle())->enabled;
    }

    public function find(Entry $entry): ?GovernanceRecord
    {
        return $this->records->findByGovernable('entry', $entry->id(), $entry->locale());
    }

    /**
     * Creates the entry's record if it has none, and starts its clocks the first time it's published.
     */
    public function sync(Entry $entry, ?string $actorId): GovernanceRecord
    {
        return $this->syncWith($entry, $this->find($entry), $actorId);
    }

    /**
     * As sync(), for a caller that has already looked the record up.
     */
    public function syncWith(Entry $entry, ?GovernanceRecord $existing, ?string $actorId): GovernanceRecord
    {
        $rule = $this->rules->for($entry->collectionHandle());
        $record = $existing ?? $this->create($entry, $rule, $actorId);

        if ($this->isLive($entry) && $record->nextReviewAt() === null && $record->lastReviewedAt() === null) {
            $this->startClocks($record, $rule, $actorId);
        }

        return $record;
    }

    /**
     * @return array{created: int, removed: int}
     */
    /**
     * Published and not waiting for a future date. A scheduled entry starts its clocks when it goes live.
     */
    public function isLive(Entry $entry): bool
    {
        return $entry->published() && $entry->status() !== 'scheduled';
    }

    public function syncCollection(string $collection): array
    {
        $created = 0;
        $seen = [];

        Entries::query()->where('collection', $collection)->chunk(200, function ($entries) use (&$created, &$seen) {
            foreach ($entries as $entry) {
                $seen[$entry->id()] = true;
                $existing = $this->find($entry);
                $created += $existing ? 0 : 1;
                $this->syncWith($entry, $existing, null);
            }
        });

        // Entries deleted outside Statamic (Git, the filesystem) never fired EntryDeleted.
        $removed = $this->records->query()->where('collection', $collection)->get()
            ->reject(fn (GovernanceRecord $record) => isset($seen[$record->governableId()]))
            ->each(fn (GovernanceRecord $record) => $this->removeOrphan($record))
            ->count();

        return ['created' => $created, 'removed' => $removed];
    }

    public function entryExists(GovernanceRecord $record): bool
    {
        return Entries::find($record->governableId()) instanceof Entry;
    }

    public function removeOrphan(GovernanceRecord $record): void
    {
        $this->log($record, AuditEventType::Deleted, null, ['reason' => 'entry_missing']);
        $this->records->delete($record);
    }

    public function resetReviewAfterEdit(GovernanceRecord $record, ?string $actorId): void
    {
        $rule = $this->rules->for($record->collection());

        if (! $rule->editResetsReview || $record->nextReviewAt() === null) {
            return;
        }

        $from = $record->nextReviewAt();
        $to = $this->daysFromToday($rule->reviewIntervalFor($record), $record->site());

        // Covers the first publish (the clock was just set to this) and repeat edits on the same day.
        if ($to->equalTo($from)) {
            return;
        }

        $record->nextReviewAt($to);
        $this->records->save($record);

        $this->log($record, AuditEventType::Rescheduled, $actorId, [
            'reason' => 'content_edited',
            'from' => $from->toIso8601ZuluString(),
            'to' => $record->nextReviewAt()->toIso8601ZuluString(),
        ]);

        ReviewRescheduled::dispatch(clone $record, $actorId, $from, $record->nextReviewAt());
    }

    public function wasRepublished(GovernanceRecord $record, Entry $entry): bool
    {
        if (! $record->hasFlag(Flag::UnpublishedByGovernance) || ! $entry->published()) {
            return false;
        }

        // Entry dirty state can't be trusted here (the Stache re-syncs originals mid-save), so ask the audit log.
        $last = $this->audit->forRecord($record)
            ->first(fn (AuditEvent $event) => in_array($event->type, [AuditEventType::Unpublished, AuditEventType::Republished], true));

        return $last?->type === AuditEventType::Unpublished;
    }

    /**
     * A manual republish overrides expiry: a fresh expiry from the collection default, never a review.
     * Without a default the flag stays, so the sweep leaves the entry live but it still reads as expired.
     */
    public function republish(GovernanceRecord $record, ?string $actorId): void
    {
        $rule = $this->rules->for($record->collection());

        $this->log($record, AuditEventType::Republished, $actorId);

        if ($rule->defaultExpiryDays === null) {
            return;
        }

        $from = $record->expiresAt();
        $record->expiresAt($this->daysFromToday($rule->defaultExpiryDays, $record->site()))
            ->removeFlag(Flag::UnpublishedByGovernance);
        $this->records->save($record);

        $this->log($record, AuditEventType::DatesChanged, $actorId, [
            'reason' => 'republished',
            'expires_at_from' => $from?->toIso8601ZuluString(),
            'expires_at_to' => $record->expiresAt()->toIso8601ZuluString(),
        ]);
    }

    private function create(Entry $entry, CollectionRule $rule, ?string $actorId): GovernanceRecord
    {
        $record = $this->records->make()
            ->governableType('entry')
            ->governableId($entry->id())
            ->site($entry->locale())
            ->collection($entry->collectionHandle())
            ->originId($entry->root()->id())
            ->ownerId($this->owners->resolve(
                $rule,
                $entry->authors()->filter()->map(fn ($id) => (string) $id)->values()->all(),
                $entry->hasOrigin() ? $this->originOwner($entry) : null,
                $this->settings->fallbackOwnerId(),
            ));

        $this->records->save($record);
        $this->log($record, AuditEventType::Created, $actorId, ['owner_id' => $record->ownerId()]);

        GovernanceRecordCreated::dispatch(clone $record, $actorId);

        return $record;
    }

    private function startClocks(GovernanceRecord $record, CollectionRule $rule, ?string $actorId): void
    {
        $record->nextReviewAt($this->daysFromToday($rule->reviewIntervalFor($record), $record->site()));

        if ($rule->expiryEnabled && $rule->defaultExpiryDays !== null && $record->expiresAt() === null) {
            $record->expiresAt($this->daysFromToday($rule->defaultExpiryDays, $record->site()));
        }

        $this->records->save($record);

        $this->log($record, AuditEventType::DatesChanged, $actorId, [
            'reason' => 'first_published',
            'next_review_at' => $record->nextReviewAt()?->toIso8601ZuluString(),
            'expires_at' => $record->expiresAt()?->toIso8601ZuluString(),
        ]);
    }

    private function originOwner(Entry $entry): ?string
    {
        return $this->find($entry->origin())?->ownerId();
    }

    private function daysFromToday(int $days, string $site): CarbonImmutable
    {
        return LocalDates::daysFrom($this->clock->now(), $days, $this->timezones->for($site));
    }

    private function log(GovernanceRecord $record, AuditEventType $type, ?string $actorId, array $payload = []): void
    {
        $this->audit->append(AuditEvent::for($record, $type, $actorId, $this->clock->now(), $payload));
    }
}
