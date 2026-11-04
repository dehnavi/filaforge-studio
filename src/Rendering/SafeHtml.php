<?php

namespace FilaForge\Studio\Rendering;

use Symfony\Component\HtmlSanitizer\{HtmlSanitizer, HtmlSanitizerConfig};

final class SafeHtml
{
    public static function clean(string $html, ?string $namespace = null): string
    {
        $config = (new HtmlSanitizerConfig)->allowSafeElements()
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])->allowRelativeMedias();
        // Stored HTML is markup, never executable Blade, inline JS or CSS.
        $safe = (new HtmlSanitizer($config))->sanitize($html);
        if ($namespace === null) return $safe;
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$safe.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $ids = [];
        foreach ($dom->getElementsByTagName('*') as $element) if ($element->hasAttribute('id')) {
            $id = $element->getAttribute('id');
            $ids[$id] = 'html-'.substr(hash('sha256',$namespace.':'.$id),0,32);
            $element->setAttribute('id',$ids[$id]);
        }
        foreach ($dom->getElementsByTagName('*') as $element) {
            $href = $element->getAttribute('href');
            if (str_starts_with($href,'#') && isset($ids[substr($href,1)])) $element->setAttribute('href','#'.$ids[substr($href,1)]);
            foreach (['for','aria-labelledby','aria-describedby'] as $attribute) if ($element->hasAttribute($attribute)) {
                $values = explode(' ',$element->getAttribute($attribute));
                $element->setAttribute($attribute,implode(' ',array_map(fn($value)=>$ids[$value]??$value,$values)));
            }
        }
        $result = '';
        foreach ($dom->getElementsByTagName('div')->item(0)->childNodes as $child) $result .= $dom->saveHTML($child);
        return $result;
    }
}
