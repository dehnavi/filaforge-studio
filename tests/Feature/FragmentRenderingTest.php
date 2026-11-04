<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class FragmentRenderingTest extends AuthoringTestCase
{
    public function test_published_fragments_embed_twice_without_id_collisions_and_ignore_drafts(): void
    {
        $this->admin();
        $section=$this->node('section',['anchor'=>'contact'],[$this->node('button',['text'=>'Contact','url'=>'#contact'],[],'button'),$this->node('html',['html'=>'<div id="inside"><a href="#inside">Inside</a></div>'],[],'html-block')]);
        $section['responsive']=['mobile'=>['padding'=>12]];
        $page=$this->page($this->doc([$section]));app(PageDocuments::class)->publish($page,0);$page->refresh();
        $html=filaforge_section($page)->toHtml().filaforge_section($page)->toHtml();
        $dom=new \DOMDocument;@$dom->loadHTML($html);$xpath=new \DOMXPath($dom);$ids=[];foreach($xpath->query('//*[@data-node]') as $element)$ids[]=$element->getAttribute('data-node');$this->assertCount(count(array_unique($ids)),$ids);
        $anchors=[];foreach($xpath->query('//*[@id]') as $element)$anchors[]=$element->getAttribute('id');$this->assertCount(count(array_unique($anchors)),$anchors);
        foreach ($anchors as $id) $this->assertStringContainsString('href="#'.$id.'"',$html);
        $this->assertStringNotContainsString('<html',$html);$this->assertStringNotContainsString('<body',$html);
        $draft=$page->draft_document;$draft['root']['children'][0]['children'][0]['props']['text']='Unpublished';app(PageDocuments::class)->save($page,$draft,1);
        $this->assertStringNotContainsString('Unpublished',filaforge_section($page->fresh())->toHtml());
    }

    public function test_optional_asset_collector_deduplicates_identical_tags(): void
    {
        $assets=new \FilaForge\Studio\Rendering\RenderAssets;
        $markup='<style>.example{color:red}</style><div>Content</div><script src="/example.js" defer></script>';
        $this->assertSame('<div>Content</div>',$assets->capture($markup));
        $this->assertSame('<div>Content</div>',$assets->capture($markup));
        $this->assertSame(1,substr_count($assets->html()->toHtml(),'/example.js'));
        $this->admin();$page=$this->page();app(PageDocuments::class)->publish($page,0);
        $section=filaforge_section($page->fresh(),null,'asset-example',$assets)->toHtml();
        $this->assertStringNotContainsString('<style>',$section);
        $this->assertStringContainsString('<style>',$assets->html()->toHtml());
    }
}
