<?php

namespace Arronrees\ContentGovernance\Records;

use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Status\RecordStatus;
use Arronrees\ContentGovernance\Status\StatusResult;
use Arronrees\ContentGovernance\Status\Transitions;

abstract class BaseRecordRepository implements GovernanceRecordRepository
{
    public function __construct(
        private RecordStatus $status,
        private Transitions $transitions,
        private RecordCounts $counts,
    ) {}

    final public function save(GovernanceRecord $record): StatusResult
    {
        $before = $record->status();
        $result = $this->status->evaluate($record);

        $record->ensureId()->status($result->status)->nextActionAt($result->nextActionAt);

        $this->persist($record);
        $this->counts->invalidate();

        $this->transitions->after($record, $before, $result);

        return $result;
    }

    final public function import(GovernanceRecord $record): void
    {
        $this->persist($record);
        $this->counts->invalidate();
    }

    final public function delete(GovernanceRecord $record): void
    {
        $this->remove($record);
        $this->counts->invalidate();
    }

    abstract protected function persist(GovernanceRecord $record): void;

    abstract protected function remove(GovernanceRecord $record): void;
}
