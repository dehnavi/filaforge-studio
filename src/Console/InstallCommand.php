<?php
namespace FilaForge\Studio\Console;
use Illuminate\Console\Command;
final class InstallCommand extends Command {
    protected $signature = 'filaforge:install {--assets : Publish public assets} {--views : Publish Blade views} {--translations : Publish language catalogs}';
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
        $this->info('Run php artisan migrate, register FilaForgeStudioPlugin on your panel, and define the filaforge.* Gate abilities. A private local media disk is configured automatically unless the selected disk already exists.');
        return self::SUCCESS;
    }
}
