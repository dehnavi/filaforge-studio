<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Presets\PresetRegistry;
use FilaForge\Studio\Fonts\FontRegistry;
use FilaForge\Studio\Rendering\BladeRenderer;
final class FlowAndFontsTest extends TestCase
{
    private function document(string $preset): array {$d=DocumentValidator::empty('fa','rtl');$d['root']['children']=[app(PresetRegistry::class)->instantiate($preset)];return $d;}
    public function test_progress_has_editable_safe_properties_and_accessible_output(): void
    {
        $d=$this->document('progress');$node=&$d['root']['children'][0]['children'][1];$node['props']['label']='<script>bad()</script>';$node['props']['value']=82;$node['props']['color']='#12ab34';
        $html=app(BladeRenderer::class)->render($d);$this->assertStringContainsString('role="progressbar"',$html);$this->assertStringContainsString('aria-valuenow="82"',$html);$this->assertStringContainsString('width:82%',$html);$this->assertStringContainsString('&lt;script&gt;bad()&lt;/script&gt;',$html);$this->assertStringNotContainsString('<script>bad()',$html);
        $node['props']['value']=101;$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d);
    }
    public function test_wizard_native_steps_are_rendered_with_localized_navigation(): void
    {
        app()->setLocale('fa');$d=$this->document('wizard');$html=app(BladeRenderer::class)->render($d);
        $this->assertStringContainsString('data-ff-wizard',$html);$this->assertSame(3,substr_count($html,'data-ff-step-button='));$this->assertStringContainsString('aria-controls="ff-step-',$html);$this->assertStringContainsString('data-ff-step-prev',$html);$this->assertStringContainsString('قبلی',$html);$this->assertStringNotContainsString('filaforgeEditor',$html);
    }
    public function test_wizard_rejects_invalid_parent_children_and_start_index(): void
    {
        $original=$this->document('wizard');
        foreach(['empty','start','wrong-child','orphan'] as $case){$d=$original;
            if($case==='empty')$d['root']['children'][0]['children'][0]['children']=[];
            if($case==='start')$d['root']['children'][0]['children'][0]['props']['start']=9;
            if($case==='wrong-child')$d['root']['children'][0]['children'][0]['children'][0]=$d['root']['children'][0]['children'][0]['children'][0]['children'][0];
            if($case==='orphan')$d['root']['children']=[$d['root']['children'][0]['children'][0]['children'][0]];
            try{app(DocumentValidator::class)->validate($d);$this->fail('Invalid wizard accepted');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}
        }
    }
    public function test_english_fonts_are_available_without_external_requests(): void
    {
        $registry=app(FontRegistry::class);foreach(['arial','verdana','trebuchet','georgia','times','courier'] as $id)$this->assertContains($id,$registry->ids());
        $d=DocumentValidator::empty('en','ltr');$d['root']['styles']=['fontFamily'=>'georgia'];$html=app(BladeRenderer::class)->render($d);$this->assertStringContainsString('Georgia',$html);$this->assertStringNotContainsString('@font-face',$html);
    }
    public function test_language_fonts_and_variable_sources_load_only_when_used(): void
    {
        config(['filaforge.fonts'=>[
            'noto-jp'=>['label'=>['en'=>'Japanese font','ja'=>'日本語フォント'],'family'=>'Noto Sans JP','fallback'=>['sans-serif'],'locales'=>['ja'],'display'=>'swap','sources'=>[['url'=>'/fonts/noto-jp.woff2','weight'=>'100 900']]],
            'unused'=>['family'=>'Unused Font','sources'=>[['url'=>'/fonts/unused.woff2']]],
        ],'filaforge.font_defaults'=>['ja'=>'noto-jp']]);$this->app->forgetInstance(FontRegistry::class);$registry=app(FontRegistry::class);
        $this->assertSame('noto-jp',$registry->defaultFor('ja_JP'));$this->assertSame('日本語フォント',collect($registry->catalog('ja'))->firstWhere('id','noto-jp')['label']);
        $d=DocumentValidator::empty('ja','ltr');$html=app(BladeRenderer::class)->render($d);$this->assertStringContainsString('/fonts/noto-jp.woff2',$html);$this->assertStringContainsString('font-weight:100 900',$html);$this->assertStringNotContainsString('unused.woff2',$html);$this->assertStringContainsString('Noto Sans JP',$html);
        $d['root']['styles']=['fontFamily'=>'inherit'];$this->assertStringNotContainsString('noto-jp.woff2',app(BladeRenderer::class)->render($d));
    }
    public function test_font_configuration_rejects_css_and_unsafe_sources(): void
    {
        $registry=app(FontRegistry::class);
        foreach([
            ['family'=>'Font; color:red'],
            ['family'=>'Font','sources'=>[['url'=>'javascript:alert(1)']]],
            ['family'=>'Font','sources'=>[['url'=>'//evil.test/file.woff2']]],
            ['family'=>'Font','sources'=>[['url'=>'/fonts/file.woff2','weight'=>'900 100']]],
            ['family'=>'Font','sources'=>[['url'=>'/font.woff2?x=</style>']]],
        ] as $definition){try{$registry->register('bad',$definition);$this->fail('Unsafe font configuration accepted');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}}
        $d=DocumentValidator::empty();$d['root']['styles']=['fontFamily'=>'unregistered'];$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d);
    }
}
