<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Jobs\RecalculateCollection;
use Arronrees\ContentGovernance\Jobs\SyncCollection;
use Arronrees\ContentGovernance\Rules\RuleSnapshot;
use Arronrees\ContentGovernance\ServiceProvider;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Events\AddonSettingsSaved;
use Statamic\Events\AddonSettingsSaving;

class RecalculateOnRuleChange
{
    public function __construct(private RuleSnapshot $snapshot) {}

    public function handleSaving(AddonSettingsSaving $event): void
    {
        if ($event->settings->addon()->id() !== ServiceProvider::ADDON) {
            return;
        }

        $stored = app(SettingsRepository::class)->find(ServiceProvider::ADDON);

        $this->snapshot->take($stored?->raw()['collections'] ?? []);
    }

    public function handleSaved(AddonSettingsSaved $event): void
    {
        if ($event->settings->addon()->id() !== ServiceProvider::ADDON) {
            return;
        }

        $changes = $this->snapshot->compare($event->settings->raw()['collections'] ?? []);

        // Newly enabled: create records for entries that have none. Existing records are recalculated below.
        foreach ($changes['enabled'] as $collection) {
            SyncCollection::dispatch($collection);
        }

        foreach ($changes['changed'] as $collection) {
            RecalculateCollection::dispatch($collection);
        }
    }
}
