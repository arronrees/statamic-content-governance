<?php

namespace Arronrees\ContentGovernance\Contracts;

use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Status\StatusResult;

interface GovernanceRecordRepository
{
    public function make(): GovernanceRecord;

    public function query(): GovernanceQueryBuilder;

    public function find(string $id): ?GovernanceRecord;

    public function findByGovernable(string $type, string $id, string $site): ?GovernanceRecord;

    /**
     * Recalculates the record's status, stores it, and returns the calculation.
     */
    public function save(GovernanceRecord $record): StatusResult;

    public function import(GovernanceRecord $record): void;

    public function delete(GovernanceRecord $record): void;

    /**
     * @return array<string, array<string, int>> Counts keyed by collection, then status.
     */
    public function statusCounts(?string $site = null): array;

    /**
     * @param  list<string>|null  $collections  Limit to these collections; null for all.
     */
    public function flaggedCount(Flag $flag, ?string $site = null, ?array $collections = null): int;
}
