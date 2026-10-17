<?php

namespace Arronrees\ContentGovernance\Notifications;

use Arronrees\ContentGovernance\Support\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GovernanceDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<DigestItem>  $items
     */
    public function __construct(
        public array $items,
        public array $channels = ['mail'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject(trans_choice('{1} 1 governed item needs attention|[2,*] :count governed items need attention', count($this->items)));

        foreach (NotificationType::cases() as $type) {
            $items = array_filter($this->items, fn (DigestItem $item) => $item->type === $type);

            if ($items === []) {
                continue;
            }

            $mail->line("**{$type->subject()}**");

            foreach ($items as $item) {
                $mail->line("- [{$item->title}]({$item->url}) ({$item->site})");
            }
        }

        app(GeneralSettings::class)->applyMailFrom($mail);

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return ['record_ids' => array_map(fn (DigestItem $item) => $item->recordId, $this->items)];
    }
}
