<?php

namespace Arronrees\ContentGovernance\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class SweepHeartbeat
{
    private const KEY = 'statamic.governance.sweep.heartbeat';

    public function beat(CarbonImmutable $at): void
    {
        Cache::forever(self::KEY, $at->toIso8601ZuluString());
    }

    public function last(): ?CarbonImmutable
    {
        $at = Cache::get(self::KEY);

        return $at ? CarbonImmutable::parse($at, 'UTC') : null;
    }
}
