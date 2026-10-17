<?php

namespace Arronrees\ContentGovernance\Notifications;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;

final readonly class DigestItem
{
    public function __construct(
        public NotificationType $type,
        public string $recordId,
        public string $title,
        public string $site,
        public string $url,
    ) {}

    public static function for(NotificationType $type, GovernanceRecord $record): self
    {
        $entry = Entries::find($record->governableId());
        $entry = $entry instanceof Entry ? $entry : null;

        return new self(
            type: $type,
            recordId: $record->id(),
            title: (string) ($entry?->value('title') ?? $record->governableId()),
            site: $record->site(),
            url: $entry?->editUrl() ?? cp_route('index'),
        );
    }
}
