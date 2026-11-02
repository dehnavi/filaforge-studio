<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;
final class MenuWidget implements Widget
{
    public function __construct(private string $kind){}
    public function name():string{return $this->kind;}
    public function version():int{return 1;}
    public function acceptsChildren():bool{return true;}
    public function view():string{return 'filaforge::widgets.'.$this->kind;}
    public function defaults():array{return $this->kind==='dropdown-menu'?['brand'=>__('filaforge::studio.menu_brand'),'toggle'=>__('filaforge::studio.menu_toggle'),'collapseAt'=>'tablet']:['label'=>__('filaforge::studio.menu_item'),'url'=>'#'];}
    public function validate(array $props):void
    {
        $d=$this->defaults();if(array_diff(array_keys($props),[...array_keys($d),'formatted'])||array_diff(array_diff(array_keys($d),['collapseAt']),array_keys($props)))throw new InvalidArgumentException('Invalid menu properties.');
        foreach($d as $key=>$value)if(array_key_exists($key,$props)&&(!is_string($props[$key])||mb_strlen($props[$key])>($key==='url'?2048:200)))throw new InvalidArgumentException('Invalid menu value.');
        if(isset($props['collapseAt'])&&!in_array($props['collapseAt'],['mobile','tablet','never'],true))throw new InvalidArgumentException('Invalid menu collapse breakpoint.');
        if(isset($props['formatted']))\FilaForge\Studio\Rendering\RichText::validateFormatted($props['formatted'],$this->kind==='dropdown-menu'?['brand']:['label']);
        if($this->kind==='menu-item'){
            $url=$props['url'];$absolute=filter_var($url,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true);
            $relative=str_starts_with($url,'/')&&!str_starts_with($url,'//');$anchor=preg_match('/^#[A-Za-z0-9_-]*$/D',$url);
            if(preg_match('/[\x00-\x20\x7f<>"\'\\\\]/',$url)||(!$absolute&&!$relative&&!$anchor))throw new InvalidArgumentException('Invalid menu URL.');
        }
    }
}
