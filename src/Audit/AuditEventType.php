<?php

namespace Arronrees\ContentGovernance\Audit;

enum AuditEventType: string
{
    case Created = 'created';
    case Reviewed = 'reviewed';
    case Rescheduled = 'rescheduled';
    case OwnerChanged = 'owner_changed';
    case DatesChanged = 'dates_changed';
    case ContentEdited = 'content_edited';
    case ReminderSent = 'reminder_sent';
    case ExpiryWarningSent = 'expiry_warning_sent';
    case Expired = 'expired';
    case Unpublished = 'unpublished';
    case Republished = 'republished';
    case Orphaned = 'orphaned';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Created => __('Governance started'),
            self::Reviewed => __('Reviewed'),
            self::Rescheduled => __('Review rescheduled'),
            self::OwnerChanged => __('Owner changed'),
            self::DatesChanged => __('Dates changed'),
            self::ContentEdited => __('Content edited'),
            self::ReminderSent => __('Reminder sent'),
            self::ExpiryWarningSent => __('Expiry warning'),
            self::Expired => __('Expired'),
            self::Unpublished => __('Unpublished'),
            self::Republished => __('Republished'),
            self::Orphaned => __('Orphaned'),
            self::Deleted => __('Entry deleted'),
        };
    }
}
