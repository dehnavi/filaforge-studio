<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use FilaForge\Studio\Models\{Page,PageLocale};
$page=PageLocale::where('slug','picker-ux')->first();
if(!$page)$page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'آزمایش ابزارهای تصویری','slug'=>'picker-ux','direction'=>'rtl','draft_document'=>FilaForge\Studio\Core\DocumentValidator::empty('fa','rtl')]);
echo $page->id."\n";
