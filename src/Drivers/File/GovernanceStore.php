<?php

namespace Arronrees\ContentGovernance\Drivers\File;

use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;

class GovernanceStore extends BasicStore
{
    public const KEY = 'governance';

    protected $storeIndexes = [
        'governable_type',
        'governable_id',
        'site',
        'collection',
        'origin_id',
        'owner_id',
        'status',
        'flags',
        'next_review_at',
        'expires_at',
        'next_action_at',
    ];

    public function key()
    {
        return self::KEY;
    }

    public function getItemKey($item)
    {
        return $item->id();
    }

    public function makeItemFromFile($path, $contents)
    {
        return StacheGovernanceRecord::fromArray(YAML::file($path)->parse($contents))->initialPath($path);
    }
}
