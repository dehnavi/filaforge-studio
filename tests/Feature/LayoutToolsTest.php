<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Rendering\BladeRenderer;
use FilaForge\Studio\Tests\TestCase;
final class LayoutToolsTest extends TestCase
{
    private function node(string $id,string $type,array $props,array $children=[]):array {return ['id'=>$id,'type'=>$type,'widgetVersion'=>1,'props'=>$props,'children'=>$children,'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[]];}

    public function test_canvas_layout_tools_are_translated_accessible_and_never_editing_targets():void
    {
        // @js() writes non-ASCII labels as \uXXXX escapes, so compare with the same encoding.
        foreach (['fa'=>['padding-up'=>'افزایش فاصله','padding-down'=>'کاهش فاصله','remove-column'=>'حذف ستون'],'en'=>['padding-up'=>'Increase spacing','padding-down'=>'Decrease spacing','remove-column'=>'Remove column']] as $locale=>$labels) {
            app()->setLocale($locale);$canvas=view('filaforge::editor.canvas')->render();
            foreach ($labels as $action=>$label) $this->assertStringContainsString("'".$action."':'".substr(json_encode($label,JSON_THROW_ON_ERROR),1,-1)."'",$canvas,$locale.' '.$action);
            $this->assertStringContainsString("button.setAttribute('aria-label',layoutLabels[action])",$canvas,$locale);
            $this->assertStringNotContainsString('title=action',$canvas,$locale);
        }
        app()->setLocale('en');$canvas=view('filaforge::editor.canvas')->render();
        // Editor chrome (grips, layout toolbars) must not start inline editing, and tools stay out of the published canvas until hovered, selected or focused.
        $this->assertStringContainsString("document.addEventListener('dblclick',e=>{if(previewMode)return;if(e.target.closest('[data-ff-editor-only]'))return;",$canvas);
        $this->assertStringContainsString('bar.dataset.ffEditorOnly=\'toolbar\'',$canvas);
        $this->assertStringContainsString('.ff-layout-toolbar{display:flex;flex-wrap:wrap;gap:4px;position:absolute;inset-block-start:6px;inset-inline-end:6px;',$canvas);
        $this->assertStringContainsString('.ff-layout-toolbar:focus-within,[data-node]:hover>.ff-layout-toolbar,[data-selected]>.ff-layout-toolbar{opacity:1}',$canvas);
        $this->assertStringContainsString('.ff-layout-toolbar button:focus-visible{outline:2px solid #4f46e5}',$canvas);
        // The toolbar is placed with logical offsets only, so it hugs the inline end in LTR and RTL editors alike.
        $this->assertSame(1,preg_match('~\.ff-layout-toolbar\{([^}]*)\}~',$canvas,$rule));
        $this->assertStringContainsString('inset-inline-end:6px',$rule[1]);$this->assertStringContainsString('inset-block-start:6px',$rule[1]);
        $this->assertDoesNotMatchRegularExpression('/(?<![a-z-])(left|right|top):/',$rule[1]);
    }
}
