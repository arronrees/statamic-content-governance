<?php

namespace Arronrees\ContentGovernance\Status;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Events\ContentExpired;
use Arronrees\ContentGovernance\Events\ContentExpiring;
use Arronrees\ContentGovernance\Events\ReviewDue;
use Arronrees\ContentGovernance\Events\ReviewOverdue;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;

class Transitions
{
    public function __construct(
        private AuditLogRepository $audit,
        private Clock $clock,
    ) {}

    public function after(GovernanceRecord $record, Status $before, StatusResult $result): void
    {
        $snapshot = clone $record;

        if ($result->status !== $before) {
            match ($result->status) {
                Status::Due => ReviewDue::dispatch($snapshot),
                Status::Overdue => ReviewOverdue::dispatch($snapshot),
                Status::Expired => $this->expired($snapshot),
                Status::Current => null,
            };
        }

        if ($result->inExpiryWarning && ! $this->warned($record)) {
            $this->audit->append(AuditEvent::for($record, AuditEventType::ExpiryWarningSent, null, $this->clock->now(), [
                'expires_at' => $record->expiresAt()->toIso8601ZuluString(),
            ]));

            ContentExpiring::dispatch($snapshot);
        }
    }

    private function expired(GovernanceRecord $record): void
    {
        $this->audit->append(AuditEvent::for($record, AuditEventType::Expired, null, $this->clock->now()));

        ContentExpired::dispatch($record);
    }

    private function warned(GovernanceRecord $record): bool
    {
        $expiresAt = $record->expiresAt()->toIso8601ZuluString();

        return $this->audit->forRecord($record)->contains(
            fn (AuditEvent $event) => $event->type === AuditEventType::ExpiryWarningSent && ($event->payload['expires_at'] ?? null) === $expiresAt,
        );
    }
}
