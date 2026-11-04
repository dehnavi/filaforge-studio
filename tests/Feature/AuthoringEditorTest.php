<?php

namespace FilaForge\Studio\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class AuthoringEditorTest extends AuthoringTestCase
{
    public function test_editor_endpoints_authorize_page_and_render_runtime_with_new_controls(): void
    {
        $this->admin();$page=$this->page();$editor=$this->editor($page);
        $catalog=$editor->dynamicCatalog();$this->assertArrayHasKey('sections',$catalog);$this->assertFalse($catalog['structureManager']);
        $this->assertArrayHasKey('node',$editor->makeRepeater('company',['name']));
        $this->assertArrayHasKey('error',$editor->makeRepeater('company',['secret']));
        $runtime=view('filaforge::editor.runtime')->render();
        $this->assertStringContainsString('scheduleAutosave()',$runtime);$this->assertStringNotContainsString('@include(',$runtime);$this->assertStringNotContainsString('@js(',$runtime);
        if ($output=getenv('FILAFORGE_RUNTIME_OUTPUT')) file_put_contents($output,substr($runtime,strpos($runtime,'<script>')+8,strrpos($runtime,'</script>')-strpos($runtime,'<script>')-8));
        Gate::define('filaforge.update',fn()=>false);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);$editor->revisionHistory();
    }
}
