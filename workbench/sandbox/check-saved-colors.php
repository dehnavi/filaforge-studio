<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p=FilaForge\Studio\Models\PageLocale::where('slug','color-icon-test')->firstOrFail();$html=app(FilaForge\Studio\Rendering\BladeRenderer::class)->render($p->draft_document);
file_put_contents('/tmp/filaforge-sandbox-20261101/color-debug.json',json_encode($p->draft_document,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));file_put_contents('/tmp/filaforge-sandbox-20261101/color-debug.html',$html);foreach(['color:#c15795','fill="#b35fca"'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Color lost: '.$needle);
echo "Browser-saved custom text and icon colors preserved in server rendering.\n";
