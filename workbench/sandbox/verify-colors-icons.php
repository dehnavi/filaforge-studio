<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$registry=app(FilaForge\Studio\Core\WidgetRegistry::class);$registry->get('icon')->validate(['name'=>'magnifying-glass','size'=>48,'color'=>'#a16bcd']);
$clean=FilaForge\Studio\Rendering\RichText::clean('<p><span data-color="#a16bcd" onclick="alert(1)">رنگ دلخواه</span><span data-color="red;background:url(x)">خطرناک</span></p>');
if(!str_contains($clean,'style="color:#a16bcd"')||str_contains($clean,'onclick')||str_contains($clean,'url(x)'))throw new RuntimeException('Color sanitizer failed: '.$clean);
$d=FilaForge\Studio\Core\DocumentValidator::empty('fa','rtl');$n=$d['root'];$n['id']='custom-icon';$n['type']='icon';$n['props']=['name'=>'magnifying-glass','size'=>48,'color'=>'#a16bcd'];$d['root']['children'][]=$n;$html=app(FilaForge\Studio\Rendering\BladeRenderer::class)->render($d);if(!str_contains($html,'fill="#a16bcd"'))throw new RuntimeException('Icon color missing');
try{$registry->get('icon')->validate(['name'=>'not-an-icon','size'=>48,'color'=>'#a16bcd']);throw new RuntimeException('Unknown icon accepted');}catch(InvalidArgumentException){}
try{$registry->get('icon')->validate(['name'=>'star','size'=>48,'color'=>'red;background:url(x)']);throw new RuntimeException('CSS injection accepted');}catch(InvalidArgumentException){}
echo count(FilaForge\Studio\Widgets\Icons::all())." Font Awesome icons; custom text/icon colors and injection rejection passed.\n";
$p=FilaForge\Studio\Models\PageLocale::where('slug','color-icon-test')->first();if(!$p){$source=FilaForge\Studio\Models\PageLocale::where('slug','editor-upgrade')->firstOrFail();$page=FilaForge\Studio\Models\Page::create();$p=FilaForge\Studio\Models\PageLocale::create(['page_id'=>$page->id,'locale'=>'fa','title'=>'تست رنگ و آیکون','slug'=>'color-icon-test','direction'=>'rtl','draft_document'=>$source->draft_document]);}
echo 'http://127.0.0.1:8875/admin/pages/'.$p->id.'/edit'.PHP_EOL;
