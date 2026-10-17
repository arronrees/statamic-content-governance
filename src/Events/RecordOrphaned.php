<?php

namespace Arronrees\ContentGovernance\Events;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Foundation\Events\Dispatchable;

class RecordOrphaned
{
    use Dispatchable;

    public function __construct(
        public GovernanceRecord $record,
        public ?string $formerOwnerId,
    ) {}
}
