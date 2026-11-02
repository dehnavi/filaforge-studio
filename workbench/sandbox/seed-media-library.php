<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$repo=app(FilaForge\Studio\Media\MediaRepository::class);
foreach([['/tmp/filaforge-sandbox-20261101/fixtures/studio-test.png','studio-test.png'],[public_path('sandbox-media/sample.mp4'),'sample.mp4']]as[$path,$name]){$media=$repo->store(new Illuminate\Http\UploadedFile($path,$name,null,null,true));echo $media->name.' '.$media->id.PHP_EOL;}
