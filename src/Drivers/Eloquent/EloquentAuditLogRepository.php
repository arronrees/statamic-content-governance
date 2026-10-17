<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class EloquentAuditLogRepository implements AuditLogRepository
{
    public function append(AuditEvent $event): void
    {
        $values = $event->toArray();
        $values['created_at'] = Dates::toDatabase($event->createdAt, Dates::PRECISE_FORMAT);

        AuditEventModel::query()->create($values);
    }

    public function forRecord(GovernanceRecord $record): Collection
    {
        return $this->newestFirst(AuditEventModel::query()->where('record_id', $record->id()))->get()->map($this->toEvent(...));
    }

    public function recent(int $limit, ?string $site = null): Collection
    {
        return $this->newestFirst(AuditEventModel::query())
            ->when($site, fn (Builder $query) => $query->where('site', $site))
            ->limit($limit)
            ->get()
            ->map($this->toEvent(...));
    }

    public function all(): LazyCollection
    {
        return AuditEventModel::query()->orderBy('sequence')->lazyById(500, 'sequence')->map($this->toEvent(...));
    }

    /**
     * @param  Builder<AuditEventModel>  $query
     * @return Builder<AuditEventModel>
     */
    private function newestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('sequence');
    }

    private function toEvent(AuditEventModel $model): AuditEvent
    {
        return AuditEvent::fromArray([...$model->getAttributes(), 'payload' => $model->payload ?? []]);
    }
}
