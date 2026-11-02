<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
final class PresetWidget implements Widget {
 public function __construct(private string $kind){}
 public function name():string{return $this->kind;}
 public function version():int{return 1;}
 public function acceptsChildren():bool{return in_array($this->kind,['section','card','navigation','slider','carousel']);}
 public function view():string{return 'filaforge::widgets.'.$this->kind;}
 public function defaults():array{return match($this->kind){'section'=>['anchor'=>'','tone'=>'soft','padding'=>48,'align'=>'start'],'card'=>['tone'=>'white','padding'=>24,'align'=>'start'],'navigation'=>['brand'=>'FilaForge'],'link'=>['text'=>'لینک','url'=>'#'],'slider'=>['autoplay'=>false,'interval'=>5000,'perView'=>1],'carousel'=>['autoplay'=>false,'interval'=>5000,'perView'=>3],'comments'=>['title'=>'دیدگاه شما','button'=>'ارسال دیدگاه'],'faq-item'=>['question'=>'سؤال خود را بنویسید','answer'=>'پاسخ سؤال را اینجا ویرایش کنید.']};}
 public function validate(array $p):void {
  $d=$this->defaults();if(array_diff(array_keys($p),[...array_keys($d),'formatted'])||array_diff(array_diff(array_keys($d),['anchor']),array_keys($p)))throw new \InvalidArgumentException('Invalid preset widget properties.');
  if(array_key_exists('formatted',$p)) { $fields=match($this->kind){'navigation'=>['brand'],'link'=>['text'],'comments'=>['title','button'],'faq-item'=>['question','answer'],default=>[]}; \FilaForge\Studio\Rendering\RichText::validateFormatted($p['formatted'],$fields); }
  if(isset($p['anchor'])&&(!is_string($p['anchor'])||!preg_match('/^[a-zA-Z0-9_-]*$/D',$p['anchor'])))throw new \InvalidArgumentException('Invalid anchor.');
  foreach($p as $key=>$value){if($key==='formatted')continue;if(is_string($d[$key])&&(!is_string($value)||mb_strlen($value)>4000))throw new \InvalidArgumentException('Invalid text.');}
  if(in_array($this->kind,['section','card'])&&((!is_string($p['tone'])||(!in_array($p['tone'],['white','soft','brand'],true)&&!preg_match('/^#[0-9a-f]{6}$/iD',$p['tone'])))||!is_int($p['padding'])||$p['padding']<8||$p['padding']>96||!in_array($p['align'],['start','center'],true)))throw new \InvalidArgumentException('Invalid section.');
  if(in_array($this->kind,['slider','carousel'])&&(!is_bool($p['autoplay'])||!is_int($p['interval'])||$p['interval']<2000||$p['interval']>30000||!is_int($p['perView'])||$p['perView']<1||$p['perView']>4))throw new \InvalidArgumentException('Invalid slider settings.');
  if($this->kind==='link'&&!preg_match('~^(https?://[^\s<>]+|/[^/][^\s<>]*|#[a-zA-Z0-9_-]*)$~D',$p['url']))throw new \InvalidArgumentException('Invalid link.');
 }
}
