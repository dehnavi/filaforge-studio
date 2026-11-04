<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Rendering\SafeHtml;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class HtmlBlockTest extends AuthoringTestCase
{
    public function test_html_block_preserves_markup_and_never_executes_scripts_blade_or_php(): void
    {
        $html='<table><tr><td>Safe</td></tr></table><script>alert(1)</script><img src="/ok.jpg" onerror="alert(2)"><a href="javascript:alert(3)">Link</a><div style="position:fixed">{{ 7*7 }} <?php echo "EXECUTED"; ?></div>';
        $result=app(BladeRenderer::class)->render($this->doc([$this->node('html',['html'=>$html])]));
        foreach (['<table>','Safe','{{ 7*7 }}'] as $value) $this->assertStringContainsString($value,$result);
        foreach (['<script>','onerror','javascript:','position:fixed','EXECUTED'] as $value) $this->assertStringNotContainsString($value,$result);
        $this->assertSame(SafeHtml::clean($html),SafeHtml::clean(SafeHtml::clean($html)));
    }
}
