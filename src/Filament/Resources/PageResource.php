<?php
namespace FilaForge\Studio\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use FilaForge\Studio\Models\PageLocale;
final class PageResource extends Resource {
    protected static ?string $model = PageLocale::class;
    public static function getModelLabel(): string { return __('filaforge::studio.page'); }
    public static function form(Schema $schema): Schema {
        return $schema->components([
            TextInput::make('title')->label(__('filaforge::studio.title'))->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255)->regex('/^[a-zA-Z0-9\x{0600}-\x{06FF}_-]+$/u'),
            Select::make('locale')->options(['en'=>'English','fa'=>'فارسی'])->default('en')->required(),
            Select::make('direction')->options(['ltr'=>'LTR','rtl'=>'RTL'])->default('ltr')->required(),
        ]);
    }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('title')->searchable(),TextColumn::make('locale'),TextColumn::make('lock_version')])->recordActions([\Filament\Actions\EditAction::make()]); }
    public static function getPages(): array { return ['index'=>Pages\ListPages::route('/'),'create'=>Pages\CreatePage::route('/create'),'edit'=>Pages\EditPage::route('/{record}/edit')]; }
}
