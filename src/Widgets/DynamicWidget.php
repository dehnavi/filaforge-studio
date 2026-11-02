<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
final class DynamicWidget implements Widget
{
    public function __construct(private string $kind) {}
    public function name(): string {return $this->kind;}
    public function version(): int {return 1;}
    public function acceptsChildren(): bool {return $this->kind==='query-loop';}
    public function view(): string {return 'filaforge::widgets.'.$this->kind;}
    public function defaults(): array {return $this->kind==='query-loop'?['provider'=>'company','query'=>['limit'=>6],'emptyText'=>'']:['name'=>'contact-card','arguments'=>[]];}
    public function validate(array $props): void
    {
        if(array_diff(array_keys($props),array_keys($this->defaults()))||array_diff(array_keys($this->defaults()),array_keys($props)))throw new \InvalidArgumentException('Invalid dynamic widget.');
        if($this->kind==='query-loop'){
            if(!is_string($props['provider'])||!preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$props['provider'])||!is_array($props['query'])||!is_string($props['emptyText'])||mb_strlen($props['emptyText'])>1000)throw new \InvalidArgumentException('Invalid loop.');
        }elseif(!is_string($props['name'])||!preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$props['name'])||!is_array($props['arguments']))throw new \InvalidArgumentException('Invalid shortcode.');
    }
}
