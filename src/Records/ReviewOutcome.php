<?php

namespace Arronrees\ContentGovernance\Records;

enum ReviewOutcome: string
{
    case Confirmed = 'confirmed';
    case NeedsChanges = 'needs_changes';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => __('Confirmed accurate'),
            self::NeedsChanges => __('Needs changes'),
        };
    }
}
