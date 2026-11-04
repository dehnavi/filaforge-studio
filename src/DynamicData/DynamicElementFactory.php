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
    /** One visual card template, bound to the current row, repeated by the bounded query engine. */
    public function repeater(string $source, array $fields, array $query = [], int $columns = 3): array
    {
        $provider = $this->sources->get($source) ?? throw new InvalidArgumentException('Unknown repeater source.');
        if (!array_is_list($fields) || !$fields || count($fields) > 12 || count(array_unique($fields, SORT_REGULAR)) !== count($fields) || $columns < 1 || $columns > 6) throw new InvalidArgumentException('Invalid repeater template.');
        $this->sources->query($source, $query);
        $node = fn ($type, $props, $children = []) => ['id'=>(string) Str::uuid(),'type'=>$type,'widgetVersion'=>1,'props'=>$props,'children'=>$children,'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[]];
        $children = [];
        foreach ($fields as $field) {
            if (!is_string($field)) throw new InvalidArgumentException('Invalid repeater field.');
            $definition = $provider->fields()[$field] ?? throw new InvalidArgumentException('Unknown repeater field.');
            $type = match ($definition['type']) { 'image'=>'image', 'url'=>'link', default=>'text' };
            $slot = match ($type) { 'image'=>'src', 'link'=>'url', default=>'text' };
            $child = $node($type, $this->widgets->get($type)->defaults());
            if ($type === 'image') $child['props']['alt'] = $definition['label'];
            if ($type === 'link') $child['props']['text'] = $definition['label'];
            $child['bindings'] = [$slot=>['provider'=>'@item','field'=>$field,'formatter'=>'text','fallback'=>$slot === 'url' ? '#' : '']];
            $children[] = $child;
        }
        $card = $node('card', $this->widgets->get('card')->defaults(), $children);
        $loop = $node('query-loop', ['provider'=>$source,'query'=>$query,'emptyText'=>__('filaforge::studio.builder.no_results')], [$card]);
        $loop['responsive'] = ['desktop'=>['display'=>'grid','columns'=>$columns,'gap'=>24], 'tablet'=>['columns'=>min(2,$columns)], 'mobile'=>['columns'=>1]];
        $document = DocumentValidator::empty(); $document['root']['children'] = [$loop];
        app(DocumentValidator::class)->validate($document);
        return $loop;
    }

}
