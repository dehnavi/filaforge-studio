<?php

namespace FilaForge\Studio\Support;

final class ResourceLabels
{
    public static function column(string $resource, string $column, string $defaultKey): string
    {
        $label = config("filaforge.resource_labels.{$resource}.columns.{$column}");

        return __(is_string($label) && $label !== '' ? $label : $defaultKey);
    }
}
