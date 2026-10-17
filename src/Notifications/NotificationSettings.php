<?php

namespace Arronrees\ContentGovernance\Notifications;

class NotificationSettings
{
    public const INSTANT = 'instant';

    public const DIGEST = 'digest';

    public function enabled(): bool
    {
        return (bool) config('statamic.governance.notifications.enabled', true);
    }

    public function channels(): array
    {
        return config('statamic.governance.notifications.channels', ['mail']);
    }

    public function delivers(NotificationType $type, string $mode): bool
    {
        return $this->enabled() && config("statamic.governance.notifications.delivery.{$type->value}") === $mode;
    }

    public function digestTime(): string
    {
        return config('statamic.governance.notifications.digest.time', '08:00');
    }

    /**
     * @return list<int> Days of the week, 0 = Sunday.
     */
    public function digestDays(): array
    {
        $days = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];

        return collect(config('statamic.governance.notifications.digest.days', []))
            ->map(fn (string $day) => $days[strtolower(substr($day, 0, 3))] ?? null)
            ->filter(fn (?int $day) => $day !== null)
            ->values()
            ->all();
    }
}
