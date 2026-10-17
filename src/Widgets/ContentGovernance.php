<?php

namespace Arronrees\ContentGovernance\Widgets;

use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Dashboard\Dashboard;
use Arronrees\ContentGovernance\Query\Scopes\Filters\GovernanceAssignedToMe;
use Arronrees\ContentGovernance\Query\Scopes\Filters\GovernanceStatus;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;
use Arronrees\ContentGovernance\Review\ReviewRow;
use Statamic\Facades\User;
use Statamic\Widgets\VueComponent;
use Statamic\Widgets\Widget;

class ContentGovernance extends Widget
{
    protected static $handle = 'content_governance';

    public function component()
    {
        $user = User::current();

        if (! app(Abilities::class)->view($user)) {
            return null;
        }

        $mine = $this->config('scope', 'mine') === 'mine';
        $statuses = collect($this->config('statuses', ['due', 'overdue', 'expired']))
            ->map(fn ($status) => Status::tryFrom($status))
            ->filter()
            ->values();
        $ownerId = (string) $user->getAuthIdentifier();

        $collections = app(Abilities::class)->viewableCollections($user);

        $query = fn () => app(GovernanceRecordRepository::class)->query()
            ->when($mine, fn ($query) => $query->where('owner_id', $ownerId))
            ->when($collections !== null, fn ($query) => $query->whereIn('collection', $collections));

        return VueComponent::render('content-governance-widget', [
            'title' => $this->config('title', $mine ? __('My content needing attention') : __('Content needing attention')),
            'counts' => $statuses->mapWithKeys(fn (Status $status) => [$status->value => [
                'label' => $status->label(),
                'count' => $query()->where('status', $status)->count(),
            ]])->all(),
            'items' => $query()
                ->whereIn('status', $statuses->all())
                ->orderBy('next_action_at')
                ->limit((int) $this->config('limit', 5))
                ->get()
                ->map(fn (GovernanceRecord $record) => ReviewRow::for($record, false))
                ->values()
                ->all(),
            'reviewUrl' => $this->reviewUrl($statuses->map->value->all(), $mine),
            'scheduler' => app(Dashboard::class)->scheduler(),
        ]);
    }

    private function reviewUrl(array $statuses, bool $mine): string
    {
        $filters = [GovernanceStatus::handle() => ['statuses' => $statuses]];

        if ($mine) {
            $filters[GovernanceAssignedToMe::handle()] = ['mine' => true];
        }

        return cp_route('governance.records.index', ['filters' => base64_encode(json_encode($filters))]);
    }
}
