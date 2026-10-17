<?php

namespace Arronrees\ContentGovernance\Status;

use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;
use Arronrees\ContentGovernance\Rules\CollectionRule;
use Arronrees\ContentGovernance\Support\LocalDates;
use Carbon\CarbonImmutable;

class StatusCalculator
{
    public function __construct(private Clock $clock) {}

    public function calculate(GovernanceRecord $record, CollectionRule $rule, string $timezone): StatusResult
    {
        // An ungoverned collection keeps its records and history, but nothing moves or happens automatically.
        if (! $rule->enabled) {
            return new StatusResult($record->status(), null);
        }

        $now = $this->clock->now();

        $reviewAt = $record->nextReviewAt();
        $dueFrom = $reviewAt ? LocalDates::addDays($reviewAt, -$rule->dueWindowDays, $timezone) : null;

        $expiresAt = $rule->expiryEnabled ? $record->expiresAt() : null;
        $warnFrom = $expiresAt ? LocalDates::addDays($expiresAt, -$rule->expiryWarningDays, $timezone) : null;
        $unpublishAt = $expiresAt && $rule->autoUnpublish && ! $record->hasFlag(Flag::UnpublishedByGovernance)
            ? LocalDates::addDays($expiresAt, $rule->graceDays, $timezone)
            : null;

        $status = match (true) {
            $expiresAt && $now->greaterThan($expiresAt) => Status::Expired,
            $reviewAt && $now->greaterThan($reviewAt) => Status::Overdue,
            $dueFrom && $now->greaterThan($dueFrom) => Status::Due,
            default => Status::Current,
        };

        $upcoming = array_filter(
            [$dueFrom, $reviewAt, $warnFrom, $expiresAt],
            fn (?CarbonImmutable $at) => $at !== null && $at->greaterThanOrEqualTo($now),
        );

        if ($unpublishAt) {
            $upcoming[] = $unpublishAt;
        }

        return new StatusResult(
            $status,
            $upcoming ? min($upcoming) : null,
            shouldUnpublish: $unpublishAt !== null && $now->greaterThan($unpublishAt),
            inExpiryWarning: $warnFrom !== null && $now->greaterThan($warnFrom) && $now->lessThanOrEqualTo($expiresAt),
        );
    }
}
