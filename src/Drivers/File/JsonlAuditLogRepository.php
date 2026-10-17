<?php

namespace Arronrees\ContentGovernance\Drivers\File;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class JsonlAuditLogRepository implements AuditLogRepository
{
    public function __construct(private string $path) {}

    public function append(AuditEvent $event): void
    {
        $line = json_encode($event->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        $this->appendLine($this->entryFile($event->governableId), $line);
        $this->appendLine($this->activityFile($event->createdAt->format('Y-m')), $line);
    }

    public function forRecord(GovernanceRecord $record): Collection
    {
        return $this->newestFirst($this->read($this->entryFile($record->governableId())))
            ->filter(fn (AuditEvent $event) => $event->recordId === $record->id())
            ->values();
    }

    public function recent(int $limit, ?string $site = null): Collection
    {
        $months = glob($this->path.'/activity/activity-*.jsonl') ?: [];
        rsort($months);

        $events = [];

        // Lines are appended in time order, so read each month from the end and stop once there are enough.
        foreach ($months as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $event = AuditEvent::fromArray(json_decode($lines[$i], true, flags: JSON_THROW_ON_ERROR));

                if ($site === null || $event->site === $site) {
                    $events[] = $event;
                }

                if (count($events) >= $limit) {
                    return collect($events);
                }
            }
        }

        return collect($events);
    }

    public function all(): LazyCollection
    {
        return LazyCollection::make(function () {
            foreach (glob($this->path.'/entries/*.jsonl') ?: [] as $file) {
                yield from $this->read($file);
            }
        });
    }

    /**
     * @return Collection<int, AuditEvent>
     */
    private function read(string $file): Collection
    {
        if (! is_file($file)) {
            return collect();
        }

        return collect(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(fn (string $line) => AuditEvent::fromArray(json_decode($line, true, flags: JSON_THROW_ON_ERROR)))
            ->values();
    }

    /**
     * Stable sort, so events sharing a timestamp stay in reverse append order.
     *
     * @param  Collection<int, AuditEvent>  $events
     * @return Collection<int, AuditEvent>
     */
    private function newestFirst(Collection $events): Collection
    {
        return $events->reverse()->sortByDesc(fn (AuditEvent $event) => $event->createdAt->format('Y-m-d H:i:s.u'))->values();
    }

    private function appendLine(string $file, string $line): void
    {
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private function entryFile(string $governableId): string
    {
        return $this->path.'/entries/'.basename($governableId).'.jsonl';
    }

    private function activityFile(string $month): string
    {
        return $this->path.'/activity/activity-'.$month.'.jsonl';
    }
}
