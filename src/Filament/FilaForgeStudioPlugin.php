<?php
namespace FilaForge\Studio\Filament;
use Filament\Contracts\Plugin;
use Filament\Panel;
final class FilaForgeStudioPlugin implements Plugin {
    public static function make(): self { return new self; }
    public function getId(): string { return 'filaforge-studio'; }
    public function register(Panel $panel): void { $panel->middleware([\FilaForge\Studio\Http\Middleware\StudioSecurity::class])->resources([Resources\PageResource::class,Resources\CommentResource::class]); }
    public function boot(Panel $panel): void {}
}
