<?php
namespace FilaForge\Studio\Importing;

use DOMDocument;
use DOMElement;
use DOMNode;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Rendering\RichText;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class HtmlImporter
{
    private int $count = 0;
    private array $warnings = [];

    public function __construct(private DocumentValidator $validator) {}

    /** Parse inert HTML; never fetch styles, execute scripts or compile Blade. */
    public function import(string $html, string $locale = 'fa', string $direction = 'rtl'): array
    {
        if (strlen($html) > 262144 || trim($html) === '') throw new InvalidArgumentException('import_invalid');
        $this->count = 0;
        $this->warnings = [];
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$loaded) throw new InvalidArgumentException('import_invalid');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $document = DocumentValidator::empty($locale, $direction);
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) $document['root']['children'] = $this->children($body, 1);
        if (!$document['root']['children']) throw new InvalidArgumentException('import_empty');
        $this->validator->validate($document);
        return ['document'=>$document, 'warnings'=>array_keys($this->warnings)];
    }

    private function children(DOMNode $parent, int $depth): array
    {
        if ($depth > 24) throw new InvalidArgumentException('import_limits');
        $nodes = [];
        foreach ($parent->childNodes as $element) {
            if (++$this->count > 2000) throw new InvalidArgumentException('import_limits');
            if ($element->nodeType === XML_TEXT_NODE) {
                if (trim($element->textContent) !== '') $nodes[] = $this->node('text', ['text'=>trim($element->textContent)]);
                continue;
            }
            if (!$element instanceof DOMElement) continue;
            $tag = strtolower($element->tagName);
            if (in_array($tag, ['script','style','iframe','object','embed','svg','math','form','input','button','textarea','select','link','meta','head','template'], true)) {
                $this->warnings['import_removed'] = true;
                continue;
            }
            if (preg_match('/^h[1-6]$/D', $tag)) {
                $node = $this->node('heading', ['text'=>$element->textContent,'tag'=>$tag]);
            } elseif ($tag === 'img') {
                $url = $element->getAttribute('src');
                if (!$this->url($url)) { $this->warnings['import_bad_url']=true; continue; }
                $node = $this->node('image', ['src'=>$url,'alt'=>$element->getAttribute('alt'),'width'=>100]);
            } elseif ($tag === 'video') {
                $url = $element->getAttribute('src') ?: $element->getElementsByTagName('source')->item(0)?->getAttribute('src');
                if (!$this->url($url ?? '')) { $this->warnings['import_bad_url']=true; continue; }
                $poster = $element->getAttribute('poster');
                $node = $this->node('video', ['src'=>$url,'poster'=>$this->url($poster)?$poster:'','controls'=>true]);
            } elseif ($tag === 'a') {
                $url = $element->getAttribute('href');
                $node = $this->node('button', ['text'=>$element->textContent,'url'=>$this->url($url, true)?$url:'#']);
            } elseif ($tag === 'hr') {
                $node = $this->node('divider', []);
            } elseif (in_array($tag, ['p','ul','ol','blockquote','pre','span','strong','em','b','i'], true)) {
                $node = $this->node('rich-text', ['html'=>RichText::clean($element->ownerDocument->saveHTML($element))]);
            } else {
                if (!in_array($tag, ['div','section','main','article','header','footer','nav','aside'], true)) $this->warnings['import_unsupported']=true;
                $classes = $element->getAttribute('class');
                $columns = preg_match('/(?:^|\s)grid-cols-([1-9]|1[0-2])(?:\s|$)/', $classes, $match) ? (int) $match[1] : null;
                $node = $columns ? $this->node('grid', ['columns'=>$columns,'gap'=>24]) : $this->node('container', ['direction'=>preg_match('/(?:^|\s)flex-row(?:\s|$)/', $classes)?'row':'column']);
                $node['children'] = $this->children($element, $depth + 1);
            }
            if ($element->hasAttribute('class')) $this->warnings['import_css']=true;
            if ($element->hasAttribute('style')) $this->styles($node, $element->getAttribute('style'));
            $nodes[] = $node;
        }
        return $nodes;
    }

    private function node(string $type, array $props): array
    {
        return ['id'=>(string) Str::uuid(),'type'=>$type,'widgetVersion'=>1,'props'=>$props,'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[],'children'=>[]];
    }

    private function url(string $url, bool $anchor = false): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\<>]/', $url)) return false;
        return ($anchor && preg_match('/^#[A-Za-z0-9_-]*$/D', $url))
            || (str_starts_with($url, '/') && !str_starts_with($url, '//'))
            || (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http','https'], true));
    }

    private function styles(array &$node, string $css): void
    {
        foreach (explode(';', $css) as $declaration) {
            if (!str_contains($declaration, ':')) continue;
            [$property,$value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $colors = ['color'=>'color','background-color'=>'backgroundColor','border-color'=>'borderColor'];
            $dimensions = ['width'=>2400,'height'=>2000,'padding'=>256,'margin'=>256,'font-size'=>120,'gap'=>128];
            if (isset($colors[$property]) && preg_match('/^#[0-9a-f]{6}$/iD', $value)) $node['styles'][$colors[$property]]=$value;
            elseif (isset($dimensions[$property]) && preg_match('/^(\d+)px$/D', $value, $m) && (int)$m[1] <= $dimensions[$property] && ($property !== 'font-size' || (int)$m[1]>=8)) {
                $key = $property === 'font-size' ? 'fontSize' : $property;
                $node['responsive']['desktop'][$key]=(int)$m[1];
            } elseif ($property === 'display' && in_array($value,['flex','grid','block','none'],true)) $node['responsive']['desktop']['display']=$value;
            elseif ($property === 'flex-direction' && in_array($value,['row','column'],true)) $node['responsive']['desktop']['flexDirection']=$value;
            elseif ($property === 'text-align' && in_array($value,['start','center','end','justify'],true)) $node['responsive']['desktop']['textAlign']=$value;
            else $this->warnings['import_css']=true;
        }
    }
}
