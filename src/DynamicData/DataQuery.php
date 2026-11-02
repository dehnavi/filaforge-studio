<?php
namespace FilaForge\Studio\DynamicData;
final readonly class DataQuery
{
    public function __construct(public array $filters = [], public ?string $sort = null, public int $limit = 6, public int $page = 1) {}
}
