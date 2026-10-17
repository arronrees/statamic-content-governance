<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Arronrees\ContentGovernance\Contracts\GovernanceQueryBuilder;
use BackedEnum;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;

class EloquentRecordQueryBuilder extends EloquentQueryBuilder implements GovernanceQueryBuilder
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
        $timestamp = fn (?string $date) => $date === null ? null : strtotime($date.' UTC');

        return $this->builder->toBase()
            ->get(['id', 'governable_id', 'origin_id', 'site', 'status', 'next_action_at', 'next_review_at', 'expires_at'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'governable_id' => $row->governable_id,
                'origin_id' => $row->origin_id,
                'site' => $row->site,
                'status' => $row->status,
                'next_action_at' => $timestamp($row->next_action_at),
                'next_review_at' => $timestamp($row->next_review_at),
                'expires_at' => $timestamp($row->expires_at),
            ]);
    }

    public function limit($value)
    {
        $this->builder->limit($value);

        return $this;
    }

    public function count()
    {
        return $this->builder->count();
    }

    protected function transform($items, $columns = ['*'])
    {
        return $items->map(fn (GovernanceRecordModel $model) => RecordMapper::toRecord($model));
    }

    private function normalise(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => Dates::toDatabase($value),
            $value instanceof BackedEnum => $value->value,
            default => $value,
        };
    }
}
