<?php

namespace Arronrees\ContentGovernance\Notifications;

enum NotificationType: string
{
    case Due = 'due';
    case Overdue = 'overdue';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case Unpublished = 'unpublished';

    public function subject(): string
    {
        return match ($this) {
            self::Due => __('Content due for review'),
            self::Overdue => __('Content overdue for review'),
            self::Expiring => __('Content expiring soon'),
            self::Expired => __('Content has expired'),
            self::Unpublished => __('Expired content has been unpublished'),
        };
    }

    public function line(): string
    {
        return match ($this) {
            self::Due => __('is due for review.'),
            self::Overdue => __('is overdue for review.'),
            self::Expiring => __('expires soon.'),
            self::Expired => __('has expired.'),
            self::Unpublished => __('has expired and was unpublished.'),
        };
    }
}
