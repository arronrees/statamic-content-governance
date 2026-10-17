<?php

namespace Arronrees\ContentGovernance\Records;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Events\ContentReviewed;
use Arronrees\ContentGovernance\Events\OwnerChanged;
use Arronrees\ContentGovernance\Events\ReviewRescheduled;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Arronrees\ContentGovernance\Support\LocalDates;
use Arronrees\ContentGovernance\Support\SiteTimezone;
use Carbon\CarbonImmutable;

class RecordChanges
{
    public function __construct(
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private CollectionRules $rules,
        private SiteTimezone $timezones,
        private Clock $clock,
    ) {}

    public function review(GovernanceRecord $record, ReviewOutcome $outcome, ?string $note, ?string $actorId): void
    {
        $now = $this->clock->now();

        $record->lastReviewedAt($now)
            ->lastReviewedBy($actorId)
            ->nextReviewAt($this->intervalFrom($now, $record));

        if ($outcome === ReviewOutcome::NeedsChanges) {
            $record->addFlag(Flag::NeedsChanges);
        } else {
            $record->removeFlag(Flag::NeedsChanges);
        }

        $this->records->save($record);
        $this->log($record, AuditEventType::Reviewed, $actorId, ['outcome' => $outcome->value, 'note' => $note]);

        ContentReviewed::dispatch(clone $record, $actorId, $outcome->value, $note);
    }

    public function reschedule(GovernanceRecord $record, ?string $date, bool $byOneInterval, ?string $actorId): void
    {
        $from = $record->nextReviewAt();

        $to = $byOneInterval
            ? $this->intervalFrom($from ?? $this->clock->now(), $record)
            : LocalDates::endOfDay($date, $this->timezone($record));

        $this->records->save($record->nextReviewAt($to));
        $this->log($record, AuditEventType::Rescheduled, $actorId, [
            'from' => $from?->toIso8601ZuluString(),
            'to' => $to->toIso8601ZuluString(),
        ]);

        ReviewRescheduled::dispatch(clone $record, $actorId, $from, $to);
    }

    public function reassign(GovernanceRecord $record, ?string $ownerId, ?string $actorId): void
    {
        $from = $record->ownerId();

        if ($from === $ownerId) {
            return;
        }

        $record->ownerId($ownerId);

        if ($ownerId !== null) {
            $record->removeFlag(Flag::Orphaned);
        }

        $this->records->save($record);
        $this->log($record, AuditEventType::OwnerChanged, $actorId, ['from' => $from, 'to' => $ownerId]);

        OwnerChanged::dispatch(clone $record, $actorId, $from, $ownerId);
    }

    public function setExpiry(GovernanceRecord $record, ?string $date, ?string $actorId): void
    {
        $from = $record->expiresAt();
        $to = $date === null ? null : LocalDates::endOfDay($date, $this->timezone($record));

        $this->records->save($record->expiresAt($to));
        $this->log($record, AuditEventType::DatesChanged, $actorId, [
            'expires_at_from' => $from?->toIso8601ZuluString(),
            'expires_at_to' => $to?->toIso8601ZuluString(),
        ]);
    }

    private function intervalFrom(CarbonImmutable $from, GovernanceRecord $record): CarbonImmutable
    {
        return LocalDates::daysFrom($from, $this->rules->for($record->collection())->reviewIntervalFor($record), $this->timezone($record));
    }

    private function timezone(GovernanceRecord $record): string
    {
        return $this->timezones->for($record->site());
    }

    private function log(GovernanceRecord $record, AuditEventType $type, ?string $actorId, array $payload): void
    {
        $this->audit->append(AuditEvent::for($record, $type, $actorId, $this->clock->now(), $payload));
    }
}
