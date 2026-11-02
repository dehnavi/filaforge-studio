<?php
namespace FilaForge\Studio\Rendering;
final class Colors {
 public static function background(string $tone):string {return ['white'=>'#ffffff','soft'=>'#f5f4ff','brand'=>'#eeedff'][$tone]??(preg_match('/^#[0-9a-f]{6}$/iD',$tone)?$tone:'#ffffff');}
 public static function foreground(string $tone):string {$hex=ltrim(self::background($tone),'#');$l=0;foreach([0.2126,0.7152,0.0722] as $i=>$weight){$v=hexdec(substr($hex,$i*2,2))/255;$l+=$weight*($v<=0.04045?$v/12.92:(($v+0.055)/1.055)**2.4);}return 1.05/($l+0.05)>($l+0.05)/0.065?'#ffffff':'#18212f';}
}
