<?php

namespace Arronrees\ContentGovernance\Events;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

class ReviewRescheduled
{
    use Dispatchable;

    public function __construct(
        public GovernanceRecord $record,
        public ?string $actorId,
        public ?CarbonImmutable $from,
        public ?CarbonImmutable $to,
    ) {}
}
