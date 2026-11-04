<?php
namespace FilaForge\Studio\Core;
use InvalidArgumentException;
final class DocumentValidator
{
    public function __construct(private WidgetRegistry $widgets, private int $maxBytes = 1048576, private int $maxNodes = 1000, private int $maxDepth = 32, private ?\FilaForge\Studio\DynamicData\BindingValidator $dynamic = null) {}
    public function validate(array $document, bool $allowMissingProviders = false): array
    {
        if (strlen(json_encode($document, JSON_THROW_ON_ERROR)) > $this->maxBytes || ($document['schemaVersion'] ?? null) !== 1 || !in_array($document['direction'] ?? null, ['rtl','ltr'], true) || !is_string($document['locale'] ?? null) || !preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z]{2,4})?$/D', $document['locale']) || array_diff(array_keys($document), ['schemaVersion','locale','direction','root','integrations'])) {
            throw new InvalidArgumentException('Invalid document envelope.');
        }
        app(\FilaForge\Studio\Integrations\PageIntegrations::class)->normalize($document['integrations']??[]);
        $ids = []; $count = 0;
        $visit = function (mixed $node, int $depth, ?string $rowProvider = null, ?string $parentType = null, int $menuDepth = 0) use (&$visit, &$ids, &$count, $allowMissingProviders): void {
            if (!is_array($node) || $depth > $this->maxDepth || ++$count > $this->maxNodes || !is_string($node['id'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $node['id']) || isset($ids[$node['id']])) {
                throw new InvalidArgumentException('Invalid tree or duplicate node ID.');
            }
            $ids[$node['id']] = true;
            if (!is_string($node['type'] ?? null)) throw new InvalidArgumentException('Missing widget type.');
            $widget = $this->widgets->get($node['type']);
            if (($node['widgetVersion'] ?? null) !== $widget->version() || !is_array($node['props'] ?? null) || !is_array($node['children'] ?? null) || !array_is_list($node['children']) || (!$widget->acceptsChildren() && $node['children']) || array_diff(array_keys($node), ['id','type','widgetVersion','props','children','styles','responsive','bindings','visibility','meta'])) {
                throw new InvalidArgumentException('Invalid widget structure.');
            }
            if (!is_array($node['styles'] ?? [])) throw new InvalidArgumentException('Invalid styles.');
            $this->validateStyles(app(\FilaForge\Studio\Rendering\DesignTokens::class)->values($node['styles'] ?? []));
            $responsive = $node['responsive'] ?? [];
            if (!is_array($responsive)) throw new InvalidArgumentException('Invalid responsive settings.');
            foreach ($responsive as $key => $values) {
                if (!is_array($values)) throw new InvalidArgumentException('Invalid responsive settings.');
                $responsive[$key] = app(\FilaForge\Studio\Rendering\DesignTokens::class)->values($values);
            }
            $this->validateResponsive($responsive,$node['type']);
            foreach (['meta'] as $field) {
                if (($node[$field] ?? null) !== []) throw new InvalidArgumentException('Unsupported node field: '.$field);
            }
            $widget->validate($node['props']);
            if($node['type']==='dropdown-menu'){
                if(count($node['children'])<1||count($node['children'])>30)throw new InvalidArgumentException('Menu requires 1–30 top-level items.');
            }
            if(in_array($node['type'],['dropdown-menu','menu-item'],true))foreach($node['children'] as $item)if(($item['type']??null)!=='menu-item')throw new InvalidArgumentException('Menus accept only menu items.');
            if($node['type']==='menu-item'&&(!in_array($parentType,['dropdown-menu','menu-item'],true)||$menuDepth>4||count($node['children'])>30))throw new InvalidArgumentException('Invalid menu item hierarchy.');
            if($node['type']==='wizard'){
                if(count($node['children'])<1||count($node['children'])>12||$node['props']['start']>=count($node['children']))throw new InvalidArgumentException('Wizard requires 1–12 steps and a valid start index.');
                foreach($node['children'] as $step)if(($step['type']??null)!=='wizard-step')throw new InvalidArgumentException('Wizard children must be steps.');
            }
            if($node['type']==='wizard-step'&&$parentType!=='wizard')throw new InvalidArgumentException('Wizard steps require a wizard parent.');
            if ($this->dynamic) $this->dynamic->validate($node, $rowProvider, $allowMissingProviders);
            elseif (($node['bindings']??[])!==[] || ($node['visibility']??[])!==[]) throw new InvalidArgumentException('Dynamic engine unavailable.');
            $childProvider = $node['type']==='query-loop' ? $node['props']['provider'] : $rowProvider;
            foreach ($node['children'] as $child) $visit($child, $depth + 1, $childProvider, $node['type'], $node['type']==='dropdown-menu'?1:($node['type']==='menu-item'?$menuDepth+1:0));
        };
        $visit($document['root'] ?? null, 1);
        if ($document['root']['type'] !== 'container') throw new InvalidArgumentException('Root must be a container.');
        return $document;
    }
    private function validateStyles(mixed $styles): void
    {
        if (!is_array($styles) || ($styles !== [] && array_is_list($styles)) || array_diff(array_keys($styles), ['backgroundColor','backgroundImage','backgroundOpacity','backgroundSize','backgroundPosition','backgroundRepeat','color','borderColor','borderRadius','fontFamily','hoverEffect','entranceEffect','transitionDuration','clickAction','clickTarget'])) {
            throw new InvalidArgumentException('Invalid node styles.');
        }
        foreach (['backgroundColor','color','borderColor'] as $key) {
            if (isset($styles[$key]) && (!is_string($styles[$key]) || !preg_match('/^#[0-9a-f]{6}$/iD', $styles[$key]))) throw new InvalidArgumentException('Invalid style color.');
        }
        if (isset($styles['backgroundImage']) && (!is_string($styles['backgroundImage']) || ($styles['backgroundImage'] !== '' && !preg_match('/^media:[0-9A-HJKMNP-TV-Z]{26}$/iD', $styles['backgroundImage'])))) throw new InvalidArgumentException('Background images must reference the media library.');
        if (isset($styles['backgroundOpacity']) && (!is_numeric($styles['backgroundOpacity']) || $styles['backgroundOpacity'] < 0 || $styles['backgroundOpacity'] > 1)) throw new InvalidArgumentException('Invalid background opacity.');
        if (isset($styles['backgroundSize']) && !in_array($styles['backgroundSize'], ['cover','contain','auto'], true)) throw new InvalidArgumentException('Invalid background size.');
        if (isset($styles['backgroundPosition']) && !in_array($styles['backgroundPosition'], ['center','top','bottom','left','right','top left','top right','bottom left','bottom right'], true)) throw new InvalidArgumentException('Invalid background position.');
        if (isset($styles['backgroundRepeat']) && !in_array($styles['backgroundRepeat'], ['no-repeat','repeat','repeat-x','repeat-y'], true)) throw new InvalidArgumentException('Invalid background repeat.');
        if (isset($styles['borderRadius']) && (!is_int($styles['borderRadius']) || $styles['borderRadius'] < 0 || $styles['borderRadius'] > 64)) throw new InvalidArgumentException('Invalid border radius.');
        foreach (['fontFamily'=>['inherit',...app(\FilaForge\Studio\Fonts\FontRegistry::class)->ids()],'hoverEffect'=>['none','lift','scale','glow','brighten','darken'],'entranceEffect'=>['none','fade','slide-up','zoom'],'transitionDuration'=>['fast','normal','slow'],'clickAction'=>['none','scroll','link']] as $key=>$allowed) {
            if (isset($styles[$key]) && !in_array($styles[$key], $allowed, true)) throw new InvalidArgumentException('Invalid '.$key.'.');
        }
        if (isset($styles['clickTarget'])) {
            $action = $styles['clickAction'] ?? 'none';
            $target = $styles['clickTarget'];
            $valid = is_string($target) && strlen($target) <= 2048 && match ($action) {
                'scroll' => preg_match('/^#[A-Za-z][A-Za-z0-9_-]{0,79}$/D', $target) === 1,
                'link' => preg_match('~^(#[A-Za-z][A-Za-z0-9_-]{0,79}|https?://[^\s]+|mailto:[^\s@]+@[^\s@]+)$~iD', $target) === 1,
                default => $target === '',
            };
            if (!$valid) throw new InvalidArgumentException('Invalid interaction target.');
        }
    }
    private function validateResponsive(mixed $responsive,string $widget): void
    {
        $allowed = ['width'=>[0,2400],'height'=>[0,2000],'maxWidth'=>[0,2400],'padding'=>[0,256],'margin'=>[0,256],'fontSize'=>[8,120],'gap'=>[0,128],'columns'=>[1,12]];
        if(in_array($widget,['dropdown-menu','menu-item'],true))$allowed += ['menuGap'=>[0,48],'submenuWidth'=>[140,600],'submenuIndent'=>[0,32]];
        if (!is_array($responsive) || ($responsive !== [] && array_is_list($responsive)) || array_diff(array_keys($responsive), ['desktop','tablet','mobile'])) throw new InvalidArgumentException('Invalid responsive settings.');
        foreach ($responsive as $breakpoint=>$settings) {
            if (!is_array($settings) || ($settings !== [] && array_is_list($settings)) || array_diff(array_keys($settings), [...array_keys($allowed),'textAlign','display','flexDirection','alignItems','justifyContent'])) throw new InvalidArgumentException('Invalid responsive settings.');
            foreach ($allowed as $key=>[$min,$max]) if (isset($settings[$key]) && (!is_int($settings[$key]) || $settings[$key] < $min || $settings[$key] > $max)) throw new InvalidArgumentException('Invalid responsive dimension.');
            foreach (['textAlign'=>['start','center','end','justify'],'display'=>['block','inline-block','flex','grid','none'],'flexDirection'=>['row','column'],'alignItems'=>['start','center','end','stretch'],'justifyContent'=>['start','center','end','between']] as $key=>$values) if (isset($settings[$key]) && !in_array($settings[$key], $values, true)) throw new InvalidArgumentException('Invalid responsive layout value.');
        }
    }
    public static function empty(string $locale = 'en', string $direction = 'ltr'): array
    {
        return ['schemaVersion'=>1,'locale'=>$locale,'direction'=>$direction,'root'=>['id'=>'root','type'=>'container','widgetVersion'=>1,'props'=>['direction'=>'column'],'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[],'children'=>[]]];
    }
}
