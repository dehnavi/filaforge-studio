<?php
namespace FilaForge\Studio\Core;
use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;
final class WidgetRegistry
{
    private array $widgets = [];
    public function register(Widget $widget): void
    {
        if (!preg_match('/^[a-z][a-z0-9-]*$/D', $widget->name()) || isset($this->widgets[$widget->name()])) {
            throw new InvalidArgumentException('Invalid or duplicate widget name.');
        }
        $this->widgets[$widget->name()] = $widget;
    }
    public function get(string $name): Widget { return $this->widgets[$name] ?? throw new InvalidArgumentException('Unknown widget: '.$name); }
    public function all(): array { return $this->widgets; }
}
