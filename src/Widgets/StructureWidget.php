<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
final class StructureWidget implements Widget {
 public function __construct(private string $kind) {}
 public function name(): string { return $this->kind; }
 public function version(): int { return 1; }
 public function acceptsChildren(): bool { return in_array($this->kind,['grid','columns']); }
 public function view(): string { return 'filaforge::widgets.'.$this->kind; }
 public function defaults(): array { return match($this->kind){'grid'=>['columns'=>3,'gap'=>24],'columns'=>['columns'=>2,'gap'=>24],'icon'=>['name'=>'star','size'=>40,'color'=>'indigo'],'table'=>['rows'=>[['عنوان اول','عنوان دوم'],['مقدار اول','مقدار دوم']],'caption'=>'جدول نمونه'],'video'=>['src'=>'','poster'=>'','controls'=>true],'quote'=>['text'=>'یک جملهٔ الهام‌بخش بنویسید.','author'=>'نام نویسنده']}; }
 public function validate(array $props): void {
  if(array_diff(array_keys($props),[...array_keys($this->defaults()),'formatted'])||array_diff(array_keys($this->defaults()),array_keys($props)))throw new \InvalidArgumentException('Invalid properties.');
  if(array_key_exists('formatted',$props)&&$this->kind!=='table') \FilaForge\Studio\Rendering\RichText::validateFormatted($props['formatted'],$this->kind==='quote'?['text','author']:[]);
  if($this->acceptsChildren()){if(!is_int($props['columns'])||$props['columns']<1||$props['columns']>6||!is_int($props['gap'])||$props['gap']<0||$props['gap']>100)throw new \InvalidArgumentException('Invalid grid.');return;}
  if($this->kind==='icon'){if((!is_string($props['name'])||!Icons::get($props['name']))||(!is_string($props['color'])||(!in_array($props['color'],['indigo','red','green','dark'],true)&&!preg_match('/^#[0-9a-f]{6}$/iD',$props['color'])))||!is_int($props['size'])||$props['size']<12||$props['size']>160)throw new \InvalidArgumentException('Invalid icon.');return;}
  if($this->kind==='table'){if(!is_array($props['rows'])||!array_is_list($props['rows'])||!count($props['rows'])||count($props['rows'])>30)throw new \InvalidArgumentException('Invalid rows.');$width=count($props['rows'][0]);if($width<1||$width>10)throw new \InvalidArgumentException('Invalid columns.');foreach($props['rows'] as $row){if(!is_array($row)||!array_is_list($row)||count($row)!==$width)throw new \InvalidArgumentException('Unequal columns.');foreach($row as $cell)if(!is_string($cell)||mb_strlen($cell)>1000)throw new \InvalidArgumentException('Invalid cell.');}if(array_key_exists('formatted',$props)){ $fields=['caption'];foreach($props['rows'] as $i=>$row)foreach($row as $j=>$cell)$fields[]='cell:'.$i.':'.$j; \FilaForge\Studio\Rendering\RichText::validateFormatted($props['formatted'],$fields); }}
  foreach($props as $key=>$value){if(in_array($key,['rows','controls','formatted']))continue;if(!is_string($value)||mb_strlen($value)>10000)throw new \InvalidArgumentException('Invalid text.');}
  if($this->kind==='video'){if(!is_bool($props['controls']))throw new \InvalidArgumentException('Invalid controls.');foreach(['src','poster'] as $key)if($props[$key]!==''&&!preg_match('/^media:[0-9A-HJKMNP-TV-Z]{26}$/iD',$props[$key])&&!preg_match('~^(https?://[^\s<>]+|/[^/][^\s<>]*)$~D',$props[$key]))throw new \InvalidArgumentException('Use HTTP(S) video or poster URL.');}
 }
}
