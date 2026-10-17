<?php

namespace Arronrees\ContentGovernance\Http\Controllers\CP;

use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Query\Scopes\Filters\GovernanceFilter;
use Arronrees\ContentGovernance\Review\RecordDetail;
use Arronrees\ContentGovernance\Review\ReviewList;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\Statamic;

class GovernanceRecordController extends CpController
{
    use QueriesFilters;

    private const MAX_PER_PAGE = 100;

    public function index(Request $request, GovernanceRecordRepository $records, ReviewList $list, Abilities $abilities)
    {
        abort_unless($abilities->view(User::current()), 403);

        if (! $request->wantsJson()) {
            return Inertia::render('content-governance::Records/Index', [
                'columns' => $this->columns(),
                'filters' => Scope::filters(GovernanceFilter::LISTING),
                'actionUrl' => cp_route('governance.records.actions.run'),
                'proRequired' => ! Statamic::pro(),
            ]);
        }

        $query = $records->query();

        if (($collections = $abilities->viewableCollections(User::current())) !== null) {
            $query->whereIn('collection', $collections);
        }

        $badges = $this->queryFilters($query, $this->decodedFilters($request));

        $page = $list->paginate(
            $query,
            $request->input('search'),
            $request->input('sort', 'next_action_at'),
            $request->input('order', 'asc'),
            min(max((int) $request->input('perPage', 25), 1), self::MAX_PER_PAGE),
            max((int) $request->input('page', 1), 1),
        );

        return [
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
                'columns' => $this->columns(),
                'activeFilterBadges' => $badges,
            ],
        ];
    }

    public function show(string $record, GovernanceRecordRepository $records, RecordDetail $detail, Abilities $abilities)
    {
        abort_unless($abilities->view(User::current()), 403);

        $found = $records->find($record);
        abort_unless($found !== null, 404);
        abort_unless($abilities->view(User::current(), $found), 403);

        return Inertia::render('content-governance::Records/Show', $detail->props($found));
    }

    private function columns(): array
    {
        return collect([
            Column::make('title')->label(__('Title')),
            Column::make('status')->label(__('Status')),
            Site::multiEnabled() ? Column::make('site')->label(__('Site'))->sortable(false) : null,
            Column::make('collection')->label(__('Collection'))->sortable(false),
            Column::make('owner')->label(__('Owner'))->sortable(false),
            Column::make('next_review_at')->label(__('Next review')),
            Column::make('expires_at')->label(__('Expires')),
            Column::make('next_action_at')->label(__('Next action')),
        ])->filter()->map->toArray()->values()->all();
    }

    private function decodedFilters(Request $request): array
    {
        $filters = $request->input('filters');
        $decoded = $filters ? json_decode(base64_decode($filters), true) : null;

        // Only our own filters: the handles are global, and the request can name any of them.
        return collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($values, $handle) => is_array($values) && Scope::find($handle) instanceof GovernanceFilter)
            ->all();
    }
}
