<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Auth::login(App\Models\User::where('email','admin@filaforge.test')->firstOrFail());
$p=FilaForge\Studio\Models\PageLocale::where('slug','editor-upgrade')->firstOrFail();$d=$p->draft_document;foreach($d['root']['children'] as &$node)if($node['type']==='video')$node['props']['src']='/sandbox-media/sample.mp4';unset($node);
$service=app(FilaForge\Studio\Documents\PageDocuments::class);$version=$service->save($p,$d,$p->lock_version);$revision=$service->publish($p,$version);
$html=app(FilaForge\Studio\Rendering\BladeRenderer::class)->render($revision->document);
foreach(['<table','<svg','<video','data-align="center"','data-tone="green"','سلول ویرایش‌شده'] as $expected){if(!str_contains($html,$expected))throw new RuntimeException('Missing: '.$expected);}
echo "Published revision rendered with video, table, icon and saved inline formatting.\n";
