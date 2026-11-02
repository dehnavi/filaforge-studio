<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Schema;use Illuminate\Database\Schema\Blueprint;use FilaForge\Studio\Models\{Page,PageLocale};use FilaForge\Studio\Core\DocumentValidator;
if(!Schema::hasTable('filaforge_demo_products'))Schema::create('filaforge_demo_products',function(Blueprint $t){$t->id();$t->string('tenant_key');$t->string('title');$t->decimal('price',12,2);$t->boolean('available');$t->boolean('published');$t->string('category');});
if(!App\Models\DemoProduct::exists())foreach([['sandbox','طراحی وب',2500000,true,true],['sandbox','فروشگاه آنلاین',4900000,true,true],['sandbox','مشاورهٔ اختصاصی',890000,true,true],['other','PRIVATE TENANT',1,true,true],['sandbox','PRIVATE DRAFT',1,true,false]] as $r)App\Models\DemoProduct::create(array_combine(['tenant_key','title','price','available','published','category'],[...$r,'studio']));
if(PageLocale::where('slug','dynamic-demo')->exists()){echo PageLocale::where('slug','dynamic-demo')->value('id');exit;}
function n($id,$type,$props,$children=[],$bindings=[],$visibility=[]){return ['id'=>$id,'type'=>$type,'widgetVersion'=>1,'props'=>$props,'children'=>$children,'styles'=>[],'responsive'=>[],'bindings'=>$bindings,'visibility'=>$visibility,'meta'=>[]];}
$doc=DocumentValidator::empty('fa','rtl');$registry=app(FilaForge\Studio\Core\WidgetRegistry::class);
$heading=n('dynamic-company','heading',['text'=>'عنوان شرکت','tag'=>'h1'],[],['text'=>['provider'=>'company','field'=>'name','fallback'=>'شرکت']]);
$phone=n('dynamic-phone','text',['text'=>'شماره تماس'],[],['text'=>['provider'=>'company','field'=>'phone','fallback'=>'—']]);
$title=n('product-title','heading',['text'=>'نام محصول','tag'=>'h2'],[],['text'=>['provider'=>'@item','field'=>'title','fallback'=>'محصول']]);
$price=n('product-price','text',['text'=>'قیمت'],[],['text'=>['provider'=>'@item','field'=>'price','formatter'=>'persian_number','fallback'=>'—']]);
$link=n('product-link','button',['text'=>'بیشتر بدانید','url'=>'#'],[],['url'=>['provider'=>'@item','field'=>'url','fallback'=>'#']]);
$card=n('product-card','card',$registry->get('card')->defaults(),[$title,$price,$link],[],['provider'=>'@item','field'=>'available','operator'=>'eq','value'=>true]);
$loop=n('products-loop','query-loop',['provider'=>'products','query'=>['limit'=>2,'sort'=>'price'],'emptyText'=>'محصولی پیدا نشد'],[$card]);
$doc['root']['children']=[$heading,$phone,$loop,n('contact-component','shortcode',['name'=>'contact-card','arguments'=>[]])];
app(DocumentValidator::class)->validate($doc);auth()->login(App\Models\User::where('email','admin@filaforge.test')->firstOrFail());
$page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'نمونهٔ محتوای پویا','slug'=>'dynamic-demo','direction'=>'rtl','draft_document'=>$doc]);app(FilaForge\Studio\Documents\PageDocuments::class)->publish($page,0);echo $page->id."\n";
