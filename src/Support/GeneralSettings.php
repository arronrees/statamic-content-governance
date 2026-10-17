<?php

namespace Arronrees\ContentGovernance\Support;

use Arronrees\ContentGovernance\Settings\AddonSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Statamic\Support\Arr;

class GeneralSettings
{
    public function __construct(private AddonSettings $settings) {}

    public function fallbackOwnerId(): ?string
    {
        return Arr::first(Arr::wrap($this->get('fallback_owner')));
    }

    public function defaultDueWindowDays(): int
    {
        return (int) ($this->get('default_due_window_days') ?? 14);
    }

    public function applyMailFrom(MailMessage $mail): void
    {
        if ($address = $this->get('mail_from_address')) {
            $mail->from($address, $this->get('mail_from_name'));
        }
    }

    private function get(string $key): mixed
    {
        return $this->settings->get($key);
    }
}
