<?php

namespace Arronrees\ContentGovernance\Query\Scopes\Filters;

use Arronrees\ContentGovernance\Records\Flag;

class GovernanceFlags extends GovernanceFilter
{
    protected static $handle = 'governance_flags';

    public static function title()
    {
        return __('Flags');
    }

    public function fieldItems()
    {
        return [
            'flags' => [
                'type' => 'checkboxes',
                // Only auto-unpublish sets that flag, and it's off until phase 2.
                'options' => collect(Flag::cases())->reject(fn (Flag $flag) => $flag === Flag::UnpublishedByGovernance)->mapWithKeys(fn (Flag $flag) => [$flag->value => self::label($flag)])->all(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        foreach ($values['flags'] ?? [] as $flag) {
            $query->whereJsonContains('flags', $flag);
        }
    }

    public function badge($values)
    {
        return __('Flags').': '.collect($values['flags'] ?? [])->map(fn ($flag) => self::label(Flag::from($flag)))->join(', ');
    }

    public static function label(Flag $flag): string
    {
        return match ($flag) {
            Flag::NeedsChanges => __('Needs changes'),
            Flag::Orphaned => __('Orphaned'),
            Flag::UnpublishedByGovernance => __('Unpublished by governance'),
        };
    }
}
