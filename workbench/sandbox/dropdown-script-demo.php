<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use FilaForge\Studio\Models\{Page,PageLocale};use FilaForge\Studio\Core\DocumentValidator;use FilaForge\Studio\Presets\PresetRegistry;
$page=PageLocale::where('slug','dropdown-script-demo')->first();
if(!$page){$doc=DocumentValidator::empty('fa','rtl');$r=app(PresetRegistry::class);$doc['root']['children']=[$r->instantiate('dropdown-menu'),$r->instantiate('hero'),$r->instantiate('features')];$doc['integrations']=['bodyEnd'=>'document.documentElement.dataset.filaforgeScriptDemo = "ready";'];$page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'منوی دراپ‌داون و اسکریپت صفحه','slug'=>'dropdown-script-demo','direction'=>'rtl','draft_document'=>$doc]);auth()->login(App\Models\User::where('email','admin@filaforge.test')->firstOrFail());app(FilaForge\Studio\Documents\PageDocuments::class)->publish($page,0);}
echo $page->id."\n";
