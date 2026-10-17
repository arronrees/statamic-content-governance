<?php

namespace Arronrees\ContentGovernance\Status;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Rules\CollectionRules;
use Arronrees\ContentGovernance\Support\SiteTimezone;

class RecordStatus
{
    public function __construct(
        private StatusCalculator $calculator,
        private CollectionRules $rules,
        private SiteTimezone $timezones,
    ) {}

    public function evaluate(GovernanceRecord $record): StatusResult
    {
        return $this->calculator->calculate(
            $record,
            $this->rules->for($record->collection()),
            $this->timezones->for($record->site()),
        );
    }
}
