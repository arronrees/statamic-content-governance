<?php

namespace Arronrees\ContentGovernance\Exceptions;

use RuntimeException;

class DuplicateRecord extends RuntimeException
{
    public static function for(string $type, string $id, string $site): self
    {
        return new self("A governance record already exists for {$type} [{$id}] on site [{$site}].");
    }
}
