<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Core\WidgetRegistry;
use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\Models\Page;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\BladeRenderer;
use Illuminate\Support\Facades\Gate;
use FilaForge\Studio\Tests\TestCase;
final class StudioTest extends TestCase {
    private function document(): array { $d=DocumentValidator::empty('fa','rtl'); $n=$d['root']; $n['id']='heading';$n['type']='heading';$n['props']=['text'=>'سلام <script>alert(1)</script>','tag'=>'h1'];$d['root']['children'][]=$n;return $d; }
    private function page(): PageLocale { return PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'سلام','slug'=>'hello','direction'=>'rtl','draft_document'=>$this->document()]); }
    public function test_provider_registry_and_persian_rendering(): void { $this->assertEqualsCanonicalizing(['html','shared-section','query-loop','shortcode','dropdown-menu','menu-item','progress','wizard','wizard-step','data-pagination','container','heading','text','rich-text','button','image','divider','spacer','grid','columns','icon','table','video','quote','section','card','navigation','link','slider','carousel','comments','faq-item'],array_keys(app(WidgetRegistry::class)->all()));$html=app(BladeRenderer::class)->render($this->document());$this->assertStringContainsString('dir="rtl"',$html);$this->assertStringContainsString('سلام &lt;script&gt;',$html);$this->assertStringNotContainsString('<script>',$html); }
    public function test_duplicate_ids_are_rejected(): void { $d=$this->document();$d['root']['children'][0]['id']='root';$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d); }
    public function test_unsafe_heading_tag_is_rejected(): void { $d=$this->document();$d['root']['children'][0]['props']['tag']='script';$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d); }
    public function test_limits_are_enforced(): void { $this->expectException(\InvalidArgumentException::class);(new DocumentValidator(app(WidgetRegistry::class),10))->validate($this->document()); }
    public function test_save_conflict_and_publication_isolation(): void {
        Gate::before(fn (?\Illuminate\Contracts\Auth\Authenticatable $user) => true);$p=$this->page();$service=app(PageDocuments::class);$this->assertSame(1,$service->save($p,$this->document(),0));
        $revision=$service->publish($p,1);$d=$this->document();$d['root']['children'][0]['props']['text']='Draft';$service->save($p,$d,2);
        $this->assertSame($revision->document,$p->fresh()->publishedRevision->document);
        $this->expectException(\RuntimeException::class);$service->save($p,$d,1);
    }
    public function test_save_denies_unauthorized_callers(): void { $p=$this->page();$this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);app(PageDocuments::class)->save($p,$this->document(),0); }
    public function test_plugin_is_panel_opt_in(): void { $plugin=\FilaForge\Studio\Filament\FilaForgeStudioPlugin::make();$panel=\Filament\Panel::make()->id('studio');$plugin->register($panel);$this->assertContains(\FilaForge\Studio\Filament\Resources\PageResource::class,$panel->getResources());$this->assertSame('filaforge-studio',$plugin->getId()); }
    public function test_livewire_editor_renders_and_saves(): void {
        Gate::before(fn (?\Illuminate\Contracts\Auth\Authenticatable $user) => true);
        $panel=\Filament\Panel::make()->id('test')->path('test')->plugin(\FilaForge\Studio\Filament\FilaForgeStudioPlugin::make());
        \Filament\Facades\Filament::registerPanel($panel);
        \Filament\Facades\Filament::setCurrentPanel($panel);
        \Illuminate\Support\Facades\Route::name('filament.test.')->group(fn () => \FilaForge\Studio\Filament\Resources\PageResource::registerRoutes($panel));
        $page=$this->page();
        \Livewire\Livewire::test(\FilaForge\Studio\Filament\Resources\Pages\EditPage::class,['record'=>$page->id])
            ->assertSee('Page canvas')
            ->call('saveDocument',$this->document(),0)
            ->assertReturned(['version'=>1]);
    }
    public function test_editor_classes_and_templates_compile(): void {
        $this->assertTrue(class_exists(\FilaForge\Studio\Filament\Resources\Pages\EditPage::class));
        $this->assertTrue(class_exists(\FilaForge\Studio\Filament\Resources\Pages\CreatePage::class));
        foreach (['page','runtime'] as $name) {
            $compiled=app('blade.compiler')->compileString(file_get_contents(__DIR__.'/../../resources/views/editor/'.$name.'.blade.php'));
            $this->assertNotEmpty($compiled);
        }
    }
    public function test_duplicate_registry_entry_is_rejected(): void { $this->expectException(\InvalidArgumentException::class);app(WidgetRegistry::class)->register(new \FilaForge\Studio\Widgets\Heading); }
}
