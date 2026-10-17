<?php

namespace Arronrees\ContentGovernance\Rules;

use Arronrees\ContentGovernance\Records\GovernanceRecord;

final readonly class CollectionRule
{
    public function __construct(
        public string $collection,
        public bool $enabled = true,
        public int $reviewIntervalDays = 180,
        public int $dueWindowDays = 14,
        public bool $expiryEnabled = false,
        public ?int $defaultExpiryDays = null,
        public int $expiryWarningDays = 14,
        public int $graceDays = 0,
        public bool $autoUnpublish = false,
        public OwnerStrategy $defaultOwnerStrategy = OwnerStrategy::Author,
        public ?string $defaultOwnerId = null,
        public bool $editResetsReview = false,
        public bool $localisationsInheritOwner = true,
    ) {}

    public static function fromArray(array $values): self
    {
        return new self(
            collection: $values['collection'],
            enabled: (bool) ($values['enabled'] ?? false),
            reviewIntervalDays: (int) ($values['review_interval_days'] ?? 180),
            dueWindowDays: (int) ($values['due_window_days'] ?? 14),
            expiryEnabled: (bool) ($values['expiry_enabled'] ?? false),
            defaultExpiryDays: isset($values['default_expiry_days']) ? (int) $values['default_expiry_days'] : null,
            expiryWarningDays: (int) ($values['expiry_warning_days'] ?? 14),
            // Auto-unpublish is switched off from settings until phase 2 (see plan/decisions.md).
            defaultOwnerStrategy: OwnerStrategy::tryFrom($values['default_owner_strategy'] ?? '') ?? OwnerStrategy::Author,
            defaultOwnerId: $values['default_owner_id'] ?? null,
            editResetsReview: (bool) ($values['edit_resets_review'] ?? false),
            localisationsInheritOwner: (bool) ($values['localisations_inherit_owner'] ?? true),
        );
    }

    public function reviewIntervalFor(GovernanceRecord $record): int
    {
        return $record->reviewIntervalDays() ?? $this->reviewIntervalDays;
    }
}
