<?php

namespace Arronrees\ContentGovernance\Records;

enum Status: string
{
    case Current = 'current';
    case Due = 'due';
    case Overdue = 'overdue';
    case Expired = 'expired';

    public function severity(): int
    {
        return match ($this) {
            self::Current => 0,
            self::Due => 1,
            self::Overdue => 2,
            self::Expired => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Current => __('Current'),
            self::Due => __('Due'),
            self::Overdue => __('Overdue'),
            self::Expired => __('Expired'),
        };
    }
}
