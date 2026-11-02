<?php
namespace FilaForge\Studio\DynamicData;
use InvalidArgumentException;
final class BindingValidator
{
    public const SLOTS = ['heading'=>['text'],'text'=>['text'],'rich-text'=>[],'button'=>['text','url'],'link'=>['text','url'],'image'=>['src','alt'],'video'=>['src','poster'],'quote'=>['text','author'],'navigation'=>['brand'],'faq-item'=>['question','answer']];
    public function __construct(private DataRegistry $sources,private Formatters $formatters,private ShortcodeRegistry $shortcodes) {}
    public function slots(string $widget): array {return self::SLOTS[$widget]??config('filaforge.dynamic_slots.'.$widget,[]);}
    public function validate(array $node,?string $rowProvider,bool $missing = false): void
    {
        $bindings=$node['bindings']??[];$visibility=$node['visibility']??[];
        if(!is_array($bindings)||array_diff(array_keys($bindings),$this->slots($node['type'])))throw new InvalidArgumentException('Unsupported field binding.');
        foreach($bindings as $binding)$this->reference($binding,$rowProvider,false,$missing);
        if($visibility!==[])$this->reference($visibility,$rowProvider,true,$missing);
        if($node['type']==='query-loop'){
            if($this->sources->get($node['props']['provider']))$this->sources->query($node['props']['provider'],$node['props']['query']);
            elseif(!$missing)throw new InvalidArgumentException('Unknown loop provider.');
        }
        if($node['type']==='shortcode'){
            if(!$missing||in_array($node['props']['name'],array_column($this->shortcodes->catalog(),'name'),true))$this->shortcodes->validate($node['props']['name'],$node['props']['arguments']);
        }
        if($rowProvider!==null&&$node['type']==='comments')throw new InvalidArgumentException('Comment forms cannot be repeated in query loops.');
    }
    private function reference(mixed $binding,?string $rowProvider,bool $condition,bool $missing): void
    {
        $allowed=$condition?['provider','field','query','operator','value']:['provider','field','query','formatter','fallback'];
        if(!is_array($binding)||array_diff(array_keys($binding),$allowed)||!is_string($binding['provider']??null)||!preg_match('/^(?:[a-z][a-z0-9-]{0,63}|@item)$/D',$binding['provider'])||!is_string($binding['field']??null)||!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/D',$binding['field']))throw new InvalidArgumentException('Invalid binding reference.');
        $provider=$binding['provider']==='@item'?$rowProvider:$binding['provider'];
        if($provider===null)throw new InvalidArgumentException('Item bindings require a query loop.');
        $source=$this->sources->get($provider);
        if(!$source&&!$missing)throw new InvalidArgumentException('Unknown binding provider.');
        if($source&&!array_key_exists($binding['field'],$source->fields()))throw new InvalidArgumentException('Unknown binding field.');
        if(isset($binding['query'])&&!is_array($binding['query']))throw new InvalidArgumentException('Invalid binding query.');
        if($binding['provider']==='@item'&&!empty($binding['query']))throw new InvalidArgumentException('Item bindings cannot query.');
        if($source&&$binding['provider']!=='@item')$this->sources->query($provider,$binding['query']??[]);
        if($condition){
            if(!in_array($binding['operator']??null,['eq','neq','gt','gte','lt','lte','contains','empty','not_empty'],true))throw new InvalidArgumentException('Unknown condition operator.');
            $value=$binding['value']??null;
            if((!is_scalar($value)&&$value!==null)||(is_string($value)&&mb_strlen($value)>1000))throw new InvalidArgumentException('Invalid condition value.');
        }else{
            if(!in_array($binding['formatter']??'text',$this->formatters->names(),true)||!is_string($binding['fallback']??'')||mb_strlen($binding['fallback']??'')>20000)throw new InvalidArgumentException('Invalid formatter or fallback.');
        }
    }
}
