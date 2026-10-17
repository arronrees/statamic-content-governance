<?php

namespace Arronrees\ContentGovernance\Drivers\File;

use Arronrees\ContentGovernance\Contracts\GovernanceQueryBuilder;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use BackedEnum;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Statamic\Data\DataCollection;
use Statamic\Stache\Query\Builder;

class StacheRecordQueryBuilder extends Builder implements GovernanceQueryBuilder
{
    public function whereActionDue(CarbonImmutable $now): static
    {
        return $this->whereNotNull('next_action_at')->where('next_action_at', '<=', $now);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (func_num_args() === 2) {
            return parent::where($column, '=', $this->normalise($operator), $boolean);
        }

        return parent::where($column, $operator, $this->normalise($value), $boolean);
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        return parent::whereIn($column, array_map($this->normalise(...), $values), $boolean);
    }

    public function listingRows(): Collection
    {
        return $this->get()->map(fn (GovernanceRecord $record) => [
            'id' => $record->id(),
            'governable_id' => $record->governableId(),
            'origin_id' => $record->originId(),
            'site' => $record->site(),
            'status' => $record->status()->value,
            'next_action_at' => $record->nextActionAt()?->getTimestamp(),
            'next_review_at' => $record->nextReviewAt()?->getTimestamp(),
            'expires_at' => $record->expiresAt()?->getTimestamp(),
        ])->values();
    }

    protected function collect($items = [])
    {
        return DataCollection::make($items)->map(fn ($item) => clone $item);
    }

    protected function getFilteredKeys()
    {
        if (empty($this->wheres)) {
            return collect($this->store->paths()->keys());
        }

        return $this->getKeysWithWheres($this->wheres);
    }

    protected function getKeysWithWheres($wheres)
    {
        return collect($wheres)->reduce(function ($ids, $where) {
            $keys = $where['type'] == 'Nested'
                ? $this->getKeysWithWheres($where['query']->wheres)
                : $this->getKeysWithWhere($where);

            return $this->intersectKeysFromWhereClause($ids, $keys, $where);
        });
    }

    protected function getKeysWithWhere($where)
    {
        $items = $this->store->index($where['column'])->items();

        $method = 'filterWhere'.$where['type'];

        return $this->{$method}($items, $where)->keys();
    }

    protected function getOrderKeyValuesByIndex()
    {
        return collect($this->orderBys)->mapWithKeys(fn ($orderBy) => [
            $orderBy->sort => $this->store->index($orderBy->sort)->items()->all(),
        ]);
    }

    private function normalise(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => CarbonImmutable::instance($value)->utc()->toIso8601ZuluString(),
            $value instanceof BackedEnum => $value->value,
            default => $value,
        };
    }
}
