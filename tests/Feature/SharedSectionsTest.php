<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\DynamicData\DataContext;
use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Rendering\HtmlRenderer;
use FilaForge\Studio\Sections\SectionRegistry;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class SharedSectionsTest extends AuthoringTestCase
{
    public function test_shared_sections_follow_publication_and_authorize_every_resolution(): void
    {
        $this->admin();$source=$this->page($this->doc([$this->node('text',['text'=>'Published'],[],'title'),$this->node('section',['anchor'=>'guarantee'],[$this->node('link',['text'=>'Jump','url'=>'#guarantee'],[],'jump')],'guarantee-section')]));app(PageDocuments::class)->publish($source,0);$source->refresh();
        $registry=app(SectionRegistry::class);
        $resolutions=0;
        $registry->register('notice','Notice',function($context)use($source,&$resolutions){$resolutions++;return $source->fresh();},fn($context)=>$context->tenantKey==='a');
        $doc=$this->doc([$this->node('shared-section',['key'=>'notice'],[],'one'),$this->node('shared-section',['key'=>'notice'],[],'two')]);
        $renderer=app(BladeRenderer::class);
        $html=$renderer->render($doc,null,new DataContext(tenantKey:'a'));$this->assertSame(2,substr_count($html,'Published'));$this->assertSame(1,$resolutions);
        $dom=new \DOMDocument;@$dom->loadHTML($html);$xpath=new \DOMXPath($dom);foreach($xpath->query('//a[starts-with(@href,"#")]') as $link)$this->assertSame(1,$xpath->query('//*[@id="'.substr($link->getAttribute('href'),1).'"]')->length);
        $dom=new \DOMDocument;@$dom->loadHTML($html);$xpath=new \DOMXPath($dom);$ids=[];foreach($xpath->query('//*[@data-node]') as $element)$ids[]=$element->getAttribute('data-node');$this->assertCount(count(array_unique($ids)),$ids);
        $this->assertStringNotContainsString('Published',$renderer->render($doc,null,new DataContext(tenantKey:'b')));
        $this->assertSame([],$registry->catalog(new DataContext(tenantKey:'b')));
        $next=$source->draft_document;$next['root']['children'][0]['props']['text']='Updated';app(PageDocuments::class)->save($source,$next,1);
        $this->assertStringNotContainsString('Updated',$renderer->render($doc,null,new DataContext(tenantKey:'a')));
        app(PageDocuments::class)->publish($source,2);
        $this->assertSame(2,substr_count($renderer->render($doc,null,new DataContext(tenantKey:'a')),'Updated'));
    }

    public function test_shared_section_cycles_are_bounded(): void
    {
        $this->admin();$source=$this->page($this->doc([$this->node('shared-section',['key'=>'cycle'])]));app(PageDocuments::class)->publish($source,0);
        app(SectionRegistry::class)->register('cycle','Cycle',fn($context)=>$source->fresh(),fn($context)=>true);
        $this->expectException(\InvalidArgumentException::class);
        app(BladeRenderer::class)->render($source->draft_document);
    }

    public function test_shared_section_overrides_only_exposed_properties_without_changing_source(): void
    {
        $this->admin();$source=$this->page();app(PageDocuments::class)->publish($source,0);
        $registry=app(SectionRegistry::class);
        $registry->register('cta','CTA',fn($context)=>$source->fresh(),fn($context)=>true, [
            'headline'=>['node'=>'sample','property'=>'text','label'=>'Headline','type'=>'text'],
        ]);
        $one=$this->node('shared-section',['key'=>'cta','values'=>['headline'=>'First instance']],[],'one');
        $two=$this->node('shared-section',['key'=>'cta'],[],'two');
        $html=app(BladeRenderer::class)->render($this->doc([$one,$two]));
        $this->assertStringContainsString('First instance',$html);$this->assertStringContainsString('Published',$html);
        $this->assertSame('Published',$source->fresh()->draft_document['root']['children'][0]['props']['text']);
        $fields=$registry->catalog(new DataContext)[0]['fields'];$this->assertSame('Headline',$fields['headline']['label']);
        $one['props']['values']=['headline'=>'<script>bad</script>'];
        $html=app(BladeRenderer::class)->render($this->doc([$one]));$this->assertStringNotContainsString('<script>bad',$html);$this->assertStringContainsString('&lt;script&gt;bad',$html);
        $one['props']['values']=['unknown'=>'SHOULD NOT RENDER'];
        $this->assertStringNotContainsString('SHOULD NOT RENDER',app(BladeRenderer::class)->render($this->doc([$one])));
        $this->assertSame('Published',app(HtmlRenderer::class)->publishedDocument($source->fresh())['root']['children'][0]['props']['text']);
    }
}
