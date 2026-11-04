<?php

namespace FilaForge\Studio\Widgets;

use FilaForge\Studio\Contracts\Widget;

final class HtmlWidget implements Widget
{
    public function name(): string { return 'html'; }
    public function version(): int { return 1; }
    public function acceptsChildren(): bool { return false; }
    public function view(): string { return 'filaforge::widgets.html'; }
    public function defaults(): array { return ['html' => '<p>HTML</p>']; }
    public function validate(array $props): void
    {
        if (array_keys($props) !== ['html'] || !is_string($props['html']) || strlen($props['html']) > 100000) throw new \InvalidArgumentException('Invalid HTML block.');
    }
}
