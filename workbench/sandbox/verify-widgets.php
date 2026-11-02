<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$registry=app(FilaForge\Studio\Core\WidgetRegistry::class);$renderer=app(FilaForge\Studio\Rendering\BladeRenderer::class);
foreach(['text','rich-text','button','image','divider','spacer'] as $kind){
 $d=FilaForge\Studio\Core\DocumentValidator::empty('fa','rtl');$n=$d['root'];$n['id']='widget-'.$kind;$n['type']=$kind;$n['props']=$registry->get($kind)->defaults();$d['root']['children'][]=$n;$renderer->render($d);echo $kind.': validated and rendered'.PHP_EOL;
}
$clean=FilaForge\Studio\Rendering\RichText::clean('<p onclick="alert(1)"><strong>سلام</strong><script>alert(1)</script><img src=x onerror=alert(1)></p>');
if(str_contains($clean,'<script')||str_contains($clean,'onclick')||str_contains($clean,'<img')||!str_contains($clean,'<strong>سلام</strong>'))throw new RuntimeException('Sanitizer failed');
echo "Rich text sanitization passed\n";
try{$registry->get('button')->validate(['text'=>'X','url'=>'javascript:alert(1)']);throw new RuntimeException('Unsafe URL accepted');}catch(InvalidArgumentException){echo "Unsafe URL rejected\n";}
