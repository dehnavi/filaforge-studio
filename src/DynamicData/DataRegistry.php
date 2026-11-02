<?php
namespace FilaForge\Studio\DynamicData;
use FilaForge\Studio\Contracts\DataProvider;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
final class DataRegistry
{
    private array $providers = [];
    public function register(DataProvider $provider): void
    {
        $name=$provider->name();
        if (!preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$name) || isset($this->providers[$name])) throw new InvalidArgumentException('Invalid or duplicate data provider.');
        foreach ($provider->fields() as $field=>$definition) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D',$field) || !in_array($definition['type']??null,['text','number','boolean','url','image','date'],true)) throw new InvalidArgumentException('Invalid provider field.');
        }
        if ($provider->maxLimit()<1 || $provider->maxLimit()>24) throw new InvalidArgumentException('Invalid provider limit.');
        $this->providers[$name]=$provider;
    }
    public function get(string $name): ?DataProvider { return $this->providers[$name]??null; }
    public function catalog(): array
    {
        return array_map(fn($p)=>['name'=>$p->name(),'label'=>$p->label(),'fields'=>array_map(fn($field)=>array_intersect_key($field,array_flip(['label','type'])),$p->fields()),'filters'=>array_keys($p->filters()),'sorts'=>$p->sorts(),'maxLimit'=>$p->maxLimit(),'public'=>$p->isPublic()],array_values($this->providers));
    }
    public function query(string $provider, array $spec): DataQuery
    {
        if (array_diff(array_keys($spec),['filters','sort','limit','page'])) throw new InvalidArgumentException('Unknown query option.');
        $source=$this->get($provider);
        if (!$source) throw new InvalidArgumentException('Unknown data provider.');
        $filters=$spec['filters']??[];
        if (!is_array($filters) || count($filters)>20 || array_diff(array_keys($filters),array_keys($source->filters()))) throw new InvalidArgumentException('Unknown data filter.');
        foreach ($filters as $value) {
            $values=is_array($value)?$value:[$value];
            if (count($values)>24) throw new InvalidArgumentException('Too many filter values.');
            foreach ($values as $part) if ((!is_scalar($part)&&$part!==null) || (is_string($part)&&mb_strlen($part)>500)) throw new InvalidArgumentException('Invalid filter value.');
        }
        $rules=[];foreach($filters as $key=>$_)$rules[$key]=$source->filters()[$key];
        if (Validator::make($filters,$rules)->fails()) throw new InvalidArgumentException('Invalid data filter.');
        $sort=$spec['sort']??array_key_first($source->sorts());
        if ($sort!==null && (!is_string($sort)||!array_key_exists($sort,$source->sorts()))) throw new InvalidArgumentException('Unknown sort.');
        $limit=$spec['limit']??min(6,$source->maxLimit());$page=$spec['page']??1;
        if (!is_int($limit)||$limit<1||$limit>$source->maxLimit()||!is_int($page)||$page<1||$page>1000) throw new InvalidArgumentException('Invalid query bounds.');
        return new DataQuery($filters,$sort,$limit,$page);
    }
}
