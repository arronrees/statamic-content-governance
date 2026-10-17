<?php

namespace Arronrees\ContentGovernance\Owners;

use Arronrees\ContentGovernance\Rules\CollectionRule;
use Arronrees\ContentGovernance\Rules\OwnerStrategy;
use Statamic\Facades\User;

class OwnerResolver
{
    /**
     * @param  list<string>  $authorIds
     */
    public function resolve(CollectionRule $rule, array $authorIds, ?string $originOwnerId, ?string $fallbackOwnerId): ?string
    {
        if ($rule->localisationsInheritOwner && $this->exists($originOwnerId)) {
            return $originOwnerId;
        }

        if ($rule->defaultOwnerStrategy === OwnerStrategy::None) {
            return null;
        }

        $candidates = $rule->defaultOwnerStrategy === OwnerStrategy::Author
            ? [...$authorIds, $rule->defaultOwnerId, $fallbackOwnerId]
            : [$rule->defaultOwnerId, $fallbackOwnerId];

        foreach ($candidates as $candidate) {
            if ($this->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function exists(?string $userId): bool
    {
        return $userId !== null && User::find($userId) !== null;
    }
}
