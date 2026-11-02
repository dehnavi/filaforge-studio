<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;
final class Heading implements Widget
{
    public function name(): string { return 'heading'; }
    public function version(): int { return 1; }
    public function defaults(): array { return ['text' => 'Heading', 'tag' => 'h2']; }
    public function acceptsChildren(): bool { return false; }
    public function view(): string { return 'filaforge::widgets.heading'; }
    public function validate(array $props): void
    {
        if (array_diff(array_keys($props), ['text', 'tag', 'formatted']) || !is_string($props['text'] ?? null) || mb_strlen($props['text']) > 10000 || !in_array($props['tag'] ?? null, ['h1','h2','h3','h4','h5','h6'], true)) {
            throw new InvalidArgumentException('Invalid heading properties.');
        }
        if (array_key_exists('formatted', $props)) \FilaForge\Studio\Rendering\RichText::validateFormatted($props['formatted'], ['text']);
    }
}
