<?php

namespace Arronrees\ContentGovernance\Jobs;

use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateCollection implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $collection) {}

    public function handle(GovernanceRecordRepository $records): void
    {
        $records->query()->where('collection', $this->collection)->get()->each($records->save(...));
    }
}
