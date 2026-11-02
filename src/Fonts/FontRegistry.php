<?php
namespace FilaForge\Studio\Fonts;
use InvalidArgumentException;
final class FontRegistry
{
    private array $fonts=[];
    public function __construct()
    {
        $defaults=[
            'system'=>['label'=>'System UI','family'=>'system-ui','fallback'=>['sans-serif']],
            'serif'=>['label'=>'Serif','family'=>'Georgia','fallback'=>['serif']],
            'monospace'=>['label'=>'Monospace','family'=>'ui-monospace','fallback'=>['monospace']],
            'arial'=>['label'=>'Arial','family'=>'Arial','fallback'=>['Helvetica','sans-serif'],'locales'=>['en']],
            'verdana'=>['label'=>'Verdana','family'=>'Verdana','fallback'=>['sans-serif'],'locales'=>['en']],
            'trebuchet'=>['label'=>'Trebuchet MS','family'=>'Trebuchet MS','fallback'=>['sans-serif'],'locales'=>['en']],
            'georgia'=>['label'=>'Georgia','family'=>'Georgia','fallback'=>['serif'],'locales'=>['en']],
            'times'=>['label'=>'Times New Roman','family'=>'Times New Roman','fallback'=>['serif'],'locales'=>['en']],
            'courier'=>['label'=>'Courier New','family'=>'Courier New','fallback'=>['monospace'],'locales'=>['en']],
        ];
        foreach(['vazirmatn'=>'Vazirmatn','iransans'=>'IRANSans','shabnam'=>'Shabnam','sahel'=>'Sahel','yekan'=>'Yekan Bakh'] as $id=>$family)$defaults[$id]=['label'=>$family,'family'=>$family,'fallback'=>['Tahoma','sans-serif'],'locales'=>['fa','ar']];
        $definitions=array_replace($defaults,config('filaforge.fonts',[]));
        if(count($definitions)>200)throw new InvalidArgumentException('Too many fonts.');
        foreach($definitions as $id=>$font)$this->register($id,$font);
    }
    public function register(string $id,array $font): void
    {
        if(!preg_match('/^[a-z][a-z0-9-]{0,63}$/D',$id)||$id==='inherit'||isset($this->fonts[$id]))throw new InvalidArgumentException('Invalid or duplicate font identifier.');
        $family=$font['family']??'';$fallback=$font['fallback']??['sans-serif'];$sources=$font['sources']??[];$display=$font['display']??'swap';
        $validName=fn($name)=>is_string($name)&&preg_match('/^[\p{L}\p{N} _-]{1,80}$/uD',$name);
        if(!$validName($family)||!is_array($fallback)||count($fallback)>8||!in_array($display,['auto','block','swap','fallback','optional'],true)||!is_array($sources)||count($sources)>8)throw new InvalidArgumentException('Invalid font configuration.');
        foreach($fallback as $name)if(!$validName($name))throw new InvalidArgumentException('Invalid font fallback.');
        $label=$font['label']??$family;
        if(!is_string($label)&&!is_array($label))throw new InvalidArgumentException('Invalid font label.');
        foreach(is_array($label)?$label:[$label] as $text)if(!is_string($text)||mb_strlen($text)>100)throw new InvalidArgumentException('Invalid font label.');
        $locales=$font['locales']??[];
        if(!is_array($locales)||count($locales)>50)throw new InvalidArgumentException('Invalid font locales.');
        foreach($locales as $locale)if(!is_string($locale)||!preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z]{2,4})?$/D',$locale))throw new InvalidArgumentException('Invalid font locale.');
        $css='';
        foreach($sources as $source){
            $url=$source['url']??'';$weight=(string)($source['weight']??400);$style=$source['style']??'normal';
            if(!is_string($url)||strlen($url)>2048||preg_match('/[\x00-\x20\x7f<>"\'\\\\(){}]/',$url)||!preg_match('~^(?:https?://[^/]+/|/(?!/)).*\.woff2(?:\?[^#]*)?$~iD',$url)||!preg_match('/^[1-9][0-9]{0,2}(?: [1-9][0-9]{0,2})?$/D',$weight)||!in_array($style,['normal','italic'],true))throw new InvalidArgumentException('Invalid WOFF2 font source.');
            $range=explode(' ',$weight);if(count($range)===2&&(int)$range[0]>(int)$range[1])throw new InvalidArgumentException('Invalid variable font weight range.');
            $css.='@font-face{font-family:"'.$family.'";src:url("'.$url.'") format("woff2");font-weight:'.$weight.';font-style:'.$style.';font-display:'.$display.'}';
        }
        $generic=['serif','sans-serif','monospace','system-ui','ui-serif','ui-sans-serif','ui-monospace','cursive','fantasy'];
        $stack=(in_array($family,$generic,true)?$family:'"'.$family.'"').($fallback?','.implode(',',array_map(fn($name)=>str_contains($name,' ')?'"'.$name.'"':$name,$fallback)):'');
        $this->fonts[$id]=compact('id','label','family','stack','css','locales');
    }
    public function ids(): array {return array_keys($this->fonts);}
    public function stack(string $id): string {return $this->fonts[$id]['stack']??throw new InvalidArgumentException('Unknown font.');}
    public function defaultFor(string $locale): ?string
    {
        $base=preg_split('/[-_]/',$locale)[0];$id=config('filaforge.font_defaults.'.$locale)??config('filaforge.font_defaults.'.$base);
        if($id!==null&&!isset($this->fonts[$id]))throw new InvalidArgumentException('Unknown locale default font.');
        return $id;
    }
    public function catalog(string $locale): array
    {
        $base=preg_split('/[-_]/',$locale)[0];$fonts=[];
        foreach($this->fonts as $font){$label=$font['label'];$font['label']=is_array($label)?($label[$locale]??$label[$base]??$label['en']??$font['family']):$label;$fonts[]=$font;}
        return $fonts;
    }
    public function cssFor(array $document): string
    {
        $used=[];$walk=function($node)use(&$walk,&$used){$id=$node['styles']['fontFamily']??null;if(isset($this->fonts[$id]))$used[$id]=$this->fonts[$id]['css'];foreach($node['children'] as $child)$walk($child);};$walk($document['root']);
        return implode('',$used);
    }
}
