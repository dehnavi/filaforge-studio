<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class RevisionHistoryTest extends AuthoringTestCase
{
    public function test_revision_restore_is_scoped_versioned_and_does_not_publish(): void
    {
        $this->admin();$page=$this->page();$service=app(PageDocuments::class);$revision=$service->publish($page,0);$page->refresh();
        $next=$page->draft_document;$next['root']['children'][0]['props']['text']='New draft';$service->save($page,$next,1);
        $this->assertSame(3,$service->restore($page,$revision->id,2));
        $this->assertSame($revision->id,$page->fresh()->published_revision_id);
        $this->assertSame('Published',$page->fresh()->draft_document['root']['children'][0]['props']['text']);
        $other=$this->page();$foreign=$service->publish($other,0);
        try {$service->restore($page,$foreign->id,3);$this->fail('Cross-page revision accepted');}catch(\Illuminate\Database\Eloquent\ModelNotFoundException){$this->addToAssertionCount(1);}
        $this->expectException(\RuntimeException::class);$service->restore($page,$revision->id,1);
    }
}
