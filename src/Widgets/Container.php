<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;
final class Container implements Widget
{
    public function name(): string { return 'container'; }
    public function version(): int { return 1; }
    public function defaults(): array { return ['direction' => 'column']; }
    public function acceptsChildren(): bool { return true; }
    public function view(): string { return 'filaforge::widgets.container'; }
    public function validate(array $props): void
    {
        if (array_diff(array_keys($props), ['direction']) || !in_array($props['direction'] ?? null, ['column', 'row'], true)) {
            throw new InvalidArgumentException('Invalid container properties.');
        }
    }
}
