<?php

namespace Arronrees\ContentGovernance\Settings;

use Arronrees\ContentGovernance\ServiceProvider;
use Statamic\Facades\Addon;

/**
 * Statamic re-reads addon settings from disk (or the database) on every call, and governance asks for
 * them on every entry save. Read once, and forget whenever they might have changed.
 */
class AddonSettings
{
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->values ??= Addon::get(ServiceProvider::ADDON)->settings()->all();

        return $this->values[$key] ?? $default;
    }

    public function forget(): void
    {
        $this->values = null;
    }
}
