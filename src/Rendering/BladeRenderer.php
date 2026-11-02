<?php
namespace FilaForge\Studio\Rendering;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Core\WidgetRegistry;
final class BladeRenderer {
    public function __construct(private DocumentValidator $validator, private WidgetRegistry $widgets) {}
    public function render(array $document, ?\FilaForge\Studio\Models\PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): string {
        if ($page !== null && $page->exists) $page = $page->fresh();
        $defaultFont=app(\FilaForge\Studio\Fonts\FontRegistry::class)->defaultFor($document['locale']??'en');
        if($defaultFont!==null&&!isset($document['root']['styles']['fontFamily']))$document['root']['styles']['fontFamily']=$defaultFont;
        $this->validator->validate($document, true);
        if ($context === null) {
            $pages = request()->query('ff_page', []);
            $pages = is_array($pages) ? array_slice($pages, 0, 20, true) : [];
            $context = new \FilaForge\Studio\DynamicData\DataContext(locale: $document['locale'], page: $page, pagination: $pages);
        }
        $document = app(\FilaForge\Studio\DynamicData\DynamicEngine::class)->resolve($document, $context);
        $this->validator->validate($document, true);
        $render = function (array $node) use (&$render,$page,$context): string {
            $renderedChildren=array_map($render,$node['children']);
            $children = implode('', $renderedChildren);
            foreach(['src','poster'] as $key)if(isset($node['props'][$key]))$node['props'][$key]=app(\FilaForge\Studio\Media\MediaRepository::class)->resolve($node['props'][$key],$node['type']==='video'&&$key==='src'?'video':'image');
            $html = view($this->widgets->get($node['type'])->view(), ['node'=>$node,'props'=>$node['props'],'children'=>$children,'renderedChildren'=>$renderedChildren,'page'=>$page,'dataContext'=>$context])->render();
            return NodeStyles::decorate($html, $node);
        };
        $hasActions = false;
        $checkActions = function (array $node) use (&$checkActions, &$hasActions): void { if (in_array($node['styles']['clickAction'] ?? 'none', ['scroll','link'], true) && !empty($node['styles']['clickTarget'])) $hasActions = true; foreach ($node['children'] as $child) $checkActions($child); };
        $checkActions($document['root']);
        $actions = $hasActions ? '<script>document.addEventListener("click",function(e){var n=e.target.closest("[data-ff-action]");if(!n)return;if(e.target.closest("a,button,input,select,textarea,[role=button]")&&e.target!==n)return;var a=n.dataset.ffAction,t=n.dataset.ffActionTarget;if(a==="scroll"&&/^#[A-Za-z][A-Za-z0-9_-]{0,79}$/.test(t)){var target=document.getElementById(t.slice(1));if(target){e.preventDefault();target.scrollIntoView({behavior:"smooth",block:"start"})}}else if(a==="link"&&/^(https?:\\/\\/|mailto:|#[A-Za-z][A-Za-z0-9_-]{0,79}$)/i.test(t)){window.location.assign(t)}});</script>' : '';
        return '<div lang="'.e($document['locale']).'" dir="'.e($document['direction']).'">'.NodeStyles::stylesheet($document).$render($document['root']).$actions.'</div>';
    }
}
