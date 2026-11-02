<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;
final class FlowWidget implements Widget
{
    public function __construct(private string $kind) {}
    public function name(): string {return $this->kind;}
    public function version(): int {return 1;}
    public function acceptsChildren(): bool {return $this->kind!=='progress';}
    public function view(): string {return 'filaforge::widgets.'.$this->kind;}
    public function defaults(): array
    {
        return match($this->kind){
            'progress'=>['label'=>__('filaforge::studio.progress_label'),'value'=>65,'color'=>'#4f46e5','height'=>12,'showValue'=>true],
            'wizard'=>['previous'=>__('filaforge::studio.wizard_previous'),'next'=>__('filaforge::studio.wizard_next'),'start'=>0],
            'wizard-step'=>['title'=>__('filaforge::studio.wizard_step')],
        };
    }
    public function validate(array $props): void
    {
        $defaults=$this->defaults();$formatted=$this->kind==='progress'?['formatted']:[];
        if(array_diff(array_keys($props),[...array_keys($defaults),...$formatted])||array_diff(array_keys($defaults),array_keys($props)))throw new InvalidArgumentException('Invalid flow properties.');
        foreach($defaults as $key=>$value)if(is_string($value)&&(!is_string($props[$key])||mb_strlen($props[$key])>200))throw new InvalidArgumentException('Invalid flow label.');
        if($this->kind==='progress'){
            if(!is_int($props['value'])||$props['value']<0||$props['value']>100||!is_int($props['height'])||$props['height']<8||$props['height']>40||!is_bool($props['showValue'])||!preg_match('/^#[0-9a-f]{6}$/iD',$props['color']))throw new InvalidArgumentException('Invalid progress settings.');
            if(isset($props['formatted']))\FilaForge\Studio\Rendering\RichText::validateFormatted($props['formatted'],['label']);
        }
        if($this->kind==='wizard'&&(!is_int($props['start'])||$props['start']<0||$props['start']>11))throw new InvalidArgumentException('Invalid initial wizard step.');
    }
}
