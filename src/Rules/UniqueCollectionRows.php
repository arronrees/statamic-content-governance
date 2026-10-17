<?php

namespace Arronrees\ContentGovernance\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Support\Arr;

class UniqueCollectionRows implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $handles = collect($value ?? [])
            ->map(fn ($row) => Arr::first(Arr::wrap($row['collection'] ?? null)))
            ->filter();

        if ($handles->duplicates()->isNotEmpty()) {
            $fail(__('Each collection can only have one row.'));
        }
    }
}
