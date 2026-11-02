<?php
namespace FilaForge\Studio\Contracts;
use FilaForge\Studio\DynamicData\{DataContext,DataQuery,DataResult};
interface DataProvider
{
    public function name(): string;
    public function label(): string;
    /** Public aliases => ['label'=>string, 'type'=>text|number|boolean|url|image|date]. */
    public function fields(): array;
    /** Public aliases => Laravel validation rule arrays. */
    public function filters(): array;
    /** Public sort identifiers => translated labels. */
    public function sorts(): array;
    public function maxLimit(): int;
    public function isPublic(): bool;
    public function authorize(DataContext $context): bool;
    public function fetch(DataQuery $query, DataContext $context): DataResult;
}
