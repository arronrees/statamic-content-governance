<?php

namespace Arronrees\ContentGovernance\Records;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Every write bumps a version, so cached dashboard counts never outlive the data they describe.
 * Counts also expire, so superseded versions don't pile up in the cache, and changes made outside
 * the addon (records pulled in from Git) show within the hour.
 */
class RecordCounts
{
    private const VERSION_KEY = 'statamic.governance.counts.version';

    private const TTL_SECONDS = 3600;

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }

    public function remember(string $key, callable $callback): mixed
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn () => (string) Str::uuid());

        return Cache::remember("statamic.governance.counts.{$version}.{$key}", self::TTL_SECONDS, $callback);
    }
}
