<?php

namespace Arronrees\ContentGovernance\Support;

use Arronrees\ContentGovernance\Contracts\Clock;
use Carbon\CarbonImmutable;

class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }
}
