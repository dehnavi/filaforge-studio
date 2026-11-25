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

    public function test_canvas_keeps_direction_for_widgets_that_are_their_own_block():void
    {
        $canvas=view('filaforge::editor.canvas')->render();
        $this->assertStringContainsString('if(button.dataset.dir)directionMark(button.dataset.dir)',$canvas);
        // A partial selection becomes an isolated inline run; a collapsed caret on the editing root wraps its content instead of marking the root, which is never saved.
        $this->assertStringContainsString('if(partial){inlineMark(\'data-direction\',value);return}',$canvas);
        $this->assertStringContainsString('if(block===editing){const content=[...editing.childNodes].filter(node=>!(node.nodeType===1&&node.hasAttribute(\'data-ff-editor-only\')));',$canvas);
        $this->assertStringContainsString('wrapper.setAttribute(\'data-direction\',value);wrapper.append(...content);editing.append(wrapper)', $canvas);
    }

    public function test_published_output_styles_inline_marks_in_every_text_widget():void
    {
        $text=$this->node('text','text',['text'=>'Hello world!','formatted'=>['text'=>'<span data-direction="ltr">Hello world!</span><span data-tone="red">red</span><span data-size="large">big</span>']]);
        $heading=$this->node('heading','heading',['text'=>'Heading','tag'=>'h2','formatted'=>['text'=>'<div data-align="center">Heading</div>']]);
        $document=DocumentValidator::empty('fa','rtl');$document['root']['children']=[$text,$heading];
        $html=app(BladeRenderer::class)->render($document);
        $this->assertStringContainsString('<div lang="fa" dir="rtl">',$html);
        $this->assertStringContainsString('<span data-direction="ltr">Hello world!</span><span data-tone="red">red</span><span data-size="large">big</span>',$html,'inline marks survive sanitizing');
        $this->assertStringNotContainsString('ff-rich',$html,'no rich-text widget is needed');
        // An inline direction mark isolates its run: direction alone has no effect on inline elements.
        foreach (['[data-node] [data-align=center]{text-align:center}','[data-node] [data-tone=red]{color:#b42318}','[data-node] [data-size=large]{font-size:1.3em}','[data-node] [data-direction=ltr]{direction:ltr}','[data-direction]{unicode-bidi:isolate}'] as $rule) $this->assertStringContainsString($rule,$html);
        $this->assertStringContainsString('[data-node] [data-direction=ltr]{direction:ltr}',view('filaforge::editor.canvas')->render(),'the canvas ships the same rules');
    }
}
