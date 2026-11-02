<?php
namespace FilaForge\Studio\DynamicData;
use Closure;
use FilaForge\Studio\Contracts\DataProvider;
use Illuminate\Database\Eloquent\{Builder,Model};
use InvalidArgumentException;
final class EloquentDataProvider implements DataProvider
{
    /** All model/column/relation names are defined by trusted host code. */
    public function __construct(private string $id,private string $title,private array $schema,private Closure $scope,private Closure $access,private bool $public,private array $filterDefinitions=[],private array $sortDefinitions=[],private int $limit=24) {}
    public function name(): string {return $this->id;}
    public function label(): string {return $this->title;}
    public function fields(): array {return array_map(fn($f)=>array_intersect_key($f,array_flip(['label','type'])),$this->schema);}
    public function filters(): array {return array_map(fn($f)=>$f['rules'],$this->filterDefinitions);}
    public function sorts(): array {return array_map(fn($f)=>$f['label'],$this->sortDefinitions);}
    public function maxLimit(): int {return $this->limit;}
    public function isPublic(): bool {return $this->public;}
    public function authorize(DataContext $context): bool {return (bool)($this->access)($context);}
    public function fetch(DataQuery $query,DataContext $context): DataResult
    {
        $builder=($this->scope)($context);
        if (!$builder instanceof Builder) throw new InvalidArgumentException('Expected a scoped Eloquent builder.');
        $builder=clone $builder;
        foreach($query->filters as $key=>$value){
            $definition=$this->filterDefinitions[$key];$column=$this->column($definition['column']);
            match($definition['operator']??'eq'){
                'eq'=>$builder->where($column,'=',$value),
                'contains'=>$builder->where($column,'like','%'.$value.'%'),
                'in'=>$builder->whereIn($column,$value),
                default=>throw new InvalidArgumentException('Invalid configured operator.'),
            };
        }
        if($query->sort!==null){$sort=$this->sortDefinitions[$query->sort];$direction=$sort['direction'];if(!in_array($direction,['asc','desc'],true))throw new InvalidArgumentException('Invalid configured order.');$builder->orderBy($this->column($sort['column']),$direction);}
        $results=$builder->paginate($query->limit,['*'],'ff_page',$query->page);
        $rows=[];
        foreach($results->items() as $model){
            $row=[];foreach($this->schema as $alias=>$definition){
                $value=$this->attribute($model,explode('.',$definition['attribute']??$alias));
                if($value instanceof \DateTimeInterface)$value=$value->format(DATE_ATOM);
                $row[$alias]=$value;
            }$rows[]=$row;
        }
        return new DataResult($rows,$results->total());
    }
    private function column(string $column): string
    {
        if(!preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/D',$column))throw new InvalidArgumentException('Invalid configured column.');
        return $column;
    }
    private function attribute(?Model $model,array $parts): mixed
    {
        if($model===null)return null;
        $part=array_shift($parts);
        if(!$parts)return $model->getAttribute($part);
        // Never trigger relationship lazy loading while rendering a collection.
        if(!$model->relationLoaded($part))throw new InvalidArgumentException('Provider relationships must be eager loaded.');
        $related=$model->getRelation($part);
        if($related!==null&&!$related instanceof Model)throw new InvalidArgumentException('Expected a singular relation.');
        return $this->attribute($related,$parts);
    }
}
