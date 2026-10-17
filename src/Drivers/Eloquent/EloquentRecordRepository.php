<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Arronrees\ContentGovernance\Contracts\GovernanceQueryBuilder;
use Arronrees\ContentGovernance\Exceptions\DuplicateRecord;
use Arronrees\ContentGovernance\Records\BaseRecordRepository;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class EloquentRecordRepository extends BaseRecordRepository
{
    public function make(): GovernanceRecord
    {
        return GovernanceRecord::make();
    }

    public function query(): GovernanceQueryBuilder
    {
        return new EloquentRecordQueryBuilder(GovernanceRecordModel::query());
    }

    public function find(string $id): ?GovernanceRecord
    {
        $model = GovernanceRecordModel::find($id);

        return $model ? RecordMapper::toRecord($model) : null;
    }

    public function findByGovernable(string $type, string $id, string $site): ?GovernanceRecord
    {
        return $this->query()
            ->where('governable_type', $type)
            ->where('governable_id', $id)
            ->where('site', $site)
            ->first();
    }

    protected function remove(GovernanceRecord $record): void
    {
        GovernanceRecordModel::query()->whereKey($record->id())->delete();
    }

    public function statusCounts(?string $site = null): array
    {
        $rows = GovernanceRecordModel::query()
            ->selectRaw('collection, status, count(*) as total')
            ->when($site, fn ($query) => $query->where('site', $site))
            ->groupBy('collection', 'status')
            ->toBase()
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[$row->collection][$row->status] = (int) $row->total;
        }

        return $counts;
    }

    public function flaggedCount(Flag $flag, ?string $site = null, ?array $collections = null): int
    {
        return GovernanceRecordModel::query()
            ->whereJsonContains('flags', $flag->value)
            ->when($site, fn ($query) => $query->where('site', $site))
            ->when($collections !== null, fn ($query) => $query->whereIn('collection', $collections))
            ->count();
    }

    protected function persist(GovernanceRecord $record): void
    {
        try {
            GovernanceRecordModel::query()->updateOrCreate(
                ['id' => $record->id()],
                RecordMapper::toAttributes($record),
            );
        } catch (UniqueConstraintViolationException) {
            throw DuplicateRecord::for($record->governableType(), $record->governableId(), $record->site());
        }
    }
}
