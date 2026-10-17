<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\User;
use Statamic\Statamic;
use Statamic\Support\Str;

class InjectGovernancePanel
{
    public const HANDLE = 'content_governance';

    public function __construct(private CollectionRules $rules, private Abilities $abilities) {}

    // Runs whenever Statamic loads an entry blueprint: a governance fault is reported, never allowed to break the page.
    public function handle(EntryBlueprintFound $event): void
    {
        rescue(fn () => $this->apply($event));
    }

    private function apply(EntryBlueprintFound $event): void
    {
        // The event fires on every blueprint load (front end, GraphQL, validation); only the CP needs the panel.
        if (! Statamic::isCpRoute() || ! $this->abilities->view(User::current())) {
            return;
        }

        $collection = $event->entry?->collectionHandle() ?? Str::after((string) $event->blueprint->namespace(), 'collections.');

        if (! $this->rules->for($collection)->enabled) {
            return;
        }

        $event->blueprint->ensureFieldInTab(self::HANDLE, [
            'type' => 'governance_panel',
            'display' => __('Governance'),
            'visibility' => 'computed',
            'listable' => false,
        ], 'sidebar');
    }
}
