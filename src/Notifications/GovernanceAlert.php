<?php

namespace Arronrees\ContentGovernance\Notifications;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GovernanceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NotificationType $type,
        public GovernanceRecord $record,
        public array $channels = ['mail'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item = DigestItem::for($this->type, $this->record);

        $mail = (new MailMessage)
            ->subject($this->type->subject().': '.$item->title)
            ->line("\"{$item->title}\" ({$item->site}) {$this->type->line()}")
            ->action(__('Open entry'), $item->url);

        app(GeneralSettings::class)->applyMailFrom($mail);

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'record_id' => $this->record->id(),
        ];
    }
}
