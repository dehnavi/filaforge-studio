<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$registry=app(FilaForge\Studio\Core\WidgetRegistry::class);$doc=FilaForge\Studio\Core\DocumentValidator::empty('fa','rtl');
foreach(['heading','rich-text','grid','columns','icon','table','video','quote'] as $kind){$n=$doc['root'];$n['children']=[];$n['id']='qa-'.$kind;$n['type']=$kind;$n['props']=$registry->get($kind)->defaults();if(in_array($kind,['grid','columns'])){for($i=0;$i<$n['props']['columns'];$i++){$c=$doc['root'];$c['children']=[];$c['id']=$kind.'-cell-'.$i;$t=$doc['root'];$t['children']=[];$t['id']=$kind.'-text-'.$i;$t['type']='text';$t['props']=['text'=>'محتوای ستون '.($i+1)];$c['children']=[$t];$n['children'][]=$c;}}$doc['root']['children'][]=$n;}
$html=app(FilaForge\Studio\Rendering\BladeRenderer::class)->render($doc);foreach(['<table','<svg','display:grid'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Missing '.$needle);
$rich=FilaForge\Studio\Rendering\RichText::clean('<h2 data-align="center">سلام</h2><p><span data-tone="red" data-size="large">متن</span><a href="https://example.com">لینک</a><script>alert(1)</script><span data-tone="bad">bad</span></p>');
if(!str_contains($rich,'data-align="center"')||!str_contains($rich,'data-tone="red"')||str_contains($rich,'<script')||str_contains($rich,'data-tone="bad"'))throw new RuntimeException('Rich sanitizer failed: '.$rich);
echo "8 widget types validated/rendered; rich formatting preserved; unsafe attributes stripped\n";
Illuminate\Support\Facades\Auth::login(App\Models\User::where('email','admin@filaforge.test')->firstOrFail());
$locale=FilaForge\Studio\Models\PageLocale::where('slug','editor-upgrade')->first();
if(!$locale){$page=FilaForge\Studio\Models\Page::create();$locale=FilaForge\Studio\Models\PageLocale::create(['page_id'=>$page->id,'locale'=>'fa','title'=>'تست ابزارهای جدید','slug'=>'editor-upgrade','direction'=>'rtl','draft_document'=>$doc]);app(FilaForge\Studio\Documents\PageDocuments::class)->publish($locale,0);}
echo 'http://127.0.0.1:8875/admin/pages/'.$locale->id.'/edit'.PHP_EOL;
