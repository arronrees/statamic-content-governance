<?php

namespace Arronrees\ContentGovernance\Audit;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class AuditEvent
{
    public function __construct(
        public string $id,
        public string $recordId,
        public string $governableId,
        public string $site,
        public AuditEventType $type,
        public ?string $actorId,
        public array $payload,
        public CarbonImmutable $createdAt,
    ) {}

    public static function for(GovernanceRecord $record, AuditEventType $type, ?string $actorId, CarbonImmutable $at, array $payload = []): self
    {
        return new self(
            id: (string) Str::uuid(),
            recordId: $record->id(),
            governableId: $record->governableId(),
            site: $record->site(),
            type: $type,
            actorId: $actorId,
            payload: $payload,
            createdAt: $at->utc(),
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            id: $values['id'],
            recordId: $values['record_id'],
            governableId: $values['governable_id'],
            site: $values['site'],
            type: AuditEventType::from($values['type']),
            actorId: $values['actor_id'] ?? null,
            payload: $values['payload'] ?? [],
            createdAt: CarbonImmutable::parse($values['created_at'], 'UTC'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'record_id' => $this->recordId,
            'governable_id' => $this->governableId,
            'site' => $this->site,
            'type' => $this->type->value,
            'actor_id' => $this->actorId,
            'payload' => $this->payload,
            'created_at' => $this->createdAt->toIso8601ZuluString('microsecond'),
        ];
    }
}
