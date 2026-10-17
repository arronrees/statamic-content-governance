<?php

namespace Arronrees\ContentGovernance\Review;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\ReviewOutcome;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Statamic\Auth\User as StatamicUser;
use Statamic\Entries\Entry;
use Statamic\Facades\Action;
use Statamic\Facades\Entry as Entries;
use Statamic\Facades\User;

class RecordDetail
{
    public function __construct(
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private CollectionRules $rules,
    ) {}

    public function props(GovernanceRecord $record): array
    {
        $entry = Entries::find($record->governableId());
        $entry = $entry instanceof Entry ? $entry : null;

        return [
            'record' => [
                ...ReviewRow::for($record, false),
                'last_reviewed_at' => $record->lastReviewedAt()?->toIso8601ZuluString(),
                'last_reviewed_by' => $this->userName($record->lastReviewedBy()),
            ],
            'entry' => [
                'exists' => $entry !== null,
                'title' => $entry?->value('title'),
                'edit_url' => $entry?->editUrl(),
                'published' => (bool) $entry?->published(),
                'has_unpublished_changes' => $entry !== null && $entry->revisionsEnabled() && $entry->hasWorkingCopy(),
            ],
            'rule' => $this->rule($record),
            'localisations' => $this->records->query()->where('origin_id', $record->originId())->get()
                ->reject(fn (GovernanceRecord $sibling) => $sibling->id() === $record->id())
                ->map(fn (GovernanceRecord $sibling) => ReviewRow::for($sibling, true))
                ->values()->all(),
            'timeline' => $this->audit->forRecord($record)->map(fn (AuditEvent $event) => $this->timelineItem($event))->all(),
            'actions' => Action::for($record, ['view' => 'detail']),
            'actionUrl' => cp_route('governance.records.actions.run'),
            'reviewUrl' => cp_route('governance.records.index'),
        ];
    }

    private function rule(GovernanceRecord $record): array
    {
        $rule = $this->rules->for($record->collection());

        return [
            'review_interval_days' => $rule->reviewIntervalFor($record),
            'review_interval_overridden' => $record->reviewIntervalDays() !== null,
            'due_window_days' => $rule->dueWindowDays,
            'expiry_enabled' => $rule->expiryEnabled,
            'default_expiry_days' => $rule->defaultExpiryDays,
            'expiry_warning_days' => $rule->expiryWarningDays,
            'edit_resets_review' => $rule->editResetsReview,
        ];
    }

    private function timelineItem(AuditEvent $event): array
    {
        $outcome = ReviewOutcome::tryFrom($event->payload['outcome'] ?? '');

        return [
            'id' => $event->id,
            'type' => $event->type->value,
            'label' => $event->type->label(),
            'actor' => $this->userName($event->actorId) ?? __('System'),
            'at' => $event->createdAt->toIso8601ZuluString(),
            'outcome' => $outcome?->label(),
            'note' => $event->payload['note'] ?? null,
            'revision_id' => $event->payload['revision_id'] ?? null,
        ];
    }

    private function userName(?string $userId): ?string
    {
        $user = $userId ? User::find($userId) : null;

        return $user instanceof StatamicUser ? $user->name() : null;
    }
}
