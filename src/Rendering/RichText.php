<?php
namespace FilaForge\Studio\Rendering;
final class RichText {
 public static function field(array $props, string $field): string {
  return self::content($props, $field, (string) ($props[$field] ?? ''));
 }
 public static function content(array $props, string $field, string $fallback): string {
  if (isset($props['formatted'][$field]) && is_string($props['formatted'][$field])) return self::clean($props['formatted'][$field]);
  return e($fallback);
 }
 public static function validateFormatted(mixed $formatted, array $allowedFields): void {
  if (!is_array($formatted) || ($formatted !== [] && array_is_list($formatted)) || array_diff(array_keys($formatted), $allowedFields)) throw new \InvalidArgumentException('Invalid formatted content.');
  foreach ($formatted as $html) if (!is_string($html) || mb_strlen($html) > 20000) throw new \InvalidArgumentException('Invalid formatted content.');
 }
 public static function clean(string $html): string {
  $config=new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig();
  foreach(['div','p','br','strong','b','em','i','u','s','strike','sub','sup','ul','ol','li','h2','h3','h4','blockquote','pre','code','span'] as $tag) $config=$config->allowElement($tag,['data-align','data-tone','data-size','data-direction','data-color']);
  $config=$config->allowElement('a',['href'])->allowLinkSchemes(['http','https','mailto'])->allowRelativeLinks();
  $html=(new \Symfony\Component\HtmlSanitizer\HtmlSanitizer($config))->sanitize($html);
  $dom=new \DOMDocument();$previous=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);
  $allowed=['data-align'=>['left','right','center','justify'],'data-tone'=>['indigo','red','green','muted'],'data-size'=>['small','large','huge'],'data-direction'=>['rtl','ltr']];
  foreach($dom->getElementsByTagName('*') as $el) foreach($allowed as $key=>$values) if($el->hasAttribute($key)&&!in_array($el->getAttribute($key),$values,true))$el->removeAttribute($key);
  foreach($dom->getElementsByTagName('*') as $el)if($el->hasAttribute('data-color')){if(preg_match('/^#[0-9a-f]{6}$/iD',$el->getAttribute('data-color')))$el->setAttribute('style','color:'.$el->getAttribute('data-color'));else $el->removeAttribute('data-color');}
  $root=$dom->getElementsByTagName('div')->item(0);$out='';if($root)foreach($root->childNodes as $node)$out.=$dom->saveHTML($node);return $out;
 }
}
