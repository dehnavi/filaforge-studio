<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config(['filaforge.integrations.custom_scripts_enabled'=>true]);
        \Illuminate\Support\Facades\Gate::define('filaforge.manageScripts',fn($user,$page)=>$user->email==='admin@filaforge.test');
        config(['filaforge.company'=>['name'=>'استودیوی فیلافورج','phone'=>'۰۲۱۱۲۳۴۵۶۷۸','email'=>'hello@example.test','url'=>'https://example.test']]);
        app(\FilaForge\Studio\DynamicData\DataRegistry::class)->register(new \FilaForge\Studio\DynamicData\EloquentDataProvider(
            id:'products', title:app()->getLocale()==='fa'?'محصولات آزمایشی':'Demo products',
            schema:['title'=>['label'=>'عنوان / Title','type'=>'text'],'price'=>['label'=>'قیمت / Price','type'=>'number'],'url'=>['label'=>'پیوند / Link','type'=>'url','attribute'=>'public_url'],'available'=>['label'=>'موجود / Available','type'=>'boolean']],
            scope:fn($context)=>\App\Models\DemoProduct::query()->where('tenant_key','sandbox')->where('published',true),
            access:fn($context)=>true, public:true,
            filterDefinitions:['category'=>['column'=>'category','rules'=>['string','max:80'],'operator'=>'eq'],'available'=>['column'=>'available','rules'=>['boolean'],'operator'=>'eq']],
            sortDefinitions:['price'=>['label'=>'قیمت / Price','column'=>'price','direction'=>'asc'],'newest'=>['label'=>'جدیدترین / Newest','column'=>'id','direction'=>'desc']]
        ));
        config(['filesystems.disks.filaforge_media'=>['driver'=>'local','root'=>storage_path('app/filaforge-media'),'throw'=>true]]);
        foreach (['filaforge.comment','filaforge.viewComments'] as $ability) {
            \Illuminate\Support\Facades\Gate::define($ability, fn (?\Illuminate\Contracts\Auth\Authenticatable $user, \FilaForge\Studio\Models\PageLocale $page) => $page->page()->exists());
        }
        \Illuminate\Support\Facades\Gate::define('filaforge.moderateAllComments',fn($user)=>$user->email==='admin@filaforge.test');
        foreach (['viewAny', 'view', 'create', 'update', 'publish'] as $ability) {
            \Illuminate\Support\Facades\Gate::define('filaforge.'.$ability, fn ($user) => $user->email === 'admin@filaforge.test');
        }
    }
}
