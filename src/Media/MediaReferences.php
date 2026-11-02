<?php
namespace FilaForge\Studio\Media;
final class MediaReferences {
 public function validate(array $document): void {
  $walk=function(array $node)use(&$walk){foreach(['src','poster'] as $key)if(isset($node['props'][$key])&&str_starts_with($node['props'][$key],'media:'))app(MediaRepository::class)->resolve($node['props'][$key],$node['type']==='video'&&$key==='src'?'video':'image');$background=$node['styles']['backgroundImage']??'';if(is_string($background)&&str_starts_with($background,'media:'))app(MediaRepository::class)->resolve($background,'image');foreach($node['children'] as $child)$walk($child);};$walk($document['root']);
 }
}
