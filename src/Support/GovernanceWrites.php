<?php

namespace Arronrees\ContentGovernance\Support;

class GovernanceWrites
{
    private int $depth = 0;

    public function during(callable $callback): mixed
    {
        $this->depth++;

        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }

    public function active(): bool
    {
        return $this->depth > 0;
    }
}
