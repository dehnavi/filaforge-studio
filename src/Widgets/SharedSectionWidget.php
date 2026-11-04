<?php

namespace FilaForge\Studio\Widgets;

use FilaForge\Studio\Contracts\Widget;

final class SharedSectionWidget implements Widget
{
    public function name(): string { return 'shared-section'; }
    public function version(): int { return 1; }
    public function acceptsChildren(): bool { return false; }
    public function view(): string { return 'filaforge::widgets.container'; }
    public function defaults(): array { return ['key'=>'','values'=>[]]; }
    public function validate(array $props): void
    {
        if (array_diff(array_keys($props),['key','values']) || !is_string($props['key'] ?? null) || !preg_match('/^[a-z][a-z0-9-]{0,63}$/D', $props['key'])) throw new \InvalidArgumentException('Select a registered shared section.');
        $values = $props['values'] ?? [];
        if (!is_array($values) || count($values)>20 || ($values !== [] && array_is_list($values))) throw new \InvalidArgumentException('Invalid section overrides.');
        foreach ($values as $key => $value) if (!is_string($key) || !preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$key) || (!is_scalar($value) && $value !== null) || (is_string($value) && mb_strlen($value)>20000)) throw new \InvalidArgumentException('Invalid section override.');
    }
}
