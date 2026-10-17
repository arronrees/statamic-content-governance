<?php

namespace Arronrees\ContentGovernance\Events;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Foundation\Events\Dispatchable;

class OwnerChanged
{
    use Dispatchable;

    public function __construct(
        public GovernanceRecord $record,
        public ?string $actorId,
        public ?string $from,
        public ?string $to,
    ) {}
}
