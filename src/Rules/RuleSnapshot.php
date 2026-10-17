<?php

namespace Arronrees\ContentGovernance\Rules;

class RuleSnapshot
{
    private ?array $before = null;

    public function take(array $rows): void
    {
        $this->before = $rows;
    }

    /**
     * @return array{enabled: list<string>, changed: list<string>}
     */
    public function compare(array $after): array
    {
        $before = collect($this->before ?? [])->keyBy('collection');
        $after = collect($after)->keyBy('collection');
        $this->before = null;

        $isEnabled = fn (?array $row) => (bool) ($row['enabled'] ?? false);

        $enabled = $after->filter(fn ($row, $handle) => $isEnabled($row) && ! $isEnabled($before->get($handle)))->keys();
        $changed = $before->keys()->merge($after->keys())->unique()
            ->filter(fn ($handle) => $before->get($handle) != $after->get($handle));

        return [
            'enabled' => $enabled->values()->all(),
            'changed' => $changed->values()->all(),
        ];
    }
}
