<?php

namespace Arronrees\ContentGovernance\Rules;

use Arronrees\ContentGovernance\Auth\Abilities;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Facades\User;
use Statamic\Support\Arr;

/**
 * An owner has to be someone who can review. The pickers only offer those people; this enforces it.
 */
class Reviewer implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $id = Arr::first(Arr::wrap($value));

        if ($id === null || $id === '') {
            return;
        }

        if (! app(Abilities::class)->isReviewer(User::find($id))) {
            $fail(__('The owner must be a user who can review governed content.'));
        }
    }
}
