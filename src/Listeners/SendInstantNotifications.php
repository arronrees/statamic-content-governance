<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Audit\AuditEventType;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Events\ContentExpired;
use Arronrees\ContentGovernance\Events\ContentExpiring;
use Arronrees\ContentGovernance\Events\ContentUnpublishedByGovernance;
use Arronrees\ContentGovernance\Events\ReviewDue;
use Arronrees\ContentGovernance\Events\ReviewOverdue;
use Arronrees\ContentGovernance\Notifications\GovernanceAlert;
use Arronrees\ContentGovernance\Notifications\NotificationGate;
use Arronrees\ContentGovernance\Notifications\NotificationSettings;
use Arronrees\ContentGovernance\Notifications\NotificationType;
use Arronrees\ContentGovernance\Notifications\Recipients;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Support\Facades\Notification;

class SendInstantNotifications
{
    public function __construct(
        private NotificationSettings $settings,
        private Recipients $recipients,
        private NotificationGate $gate,
        private AuditLogRepository $audit,
        private Clock $clock,
    ) {}

    public function handleDue(ReviewDue $event): void
    {
        $this->send(NotificationType::Due, $event->record);
    }

    public function handleOverdue(ReviewOverdue $event): void
    {
        $this->send(NotificationType::Overdue, $event->record);
    }

    public function handleExpiring(ContentExpiring $event): void
    {
        $this->send(NotificationType::Expiring, $event->record);
    }

    public function handleExpired(ContentExpired $event): void
    {
        $this->send(NotificationType::Expired, $event->record);
    }

    public function handleUnpublished(ContentUnpublishedByGovernance $event): void
    {
        $this->send(NotificationType::Unpublished, $event->record);
    }

    private function send(NotificationType $type, GovernanceRecord $record): void
    {
        if (! $this->settings->delivers($type, NotificationSettings::INSTANT) || ! $this->gate->allows($type, $record)) {
            return;
        }

        if (! $recipient = $this->recipients->for($record)) {
            return;
        }

        Notification::send($recipient, new GovernanceAlert($type, $record, $this->settings->channels()));

        $this->audit->append(AuditEvent::for($record, AuditEventType::ReminderSent, null, $this->clock->now(), [
            'type' => $type->value,
            'recipient_id' => (string) $recipient->getAuthIdentifier(),
        ]));
    }
}
