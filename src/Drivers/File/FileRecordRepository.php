<?php

namespace Arronrees\ContentGovernance\Drivers\File;

use Arronrees\ContentGovernance\Contracts\GovernanceQueryBuilder;
use Arronrees\ContentGovernance\Exceptions\DuplicateRecord;
use Arronrees\ContentGovernance\Records\BaseRecordRepository;
use Arronrees\ContentGovernance\Records\Flag;
use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;

class FileRecordRepository extends BaseRecordRepository
{
    public function make(): GovernanceRecord
    {
        return StacheGovernanceRecord::make();
    }

    public function query(): GovernanceQueryBuilder
    {
        return new StacheRecordQueryBuilder($this->store());
    }

    public function find(string $id): ?GovernanceRecord
    {
        $record = $this->store()->getItem($id);

        return $record ? clone $record : null;
    }

    public function findByGovernable(string $type, string $id, string $site): ?GovernanceRecord
    {
        return $this->query()
            ->where('governable_type', $type)
            ->where('governable_id', $id)
            ->where('site', $site)
            ->first();
    }

    protected function remove(GovernanceRecord $record): void
    {
        $this->store()->delete(StacheGovernanceRecord::fromRecord($record));
    }

    protected function persist(GovernanceRecord $record): void
    {
        $item = StacheGovernanceRecord::fromRecord($record);

        // One file per entry and site: a second record would silently overwrite the first. Reading that one
        // file is O(1); asking the indexes would scan every record on every save.
        $existingId = File::exists($item->path()) ? (YAML::file($item->path())->parse()['id'] ?? null) : null;

        if ($existingId !== null && $existingId !== $record->id()) {
            throw DuplicateRecord::for($record->governableType(), $record->governableId(), $record->site());
        }

        $this->store()->save($item);
    }

    public function statusCounts(?string $site = null): array
    {
        $collections = $this->store()->index('collection')->items();
        $sites = $this->store()->index('site')->items();

        $counts = [];

        foreach ($this->store()->index('status')->items() as $key => $status) {
            if ($site !== null && $sites->get($key) !== $site) {
                continue;
            }

            $collection = $collections->get($key);
            $counts[$collection][$status] = ($counts[$collection][$status] ?? 0) + 1;
        }

        return $counts;
    }

    public function flaggedCount(Flag $flag, ?string $site = null, ?array $collections = null): int
    {
        $sites = $this->store()->index('site')->items();
        $inCollections = $this->store()->index('collection')->items();

        return $this->store()->index('flags')->items()
            ->filter(fn ($flags, $key) => in_array($flag->value, $flags ?? [], true)
                && ($site === null || $sites->get($key) === $site)
                && ($collections === null || in_array($inCollections->get($key), $collections, true)))
            ->count();
    }

    private function store(): GovernanceStore
    {
        return Stache::store(GovernanceStore::KEY);
    }
}
