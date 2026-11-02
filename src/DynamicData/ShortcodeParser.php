<?php
namespace FilaForge\Studio\DynamicData;
use FilaForge\Studio\Core\DocumentValidator;
use Illuminate\Support\Str;
use InvalidArgumentException;
final class ShortcodeParser
{
    public function parse(string $text,string $locale='en',string $direction='ltr'): array
    {
        if(mb_strlen($text)>20000)throw new InvalidArgumentException('Shortcode input too large.');
        $document=DocumentValidator::empty($locale,$direction);
        $parts=preg_split('/(\[\[.*?\]\])/s',$text,-1,PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $part){
            if($part==='')continue;
            $node=['id'=>(string)Str::uuid(),'type'=>'text','widgetVersion'=>1,'props'=>['text'=>$part],'children'=>[],'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[]];
            if(str_starts_with($part,'[[')){
                if(!preg_match('/^\[\[(field|component)\s+(.+)\]\]$/sD',$part,$match))throw new InvalidArgumentException('Unknown shortcode syntax.');
                $attributes=[];$tail=trim($match[2]);
                while($tail!==''){
                    if(!preg_match('/^([a-z][a-z0-9_]*)="([^"\r\n<>]{0,1000})"(?:\s+|$)/u',$tail,$attr)||array_key_exists($attr[1],$attributes))throw new InvalidArgumentException('Invalid shortcode attributes.');
                    $attributes[$attr[1]]=$attr[2];$tail=substr($tail,strlen($attr[0]));
                }
                if($match[1]==='field'){
                    if(array_diff(array_keys($attributes),['source','name','formatter','fallback'])||!isset($attributes['source'],$attributes['name']))throw new InvalidArgumentException('Invalid field shortcode.');
                    $node['props']['text']=$attributes['fallback']??'';$node['bindings']['text']=['provider'=>$attributes['source'],'field'=>$attributes['name'],'formatter'=>$attributes['formatter']??'text','fallback'=>$attributes['fallback']??''];
                }else{
                    $name=$attributes['name']??throw new InvalidArgumentException('Missing component name.');unset($attributes['name']);
                    $node['type']='shortcode';$node['props']=['name'=>$name,'arguments'=>$attributes];
                }
            }
            $document['root']['children'][]=$node;
        }
        return app(DocumentValidator::class)->validate($document);
    }
}
