<?php
namespace FilaForge\Studio\Widgets;
use FilaForge\Studio\Contracts\Widget;
final class ContentWidget implements Widget {
 public function __construct(private string $kind) {}
 public function name(): string { return $this->kind; }
 public function version(): int { return 1; }
 public function acceptsChildren(): bool { return false; }
 public function view(): string { return 'filaforge::widgets.'.$this->kind; }
 public function defaults(): array { return match($this->kind) {
 'text'=>['text'=>'متن خود را اینجا بنویسید.'], 'rich-text'=>['html'=>'<p>برای ویرایش این متن <strong>دوبار کلیک</strong> کنید.</p>'],
 'button'=>['text'=>'شروع کنید','url'=>'#'], 'image'=>['src'=>'','alt'=>'تصویر','width'=>100], 'spacer'=>['height'=>40], 'divider'=>[],
 }; }
 public function validate(array $props): void {
  $defaults=$this->defaults();
  if(array_diff(array_keys($props),[...array_keys($defaults),'formatted']) || array_diff(array_keys($defaults),array_keys($props))) throw new \InvalidArgumentException('Invalid widget properties.');
  if(array_key_exists('formatted',$props)) \FilaForge\Studio\Rendering\RichText::validateFormatted($props['formatted'], $this->kind==='rich-text'?[]:['text']);
  foreach($props as $key=>$value) {
   if($key==='formatted') continue;
   if(in_array($key,['height','width'])) { if(!is_int($value)||$value<1||$value>($key==='width'?100:400)) throw new \InvalidArgumentException('Invalid dimension.'); }
   elseif(!is_string($value)||mb_strlen($value)>20000) throw new \InvalidArgumentException('Invalid content.');
  }
  foreach(['url','src'] as $key) if(isset($props[$key]) && !preg_match('/^media:[0-9A-HJKMNP-TV-Z]{26}$/iD',$props[$key]) && $props[$key]!=='' && !preg_match('~^(https?://[^\s<>]+|/[^/][^\s<>]*|#[a-zA-Z0-9_-]*)$~D',$props[$key])) throw new \InvalidArgumentException('Use an HTTPS URL, local path or anchor.');
 }
}
