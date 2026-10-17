<?php

namespace Arronrees\ContentGovernance\Commands;

use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Notifications\DigestItem;
use Arronrees\ContentGovernance\Notifications\GovernanceDigest;
use Arronrees\ContentGovernance\Notifications\NotificationGate;
use Arronrees\ContentGovernance\Notifications\NotificationSettings;
use Arronrees\ContentGovernance\Notifications\NotificationType;
use Arronrees\ContentGovernance\Notifications\Recipients;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;
use Arronrees\ContentGovernance\Status\RecordStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Statamic\Console\RunsInPlease;

class Digest extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:governance:digest';

    protected $description = 'Email each owner a summary of their governed content that needs attention';

    public function handle(GovernanceRecordRepository $records, RecordStatus $status, Recipients $recipients, NotificationSettings $settings, NotificationGate $gate): int
    {
        $types = collect(NotificationType::cases())->filter(fn ($type) => $settings->delivers($type, NotificationSettings::DIGEST));

        if ($types->isEmpty()) {
            $this->components->info('Nothing is set to be sent in the digest.');

            return self::SUCCESS;
        }

        $digests = [];

        foreach ($this->itemsNeedingAttention($records, $status, $types) as [$type, $record]) {
            if (! $gate->allows($type, $record) || ! $recipient = $recipients->for($record)) {
                continue;
            }

            $id = (string) $recipient->getAuthIdentifier();
            $digests[$id]['recipient'] = $recipient;
            $digests[$id]['items'][] = DigestItem::for($type, $record);
        }

        foreach ($digests as $digest) {
            Notification::send($digest['recipient'], new GovernanceDigest($digest['items'], $settings->channels()));
        }

        $sent = count($digests);

        $this->components->info("Sent {$sent} governance digests.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{0: NotificationType, 1: GovernanceRecord}>
     */
    private function itemsNeedingAttention(GovernanceRecordRepository $records, RecordStatus $status, Collection $types): Collection
    {
        return $this->candidates($records, $types)->map(function (GovernanceRecord $record) use ($status, $types) {
            $type = match (true) {
                $record->hasFlag(Flag::UnpublishedByGovernance) => NotificationType::Unpublished,
                $record->status() === Status::Expired => NotificationType::Expired,
                $status->evaluate($record)->inExpiryWarning => NotificationType::Expiring,
                $record->status() === Status::Overdue => NotificationType::Overdue,
                $record->status() === Status::Due => NotificationType::Due,
                default => null,
            };

            return $type && $types->contains($type) ? [$type, $record] : null;
        })->filter()->values();
    }

    /**
     * Only the records that could be in a digest of these types, rather than every record.
     *
     * @return Collection<int, GovernanceRecord>
     */
    private function candidates(GovernanceRecordRepository $records, Collection $types): Collection
    {
        $statuses = $types->map(fn (NotificationType $type) => match ($type) {
            NotificationType::Due => Status::Due,
            NotificationType::Overdue => Status::Overdue,
            NotificationType::Expired => Status::Expired,
            default => null,
        })->filter()->values()->all();

        $queries = array_filter([
            $statuses ? fn () => $records->query()->whereIn('status', $statuses)->get() : null,
            $types->contains(NotificationType::Unpublished)
                ? fn () => $records->query()->whereJsonContains('flags', Flag::UnpublishedByGovernance->value)->get()
                : null,
            $types->contains(NotificationType::Expiring)
                ? fn () => $records->query()->whereNotNull('expires_at')->where('status', '!=', Status::Expired)->get()
                : null,
        ]);

        return collect($queries)
            ->flatMap(fn (callable $query) => $query())
            ->unique(fn (GovernanceRecord $record) => $record->id())
            ->values();
    }
}
