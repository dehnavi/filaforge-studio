<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$demoPassword=(string) env('FILAFORGE_DEMO_ADMIN_PASSWORD','');
if (strlen($demoPassword)<24) {
    fwrite(STDERR, 'Set FILAFORGE_DEMO_ADMIN_PASSWORD to a random value of at least 24 characters in the local .env file.'.PHP_EOL);
    exit(1);
}
$user=App\Models\User::updateOrCreate(['email'=>'admin@filaforge.test'],['name'=>'مدیر تست','password'=>Illuminate\Support\Facades\Hash::make($demoPassword)]);
Illuminate\Support\Facades\Auth::login($user);
$doc=FilaForge\Studio\Core\DocumentValidator::empty('fa','rtl');
$node=$doc['root'];$node['id']='welcome-heading';$node['type']='heading';$node['props']=['text'=>'سلام! به محیط تست مستقل FilaForge Studio خوش آمدید.','tag'=>'h1'];$doc['root']['children'][]=$node;
$page=FilaForge\Studio\Models\Page::create();
$locale=FilaForge\Studio\Models\PageLocale::create(['page_id'=>$page->id,'locale'=>'fa','title'=>'صفحهٔ آزمایشی فارسی','slug'=>'test-page','direction'=>'rtl','draft_document'=>$doc]);
app(FilaForge\Studio\Documents\PageDocuments::class)->publish($locale,0);
echo 'Editor: /admin/pages/'.$locale->id.'/edit'.PHP_EOL;
echo 'Test administrator password was read from the local .env file.'.PHP_EOL;
