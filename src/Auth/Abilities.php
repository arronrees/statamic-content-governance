<?php

namespace Arronrees\ContentGovernance\Auth;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;

class Abilities
{
    public function __construct(private GeneralSettings $settings) {}

    public function view(?User $user, ?GovernanceRecord $record = null): bool
    {
        return $this->has($user, Permissions::VIEW)
            && ($record === null || $this->viewCollection($user, $record->collection()));
    }

    public function review(?User $user, GovernanceRecord $record): bool
    {
        if (! $this->viewCollection($user, $record->collection())) {
            return false;
        }

        if ($this->has($user, Permissions::REVIEW_ALL)) {
            return true;
        }

        // Unowned content is the fallback owner's: it's shown as theirs and they get its notifications.
        $ownerId = $record->ownerId() ?? $this->settings->fallbackOwnerId();

        return $this->has($user, Permissions::REVIEW_OWN)
            && $ownerId !== null
            && $ownerId === (string) $user->getAuthIdentifier();
    }

    public function manage(?User $user, GovernanceRecord $record): bool
    {
        return $this->has($user, Permissions::MANAGE) && $this->viewCollection($user, $record->collection());
    }

    /**
     * Governance never shows or changes content from a collection the user can't view in Statamic.
     */
    public function viewCollection(?User $user, string $collection): bool
    {
        return $this->has($user, "view {$collection} entries");
    }

    /**
     * @return list<string>|null The collection handles the user may see, or null for all of them.
     */
    public function viewableCollections(?User $user): ?array
    {
        if ($user?->isSuper()) {
            return null;
        }

        return Collection::handles()->filter(fn (string $handle) => $this->viewCollection($user, $handle))->values()->all();
    }

    /**
     * Whether someone can review anything at all: who may be offered, and assigned, as an owner.
     */
    public function isReviewer(?User $user): bool
    {
        return $this->has($user, Permissions::REVIEW_OWN) || $this->has($user, Permissions::REVIEW_ALL);
    }

    public function configure(?User $user): bool
    {
        return $this->has($user, Permissions::CONFIGURE);
    }

    private function has(?User $user, string $permission): bool
    {
        return $user !== null && ($user->isSuper() || $user->hasPermission($permission));
    }
}
