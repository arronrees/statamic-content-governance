<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Records\ReviewOutcome;

class ReviewFields
{
    public static function make(): array
    {
        return [
            'outcome' => [
                'type' => 'button_group',
                'display' => __('Outcome'),
                'options' => collect(ReviewOutcome::cases())->mapWithKeys(fn (ReviewOutcome $outcome) => [$outcome->value => $outcome->label()])->all(),
                'default' => ReviewOutcome::Confirmed->value,
                'validate' => 'required',
            ],
            'note' => [
                'type' => 'textarea',
                'display' => __('Note'),
                'instructions' => __('Optional. Kept in the audit history.'),
            ],
        ];
    }
}
