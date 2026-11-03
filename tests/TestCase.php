<?php
namespace FilaForge\Studio\Tests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array { return [\BladeUI\Icons\BladeIconsServiceProvider::class, \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class, \Livewire\LivewireServiceProvider::class, \Filament\Support\SupportServiceProvider::class, \Filament\Actions\ActionsServiceProvider::class, \Filament\Schemas\SchemasServiceProvider::class, \Filament\Forms\FormsServiceProvider::class, \Filament\Tables\TablesServiceProvider::class, \Filament\Notifications\NotificationsServiceProvider::class, \Filament\FilamentServiceProvider::class, \FilaForge\Studio\FilaForgeServiceProvider::class]; }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('t',32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver'=>'sqlite','database'=>':memory:','prefix'=>'']);
        $app['config']->set('auth.providers.users.model', Fixtures\User::class);
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate')->run();
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('password'); $table->timestamps();
        });
        foreach (['filaforge.comment','filaforge.viewComments'] as $ability) Gate::define($ability, fn (?\Illuminate\Contracts\Auth\Authenticatable $user, \FilaForge\Studio\Models\PageLocale $page) => true);
        foreach (['viewAny','view','create','update','publish'] as $ability) {
            Gate::define('filaforge.'.$ability, fn ($user) => $user->email === 'admin@filaforge.test');
        }
    }
    protected function user(array $attributes = []): Fixtures\User
    {
        return Fixtures\User::create(array_merge(['name'=>'Test user','email'=>uniqid().'@example.test','password'=>'test-only'], $attributes));
    }
}
