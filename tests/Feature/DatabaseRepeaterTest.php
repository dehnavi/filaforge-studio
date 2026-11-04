<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\DynamicData\DataContext;
use FilaForge\Studio\DynamicData\DynamicElementFactory;
use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class DatabaseRepeaterTest extends AuthoringTestCase
{
    public function test_repeater_renders_database_rows_with_tenant_scope_filters_sort_and_pagination(): void
    {
        $this->products();
        $loop=app(DynamicElementFactory::class)->repeater('products',['title','price'],['limit'=>2],3);
        $this->assertSame('@item',$loop['children'][0]['children'][0]['bindings']['text']['provider']);
        $this->assertSame(1,$loop['responsive']['mobile']['columns']);
        $renderer=app(BladeRenderer::class);$doc=$this->doc([$loop]);
        $html=$renderer->render($doc,null,new DataContext(tenantKey:'a'));
        foreach(['First','Second','grid-template-columns:repeat(3','rel="next"'] as $value) $this->assertStringContainsString($value,$html);
        foreach(['Third','Draft','Other tenant','PRIVATE'] as $value) $this->assertStringNotContainsString($value,$html);
        $this->assertStringContainsString('Third',$renderer->render($doc,null,new DataContext(tenantKey:'a',pagination:[$loop['id']=>2])));
        $loop['props']['query']['filters']=['price'=>20];
        $html=$renderer->render($this->doc([$loop]),null,new DataContext(tenantKey:'a'));
        $this->assertStringContainsString('Second',$html);$this->assertStringNotContainsString('First',$html);
        $this->assertStringContainsString(__('filaforge::studio.builder.no_results'),$renderer->render($doc,null,new DataContext));
    }

    public function test_repeater_rejects_unregistered_fields_sql_identifiers_and_invalid_bounds(): void
    {
        $this->products();
        foreach ([['products',['secret'],[],3],['App\\Models\\User',['title'],[],3],['products',['title'],['table'=>'users'],3],['products',['title'],['limit'=>100],3],['products',['title'],[],7],['products',[],[],2]] as $args) {
            try { app(DynamicElementFactory::class)->repeater(...$args); $this->fail('Unsafe repeater accepted'); } catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }
}
