<?php
namespace FilaForge\Studio\DynamicData;
use FilaForge\Studio\Core\{DocumentValidator,WidgetRegistry};
use Illuminate\Support\Str;
use InvalidArgumentException;
final class DynamicElementFactory
{
    public function __construct(private DataRegistry $sources,private ShortcodeRegistry $components,private WidgetRegistry $widgets) {}
    /** Build native nodes from registry aliases; never execute a component during selection. */
    public function make(string $kind,string $source,string $name,array $arguments=[]): array
    {
        $binding=[];
        if($kind==='field'){
            if($arguments!==[])throw new InvalidArgumentException('Fields do not accept component arguments.');
            $definition=$this->sources->get($source)?->fields()[$name]??throw new InvalidArgumentException('Unknown source field.');
            $type=match($definition['type']){'image'=>'image','url'=>'link',default=>'text'};
            $props=$this->widgets->get($type)->defaults();$label=$definition['label']??$name;
            $slot=match($type){'image'=>'src','link'=>'url',default=>'text'};
            if($type==='image')$props['alt']=$label;else $props['text']=$label;
            $binding=[$slot=>['provider'=>$source,'field'=>$name,'formatter'=>'text','fallback'=>$slot==='url'?'#':'']];
        }elseif($kind==='component'){
            if($source!=='')throw new InvalidArgumentException('Components do not accept a data source.');
            $this->components->validate($name,$arguments);$type='shortcode';$props=['name'=>$name,'arguments'=>$arguments];
        }else throw new InvalidArgumentException('Unknown selection kind.');
        $node=['id'=>(string)Str::uuid(),'type'=>$type,'widgetVersion'=>1,'props'=>$props,'children'=>[],'styles'=>[],'responsive'=>[],'bindings'=>$binding,'visibility'=>[],'meta'=>[]];
        $document=DocumentValidator::empty();$document['root']['children']=[$node];app(DocumentValidator::class)->validate($document);
        return $node;
    }
}
