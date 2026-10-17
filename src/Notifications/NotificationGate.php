<?php

namespace Arronrees\ContentGovernance\Notifications;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;

class NotificationGate
{
    public function __construct(private CollectionRules $rules) {}

    public function allows(NotificationType $type, GovernanceRecord $record): bool
    {
        if (! $this->rules->for($record->collection())->enabled) {
            return false;
        }

        if ($type === NotificationType::Unpublished) {
            return true;
        }

        $entry = Entries::find($record->governableId());

        return $entry instanceof Entry && $entry->published();
    }
}
