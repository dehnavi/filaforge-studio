<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
use FilaForge\Studio\DynamicData\{DynamicElementFactory,ShortcodeRegistry,DataRegistry,ArrayDataProvider};
use FilaForge\Studio\Models\{Page,PageLocale};
use FilaForge\Studio\Core\DocumentValidator;
use Illuminate\Support\Facades\Gate;
final class DynamicPickerTest extends TestCase
{
    public function test_visual_field_selection_creates_native_validated_binding(): void
    {
        $factory=app(DynamicElementFactory::class);$node=$factory->make('field','company','phone');
        $this->assertSame('text',$node['type']);$this->assertSame('phone',$node['bindings']['text']['field']);$this->assertSame('company',$node['bindings']['text']['provider']);
        $link=$factory->make('field','company','url');$this->assertSame('link',$link['type']);$this->assertArrayHasKey('url',$link['bindings']);
        app(DataRegistry::class)->register(new ArrayDataProvider('photos','Photos',['photo'=>['label'=>'Photo','type'=>'image']],fn($c)=>[],fn($c)=>true,true));
        $image=$factory->make('field','photos','photo');$this->assertSame('image',$image['type']);$this->assertArrayHasKey('src',$image['bindings']);
    }
    public function test_selection_rejects_unknown_fields_sources_and_executable_names(): void
    {
        foreach([['field','company','password'],['field','App-User','name'],['component','','<script>'],['php','','eval']] as $args){
            try{app(DynamicElementFactory::class)->make(...$args);$this->fail('Unsafe selection accepted');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}
        }
    }
    public function test_component_options_are_validated_without_executing_component(): void
    {
        $calls=0;app(ShortcodeRegistry::class)->register('notice','Notice',['message'=>['required','string','max:30']],function()use(&$calls){$calls++;return [];},fn($c)=>true,true);
        $node=app(DynamicElementFactory::class)->make('component','','notice',['message'=>'Hello']);$this->assertSame(0,$calls);$this->assertSame('Hello',$node['props']['arguments']['message']);
        $this->expectException(\InvalidArgumentException::class);app(DynamicElementFactory::class)->make('component','','notice',['message'=>'Hello','view'=>'phpinfo']);
    }
    public function test_editor_selection_checks_page_permission_and_returns_generic_error(): void
    {
        $this->actingAs($this->user(['email'=>'admin@filaforge.test']));$page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','direction'=>'rtl','title'=>'Picker','slug'=>'picker','draft_document'=>DocumentValidator::empty('fa','rtl')]);
        $editor=new \FilaForge\Studio\Filament\Resources\Pages\EditPage;$editor->pageId=$page->id;
        $this->assertArrayHasKey('node',$editor->makeDynamicElement('component','','contact-card'));
        $this->assertSame(['error'=>__('filaforge::studio.picker_failed')],$editor->makeDynamicElement('field','company','password'));
        Gate::define('filaforge.update',fn($user)=>false);$this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);$editor->makeDynamicElement('field','company','phone');
    }
}
