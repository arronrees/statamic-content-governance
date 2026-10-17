<?php

namespace Arronrees\ContentGovernance\Rules;

use Arronrees\ContentGovernance\Settings\AddonSettings;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Statamic\Support\Arr;

class CollectionRules
{
    public function __construct(
        private GeneralSettings $settings,
        private AddonSettings $addonSettings,
    ) {}

    public function for(string $collection): CollectionRule
    {
        $row = collect($this->rows())->first(fn ($row) => $this->handleOf($row) === $collection);

        if (! $row) {
            return new CollectionRule($collection, enabled: false, dueWindowDays: $this->settings->defaultDueWindowDays());
        }

        return CollectionRule::fromArray([
            ...$row,
            'collection' => $collection,
            'default_owner_id' => Arr::first(Arr::wrap($row['default_owner_id'] ?? null)),
            'due_window_days' => $row['due_window_days'] ?? $this->settings->defaultDueWindowDays(),
        ]);
    }

    private function handleOf(array $row): ?string
    {
        return Arr::first(Arr::wrap($row['collection'] ?? null));
    }

    private function rows(): array
    {
        return $this->addonSettings->get('collections', []) ?? [];
    }
}
