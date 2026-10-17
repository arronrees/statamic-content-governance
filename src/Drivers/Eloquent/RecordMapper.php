<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Arronrees\ContentGovernance\Records\GovernanceRecord;

class RecordMapper
{
    private const DATES = ['last_reviewed_at', 'next_review_at', 'expires_at', 'next_action_at'];

    public static function toRecord(GovernanceRecordModel $model): GovernanceRecord
    {
        $values = $model->getAttributes();
        $values['flags'] = $model->flags ?? [];

        foreach (self::DATES as $column) {
            $values[$column] = Dates::fromDatabase($values[$column] ?? null)?->toIso8601ZuluString();
        }

        return GovernanceRecord::fromArray($values);
    }

    public static function toAttributes(GovernanceRecord $record): array
    {
        $values = $record->toArray();

        foreach (self::DATES as $column) {
            $values[$column] = Dates::toDatabase(Dates::fromDatabase($values[$column]));
        }

        return $values;
    }
}
