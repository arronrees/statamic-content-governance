<?php

namespace Arronrees\ContentGovernance\Http\Controllers\CP;

use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Review\PanelData;
use Statamic\Http\Controllers\CP\ActionController;

class RecordActionController extends ActionController
{
    protected function getSelectedItems($items, $context)
    {
        $records = app(GovernanceRecordRepository::class);

        return $items->map(fn ($id) => $records->find($id))->filter()->values();
    }

    protected function getItemData($item, $context): array
    {
        $fresh = app(GovernanceRecordRepository::class)->find($item->id());

        return $fresh ? app(PanelData::class)->forRecord($fresh) : ['record' => null];
    }
}
