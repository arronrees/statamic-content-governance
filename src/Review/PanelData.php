<?php

namespace Arronrees\ContentGovernance\Review;

use Arronrees\ContentGovernance\Actions\MarkReviewed;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Statamic\Entries\Entry;
use Statamic\Facades\Action;

class PanelData
{
    public function __construct(private GovernanceRecordRepository $records) {}

    public function forEntry(?Entry $entry): array
    {
        $record = $entry?->id() ? $this->records->findByGovernable('entry', $entry->id(), $entry->locale()) : null;

        return $record ? $this->forRecord($record) : ['record' => null];
    }

    public function forRecord(GovernanceRecord $record): array
    {
        return [
            'record' => ReviewRow::for($record, false),
            'actions' => Action::for($record, ['view' => 'form'])
                ->filter(fn ($action) => $action instanceof MarkReviewed)
                ->values(),
            'actionUrl' => cp_route('governance.records.actions.run'),
        ];
    }
}
