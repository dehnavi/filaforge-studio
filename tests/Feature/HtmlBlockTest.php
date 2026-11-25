<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Rendering\RenderAssets;
use FilaForge\Studio\Rendering\SafeHtml;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class HtmlBlockTest extends AuthoringTestCase
{
    public function test_html_block_keeps_enabled_scripts_in_place_with_their_original_targets(): void
    {
        config(['filaforge.embeds.scripts' => true, 'filaforge.embeds.inline_scripts' => true]);
        $html = '<article id="player"><p>سلام</p><script src="https://www.aparat.com/embed/example?data[rnddiv]=player&data[responsive]=yes"></script>'
            .'<script type="module">if (1 < 2) document.getElementById("player").dataset.ready = "<style>p{color:red}</style>";</script></article>';
        $clean = SafeHtml::cleanBlock($html);
        $result = app(BladeRenderer::class)->render($this->doc([$this->node('html', ['html' => $clean])]));
        $assets = new RenderAssets;
        $fragment = $assets->capture($result);

        $this->assertSame($clean, SafeHtml::cleanBlock($clean));
        $this->assertStringContainsString('<article id="player"><p>سلام</p><script src="https://www.aparat.com/embed/example?data[rnddiv]=player&amp;data[responsive]=yes" data-ff-embed=""></script>', $fragment);
        $this->assertStringContainsString('<script type="module" data-ff-embed="">if (1 < 2) document.getElementById("player").dataset.ready = "<style>p{color:red}</style>";</script></article>', $fragment);
        $this->assertStringNotContainsString('<script', $assets->html()->toHtml());
    }

    public function test_html_block_respects_external_and_inline_script_settings(): void
    {
        $html = '<p onclick="example()">Content</p><script src="https://cdn.example.com/player.js"></script>'
            .'<script src="https://other.example.com/player.js">invalidSource()</script><script>inlineOnly()</script>';
        $this->assertSame('<p>Content</p>', SafeHtml::cleanBlock($html));
        config(['filaforge.embeds.scripts' => true, 'filaforge.embeds.allowed_hosts' => ['cdn.example.com']]);
        $this->assertSame('<p>Content</p><script src="https://cdn.example.com/player.js" data-ff-embed=""></script>', SafeHtml::cleanBlock($html));
        config(['filaforge.embeds.inline_scripts' => true]);
        $this->assertStringNotContainsString('invalidSource()', SafeHtml::cleanBlock($html));
        $this->assertStringContainsString('<script data-ff-embed="">inlineOnly()</script>', SafeHtml::cleanBlock($html));
    }

    public function test_semantic_tags_are_preserved_in_rendered_html_blocks(): void
    {
        $html = '<article><header><h2>Title</h2></header><p>Some <span>text</span></p><footer>Footer</footer></article>';
        $result = app(BladeRenderer::class)->render($this->doc([$this->node('html', ['html' => $html])]));

        $this->assertStringContainsString($html, $result);
    }

    public function test_html_block_preserves_markup_and_never_executes_scripts_blade_or_php(): void
    {
        $html='<table><tr><td>Safe</td></tr></table><script>alert(1)</script><img src="/ok.jpg" onerror="alert(2)"><a href="javascript:alert(3)">Link</a><div style="position:fixed">{{ 7*7 }} <?php echo "EXECUTED"; ?></div>';
        $result=app(BladeRenderer::class)->render($this->doc([$this->node('html',['html'=>$html])]));
        foreach (['<table>','Safe','{{ 7*7 }}'] as $value) $this->assertStringContainsString($value,$result);
        foreach (['<script>','onerror','javascript:','position:fixed','EXECUTED'] as $value) $this->assertStringNotContainsString($value,$result);
        $this->assertSame(SafeHtml::clean($html),SafeHtml::clean(SafeHtml::clean($html)));
    }
}
