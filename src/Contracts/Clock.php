<?php

namespace Arronrees\ContentGovernance\Contracts;

use Carbon\CarbonImmutable;

interface Clock
{
    public function now(): CarbonImmutable;
}
