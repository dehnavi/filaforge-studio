<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Presets\PresetRegistry;
use FilaForge\Studio\Models\{Page,PageLocale};
use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\Rendering\{HtmlRenderer,HtmlOptions,BladeRenderer};
use FilaForge\Studio\Integrations\PageIntegrations;
use Illuminate\Support\Facades\Gate;
final class MenuAndScriptsTest extends TestCase
{
    private function page():PageLocale{$this->actingAs($this->user(['email'=>'admin@filaforge.test']));return PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','direction'=>'rtl','title'=>'Menu','slug'=>'menu-test','draft_document'=>DocumentValidator::empty('fa','rtl')]);}
    private function privilege():void{Gate::define('filaforge.manageScripts',fn($user,$page)=>true);config(['filaforge.integrations.custom_scripts_enabled'=>true]);}
    public function test_dropdown_menu_uses_native_accessible_disclosure_and_escaped_links():void
    {
        $d=DocumentValidator::empty('fa','rtl');$d['root']['children']=[app(PresetRegistry::class)->instantiate('dropdown-menu')];$d['root']['children'][0]['children'][0]['props']['label']='<script>bad</script>';
        $html=app(BladeRenderer::class)->render($d);$this->assertStringContainsString('data-ff-menu',$html);$this->assertStringContainsString('<details data-ff-dropdown>',$html);$this->assertStringContainsString('&lt;script&gt;bad&lt;/script&gt;',$html);$this->assertStringContainsString('aria-label=',$html);$this->assertStringNotContainsString('<script>bad',$html);
        $d['root']['children'][0]['children'][0]['props']['url']='javascript:alert(1)';$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d);
    }
    public function test_menu_tree_rejects_orphan_items_and_excessive_nesting():void
    {
        $preset=app(PresetRegistry::class)->instantiate('dropdown-menu');$d=DocumentValidator::empty();$d['root']['children']=[$preset['children'][0]];
        try{app(DocumentValidator::class)->validate($d);$this->fail('Orphan accepted');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}
        $item=$preset['children'][0];$root=$item;for($i=0;$i<4;$i++){$copy=$item;$copy['id']='nested-'.$i;$copy['children']=[$root];$root=$copy;}$preset['children']=[$root];$d['root']['children']=[$preset];
        $this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d);
    }
    public function test_editor_permission_cannot_add_or_remove_scripts():void
    {
        $page=$this->page();$doc=$page->draft_document;$doc['integrations']=['gtm'=>'GTM-TEST123'];
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);app(PageDocuments::class)->save($page,$doc,0);
    }
    public function test_raw_scripts_require_config_opt_in_even_with_permission():void
    {
        $page=$this->page();Gate::define('filaforge.manageScripts',fn($user,$page)=>true);$doc=$page->draft_document;$doc['integrations']=['head'=>'window.test=true;'];
        $this->expectException(\InvalidArgumentException::class);app(PageDocuments::class)->save($page,$doc,0);
    }
    public function test_script_breakout_unknown_options_and_bad_gtm_ids_are_rejected():void
    {
        foreach([['gtm'=>'GTM-X"<script>'],['head'=>'</ScRiPt><img onerror=alert(1)>'],['bodyEnd'=>'<script src="https://bad.test"></script>'],['sql'=>'select * from users'],['head'=>str_repeat('a',20001)]] as $settings){try{app(PageIntegrations::class)->normalize($settings);$this->fail('Unsafe settings accepted');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}}
    }
    public function test_integrations_are_versioned_and_only_published_output_executes_them():void
    {
        $page=$this->page();$this->privilege();$documents=app(PageDocuments::class);$doc=$page->draft_document;$doc['integrations']=['gtm'=>'GTM-TEST123','head'=>'window.release="first";','bodyEnd'=>'window.end=true;'];
        $documents->save($page,$doc,0);$revision=$documents->publish($page,1);$this->assertSame($doc['integrations'],$revision->document['integrations']);
        $renderer=app(HtmlRenderer::class);$html=$renderer->publishedResponse($page->fresh())->getContent();$this->assertStringContainsString('gtm.js?id=',$html);$this->assertStringContainsString('ns.html?id=GTM-TEST123',$html);$this->assertStringContainsString('window.release="first";',$html);$this->assertTrue(strpos($html,'<body>')<strpos($html,'<noscript>'));$this->assertTrue(strpos($html,'window.end=true;')<strpos($html,'</body>'));
        $this->assertStringNotContainsString('gtm.js?id=',$renderer->document($doc)->toHtml());$this->assertStringNotContainsString('window.release',$renderer->fragment($doc)->toHtml());
        $doc['integrations']['head']='window.release="draft";';$documents->save($page,$doc,2);$this->assertStringNotContainsString('window.release="draft";',$renderer->publishedResponse($page->fresh())->getContent());
        config(['filaforge.integrations.custom_scripts_enabled'=>false,'filaforge.integrations.gtm_enabled'=>false]);$html=$renderer->publishedResponse($page->fresh())->getContent();$this->assertStringNotContainsString('googletagmanager.com',$html);$this->assertStringNotContainsString('window.release',$html);
    }
    public function test_publish_and_stale_model_cannot_bypass_script_permission():void
    {
        $page=$this->page();$stale=$page->replicate();$stale->id=$page->id;$this->privilege();$doc=$page->draft_document;$doc['integrations']=['head'=>'window.allowed=true;'];app(PageDocuments::class)->save($page,$doc,0);Gate::define('filaforge.manageScripts',fn($user,$page)=>false);
        try{app(PageDocuments::class)->publish($page,1);$this->fail('Publication accepted');}catch(\Illuminate\Auth\Access\AuthorizationException){$this->addToAssertionCount(1);}
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);app(PageDocuments::class)->save($stale,$stale->draft_document,1);
    }
    public function test_preview_never_includes_page_scripts_and_nonce_is_supported():void
    {
        $page=$this->page();$doc=$page->draft_document;$doc['integrations']=['gtm'=>'GTM-TEST123','head'=>'window.PREVIEW_MARKER=true;'];$this->privilege();
        $editor=new \FilaForge\Studio\Filament\Resources\Pages\EditPage;$editor->pageId=$page->id;$result=$editor->previewDocument($doc);$this->assertArrayHasKey('html',$result);$this->assertStringNotContainsString('PREVIEW_MARKER',$result['html']);$this->assertStringNotContainsString('googletagmanager.com',$result['html']);
        $html=app(HtmlRenderer::class)->document($doc,new HtmlOptions(includePageScripts:true,scriptNonce:'testNonce123'))->toHtml();$this->assertStringContainsString('nonce="testNonce123"',$html);$this->assertStringContainsString('tag.nonce="testNonce123"',$html);
    }
}
