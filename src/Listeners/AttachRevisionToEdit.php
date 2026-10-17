<?php

namespace Arronrees\ContentGovernance\Listeners;

use Arronrees\ContentGovernance\Audit\PendingEdits;
use Statamic\Events\RevisionSaved;

class AttachRevisionToEdit
{
    public function __construct(private PendingEdits $edits) {}

    // Runs inside Statamic's own save: a governance fault is reported, never allowed to block the editor.
    public function handle(RevisionSaved $event): void
    {
        rescue(fn () => $this->apply($event));
    }

    private function apply(RevisionSaved $event): void
    {
        $revision = $event->revision;

        if ($revision->isWorkingCopy()) {
            return;
        }

        $this->edits->attachRevision($revision->attribute('id'), $revision->id());
    }
}
