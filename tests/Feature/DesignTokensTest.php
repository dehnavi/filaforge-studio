<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class DesignTokensTest extends AuthoringTestCase
{
    public function test_design_tokens_remain_references_and_resolve_to_current_typed_values(): void
    {
        config(['filaforge.design_tokens'=>['brand'=>['label'=>'Brand','property'=>'color','value'=>'#112233'],'space'=>['label'=>'Spacing','property'=>'padding','value'=>24]]]);
        $node=$this->node('text');$node['styles']['color']=['token'=>'brand'];$node['responsive']['desktop']['padding']=['token'=>'space'];$doc=$this->doc([$node]);
        app(DocumentValidator::class)->validate($doc);
        $this->assertStringContainsString('color:#112233',app(BladeRenderer::class)->render($doc));
        config(['filaforge.design_tokens.brand.value'=>'#445566']);
        $this->assertStringContainsString('color:#445566',app(BladeRenderer::class)->render($doc));
        $this->assertSame(['token'=>'brand'],$doc['root']['children'][0]['styles']['color']);
        config(['filaforge.design_tokens.brand.value'=>'red;position:fixed']);
        $this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($doc);
    }

    public function test_malformed_tokens_are_rejected_as_validation_errors(): void
    {
        foreach ([['styles'=>['color'=>['token'=>[]]]],['styles'=>['color'=>['token'=>'missing']]]] as $change) {
            $node=array_replace($this->node('text'),$change);
            try { app(DocumentValidator::class)->validate($this->doc([$node]));$this->fail('Malformed authoring metadata accepted'); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }
}
