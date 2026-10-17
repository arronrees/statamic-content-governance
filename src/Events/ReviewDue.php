<?php

namespace Arronrees\ContentGovernance\Events;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Foundation\Events\Dispatchable;

class ReviewDue
{
    use Dispatchable;

    public function __construct(
        public GovernanceRecord $record,
    ) {}
}
