<?php

namespace Arronrees\ContentGovernance\Query\Scopes;

use Arronrees\ContentGovernance\Auth\Abilities;
use Statamic\Facades\User;
use Statamic\Query\Scopes\Scope;

class GovernanceReviewers extends Scope
{
    protected static $handle = 'governance_reviewers';

    public function apply($query, $values)
    {
        // Permissions can arrive through roles or groups, so resolve eligible users rather than query roles.
        $abilities = app(Abilities::class);

        $ids = User::all()
            ->filter(fn ($user) => $abilities->isReviewer($user))
            ->map(fn ($user) => (string) $user->getAuthIdentifier())
            ->values()
            ->all();

        $query->whereIn('id', $ids);
    }
}
