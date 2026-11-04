<?php
namespace FilaForge\Studio\Tests\Fixtures;

use FilaForge\Studio\Contracts\InspectorWidget;
use Filament\Forms\Components\TextInput;

final class BadgeWidget implements InspectorWidget
{
    public function name(): string { return 'badge'; }
    public function version(): int { return 1; }
    public function acceptsChildren(): bool { return false; }
    public function view(): string { return 'filaforge::widgets.text'; }
    public function defaults(): array { return ['text'=>'Badge']; }
    public function validate(array $props): void
    {
        if (array_keys($props)!==['text'] || !is_string($props['text']) || mb_strlen($props['text'])>20) throw new \InvalidArgumentException('Invalid badge');
    }
    public function inspector(): array { return [TextInput::make('text')->label('Badge text')->required()->maxLength(20)]; }
}
