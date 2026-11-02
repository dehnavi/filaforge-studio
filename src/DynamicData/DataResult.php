<?php
namespace FilaForge\Studio\DynamicData;
final readonly class DataResult
{
    public function __construct(public array $items = [], public int $total = 0) {}
}
