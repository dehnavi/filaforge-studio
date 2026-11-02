<?php
namespace FilaForge\Studio\DynamicData;
use FilaForge\Studio\Contracts\DataProvider;
use Closure;
final class ArrayDataProvider implements DataProvider
{
    public function __construct(private string $id,private string $title,private array $schema,private Closure $records,private Closure $access,private bool $public = false) {}
    public function name(): string {return $this->id;}
    public function label(): string {return $this->title;}
    public function fields(): array {return $this->schema;}
    public function filters(): array {return [];}
    public function sorts(): array {return [];}
    public function maxLimit(): int {return 24;}
    public function isPublic(): bool {return $this->public;}
    public function authorize(DataContext $context): bool {return (bool)($this->access)($context);}
    public function fetch(DataQuery $query,DataContext $context): DataResult
    {
        $rows=($this->records)($context);
        if (!is_array($rows)||!array_is_list($rows)||count($rows)>1000) throw new \InvalidArgumentException('Invalid array data.');
        return new DataResult(array_slice($rows,($query->page-1)*$query->limit,$query->limit),count($rows));
    }
}
