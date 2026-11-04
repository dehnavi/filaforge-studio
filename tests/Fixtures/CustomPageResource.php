<?php

namespace FilaForge\Studio\Tests\Fixtures;

class CustomPageResource extends \FilaForge\Studio\Filament\Resources\PageResource
{
    public static function getPages(): array
    {
        return ['index' => CustomListPages::route('/'), 'create' => CustomCreatePage::route('/create'), 'edit' => CustomEditPage::route('/{record}/edit')];
    }
}

class CustomListPages extends \FilaForge\Studio\Filament\Resources\Pages\ListPages
{
    protected static string $resource = CustomPageResource::class;
}

class CustomCreatePage extends \FilaForge\Studio\Filament\Resources\Pages\CreatePage
{
    protected static string $resource = CustomPageResource::class;
}

class CustomEditPage extends \FilaForge\Studio\Filament\Resources\Pages\EditPage
{
    protected static string $resource = CustomPageResource::class;
}
