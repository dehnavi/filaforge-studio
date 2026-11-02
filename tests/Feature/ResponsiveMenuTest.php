<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Presets\PresetRegistry;
use FilaForge\Studio\Rendering\BladeRenderer;
final class ResponsiveMenuTest extends TestCase
{
    private function doc():array{$d=DocumentValidator::empty('fa','rtl');$d['root']['children']=[app(PresetRegistry::class)->instantiate('dropdown-menu')];return $d;}
    public function test_menu_and_individual_submenus_have_validated_breakpoint_settings():void
    {
        $d=$this->doc();$menu=&$d['root']['children'][0];$menu['responsive']=['desktop'=>['submenuWidth'=>320,'menuGap'=>20],'tablet'=>['submenuWidth'=>240,'menuGap'=>8],'mobile'=>['submenuIndent'=>6,'menuGap'=>4]];
        $menu['children'][1]['responsive']=['desktop'=>['submenuWidth'=>280],'mobile'=>['submenuIndent'=>4]];
        $html=app(BladeRenderer::class)->render($d);$this->assertStringContainsString('--ff-submenu-width:320px!important',$html);$this->assertStringContainsString('--ff-submenu-width:280px!important',$html);$this->assertStringContainsString('--ff-submenu-indent:4px!important',$html);$this->assertStringContainsString('--ff-menu-gap:8px!important',$html);$this->assertStringContainsString('data-collapse-at="tablet"',$html);$this->assertStringContainsString('@media(max-width:1024px)',$html);
    }
    public function test_menu_collapse_choices_are_scoped_and_legacy_documents_work():void
    {
        $d=$this->doc();unset($d['root']['children'][0]['props']['collapseAt']);$this->assertStringContainsString('data-collapse-at="tablet"',app(BladeRenderer::class)->render($d));
        foreach(['mobile'=>640,'never'=>0] as $value=>$pixels){$d['root']['children'][0]['props']['collapseAt']=$value;$html=app(BladeRenderer::class)->render($d);$this->assertStringContainsString('@media(max-width:'.$pixels.'px)',$html);$this->assertStringContainsString('[data-node="'.$d['root']['children'][0]['id'].'"].ff-menu .ff-menu-line',$html);}
    }
    public function test_menu_responsive_values_cannot_inject_css_or_affect_other_widget_schemas():void
    {
        foreach([['submenuWidth'=>'200px;display:none'],['submenuWidth'=>601],['menuGap'=>-1],['submenuIndent'=>33]] as $settings){$d=$this->doc();$d['root']['children'][0]['responsive']=['mobile'=>$settings];try{app(DocumentValidator::class)->validate($d);$this->fail('Invalid menu CSS accepted');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}}
        $d=DocumentValidator::empty();$d['root']['responsive']=['mobile'=>['submenuWidth'=>200]];$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d);
    }
    public function test_invalid_menu_collapse_mode_is_rejected():void
    {
        $d=$this->doc();$d['root']['children'][0]['props']['collapseAt']='1024px){body{display:none}}';$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($d);
    }

    public function test_multiple_menus_keep_independent_collapse_breakpoints():void
    {
        $d=$this->doc();$other=app(PresetRegistry::class)->instantiate('dropdown-menu');$other['props']['collapseAt']='mobile';$d['root']['children'][]=$other;
        $html=app(BladeRenderer::class)->render($d);
        $this->assertStringContainsString('@media(max-width:1024px){'."\n".'[data-node="'.$d['root']['children'][0]['id'].'"].ff-menu',$html);
        $this->assertStringContainsString('@media(max-width:640px){'."\n".'[data-node="'.$other['id'].'"].ff-menu',$html);
    }
}
