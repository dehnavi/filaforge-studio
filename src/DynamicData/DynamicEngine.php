<?php
namespace FilaForge\Studio\DynamicData;
use FilaForge\Studio\Core\{DocumentValidator,WidgetRegistry};
use InvalidArgumentException;
final class DynamicEngine
{
    private array $cache=[];
    private int $queries=0;
    private int $nodes=0;
    private float $started;
    public function __construct(private DataRegistry $sources,private Formatters $formatters,private WidgetRegistry $widgets,private ShortcodeRegistry $shortcodes) {}
    public function resolve(array $document,DataContext $context): array
    {
        $this->cache=[];$this->queries=0;$this->nodes=0;$this->started=microtime(true);
        $roots=$this->expand($document['root'],$context,0,0,'');
        $document['root']=$roots[0]??$this->node('root','container',['direction'=>'column']);
        return $document;
    }
    private function budget(int $depth): void
    {
        if(++$this->nodes>1000||$depth>32||microtime(true)-$this->started>2)throw new InvalidArgumentException('Dynamic rendering budget exceeded.');
    }
    private function result(string $provider,array $spec,DataContext $context): DataResult
    {
        $source=$this->sources->get($provider);
        if(!$source||($context->publicAudience&&!$source->isPublic())||(!$source->isPublic()&&$context->user===null))return new DataResult;
        try { if (!$source->authorize($context)) return new DataResult; } catch (\Throwable $error) { report($error); return new DataResult; }
        $query=$this->sources->query($provider,$spec);
        $key=hash('sha256',json_encode([$provider,$spec,$context->locale,$context->tenantKey,$context->recordKey,$context->user?->getAuthIdentifier(),$context->publicAudience,$context->row],JSON_THROW_ON_ERROR));
        if(isset($this->cache[$key]))return $this->cache[$key];
        if(++$this->queries>20)throw new InvalidArgumentException('Dynamic query budget exceeded.');
        try{
            $result=$source->fetch($query,$context);
            if(!array_is_list($result->items)||count($result->items)>$query->limit||$result->total<0)throw new InvalidArgumentException('Invalid provider result.');
            $rows=[];foreach($result->items as $item){
                if(!is_array($item))throw new InvalidArgumentException('Invalid provider record.');
                $row=[];foreach($source->fields() as $field=>$definition){
                    $value=$item[$field]??null;
                    if((!is_scalar($value)&&$value!==null)||(is_string($value)&&mb_strlen($value)>20000))throw new InvalidArgumentException('Provider fields must contain bounded scalar values.');
                    if ($value !== null) {
                        $value = match ($definition['type']) {
                            'number' => is_numeric($value)&&is_finite((float)$value) ? (float)$value : throw new InvalidArgumentException('Invalid numeric provider field.'),
                            'boolean' => is_bool($value) ? $value : throw new InvalidArgumentException('Invalid boolean provider field.'),
                            default => (string)$value,
                        };
                    }
                    $row[$field]=$value;
                }$rows[]=$row;
            }
            if(microtime(true)-$this->started>2)throw new InvalidArgumentException('Dynamic provider exceeded render time budget.');
            return $this->cache[$key]=new DataResult($rows,$result->total);
        }catch(\Throwable $error){report($error);return $this->cache[$key]=new DataResult;}
    }
    private function reference(array $binding,DataContext $context): array
    {
        if($binding['provider']==='@item')return [array_key_exists($binding['field'],$context->row??[]),$context->row[$binding['field']]??null];
        $result=$this->result($binding['provider'],$binding['query']??[],$context);
        return [isset($result->items[0])&&array_key_exists($binding['field'],$result->items[0]),$result->items[0][$binding['field']]??null];
    }
    private function visible(array $condition,DataContext $context): bool
    {
        if(!$condition)return true;
        [$found,$actual]=$this->reference($condition,$context);if(!$found)return false;
        $expected=$condition['value']??null;
        if (is_float($actual)&&(is_int($expected)||is_float($expected))) $expected=(float)$expected;
        return match($condition['operator']){
            'empty'=>$actual===null||$actual==='', 'not_empty'=>$actual!==null&&$actual!=='',
            'eq'=>get_debug_type($actual)===get_debug_type($expected)&&$actual===$expected,
            'neq'=>get_debug_type($actual)!==get_debug_type($expected)||$actual!==$expected,
            'contains'=>is_string($actual)&&is_string($expected)&&str_contains($actual,$expected),
            'gt'=>is_numeric($actual)&&is_numeric($expected)&&(float)$actual>(float)$expected,
            'gte'=>is_numeric($actual)&&is_numeric($expected)&&(float)$actual>=(float)$expected,
            'lt'=>is_numeric($actual)&&is_numeric($expected)&&(float)$actual<(float)$expected,
            'lte'=>is_numeric($actual)&&is_numeric($expected)&&(float)$actual<=(float)$expected,
        };
    }
    private function expand(array $node,DataContext $context,int $depth,int $loops,string $suffix): array
    {
        $this->budget($depth);
        if(!$this->visible($node['visibility']??[],$context))return [];
        $original=$node['id'];
        if($suffix!==''){
            $node['id']=substr($original,0,45).'-'.substr(hash('sha256',$suffix.$original),0,24);
            if(isset($node['props']['anchor'])&&$node['props']['anchor']!=='')$node['props']['anchor']=$node['id'];
        }
        if($node['type']==='query-loop'){
            if($loops>=3)throw new InvalidArgumentException('Too many nested query loops.');
            $query=$node['props']['query'];$query['page']=$context->pageNumber($original);
            $results=$this->result($node['props']['provider'],$query,$context);$children=[];
            foreach($results->items as $index=>$row){
                $rowContext=$context->withRow($node['props']['provider'],$row);
                foreach($node['children'] as $child)$children=array_merge($children,$this->expand($child,$rowContext,$depth+1,$loops+1,$suffix.$original.'-'.$query['page'].'-'.$index));
            }
            if(!$children)$children[]=$this->node($node['id'].'-empty','text',['text'=>$node['props']['emptyText']]);
            $limit=$query['limit']??min(6,$this->sources->get($node['props']['provider'])?->maxLimit()??6);
            if($results->total>$limit)$children[]=$this->node(substr($node['id'],0,60).'-pagination','data-pagination',['node'=>$original,'page'=>$query['page'],'last'=>min(1000,(int)ceil($results->total/$limit))]);
            $node['type']='container';$node['props']=['direction'=>'column'];$node['children']=$children;
        }elseif($node['type']==='shortcode'){
            if($depth>=16)return [];
            try{
                $tree=$this->shortcodes->build($node['props']['name'],$node['props']['arguments'],$context);
                $document=DocumentValidator::empty($context->locale);$document['root']['children']=[$tree];
                app(DocumentValidator::class)->validate($document);
                return $this->expand($tree,$context,$depth+1,$loops,$suffix.$original);
            }catch(\Throwable $error){report($error);return [];}
        }else{
            foreach($node['bindings']??[] as $field=>$binding){
                [$found,$value]=$this->reference($binding,$context);
                $fallback=$binding['fallback']??(string)($node['props'][$field]??'');
                try{$value=$found&&$value!==null?$this->formatters->format($value,$binding['formatter']??'text',$context->locale):$fallback;}catch(\Throwable){$value=$fallback;}
                $candidate=$node['props'];$candidate[$field]=$value;unset($candidate['formatted'][$field]);
                try{$this->widgets->get($node['type'])->validate($candidate);$node['props']=$candidate;}catch(InvalidArgumentException){
                    $candidate[$field]=$fallback;
                    try{$this->widgets->get($node['type'])->validate($candidate);$node['props']=$candidate;}catch(InvalidArgumentException){unset($node['props']['formatted'][$field]);}
                }
            }
            $children=[];foreach($node['children'] as $child)$children=array_merge($children,$this->expand($child,$context,$depth+1,$loops,$suffix));$node['children']=$children;
        }
        $node['bindings']=[];$node['visibility']=[];
        return [$node];
    }
    private function node(string $id,string $type,array $props): array
    {
        return ['id'=>strlen($id)>80?substr($id,0,55).'-'.substr(hash('sha256',$id),0,24):$id,'type'=>$type,'widgetVersion'=>1,'props'=>$props,'children'=>[],'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[]];
    }
}
