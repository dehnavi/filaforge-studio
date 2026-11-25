<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;
/** A third-party embed code (iframe or external script) from any provider. Rendering keeps only what EmbedCode allows. */
final class EmbedWidget implements Widget
{
    public function name(): string { return 'embed'; }
    public function version(): int { return 1; }
    public function defaults(): array { return ['code' => '', 'title' => '']; }
    public function acceptsChildren(): bool { return false; }
    public function view(): string { return 'filaforge::widgets.embed'; }
    public function validate(array $props): void
    {
        if (array_keys($props) !== ['code', 'title'] || !is_string($props['code']) || strlen($props['code']) > \FilaForge\Studio\Rendering\EmbedCode::MAX_LENGTH || !is_string($props['title']) || mb_strlen($props['title']) > 200) {
            throw new InvalidArgumentException('Invalid embed properties.');
        }
    }
}
