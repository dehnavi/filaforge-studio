<?php
namespace FilaForge\Studio;
use Illuminate\Support\ServiceProvider;
use FilaForge\Studio\Core\WidgetRegistry;
use FilaForge\Studio\Core\DocumentValidator;
final class FilaForgeServiceProvider extends ServiceProvider {
    public function register(): void {
        require_once __DIR__.'/Support/helpers.php';
        $this->mergeConfigFrom(__DIR__.'/../config/filaforge.php','filaforge');
        $this->app->singleton(\FilaForge\Studio\DynamicData\Formatters::class);
        $this->app->singleton(\FilaForge\Studio\DynamicData\ShortcodeRegistry::class);
        $this->app->singleton(\FilaForge\Studio\DynamicData\DataRegistry::class, function ($app) {
            $registry = new \FilaForge\Studio\DynamicData\DataRegistry;
            $registry->register(new \FilaForge\Studio\DynamicData\ArrayDataProvider(
                'company', __('filaforge::studio.data_company'),
                ['name'=>['label'=>__('filaforge::studio.data_company_name'),'type'=>'text'],'phone'=>['label'=>__('filaforge::studio.data_company_phone'),'type'=>'text'],'email'=>['label'=>__('filaforge::studio.data_email'),'type'=>'text'],'url'=>['label'=>__('filaforge::studio.data_url'),'type'=>'url']],
                fn ($context) => [array_merge(['name'=>config('app.name'),'phone'=>'','email'=>'','url'=>config('app.url')], array_intersect_key(config('filaforge.company', []), array_flip(['name','phone','email','url'])))],
                fn ($context) => true, true
            ));
            foreach (config('filaforge.data_providers', []) as $class) $registry->register($app->make($class));
            return $registry;
        });
        $this->app->singleton(\FilaForge\Studio\Fonts\FontRegistry::class);
        $this->app->singleton(WidgetRegistry::class, function ($app) {
            $registry = new WidgetRegistry;
            foreach (array_merge([Widgets\Container::class, Widgets\Heading::class],config('filaforge.widgets',[])) as $class) $registry->register($app->make($class));
            foreach (['text','rich-text','button','image','divider','spacer'] as $kind) $registry->register(new Widgets\ContentWidget($kind));
            foreach (['grid','columns','icon','table','video','quote'] as $kind) $registry->register(new Widgets\StructureWidget($kind));
            foreach (['section','card','navigation','link','slider','carousel','comments','faq-item'] as $kind) $registry->register(new Widgets\PresetWidget($kind));
            $registry->register(new Widgets\DynamicWidget('query-loop'));
            $registry->register(new Widgets\DynamicWidget('shortcode'));
            $registry->register(new Widgets\PaginationWidget);
            $registry->register(new Widgets\HtmlWidget);
            foreach(['dropdown-menu','menu-item'] as $kind)$registry->register(new Widgets\MenuWidget($kind));
            foreach(['progress','wizard','wizard-step'] as $kind)$registry->register(new Widgets\FlowWidget($kind));
            return $registry;
        });
        $this->app->singleton(\FilaForge\Studio\Presets\PresetRegistry::class);
        $this->app->singleton(DocumentValidator::class, fn ($app) => new DocumentValidator($app->make(WidgetRegistry::class),config('filaforge.max_bytes'),config('filaforge.max_nodes'),config('filaforge.max_depth'),$app->make(\FilaForge\Studio\DynamicData\BindingValidator::class)));
    }
    public function boot(): void {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang','filaforge');
        \Illuminate\Support\Facades\Gate::policy(Models\Comment::class, Policies\CommentPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(Models\PageLocale::class, Policies\PageLocalePolicy::class);
        $this->app->make(\FilaForge\Studio\DynamicData\ShortcodeRegistry::class)->register(
            'contact-card', __('filaforge::studio.data_contact_card'), [],
            function ($arguments, $context) {
                $document = \FilaForge\Studio\Core\DocumentValidator::empty($context->locale);
                $node = $document['root']; $node['id']='contact-card';
                $title=$node; $title['id']='contact-card-title';$title['type']='heading';$title['props']=['text'=>'','tag'=>'h2'];$title['bindings']=['text'=>['provider'=>'company','field'=>'name','fallback'=>'']];
                $phone=$node; $phone['id']='contact-card-phone';$phone['type']='text';$phone['props']=['text'=>''];$phone['bindings']=['text'=>['provider'=>'company','field'=>'phone','fallback'=>'']];
                $node['children']=[$title,$phone];return $node;
            }, fn ($context) => true, true
        );
        $disk = config('filaforge.media_disk', 'filaforge_media');
        if (config('filesystems.disks.'.$disk) === null) {
            config(['filesystems.disks.'.$disk => ['driver'=>'local','root'=>storage_path('app/filaforge-media'),'visibility'=>'private','throw'=>true]]);
        }
        $this->loadRoutesFrom(__DIR__.'/../routes/assets.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/media.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views','filaforge');
        $this->app['view']->composer(['filaforge::widgets.wizard','filaforge::widgets.dropdown-menu'],fn($view)=>$view->with('componentsVersion',substr(hash_file('sha256',__DIR__.'/../resources/js/components.js'),0,12)));
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang','filaforge');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/filaforge.php'=>config_path('filaforge.php')],'filaforge-config');
        if ($this->app->runningInConsole()) $this->commands([Console\InstallCommand::class,Console\SecurityCheckCommand::class]);
    }
}
