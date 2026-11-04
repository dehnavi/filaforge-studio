<?php
namespace FilaForge\Studio\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
final class CommentResource extends Resource {
    protected static bool $hasTitleCaseModelLabel = false;
 protected static ?string $model=\FilaForge\Studio\Models\Comment::class;
 public static function getModelLabel():string{return __('filaforge::studio.comment_single');}
 public static function getPluralModelLabel():string{return __('filaforge::studio.comment_plural');}
 public static function form(Schema $schema):Schema{return $schema->components([TextInput::make('name')->label(__('filaforge::studio.comment_name'))->required()->maxLength(100),Textarea::make('body')->label(__('filaforge::studio.comment_body'))->required()->maxLength(3000),Select::make('status')->label(__('filaforge::studio.comment_status'))->options(['pending'=>__('filaforge::studio.comment_pending'),'approved'=>__('filaforge::studio.comment_approved'),'rejected'=>__('filaforge::studio.comment_rejected')])->required()]);}
 public static function table(Table $table):Table{return $table->columns([TextColumn::make('name')->label(\FilaForge\Studio\Support\ResourceLabels::column('comments', 'name', 'filaforge::studio.comment_name'))->searchable(),TextColumn::make('body')->label(\FilaForge\Studio\Support\ResourceLabels::column('comments', 'body', 'filaforge::studio.comment_body'))->limit(70),TextColumn::make('status')->label(\FilaForge\Studio\Support\ResourceLabels::column('comments', 'status', 'filaforge::studio.comment_status'))->formatStateUsing(fn($state)=>['pending'=>__('filaforge::studio.comment_pending'),'approved'=>__('filaforge::studio.comment_approved'),'rejected'=>__('filaforge::studio.comment_rejected')][$state]??$state),TextColumn::make('created_at')->label(\FilaForge\Studio\Support\ResourceLabels::column('comments', 'created_at', 'filaforge::studio.comment_date'))->dateTime()])->recordActions([\Filament\Actions\EditAction::make()])->defaultSort('created_at','desc');}
 public static function getPages():array{return ['index'=>Pages\ListComments::route('/'),'edit'=>Pages\EditComment::route('/{record}/edit')];}
}
