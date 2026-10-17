<?php

namespace Arronrees\ContentGovernance\Status;

use Arronrees\ContentGovernance\Records\Status;
use Carbon\CarbonImmutable;

final readonly class StatusResult
{
    public function __construct(
        public Status $status,
        public ?CarbonImmutable $nextActionAt,
        public bool $shouldUnpublish = false,
        public bool $inExpiryWarning = false,
    ) {}
}
