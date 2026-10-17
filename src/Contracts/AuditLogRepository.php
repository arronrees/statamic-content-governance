<?php

namespace Arronrees\ContentGovernance\Contracts;

use Arronrees\ContentGovernance\Audit\AuditEvent;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

interface AuditLogRepository
{
    public function append(AuditEvent $event): void;

    /**
     * @return Collection<int, AuditEvent>
     */
    public function forRecord(GovernanceRecord $record): Collection;

    /**
     * @return Collection<int, AuditEvent>
     */
    public function recent(int $limit, ?string $site = null): Collection;

    /**
     * @return LazyCollection<int, AuditEvent>
     */
    public function all(): LazyCollection;
}
