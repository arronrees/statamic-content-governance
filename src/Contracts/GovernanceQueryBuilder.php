<?php

namespace Arronrees\ContentGovernance\Contracts;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Statamic\Contracts\Query\Builder;

interface GovernanceQueryBuilder extends Builder
{
    public function where($column, $operator = null, $value = null, $boolean = 'and');

    public function whereIn($column, $values, $boolean = 'and');

    public function whereNotNull($column, $boolean = 'and');

    public function whereJsonContains($column, $values, $boolean = 'and');

    public function whereActionDue(CarbonImmutable $now): static;

    /**
     * The matching records as lightweight rows, without building record objects.
     * Dates are Unix timestamps.
     *
     * @return Collection<int, array{id: string, governable_id: string, origin_id: ?string, site: string, status: string, next_action_at: ?int, next_review_at: ?int, expires_at: ?int}>
     */
    public function listingRows(): Collection;

    public function when($value, $callback, $default = null);

    public function orderBy($column, $direction = 'asc');

    public function limit($value);

    public function get($columns = ['*']);

    public function first();

    public function count();

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null);
}
