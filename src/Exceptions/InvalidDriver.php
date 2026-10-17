<?php

namespace Arronrees\ContentGovernance\Exceptions;

use InvalidArgumentException;

class InvalidDriver extends InvalidArgumentException
{
    public static function named(string $driver): self
    {
        return new self("Unknown Content Governance driver [{$driver}]. Supported drivers: file, eloquent.");
    }
}
