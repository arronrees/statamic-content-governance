<?php

namespace Arronrees\ContentGovernance\Fieldtypes;

use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Review\PanelData;
use Statamic\Entries\Entry;
use Statamic\Facades\User;
use Statamic\Fields\Fieldtype;

class GovernancePanel extends Fieldtype
{
    protected static $handle = 'governance_panel';

    protected $selectable = false;

    public function preload()
    {
        // Also checked here in case the field was added to a blueprint by hand rather than injected.
        if (! app(Abilities::class)->view(User::current())) {
            return ['record' => null];
        }

        $entry = $this->field()?->parent();

        // A governance fault shouldn't stop the entry's edit page from opening.
        return rescue(fn () => app(PanelData::class)->forEntry($entry instanceof Entry ? $entry : null), ['record' => null]);
    }
}
