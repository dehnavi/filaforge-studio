<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Documents\PageDocuments;
use Illuminate\Support\Facades\Gate;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class EditingRulesTest extends AuthoringTestCase
{
    public function test_content_only_rules_are_enforced_server_side_and_cannot_be_stripped_or_moved(): void
    {
        $this->admin();$node=$this->node('heading');$node['meta']=['editable'=>['text']];$page=$this->page($this->doc([$node]));
        $next=$page->draft_document;$next['root']['children'][0]['props']['text']='Allowed';
        $this->assertSame(1,app(PageDocuments::class)->save($page,$next,0));$page->refresh();
        foreach (['style','tag','delete','unlock','move'] as $change) {
            $next=$page->draft_document;
            switch ($change) {
                case 'style':$next['root']['children'][0]['styles']['color']='#123456';break;
                case 'tag':$next['root']['children'][0]['props']['tag']='h1';break;
                case 'delete':$next['root']['children']=[];break;
                case 'unlock':$next['root']['children'][0]['meta']=[];break;
                case 'move':array_unshift($next['root']['children'],$this->node('text',[],[],'extra'));break;
            }
            try { app(PageDocuments::class)->save($page,$next,1);$this->fail('Protected mutation accepted: '.$change); } catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        $this->assertSame(1,$page->fresh()->lock_version);
        Gate::define('filaforge.manageStructure',fn($user,$page)=>true);
        $next=$page->draft_document;$next['root']['children'][0]['meta']=[];
        $this->assertSame(2,app(PageDocuments::class)->save($page,$next,1));
    }

    public function test_locked_subtree_and_new_rule_injection_are_rejected(): void
    {
        $this->admin();$node=$this->node('text');$node['meta']=['locked'=>true];$page=$this->page($this->doc([$node]));
        foreach (['edit','inject'] as $action) {
            $next=$page->draft_document;
            if ($action==='edit') $next['root']['children'][0]['props']['text']='Blocked';
            else {$extra=$this->node('text',[],[],'extra');$extra['meta']=['locked'=>true];$next['root']['children'][]=$extra;}
            try { app(PageDocuments::class)->save($page,$next,0);$this->fail('Protected mutation accepted'); } catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function test_layer_names_do_not_implicitly_lock_position_or_deletion(): void
    {
        $this->admin();$named=$this->node('text');$named['meta']=['label'=>'Named layer'];$page=$this->page($this->doc([$named]));
        $next=$page->draft_document;array_unshift($next['root']['children'],$this->node('text',[],[],'before'));
        $this->assertSame(1,app(PageDocuments::class)->save($page,$next,0));
        $next['root']['children']=[];
        $this->assertSame(2,app(PageDocuments::class)->save($page,$next,1));
    }

    public function test_malformed_editing_rules_are_rejected_as_validation_errors(): void
    {
        foreach ([['meta'=>['editable'=>null]],['meta'=>['locked'=>'yes']],['meta'=>['editable'=>['unknown']]]] as $change) {
            $node=array_replace($this->node('text'),$change);
            try { app(DocumentValidator::class)->validate($this->doc([$node]));$this->fail('Malformed authoring metadata accepted'); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }
}
