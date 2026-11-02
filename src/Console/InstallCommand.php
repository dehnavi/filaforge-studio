<?php
namespace FilaForge\Studio\Console;
use Illuminate\Console\Command;
final class InstallCommand extends Command {
    protected $signature = 'filaforge:install';
    protected $description = 'Publish FilaForge configuration and show integration steps';
    public function handle(): int {
        $this->call('vendor:publish',['--tag'=>'filaforge-config']);
        $this->info('Run php artisan migrate, register FilaForgeStudioPlugin on your panel, and define the filaforge.* Gate abilities. A private local media disk is configured automatically unless the selected disk already exists.');
        return self::SUCCESS;
    }
}
