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
    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $model = PageLocale::class;
    public static function getModelLabel(): string { return __('filaforge::studio.page'); }
    public static function getPluralModelLabel(): string { return __('filaforge::studio.resources.pages'); }
    public static function form(Schema $schema): Schema {
        return $schema->components([
            TextInput::make('title')->label(__('filaforge::studio.title'))->required()->maxLength(255),
            TextInput::make('slug')->label(__('filaforge::studio.resources.slug'))->required()->maxLength(255)->regex('/^[a-zA-Z0-9\x{0600}-\x{06FF}_-]+$/u'),
            Select::make('locale')->label(__('filaforge::studio.resources.locale'))->options(__('filaforge::studio.resources.locales'))->default('en')->required(),
            Select::make('direction')->label(__('filaforge::studio.resources.direction'))->options(__('filaforge::studio.resources.directions'))->default('ltr')->required(),
        ]);
    }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('title')->label(\FilaForge\Studio\Support\ResourceLabels::column('pages', 'title', 'filaforge::studio.title'))->searchable(),TextColumn::make('locale')->label(\FilaForge\Studio\Support\ResourceLabels::column('pages', 'locale', 'filaforge::studio.resources.locale'))->formatStateUsing(fn ($state) => __('filaforge::studio.resources.locales')[$state] ?? $state),TextColumn::make('lock_version')->label(\FilaForge\Studio\Support\ResourceLabels::column('pages', 'lock_version', 'filaforge::studio.resources.version'))])->recordActions([\Filament\Actions\EditAction::make()]); }
    public static function getPages(): array { return ['index'=>Pages\ListPages::route('/'),'create'=>Pages\CreatePage::route('/create'),'edit'=>Pages\EditPage::route('/{record}/edit')]; }
}
