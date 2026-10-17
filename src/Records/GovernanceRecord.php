<?php

namespace Arronrees\ContentGovernance\Records;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class GovernanceRecord
{
    use FluentlyGetsAndSets;

    protected ?string $id = null;

    protected string $governableType = 'entry';

    protected ?string $governableId = null;

    protected ?string $site = null;

    protected ?string $collection = null;

    protected ?string $originId = null;

    protected ?string $ownerId = null;

    protected ?int $reviewIntervalDays = null;

    protected ?CarbonImmutable $lastReviewedAt = null;

    protected ?string $lastReviewedBy = null;

    protected ?CarbonImmutable $nextReviewAt = null;

    protected ?CarbonImmutable $expiresAt = null;

    protected Status $status = Status::Current;

    /** @var array<string, Flag> */
    protected array $flags = [];

    protected ?CarbonImmutable $nextActionAt = null;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    public function id($id = null)
    {
        return $this->fluentlyGetOrSet('id')->args(func_get_args());
    }

    public function ensureId(): static
    {
        $this->id ??= (string) Str::uuid();

        return $this;
    }

    public function governableType($type = null)
    {
        return $this->fluentlyGetOrSet('governableType')->args(func_get_args());
    }

    public function governableId($id = null)
    {
        return $this->fluentlyGetOrSet('governableId')->args(func_get_args());
    }

    public function site($site = null)
    {
        return $this->fluentlyGetOrSet('site')->args(func_get_args());
    }

    public function collection($collection = null)
    {
        return $this->fluentlyGetOrSet('collection')->args(func_get_args());
    }

    public function originId($id = null)
    {
        return $this->fluentlyGetOrSet('originId')->args(func_get_args());
    }

    public function ownerId($id = null)
    {
        return $this->fluentlyGetOrSet('ownerId')->args(func_get_args());
    }

    public function reviewIntervalDays($days = null)
    {
        return $this->fluentlyGetOrSet('reviewIntervalDays')->args(func_get_args());
    }

    public function lastReviewedAt($date = null)
    {
        return $this->fluentlyGetOrSet('lastReviewedAt')
            ->setter(fn ($date) => $this->toUtc($date))
            ->args(func_get_args());
    }

    public function lastReviewedBy($userId = null)
    {
        return $this->fluentlyGetOrSet('lastReviewedBy')->args(func_get_args());
    }

    public function nextReviewAt($date = null)
    {
        return $this->fluentlyGetOrSet('nextReviewAt')
            ->setter(fn ($date) => $this->toUtc($date))
            ->args(func_get_args());
    }

    public function expiresAt($date = null)
    {
        return $this->fluentlyGetOrSet('expiresAt')
            ->setter(fn ($date) => $this->toUtc($date))
            ->args(func_get_args());
    }

    public function status($status = null)
    {
        return $this->fluentlyGetOrSet('status')
            ->setter(fn ($status) => $status instanceof Status ? $status : Status::from($status))
            ->args(func_get_args());
    }

    public function nextActionAt($date = null)
    {
        return $this->fluentlyGetOrSet('nextActionAt')
            ->setter(fn ($date) => $this->toUtc($date))
            ->args(func_get_args());
    }

    /**
     * @return list<Flag>
     */
    public function flags(): array
    {
        return array_values($this->flags);
    }

    public function hasFlag(Flag $flag): bool
    {
        return isset($this->flags[$flag->value]);
    }

    public function addFlag(Flag $flag): static
    {
        $this->flags[$flag->value] = $flag;

        return $this;
    }

    public function removeFlag(Flag $flag): static
    {
        unset($this->flags[$flag->value]);

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'governable_type' => $this->governableType,
            'governable_id' => $this->governableId,
            'site' => $this->site,
            'collection' => $this->collection,
            'origin_id' => $this->originId,
            'owner_id' => $this->ownerId,
            'review_interval_days' => $this->reviewIntervalDays,
            'last_reviewed_at' => $this->lastReviewedAt?->toIso8601ZuluString(),
            'last_reviewed_by' => $this->lastReviewedBy,
            'next_review_at' => $this->nextReviewAt?->toIso8601ZuluString(),
            'expires_at' => $this->expiresAt?->toIso8601ZuluString(),
            'status' => $this->status->value,
            'flags' => array_map(fn (Flag $flag) => $flag->value, $this->flags()),
            'next_action_at' => $this->nextActionAt?->toIso8601ZuluString(),
        ];
    }

    public static function fromArray(array $values): static
    {
        $record = static::make()
            ->id($values['id'] ?? null)
            ->governableType($values['governable_type'] ?? 'entry')
            ->governableId($values['governable_id'] ?? null)
            ->site($values['site'] ?? null)
            ->collection($values['collection'] ?? null)
            ->originId($values['origin_id'] ?? null)
            ->ownerId($values['owner_id'] ?? null)
            ->reviewIntervalDays(isset($values['review_interval_days']) ? (int) $values['review_interval_days'] : null)
            ->lastReviewedAt(self::parseDate($values['last_reviewed_at'] ?? null))
            ->lastReviewedBy($values['last_reviewed_by'] ?? null)
            ->nextReviewAt(self::parseDate($values['next_review_at'] ?? null))
            ->expiresAt(self::parseDate($values['expires_at'] ?? null))
            ->status($values['status'] ?? Status::Current)
            ->nextActionAt(self::parseDate($values['next_action_at'] ?? null));

        foreach ($values['flags'] ?? [] as $flag) {
            $record->addFlag(Flag::from($flag));
        }

        return $record;
    }

    private static function parseDate(?string $date): ?CarbonImmutable
    {
        return $date === null ? null : CarbonImmutable::parse($date, 'UTC');
    }

    private function toUtc(?DateTimeInterface $date): ?CarbonImmutable
    {
        if ($date === null) {
            return null;
        }

        return CarbonImmutable::instance($date)->utc()->startOfSecond();
    }
}
