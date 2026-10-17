<?php

namespace Arronrees\ContentGovernance;

use Arronrees\ContentGovernance\Audit\PendingEdits;
use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Auth\Permissions;
use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Drivers\Drivers;
use Arronrees\ContentGovernance\Drivers\File\GovernanceStore;
use Arronrees\ContentGovernance\Drivers\File\StacheGovernanceRecord;
use Arronrees\ContentGovernance\Notifications\NotificationSettings;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;
use Arronrees\ContentGovernance\Rules\RuleSnapshot;
use Arronrees\ContentGovernance\Settings\AddonSettings;
use Arronrees\ContentGovernance\Settings\SettingsBlueprint;
use Arronrees\ContentGovernance\Support\GovernanceWrites;
use Arronrees\ContentGovernance\Support\SystemClock;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Statamic\Addons\Addon as StatamicAddon;
use Statamic\Events\AddonSettingsSaved;
use Statamic\Facades\Addon;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\User;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    public const ADDON = 'arronrees/content-governance';

    protected $config = false;

    protected $vite = [
        'input' => ['resources/js/cp.js', 'resources/css/cp.css'],
        'publicDirectory' => 'resources/dist',
    ];

    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/governance.php', 'statamic.governance');

        // Stache items are cached serialised, and Laravel only unserialises classes it has been told about.
        // Registered here, not in boot, because the cache store fixes that list when it's first built.
        $this->registerSerializableClasses([
            GovernanceRecord::class,
            StacheGovernanceRecord::class,
            Status::class,
            Flag::class,
            CarbonImmutable::class,
        ]);

        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(PendingEdits::class);
        $this->app->singleton(AddonSettings::class);
        $this->app->singleton(RuleSnapshot::class);
        $this->app->singleton(GovernanceWrites::class);

        $this->app->bind(GovernanceRecordRepository::class, fn ($app) => $app->make(Drivers::class)->records($this->driver()));
        $this->app->bind(AuditLogRepository::class, fn ($app) => $app->make(Drivers::class)->audit($this->driver()));
    }

    public function bootAddon()
    {
        Drivers::ensureValid($this->driver());

        $this->app['stache']->registerStores([
            (new GovernanceStore)->directory(config('statamic.governance.records_path')),
        ]);

        if ($this->driver() === Drivers::ELOQUENT) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        Permissions::register();
        $this->registerSettingsBlueprint(fn () => SettingsBlueprint::contents());
        $this->letConfigureGovernanceEditSettings();
        $this->registerNavigation();

        $this->resetBetweenUnitsOfWork();
        $this->syncOnFirstInstall();

        $this->publishes([
            __DIR__.'/../config/governance.php' => config_path('statamic/governance.php'),
        ], 'governance-config');
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('statamic:governance:sweep')->hourly()->withoutOverlapping()->onOneServer();

        $notifications = $this->app->make(NotificationSettings::class);

        $schedule->command('statamic:governance:digest')
            ->days($notifications->digestDays())
            ->at($notifications->digestTime())
            ->timezone(Statamic::displayTimezone())
            ->onOneServer();
    }

    private function letConfigureGovernanceEditSettings(): void
    {
        // Statamic guards addon settings with its own permission, which can't be removed. Let ours in too,
        // for this addon only, rather than replacing the global AddonPolicy.
        Gate::before(function ($user, string $ability, array $arguments) {
            $addon = $arguments[0] ?? null;

            if ($ability !== 'editSettings' || ! $addon instanceof StatamicAddon || $addon->id() !== self::ADDON) {
                return null;
            }

            return $this->app->make(Abilities::class)->configure(User::fromUser($user)) ?: null;
        });
    }

    private function registerNavigation(): void
    {
        Nav::extend(function ($nav) {
            $nav->create(__('Dashboard'))
                ->section(__('Governance'))
                ->route('governance.dashboard')
                ->icon('chart-monitoring-indicator')
                ->can(Permissions::VIEW);

            $nav->create(__('Review'))
                ->section(__('Governance'))
                ->route('governance.records.index')
                ->icon('clipboard-check')
                ->can(Permissions::VIEW);

            $addon = Addon::get(self::ADDON);

            $nav->create(__('Settings'))
                ->section(__('Governance'))
                ->url($addon->settingsUrl())
                ->icon('cog')
                ->can('editSettings', $addon);
        });
    }

    private function resetBetweenUnitsOfWork(): void
    {
        $flush = function () {
            rescue(fn () => $this->app->make(PendingEdits::class)->flush());
            $this->app->make(AddonSettings::class)->forget();
        };

        // A job on the sync connection runs inline, in the middle of someone's request: that's not the end of anything.
        $inWorker = fn ($event) => $event->connectionName !== 'sync';

        $this->app->terminating($flush);
        Queue::before(fn (JobProcessing $event) => $inWorker($event) && $this->app->make(AddonSettings::class)->forget());
        Queue::after(fn (JobProcessed $event) => $inWorker($event) && $flush());
        Queue::failing(fn (JobFailed $event) => $inWorker($event) && $flush());
        Event::listen(AddonSettingsSaved::class, fn () => $this->app->make(AddonSettings::class)->forget());
    }

    private function syncOnFirstInstall(): void
    {
        // Statamic runs this after every composer install/update, so only backfill while nothing exists yet.
        Statamic::afterInstalled(function ($command) {
            try {
                $isEmpty = $this->app->make(GovernanceRecordRepository::class)->query()->count() === 0;
            } catch (QueryException) {
                return;
            }

            if ($isEmpty) {
                $command->call('statamic:governance:sync');
            }
        });
    }

    private function driver(): string
    {
        return config('statamic.governance.driver');
    }
}
