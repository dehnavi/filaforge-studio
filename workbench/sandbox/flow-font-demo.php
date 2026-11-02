<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use FilaForge\Studio\Models\{Page,PageLocale};use FilaForge\Studio\Core\DocumentValidator;use FilaForge\Studio\Presets\PresetRegistry;
$page=PageLocale::where('slug','flow-font-demo')->first();
if(!$page){$doc=DocumentValidator::empty('fa','rtl');$doc['root']['children']=[app(PresetRegistry::class)->instantiate('progress'),app(PresetRegistry::class)->instantiate('wizard')];$doc['root']['styles']=['fontFamily'=>'arial'];$page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'نوار پیشرفت و ویزارد','slug'=>'flow-font-demo','direction'=>'rtl','draft_document'=>$doc]);auth()->login(App\Models\User::where('email','admin@filaforge.test')->firstOrFail());app(FilaForge\Studio\Documents\PageDocuments::class)->publish($page,0);}
echo $page->id."\n";
