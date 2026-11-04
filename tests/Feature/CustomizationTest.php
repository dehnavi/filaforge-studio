<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
use FilaForge\Studio\Filament\Resources\CommentResource;
use FilaForge\Studio\Filament\Resources\PageResource;
use FilaForge\Studio\FilaForgeServiceProvider;
use FilaForge\Studio\Support\Assets;
use FilaForge\Studio\Tests\AuthoringTestCase;
use FilaForge\Studio\Tests\Fixtures\CustomPageResource;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class CustomizationTest extends AuthoringTestCase
{
    private string $temporaryDirectory;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $this->temporaryDirectory = sys_get_temp_dir().'/filaforge-customization-'.bin2hex(random_bytes(8));
        $app->usePublicPath($this->temporaryDirectory.'/public');
        $app->useConfigPath($this->temporaryDirectory.'/config');
        $app->useLangPath($this->temporaryDirectory.'/lang');
        mkdir($this->temporaryDirectory.'/views/vendor/filaforge/widgets', 0777, true);
        file_put_contents($this->temporaryDirectory.'/views/vendor/filaforge/widgets/text.blade.php', 'Host text: {{ $props["text"] }}');
        $app['config']->set('view.paths', array_merge([$this->temporaryDirectory.'/views'], $app['config']->get('view.paths', [])));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryDirectory);
        parent::tearDown();
    }

    public function test_install_publishes_optional_assets_and_preserves_host_edits(): void
    {
        $this->artisan('filaforge:install', ['--assets' => true, '--translations' => true])->assertExitCode(0);
        $path = public_path('vendor/filaforge/components.js');
        $this->assertFileExists($path);
        $this->assertFileExists(lang_path('vendor/filaforge/fa/studio.php'));
        $this->assertFileExists(config_path('filaforge.php'));
        config(['filaforge.published_assets' => true]);
        $before = Assets::url('components.js');
        $this->assertStringContainsString('/vendor/filaforge/components.js?v=', $before);
        File::put($path, '// Host override');
        $this->assertNotSame($before, Assets::url('components.js'));
        $this->artisan('vendor:publish', ['--tag' => 'filaforge-assets'])->assertExitCode(0);
        $this->assertSame('// Host override', File::get($path));
        $this->assertStringContainsString(Assets::url('components.js'), view('filaforge::widgets.wizard', ['node' => ['id' => 'wizard', 'children' => []], 'props' => ['start' => 0, 'previous' => 'Previous', 'next' => 'Next'], 'children' => ''])->render());
    }

    public function test_default_asset_urls_and_view_publish_mapping(): void
    {
        $this->assertStringContainsString('/filaforge-studio/assets/components.js?v=', Assets::url('components.js'));
        $views = ServiceProvider::pathsToPublish(FilaForgeServiceProvider::class, 'filaforge-views');
        $this->assertSame([dirname(__DIR__, 2).'/src/../resources/views' => resource_path('views/vendor/filaforge')], $views);
        $this->expectException(\InvalidArgumentException::class);
        Assets::url('../config.php');
    }

    public function test_host_blade_overrides_take_precedence_over_package_views(): void
    {
        $html = app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($this->doc([$this->node('text', ['text' => 'Custom content'])]));
        $this->assertStringContainsString('Host text: Custom content', $html);
    }

    public function test_resource_configuration_is_overridden_per_panel(): void
    {
        config(['filaforge.resources.pages' => CustomPageResource::class, 'filaforge.resources.comments' => null]);
        $this->assertSame([CustomPageResource::class], FilaForgeStudioPlugin::make()->getResources());
        $plugin = FilaForgeStudioPlugin::make()->pageResource(null)->commentResource(CommentResource::class);
        $panel = Panel::make()->id('customized');
        $plugin->register($panel);
        $this->assertSame([CommentResource::class], $panel->getResources());
        $this->assertSame([CustomPageResource::class], FilaForgeStudioPlugin::make()->getResources());
        $this->expectException(\InvalidArgumentException::class);
        FilaForgeStudioPlugin::make()->pageResource(\stdClass::class)->getResources();
    }

    public function test_extended_resource_pages_use_host_routes_and_keep_editor_behavior(): void
    {
        $this->admin();
        $panel = Panel::make()->id('custom')->path('custom')->plugin(FilaForgeStudioPlugin::make()->pageResource(CustomPageResource::class)->commentResource(null));
        Filament::registerPanel($panel);
        Filament::setCurrentPanel($panel);
        Route::name('filament.custom.')->group(fn () => CustomPageResource::registerRoutes($panel));
        $this->assertNotContains(PageResource::class, $panel->getResources());
        foreach (CustomPageResource::getPages() as $registration) {
            $this->assertSame(CustomPageResource::class, $registration->getPage()::getResource());
        }
        $page = $this->page();
        \Livewire\Livewire::test(\FilaForge\Studio\Tests\Fixtures\CustomEditPage::class, ['record' => $page->id])
            ->assertSee('Page canvas')
            ->call('saveDocument', $page->draft_document, 0)
            ->assertReturned(['version' => 1]);
    }
}
