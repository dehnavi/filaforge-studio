<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
final class PaginationWidget implements Widget
{
    public function name(): string {return 'data-pagination';}
    public function version(): int {return 1;}
    public function acceptsChildren(): bool {return false;}
    public function view(): string {return 'filaforge::widgets.data-pagination';}
    public function defaults(): array {return ['node'=>'root','page'=>1,'last'=>1];}
    public function validate(array $props): void
    {
        if(array_diff(array_keys($props),array_keys($this->defaults()))||array_diff(array_keys($this->defaults()),array_keys($props))||!is_string($props['node'])||!preg_match('/^[A-Za-z0-9_-]{1,80}$/D',$props['node']))throw new \InvalidArgumentException('Invalid pagination.');
        foreach(['page','last'] as $key)if(!is_int($props[$key])||$props[$key]<1||$props[$key]>1000)throw new \InvalidArgumentException('Invalid page.');
    }
}
