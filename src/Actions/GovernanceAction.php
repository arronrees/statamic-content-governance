<?php

namespace Arronrees\ContentGovernance\Actions;

use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\RecordChanges;
use Statamic\Actions\Action;
use Statamic\Facades\User;

abstract class GovernanceAction extends Action
{
    public function visibleTo($item)
    {
        return $item instanceof GovernanceRecord;
    }

    protected function abilities(): Abilities
    {
        return app(Abilities::class);
    }

    protected function changes(): RecordChanges
    {
        return app(RecordChanges::class);
    }

    protected function actorId(): ?string
    {
        $id = User::current()?->getAuthIdentifier();

        return $id === null ? null : (string) $id;
    }
}
