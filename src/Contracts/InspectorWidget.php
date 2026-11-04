<?php

namespace FilaForge\Studio\Contracts;

interface InspectorWidget extends Widget
{
    /** @return array<\Filament\Schemas\Components\Component> Trusted server-defined fields. */
    public function inspector(): array;
}
