<?php

namespace Arronrees\ContentGovernance\Review;

use Arronrees\ContentGovernance\Query\Scopes\Filters\GovernanceFlags;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Records\Status;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Statamic\Auth\User as StatamicUser;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class ReviewRow
{
    public static function for(GovernanceRecord $record, bool $isLocalisation, ?Status $groupStatus = null, int $groupSize = 1): array
    {
        $entry = Entries::find($record->governableId());
        $entry = $entry instanceof Entry ? $entry : null;
        $fallbackId = $record->ownerId() === null ? app(GeneralSettings::class)->fallbackOwnerId() : null;
        $ownerId = $record->ownerId() ?? $fallbackId;
        $owner = $ownerId ? User::find($ownerId) : null;
        $owner = $owner instanceof StatamicUser ? $owner : null;

        return [
            'id' => $record->id(),
            'title' => (string) ($entry?->value('title') ?? $record->governableId()),
            'entry_url' => $entry?->editUrl(),
            'detail_url' => cp_route('governance.records.show', $record->id()),
            'published' => (bool) $entry?->published(),
            'site' => Site::get($record->site())?->name() ?? $record->site(),
            'collection' => $record->collection(),
            'owner' => $owner?->name(),
            'owner_is_fallback' => $owner !== null && $fallbackId !== null,
            'status' => $record->status()->value,
            'status_label' => $record->status()->label(),
            'group_status' => $groupStatus?->value,
            'group_status_label' => $groupStatus?->label(),
            'flags' => array_map(fn (Flag $flag) => GovernanceFlags::label($flag), $record->flags()),
            'next_review_at' => $record->nextReviewAt()?->toIso8601ZuluString(),
            'expires_at' => $record->expiresAt()?->toIso8601ZuluString(),
            'next_action_at' => $record->nextActionAt()?->toIso8601ZuluString(),
            'is_localisation' => $isLocalisation,
            'group_size' => $groupSize,
        ];
    }
}
