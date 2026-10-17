<?php

namespace Arronrees\ContentGovernance\Commands;

use Arronrees\ContentGovernance\Jobs\SyncCollection;
use Arronrees\ContentGovernance\Records\EntryRecords;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Collection;

class Sync extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:governance:sync
        {--collection=* : Only these collections (defaults to every governed collection)}
        {--queue : Queue one job per collection instead of running now}';

    protected $description = 'Create governance records for existing entries in governed collections';

    public function handle(EntryRecords $records, CollectionRules $rules): int
    {
        $collections = collect($this->option('collection') ?: Collection::handles())
            ->filter(fn (string $handle) => $rules->for($handle)->enabled)
            ->values();

        if ($collections->isEmpty()) {
            $this->components->warn('No governed collections to sync.');

            return self::SUCCESS;
        }

        foreach ($collections as $collection) {
            if ($this->option('queue')) {
                SyncCollection::dispatch($collection);
                $this->components->info("Queued sync for [{$collection}].");

                continue;
            }

            $result = $records->syncCollection($collection);
            $this->components->info("[{$collection}]: {$result['created']} records created, {$result['removed']} removed for missing entries.");
        }

        return self::SUCCESS;
    }
}
