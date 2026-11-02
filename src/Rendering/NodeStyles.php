<?php
namespace FilaForge\Studio\Rendering;

use FilaForge\Studio\Media\MediaRepository;

final class NodeStyles
{
    public static function stylesheet(array $document = []): string
    {
        $css = '[data-ff-background]{position:relative;isolation:isolate}[data-ff-background]::before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;border-radius:inherit;background-color:var(--ff-background-color,transparent);background-image:var(--ff-background-image,none);background-size:var(--ff-background-size,cover);background-position:var(--ff-background-position,center);background-repeat:var(--ff-background-repeat,no-repeat);opacity:var(--ff-background-opacity,1)}';
        $css .= '[data-ff-hover]{transition:transform var(--ff-transition,.2s ease),box-shadow var(--ff-transition,.2s ease),filter var(--ff-transition,.2s ease)}[data-ff-hover="lift"]:hover{transform:translateY(-5px);box-shadow:0 12px 28px #17142a22}[data-ff-hover="scale"]:hover{transform:scale(1.03)}[data-ff-hover="glow"]:hover{box-shadow:0 0 0 3px #7c6cf055,0 10px 28px #4f46e522}[data-ff-hover="brighten"]:hover{filter:brightness(1.08)}[data-ff-hover="darken"]:hover{filter:brightness(.92)}[data-ff-action="scroll"],[data-ff-action="link"]{cursor:pointer}[data-ff-entrance="fade"]{animation:ffFade .45s ease both}[data-ff-entrance="slide-up"]{animation:ffSlide .5s ease both}[data-ff-entrance="zoom"]{animation:ffZoom .4s ease both}@keyframes ffFade{from{opacity:0}to{opacity:1}}@keyframes ffSlide{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}@keyframes ffZoom{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}';
        $rules = ['desktop'=>[], 'tablet'=>[], 'mobile'=>[]];
        $walk = function (array $node) use (&$walk, &$rules): void {
            foreach (($node['responsive'] ?? []) as $breakpoint=>$settings) {
                $declarations = self::responsiveDeclarations($settings);
                if ($declarations) $rules[$breakpoint][] = '[data-node="'.$node['id'].'"]{'.$declarations.'}';
            }
            foreach ($node['children'] as $child) $walk($child);
        };
        if (isset($document['root'])) $walk($document['root']);
        $css .= implode('', $rules['desktop']);
        if ($rules['tablet']) $css .= '@media(max-width:1024px){'.implode('', $rules['tablet']).'}';
        if ($rules['mobile']) $css .= '@media(max-width:640px){'.implode('', $rules['mobile']).'}';
        return '<style>'.app(\FilaForge\Studio\Fonts\FontRegistry::class)->cssFor($document).$css.'</style>';
    }

    public static function responsiveDeclarations(array $settings): string
    {
        $map = ['width'=>'width','height'=>'height','maxWidth'=>'max-width','padding'=>'padding','margin'=>'margin','fontSize'=>'font-size','gap'=>'gap','textAlign'=>'text-align','display'=>'display','flexDirection'=>'flex-direction','columns'=>'grid-template-columns','menuGap'=>'--ff-menu-gap','submenuWidth'=>'--ff-submenu-width','submenuIndent'=>'--ff-submenu-indent'];
        $values = [];
        foreach ($map as $key=>$property) if (array_key_exists($key, $settings)) {
            $value = $settings[$key];
            if ($key === 'columns') $value = 'repeat('.$value.',minmax(0,1fr))';
            elseif (in_array($key, ['width','height','maxWidth','padding','margin','fontSize','gap','menuGap','submenuWidth','submenuIndent'], true)) $value .= 'px';
            $values[] = $property.':'.$value.'!important';
        }
        if (isset($settings['alignItems'])) $values[] = 'align-items:'.(['start'=>'flex-start','end'=>'flex-end'][$settings['alignItems']] ?? $settings['alignItems']).'!important';
        if (isset($settings['justifyContent'])) $values[] = 'justify-content:'.(['start'=>'flex-start','end'=>'flex-end','between'=>'space-between'][$settings['justifyContent']] ?? $settings['justifyContent']).'!important';
        return implode(';', $values);
    }

    public static function decorate(string $markup, array $node): string
    {
        $styles = $node['styles'] ?? [];
        if ($styles === []) return $markup;
        $declarations = [];
        $baseTone = in_array($node['type'], ['section','card'], true) ? ($node['props']['tone'] ?? 'white') : null;
        $baseColor = match ($node['type']) {'button'=>'#4f46e5','comments'=>'#ffffff','faq-item'=>'#ffffff',default=>null};
        $backgroundColor = $styles['backgroundColor'] ?? ($baseTone ? Colors::background($baseTone) : $baseColor);
        if ($backgroundColor) {
            $opacity = (float) ($styles['backgroundOpacity'] ?? 1);
            if (!empty($styles['backgroundImage'])) $declarations[] = '--ff-background-color:'.$backgroundColor;
            else {
                $hex = ltrim($backgroundColor, '#');
                $declarations[] = '--ff-background-color:rgba('.hexdec(substr($hex,0,2)).','.hexdec(substr($hex,2,2)).','.hexdec(substr($hex,4,2)).','.$opacity.')';
            }
        }
        if (!empty($styles['backgroundImage'])) {
            $url = app(MediaRepository::class)->resolve($styles['backgroundImage'], 'image');
            $url = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], $url);
            $declarations[] = '--ff-background-image:url("'.$url.'")';
        }
        foreach (['backgroundSize'=>'--ff-background-size','backgroundPosition'=>'--ff-background-position','backgroundRepeat'=>'--ff-background-repeat'] as $key=>$property) {
            if (array_key_exists($key, $styles)) $declarations[] = $property.':'.$styles[$key];
        }
        if (!empty($styles['backgroundImage'])) $declarations[] = '--ff-background-opacity:'.($styles['backgroundOpacity'] ?? 1);
        foreach (['color'=>'color','borderColor'=>'border-color'] as $key=>$property) {
            if (isset($styles[$key])) $declarations[] = $property.':'.$styles[$key];
        }
        if (isset($styles['borderRadius'])) $declarations[] = 'border-radius:'.$styles['borderRadius'].'px';
        if (isset($styles['fontFamily']) && $styles['fontFamily'] !== 'inherit') $declarations[] = 'font-family:'.app(\FilaForge\Studio\Fonts\FontRegistry::class)->stack($styles['fontFamily']);
        if (!$declarations && empty($styles['hoverEffect']) && empty($styles['entranceEffect']) && empty($styles['clickAction']) && empty($styles['transitionDuration'])) return $markup;
        $id = preg_quote((string) $node['id'], '~');
        $pattern = '~(<[a-z][a-z0-9:-]*\b[^>]*\bdata-node=("|\')'.$id.'\2[^>]*)(>)~i';
        return preg_replace_callback($pattern, function (array $match) use ($declarations, $styles, $node): string {
            $tag = $match[1];
            $hasBackground = isset($styles['backgroundColor']) || !empty($styles['backgroundImage']) || (($styles['backgroundOpacity'] ?? 1) < 1 && (in_array($node['type'] ?? '', ['section','card'], true) || in_array($node['type'] ?? '', ['button','comments','faq-item'], true)));
            if ($hasBackground) $tag .= ' data-ff-background=""';
            $hover = $styles['hoverEffect'] ?? 'none';
            if ($hover !== 'none') $tag .= ' data-ff-hover="'.e($hover).'"';
            $entrance = $styles['entranceEffect'] ?? 'none';
            if ($entrance !== 'none') $tag .= ' data-ff-entrance="'.e($entrance).'"';
            if (($styles['clickAction'] ?? 'none') !== 'none' && !empty($styles['clickTarget'])) $tag .= ' data-ff-action="'.e($styles['clickAction']).'" data-ff-action-target="'.e($styles['clickTarget']).'"';
            if (isset($styles['transitionDuration'])) $declarations[] = 'transition:'.(['fast'=>'.12s','normal'=>'.22s','slow'=>'.4s'][$styles['transitionDuration']]).' ease,box-shadow '.(['fast'=>'.12s','normal'=>'.22s','slow'=>'.4s'][$styles['transitionDuration']]).' ease,filter '.(['fast'=>'.12s','normal'=>'.22s','slow'=>'.4s'][$styles['transitionDuration']]).' ease';
            $extra = e(implode(';', $declarations));
            if (preg_match('~\sstyle=("|\')(.*?)\1~i', $tag, $styleMatch, PREG_OFFSET_CAPTURE)) {
                $full = $styleMatch[0][0];
                $offset = $styleMatch[0][1];
                $quote = $styleMatch[1][0];
                $existing = $styleMatch[2][0];
                if ($hasBackground) $existing = preg_replace('~(?:^|;)\s*background(?:-color)?\s*:[^;]*~i', '', $existing);
                $replacement = ' style='.$quote.rtrim($existing, ';').';'.$extra.$quote;
                $tag = substr_replace($tag, $replacement, $offset, strlen($full));
            } else {
                $tag .= ' style="'.$extra.'"';
            }
            return $tag.$match[3];
        }, $markup, 1) ?? $markup;
    }
}
