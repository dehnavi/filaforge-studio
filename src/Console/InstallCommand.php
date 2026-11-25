<?php
namespace FilaForge\Studio\Console;
use Illuminate\Console\Command;
final class InstallCommand extends Command {
    protected $signature = 'filaforge:install {--assets : Publish public assets} {--views : Publish Blade views} {--translations : Publish language catalogs} {--authorization-snippet : Print the authorization snippet}';
    protected $description = 'Publish FilaForge configuration and show integration steps';
    public function handle(): int {
        $this->call('vendor:publish',['--tag'=>'filaforge-config']);
        foreach (['assets', 'views', 'translations'] as $feature) {
            if ($this->option($feature)) {
                $this->call('vendor:publish', ['--tag' => 'filaforge-'.$feature]);
            }
        }
        if ($this->option('assets')) {
            $this->info('Set filaforge.published_assets to true to serve public/vendor/filaforge assets.');
        }
        $this->newLine();
        $this->info('FilaForge installation guidance');
        $this->line('1. Run php artisan migrate.');
        $this->line('2. Register FilaForgeStudioPlugin::make() in each Filament PanelProvider.');
        $this->line('3. Pages and Comments are registered by default. Registration creates protected routes; Filament hides navigation when viewAny is denied.');
        $this->line('4. Define host Gate abilities to show and authorize resources. The secure default is authorization.mode=host (deny until granted).');
        $this->line('5. Published files are not overwritten; use vendor:publish --force only when explicitly intended.');
        $this->printAuthorizationSnippet();
        $this->line('A private local media disk is configured automatically unless the selected disk already exists.');
        return self::SUCCESS;
    }

    private function printAuthorizationSnippet(): void
    {
        $this->newLine();
        $this->line('AppServiceProvider authorization example:');
        $this->line("Gate::define('filaforge.viewAny', fn (\$user) => \$user->can('manage-pages'));\nGate::define('filaforge.create', fn (\$user) => \$user->can('manage-pages'));\nGate::define('filaforge.update', fn (\$user, \$page) => \$user->can('manage-pages'));\nGate::define('filaforge.publish', fn (\$user, \$page) => \$user->can('publish-pages'));\nGate::define('filaforge.moderateAllComments', fn (\$user) => \$user->can('moderate-comments'));");
        $this->line("Small local-only projects may explicitly opt in with FilaForgeStudioPlugin::make()->authorizationMode('authenticated'). Never use this as a role or tenant boundary.");
    }
}
