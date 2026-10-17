<?php

namespace Arronrees\ContentGovernance\Drivers\File;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Statamic\Contracts\Query\ContainsQueryableValues;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Stache;
use Statamic\Support\Str;

class StacheGovernanceRecord extends GovernanceRecord implements ContainsQueryableValues
{
    use ExistsAsFile, TracksQueriedColumns, TracksQueriedRelations;

    public static function fromRecord(GovernanceRecord $record): self
    {
        return $record instanceof self ? $record : self::fromArray($record->toArray());
    }

    public function path()
    {
        return vsprintf('%s/%s/%s/%s.yaml', [
            rtrim(Stache::store(GovernanceStore::KEY)->directory(), '/'),
            $this->site(),
            $this->collection(),
            $this->governableId(),
        ]);
    }

    public function fileData()
    {
        return $this->toArray();
    }

    public function getQueryableValue(string $field)
    {
        $values = $this->toArray();

        if (array_key_exists($field, $values)) {
            return $values[$field];
        }

        // The Stache indexes more than our fields; its id-to-path map is an index called "path".
        $method = Str::camel($field);

        return method_exists($this, $method) ? $this->{$method}() : null;
    }
}
