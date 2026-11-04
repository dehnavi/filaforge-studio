<?php

namespace FilaForge\Studio\Tests;

use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Core\WidgetRegistry;
use FilaForge\Studio\DynamicData\DataRegistry;
use FilaForge\Studio\DynamicData\EloquentDataProvider;
use FilaForge\Studio\Filament\Resources\Pages\EditPage;
use FilaForge\Studio\Models\Page;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Tests\Fixtures\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class AuthoringTestCase extends TestCase
{
    protected function node(string $type, array $props = [], array $children = [], string $id = 'sample'): array
    {
        return ['id'=>$id,'type'=>$type,'widgetVersion'=>1,'props'=>array_replace(app(WidgetRegistry::class)->get($type)->defaults(),$props),'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[],'children'=>$children];
    }

    protected function doc(array $nodes): array
    {
        $document=DocumentValidator::empty(); $document['root']['children']=$nodes; return $document;
    }

    protected function page(?array $document = null): PageLocale
    {
        return PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'en','direction'=>'ltr','title'=>'Section','slug'=>'section-'.uniqid(),'draft_document'=>$document ?? $this->doc([$this->node('text',['text'=>'Published'])])]);
    }

    protected function admin(): void { $this->actingAs($this->user(['email'=>'admin@filaforge.test'])); }

    protected function editor(PageLocale $page): EditPage { $editor=new EditPage; $editor->pageId=$page->id; return $editor; }

    protected function products(): void
    {
        Schema::create('test_products', function (Blueprint $table) { $table->id(); $table->string('tenant'); $table->string('title'); $table->integer('price'); $table->boolean('published'); $table->string('secret'); });
        Product::insert([
            ['tenant'=>'a','title'=>'First','price'=>10,'published'=>true,'secret'=>'PRIVATE-A'],
            ['tenant'=>'a','title'=>'Second','price'=>20,'published'=>true,'secret'=>'PRIVATE-A'],
            ['tenant'=>'a','title'=>'Third','price'=>30,'published'=>true,'secret'=>'PRIVATE-A'],
            ['tenant'=>'a','title'=>'Draft','price'=>40,'published'=>false,'secret'=>'PRIVATE-DRAFT'],
            ['tenant'=>'b','title'=>'Other tenant','price'=>50,'published'=>true,'secret'=>'PRIVATE-B'],
        ]);
        app(DataRegistry::class)->register(new EloquentDataProvider('products','Products',[
            'title'=>['label'=>'Title','type'=>'text'],'price'=>['label'=>'Price','type'=>'number'],
        ],fn($context)=>Product::query()->where('tenant',$context->tenantKey)->where('published',true),fn($context)=>$context->tenantKey!==null,true,
        ['price'=>['column'=>'price','rules'=>['integer','min:0'],'operator'=>'eq']],['price'=>['label'=>'Price','column'=>'price','direction'=>'asc']]));
    }
}
