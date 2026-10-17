<?php

namespace Arronrees\ContentGovernance\Dashboard;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\RecordCounts;
use Arronrees\ContentGovernance\Records\Status;
use Arronrees\ContentGovernance\Review\ReviewRow;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Arronrees\ContentGovernance\Support\SweepHeartbeat;
use Statamic\Auth\User as StatamicUser;
use Statamic\Entries\Entry;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry as Entries;
use Statamic\Facades\User;

class Dashboard
{
    public const STALE_AFTER_HOURS = 2;

    public function __construct(
        private GovernanceRecordRepository $records,
        private AuditLogRepository $audit,
        private RecordCounts $counts,
        private SweepHeartbeat $heartbeat,
        private Clock $clock,
        private CollectionRules $rules,
    ) {}

    /**
     * @param  list<string>|null  $collections  Limit to these collections; null for all.
     */
    public function summary(?string $site, ?array $collections = null): array
    {
        $key = 'summary.'.($site ?? 'all').'.'.($collections === null ? 'all' : md5(implode(',', $collections)));

        return $this->counts->remember($key, function () use ($site, $collections) {
            $byCollection = $this->records->statusCounts($site);

            if ($collections !== null) {
                $byCollection = array_intersect_key($byCollection, array_flip($collections));
            }

            $totals = collect(Status::cases())->mapWithKeys(fn (Status $status) => [
                $status->value => collect($byCollection)->sum(fn (array $counts) => $counts[$status->value] ?? 0),
            ])->all();

            return [
                'totals' => [...$totals, 'orphaned' => $this->records->flaggedCount(Flag::Orphaned, $site, $collections)],
                'collections' => collect($byCollection)->map(fn (array $counts, string $handle) => [
                    'handle' => $handle,
                    'title' => Collection::find($handle)?->title() ?? $handle,
                    'counts' => collect(Status::cases())->mapWithKeys(fn (Status $status) => [$status->value => $counts[$status->value] ?? 0])->all(),
                ])->sortBy('title')->values()->all(),
            ];
        });
    }

    public function expiringSoon(?string $site, ?array $collections = null, int $days = 30, int $limit = 10): array
    {
        $now = $this->clock->now();
        $withExpiry = Collection::handles()
            ->filter(fn (string $handle) => $this->rules->for($handle)->expiryEnabled)
            ->when($collections !== null, fn ($handles) => $handles->intersect($collections))
            ->values()
            ->all();

        return $this->records->query()
            ->whereIn('collection', $withExpiry)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', $now)
            ->where('expires_at', '<=', $now->addDays($days))
            ->when($site, fn ($query) => $query->where('site', $site))
            ->orderBy('expires_at')
            ->limit($limit)
            ->get()
            ->map(fn (GovernanceRecord $record) => ReviewRow::for($record, false))
            ->values()
            ->all();
    }

    public function recentActivity(?string $site, ?array $collections = null, int $limit = 10): array
    {
        // Events don't carry a collection, so fetch extra and drop those from collections the user can't see.
        $events = $this->audit->recent($collections === null ? $limit : $limit * 5, $site);

        return $events->map(function (AuditEvent $event) use ($collections) {
            $entry = Entries::find($event->governableId);
            $collection = $entry instanceof Entry ? $entry->collectionHandle() : $this->records->find($event->recordId)?->collection();

            if ($collections !== null && ! in_array($collection, $collections, true)) {
                return null;
            }

            $actor = $event->actorId ? User::find($event->actorId) : null;

            return [
                'id' => $event->id,
                'label' => $event->type->label(),
                'title' => $entry instanceof Entry ? (string) $entry->value('title') : $event->governableId,
                'detail_url' => $this->records->find($event->recordId) ? cp_route('governance.records.show', $event->recordId) : null,
                'actor' => $actor instanceof StatamicUser ? $actor->name() : __('System'),
                'at' => $event->createdAt->toIso8601ZuluString(),
            ];
        })->filter()->take($limit)->values()->all();
    }

    public function scheduler(): array
    {
        $last = $this->heartbeat->last();

        return [
            'last_sweep_at' => $last?->toIso8601ZuluString(),
            'stale' => $last === null || $last->lessThan($this->clock->now()->subHours(self::STALE_AFTER_HOURS)),
        ];
    }
}
