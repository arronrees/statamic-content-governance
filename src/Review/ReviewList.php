<?php

namespace Arronrees\ContentGovernance\Review;

use Arronrees\ContentGovernance\Contracts\GovernanceQueryBuilder;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;

/**
 * Localisations are grouped under their origin. Groups, not rows, are sorted and paginated,
 * so a group never splits across pages. Filtering happens first, in the query.
 *
 * Grouping and sorting work on lightweight rows; full records are only loaded for the page shown.
 */
class ReviewList
{
    public const SORTABLE = ['next_action_at', 'status', 'next_review_at', 'expires_at', 'title'];

    public function __construct(private GovernanceRecordRepository $records) {}

    public function paginate(GovernanceQueryBuilder $query, ?string $search, string $sort, string $order, int $perPage, int $page): LengthAwarePaginator
    {
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'next_action_at';

        // Titles mean an entry lookup per group, so only fetch them when sorting or searching needs them.
        $needsTitles = $sort === 'title' || filled($search);

        $groups = $query->listingRows()
            ->groupBy(fn (array $row) => $row['origin_id'] ?? $row['governable_id'])
            ->map(fn (Collection $rows, $originId) => $this->group($rows, (string) $originId, $needsTitles))
            ->when($search, fn (Collection $groups) => $groups->filter(fn (array $group) => $this->matches($group, $search)))
            ->pipe(fn (Collection $groups) => $this->sort($groups, $sort, $order))
            ->values();

        $shown = $groups->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($this->rows($shown), $groups->count(), $perPage, $page);
    }

    private function group(Collection $rows, string $originId, bool $withTitle): array
    {
        $origin = $rows->first(fn (array $row) => $row['governable_id'] === $originId) ?? $rows->first();

        return [
            'origin' => $origin['id'],
            'localisations' => $rows->reject(fn (array $row) => $row['id'] === $origin['id'])->sortBy('site')->pluck('id')->values()->all(),
            'worst' => $rows->map(fn (array $row) => Status::from($row['status']))->sortByDesc(fn (Status $status) => $status->severity())->first(),
            'title' => $withTitle ? $this->title($origin['governable_id']) : null,
            'rows' => $rows,
        ];
    }

    private function title(string $entryId): string
    {
        $entry = Entries::find($entryId);

        return $entry instanceof Entry ? (string) $entry->value('title') : $entryId;
    }

    private function matches(array $group, string $search): bool
    {
        return str_contains(mb_strtolower($group['title']), mb_strtolower($search));
    }

    private function sort(Collection $groups, string $sort, string $order): Collection
    {
        $key = fn (array $group) => match ($sort) {
            'status' => $group['worst']->severity(),
            'title' => mb_strtolower($group['title']),
            default => $group['rows']->pluck($sort)->filter()->min() ?? PHP_INT_MAX,
        };

        return $order === 'desc' ? $groups->sortByDesc($key) : $groups->sortBy($key);
    }

    private function rows(Collection $groups): array
    {
        $ids = $groups->flatMap(fn (array $group) => [$group['origin'], ...$group['localisations']])->all();

        $records = $ids === []
            ? collect()
            : $this->records->query()->whereIn('id', $ids)->get()->keyBy(fn (GovernanceRecord $record) => $record->id());

        return $groups->flatMap(function (array $group) use ($records) {
            $size = 1 + count($group['localisations']);

            return [
                ReviewRow::for($records[$group['origin']], false, $size > 1 ? $group['worst'] : null, $size),
                ...array_map(fn (string $id) => ReviewRow::for($records[$id], true), $group['localisations']),
            ];
        })->values()->all();
    }
}
