<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Rendering\EmbedCode;
use FilaForge\Studio\Rendering\RenderAssets;
use FilaForge\Studio\Tests\AuthoringTestCase;
use Illuminate\Support\Facades\Gate;

final class EmbedWidgetTest extends AuthoringTestCase
{
    // Official Aparat embed codes for one video.
    private const SCRIPT = '<div id="80008716930"><script type="text/JavaScript" src="https://www.aparat.com/embed/gywe525?data[rnddiv]=80008716930&data[responsive]=yes&muted=true&titleShow=true&startTime=4&recom=self"></script></div>';
    private const IFRAME = '<style>.h_iframe-aparat_embed_frame{position:relative;}.h_iframe-aparat_embed_frame .ratio{display:block;width:100%;height:auto;}.h_iframe-aparat_embed_frame iframe{position:absolute;top:0;left:0;width:100%;height:100%;}</style><div class="h_iframe-aparat_embed_frame"><span style="display: block;padding-top: 57%"></span><iframe src="https://www.aparat.com/video/video/embed/videohash/gywe525/vt/frame?titleShow=true&startTime=4&muted=true&recom=self"  allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe></div>';
    private const SCRIPT_KEPT = '<div id="80008716930"><script src="https://www.aparat.com/embed/gywe525?data[rnddiv]=80008716930&amp;data[responsive]=yes&amp;muted=true&amp;titleShow=true&amp;startTime=4&amp;recom=self" data-ff-embed=""></script></div>';
    private const IFRAME_KEPT = '<iframe src="https://www.aparat.com/video/video/embed/videohash/gywe525/vt/frame?titleShow=true&amp;startTime=4&amp;muted=true&amp;recom=self" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>';

    public function test_script_embed_keeps_its_holder_and_external_script_in_order_only_when_enabled(): void
    {
        // Off by default: nothing of the script reaches visitors and the editor learns why.
        $this->assertSame('scripts_disabled', $this->rejected(self::SCRIPT));
        $html=app(BladeRenderer::class)->render($this->doc([$this->node('embed',['code'=>self::SCRIPT])]));
        $this->assertStringNotContainsString('data-node="sample"',$html);$this->assertStringNotContainsString('<script',$html);

        config(['filaforge.embeds.scripts'=>true]);
        $embed=EmbedCode::sanitize(self::SCRIPT);
        $this->assertSame(['code'=>self::SCRIPT_KEPT,'kind'=>'script','host'=>'www.aparat.com','notices'=>[]],$embed);
        // Cleaning the cleaned code changes nothing, so rendering may clean again on every request.
        $this->assertSame($embed['code'],EmbedCode::sanitize($embed['code'])['code']);
        $html=app(BladeRenderer::class)->render($this->doc([$this->node('embed',['code'=>self::SCRIPT,'title'=>'Setup video'])]));
        $this->assertStringContainsString('<div data-node="sample" class="ff-embed">'.self::SCRIPT_KEPT.'</div>',$html);
    }

    public function test_iframe_embed_drops_provider_styles_and_gets_a_responsive_frame(): void
    {
        $embed=EmbedCode::sanitize(self::IFRAME);
        $this->assertSame('<div class="h_iframe-aparat_embed_frame"><span style="display:block;padding-top:57%"></span>'.self::IFRAME_KEPT.'</div>',$embed['code']);
        $this->assertSame('iframe',$embed['kind']);$this->assertSame('www.aparat.com',$embed['host']);
        $this->assertSame($embed['code'],EmbedCode::sanitize($embed['code'])['code']);
        $this->assertSame(['removed'],$embed['notices'],'the style element and vendor-specific attributes are reported');

        $html=app(BladeRenderer::class)->render($this->doc([$this->node('embed',['code'=>self::IFRAME,'title'=>'Setup video'])]));
        $this->assertStringContainsString('<div data-node="sample" class="ff-embed ff-embed-frame">',$html,'a lone iframe scales with a fixed aspect ratio');
        $this->assertStringContainsString(str_replace(' allowfullscreen',' title="Setup video" allowfullscreen',self::IFRAME_KEPT),$html,'the element title names the frame for screen readers');
        // The frame owns its layout even when another embed's script adds a stylesheet to the page.
        foreach (['.ff-embed-frame{aspect-ratio:16/9}','.ff-embed-frame *{position:static!important}','.ff-embed-frame iframe{position:absolute!important;inset:0!important;width:100%!important;height:100%!important}'] as $rule) $this->assertStringContainsString($rule,$html);
        $this->assertStringNotContainsString('h_iframe-aparat_embed_frame{',$html);$this->assertStringNotContainsString('webkitallowfullscreen',$html);
        $html=app(BladeRenderer::class)->render($this->doc([$this->node('embed',['code'=>self::IFRAME])]));
        $this->assertStringContainsString('title="Embedded content"',$html);
    }

    public function test_unsafe_parts_never_reach_the_output(): void
    {
        config(['filaforge.embeds.scripts'=>true]);
        $attack='<iframe src="javascript:alert(1)" onload="alert(2)" srcdoc="&lt;script&gt;alert(3)&lt;/script&gt;" style="position:fixed;inset:0" width="560" height="315"></iframe>'
            .'<iframe src="//player.vimeo.com/video/76979871" allow="autoplay; fullscreen" title="Vimeo"></iframe>'
            .'<script>alert(4)</script><script type="application/ld+json">{"a":1}</script><script type="importmap">{"imports":{}}</script>'
            .'<style>body{display:none}</style><meta http-equiv="refresh" content="0;url=https://evil.test"><form action="https://evil.test"><input name="x"></form><img src=x onerror=alert(7)>'
            .'<div style="position:fixed;top:0;width:100%;background:url(javascript:alert(5));padding-top:50%" onclick="alert(8)" data-id="9" class="ok">Text</div>'
            .'<p><a href="javascript:void(0)">hidden</a><object data="https://evil.test/x"></object><textarea>raw</textarea></p><noscript><iframe src="https://evil.test"></iframe></noscript>';
        $embed=EmbedCode::sanitize($attack);
        $this->assertSame('<iframe src="https://player.vimeo.com/video/76979871" title="Vimeo" allow="autoplay; fullscreen" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe><div style="width:100%;padding-top:50%" data-id="9" class="ok">Text</div>',$embed['code']);
        $this->assertEqualsCanonicalizing(['removed','https','inline'],$embed['notices']);
        foreach (['javascript:','onload','onclick','srcdoc','position:fixed','<script','<style','<form','<img','<meta','<object','<textarea','<noscript','evil.test','hidden','<a ','background'] as $value) $this->assertStringNotContainsString($value,$embed['code'],$value);
    }

    public function test_addresses_must_be_https_and_come_from_an_allowed_host(): void
    {
        config(['filaforge.embeds.scripts'=>true,'filaforge.embeds.allowed_hosts'=>['www.aparat.com','*.vimeo.com']]);
        $embed=EmbedCode::sanitize('<iframe src="http://www.aparat.com/x"></iframe><iframe src="https://player.vimeo.com/video/1"></iframe><iframe src="https://vimeo.com.evil.test/1"></iframe><iframe src="https://user@www.aparat.com/"></iframe><iframe src="https://localhost/"></iframe><iframe src="https://www.aparat.com:8080/v"></iframe>'
            .'<script src="https://cdn.other.test/a.js"></script><script src="https://www.aparat.com/embed/x?data[rnddiv]=1" async></script>');
        $this->assertSame('<iframe src="https://www.aparat.com/x" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe><iframe src="https://player.vimeo.com/video/1" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe><iframe src="https://www.aparat.com:8080/v" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe><script src="https://www.aparat.com/embed/x?data[rnddiv]=1" async data-ff-embed=""></script>',$embed['code']);
        foreach (['evil.test','user@','localhost','cdn.other.test'] as $value) $this->assertStringNotContainsString($value,$embed['code']);
        $this->assertEqualsCanonicalizing(['https','host','removed'],$embed['notices'],'a malformed address and an address outside the list each report their own reason');
        $this->assertSame('nothing',$this->rejected('<iframe src="https://www.youtube.com/embed/x"></iframe>'));
        config(['filaforge.embeds.iframes'=>false]);
        $this->assertSame('iframes_disabled',$this->rejected(self::IFRAME));
    }

    public function test_inline_scripts_stay_out_unless_the_host_allows_them(): void
    {
        config(['filaforge.embeds.scripts'=>true]);
        $inline='<div id="a"></div><script>document.getElementById("a").innerHTML="x"</script>';
        $this->assertSame('nothing',$this->rejected($inline));
        config(['filaforge.embeds.inline_scripts'=>true]);
        $this->assertSame('<div id="a"></div><script data-ff-embed="">document.getElementById("a").innerHTML="x"</script>',EmbedCode::sanitize($inline)['code']);
        // Markup-like content inside inline code is not something an embed needs.
        $this->assertSame('nothing',$this->rejected('<script><!-- alert(1) --></script>'));
    }

    public function test_widget_properties_are_validated_and_an_empty_embed_renders_nothing(): void
    {
        app(DocumentValidator::class)->validate($this->doc([$this->node('embed')]));
        app(DocumentValidator::class)->validate($this->doc([$this->node('embed',['code'=>'<p>not an embed</p>'])]));
        $missingTitle=$this->node('embed');unset($missingTitle['props']['title']);
        foreach ([['code'=>1],['code'=>'','extra'=>1],['code'=>str_repeat('a',20001),'title'=>''],['code'=>'','title'=>str_repeat('ا',201)],$missingTitle] as $props) {
            try { app(DocumentValidator::class)->validate($this->doc([$this->node('embed'),is_array($props)&&isset($props['id'])?$props:$this->node('embed',$props)]));$this->fail('Accepted: '.json_encode($props)); } catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        foreach ([[],['code'=>'<p>not an embed</p>']] as $props) $this->assertStringNotContainsString('data-node="sample"',app(BladeRenderer::class)->render($this->doc([$this->node('embed',$props)])));
    }

    public function test_fragment_assets_leave_embed_scripts_in_place(): void
    {
        $assets=new RenderAssets;
        $embed='<div data-node="a" class="ff-embed"><div id="h"></div><script src="https://www.aparat.com/embed/x?data[rnddiv]=h" data-ff-embed=""></script></div>';
        $this->assertSame($embed,$assets->capture($embed.'<style>.a{color:red}</style><script src="/app.js"></script>'));
        $this->assertSame('<style>.a{color:red}</style><script src="/app.js"></script>',$assets->html()->toHtml());
    }

    public function test_editor_endpoint_returns_the_kept_code_and_explains_rejections(): void
    {
        $this->admin();
        $panel=\Filament\Panel::make()->id('embedding')->path('embedding')->plugin(\FilaForge\Studio\Filament\FilaForgeStudioPlugin::make());
        \Filament\Facades\Filament::registerPanel($panel);\Filament\Facades\Filament::setCurrentPanel($panel);
        \Illuminate\Support\Facades\Route::name('filament.embedding.')->group(fn()=>\FilaForge\Studio\Filament\Resources\PageResource::registerRoutes($panel));
        $page=$this->page();$editor=\Livewire\Livewire::test(\FilaForge\Studio\Filament\Resources\Pages\EditPage::class,['record'=>$page->id]);
        $editor->call('sanitizeEmbed',self::SCRIPT)->assertReturned(['error'=>'Script embeds are turned off for this site. Use an iframe code instead.']);
        $editor->call('sanitizeEmbed',self::IFRAME)->assertReturned(['code'=>'<div class="h_iframe-aparat_embed_frame"><span style="display:block;padding-top:57%"></span>'.self::IFRAME_KEPT.'</div>','notice'=>'Iframe from www.aparat.com saved. Unsupported parts of the code were removed.']);
        $editor->call('sanitizeEmbed','<p>hello</p>')->assertReturned(['error'=>'No usable iframe or external script was found in this code.']);
        $editor->call('sanitizeEmbed',str_repeat('a',20001))->assertReturned(['error'=>'The embed code is too large.']);
        $editor->call('sanitizeEmbed','   ')->assertReturned(['code'=>'','notice'=>'']);
        config(['filaforge.embeds.scripts'=>true]);
        $editor->call('sanitizeEmbed',self::SCRIPT)->assertReturned(['code'=>self::SCRIPT_KEPT,'notice'=>'Script from www.aparat.com saved.']);
        $editor->call('sanitizeEmbed',self::SCRIPT.self::IFRAME)->assertReturned(fn ($result) => str_contains($result['code'],'<iframe ') && str_contains($result['code'],'<script ') && str_ends_with($result['notice'],'Unsupported parts of the code were removed.'));
        Gate::define('filaforge.update',fn()=>false);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->editor($page)->sanitizeEmbed(self::IFRAME);
    }

    public function test_editor_offers_the_embed_without_ever_building_its_code_in_the_canvas(): void
    {
        $this->admin();$page=$this->page();
        $panel=\Filament\Panel::make()->id('embeds')->path('embeds')->plugin(\FilaForge\Studio\Filament\FilaForgeStudioPlugin::make());
        \Filament\Facades\Filament::registerPanel($panel);\Filament\Facades\Filament::setCurrentPanel($panel);
        \Illuminate\Support\Facades\Route::name('filament.embeds.')->group(fn()=>\FilaForge\Studio\Filament\Resources\PageResource::registerRoutes($panel));
        app()->setLocale('fa');
        $html=\Livewire\Livewire::test(\FilaForge\Studio\Filament\Resources\Pages\EditPage::class,['record'=>$page->id])->html();
        // @js() writes the Persian label as \uXXXX escapes inside single quotes.
        foreach (["{type:'embed',label:'".substr(json_encode('کد embed',JSON_THROW_ON_ERROR),1,-1)."',icon:'code'}",'async applyEmbed()','wire.sanitizeEmbed(',"embedNotice='';this.embedError=''",'x-show="find(selected)?.type===\'embed\'"','x-model="draftProps.code"','dir="ltr" spellcheck="false" maxlength="20000"','role="alert"',"'html':'file-code','embed':'code'","!['html','embed','shared-section'"] as $part) $this->assertStringContainsString($part,$html,$part);
        $canvas=view('filaforge::editor.canvas')->render();
        // The canvas is rendered here in Persian.
        $this->assertStringContainsString("else if(n.type==='embed'){const host=String(n.props.code||'').match(",$canvas);
        // The placeholder is text only; the code is never parsed as markup.
        $branch=substr($canvas,strpos($canvas,"else if(n.type==='embed'"),strpos($canvas,"else inlineContent(e,n,'text',n.props.text)")-strpos($canvas,"else if(n.type==='embed'"));
        $this->assertStringContainsString('e.textContent=',$branch);$this->assertStringNotContainsString('innerHTML',$branch);
    }

    private function rejected(string $code): string
    {
        try { EmbedCode::sanitize($code); } catch (\InvalidArgumentException $error) { return $error->getMessage(); }
        $this->fail('Embed code was accepted: '.substr($code,0,60));
    }
}
