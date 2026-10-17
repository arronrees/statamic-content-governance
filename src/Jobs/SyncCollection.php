<?php

namespace Arronrees\ContentGovernance\Jobs;

use Arronrees\ContentGovernance\Records\EntryRecords;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCollection implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $collection) {}

    public function handle(EntryRecords $records): void
    {
        $records->syncCollection($this->collection);
    }
}
