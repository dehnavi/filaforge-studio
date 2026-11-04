<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FilaForge\Studio\Core\{DocumentValidator,WidgetRegistry};
use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\DynamicData\DynamicElementFactory;
use FilaForge\Studio\Models\{Page,PageLocale};

$user=App\Models\User::where('email','admin@filaforge.test')->firstOrFail();
auth()->login($user);
$registry=app(WidgetRegistry::class);
$node=function(string $id,string $type,array $props=[],array $children=[])use($registry):array{
    return ['id'=>$id,'type'=>$type,'widgetVersion'=>1,'props'=>array_replace($registry->get($type)->defaults(),$props),'children'=>$children,'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[]];
};
$create=function(string $slug,string $title,array $children):PageLocale{
    if($existing=PageLocale::where('locale','fa')->where('slug',$slug)->first())return $existing;
    $document=DocumentValidator::empty('fa','rtl');$document['root']['children']=$children;
    $page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','direction'=>'rtl','slug'=>$slug,'title'=>$title,'draft_document'=>$document]);
    app(PageDocuments::class)->publish($page,0);
    return $page->fresh();
};
$source=$create('guarantee-source','منبع بخش مشترک: ضمانت خدمات',[
    $node('guarantee-card','card',['tone'=>'brand','padding'=>32],[
        $node('guarantee-heading','heading',['text'=>'ضمانت کیفیت و پشتیبانی خدمات','tag'=>'h2']),
        $node('guarantee-description','text',['text'=>'این بخش یک منبع مشترک دارد. با انتشار تغییرات منبع، همهٔ نمونه‌ها به‌روز می‌شوند.']),
        $node('guarantee-button','button',['text'=>'درخواست مشاوره','url'=>'#contact']),
    ]),
]);
$heading=$node('authoring-title','heading',['text'=>'قابلیت‌های جدید FilaForge Studio','tag'=>'h1']);
$heading['styles']['color']=['token'=>'brand-text'];
$heading['meta']=['label'=>'عنوان با رنگ مشترک برند'];
$loop=app(DynamicElementFactory::class)->repeater('products',['title','price','url'],['limit'=>2,'sort'=>'price'],3);
$loop['meta']=['label'=>'کارت‌های محصولات دیتابیس'];
$locked=$node('locked-note','text',['text'=>'این متن نمونهٔ عنصر قفل‌شده است. مدیر ساختار می‌تواند قفل آن را تغییر دهد.']);
$locked['meta']=['label'=>'نمونهٔ عنصر محافظت‌شده','locked'=>true];
$page=$create('authoring-demo','دموی HTML، بخش مشترک و repeater دیتابیس',[
    $heading,
    $node('authoring-intro','text',['text'=>'HTML مستقل، کارت‌های دیتابیس، استایل‌های مشترک، نسخه‌ها و ذخیرهٔ خودکار را در این صفحه امتحان کنید.']),
    $node('html-example','html',['html'=>'<section><h2>بخش HTML دلخواه</h2><p>این بخش از HTML ساخته شده و به layout خاصی وابسته نیست.</p><table><thead><tr><th>قابلیت</th><th>وضعیت</th></tr></thead><tbody><tr><td>HTML مستقل</td><td>فعال</td></tr><tr><td>نمایش اطلاعات دیتابیس</td><td>فعال</td></tr></tbody></table></section>']),
    $node('products-title','heading',['text'=>'محصولات از دیتابیس','tag'=>'h2']),
    $loop,
    $node('shared-default','shared-section',['key'=>'sandbox-guarantee']),
    $node('shared-override','shared-section',['key'=>'sandbox-guarantee','values'=>['headline'=>'عنوان اختصاصی نمونهٔ دوم؛ ساختار همچنان مشترک است']]),
    $locked,
    $node('contact','section',['anchor'=>'contact','tone'=>'soft','padding'=>32],[
        $node('contact-heading','heading',['text'=>'تماس با ما','tag'=>'h2']),
        $node('contact-text','text',['text'=>'این بخش مقصد پیوندهای داخل صفحه است.']),
    ]),
]);
echo json_encode(['editor'=>'/admin/pages/'.$page->id.'/edit','preview'=>'/preview/authoring-demo','sourceEditor'=>'/admin/pages/'.$source->id.'/edit','embedded'=>'/sections-demo'],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
