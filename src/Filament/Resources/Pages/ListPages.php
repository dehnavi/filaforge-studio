<?php
namespace FilaForge\Studio\Filament\Resources\Pages;
class ListPages extends \Filament\Resources\Pages\ListRecords {
    protected static string $resource = \FilaForge\Studio\Filament\Resources\PageResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
