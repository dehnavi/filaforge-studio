<?php

namespace FilaForge\Studio\Rendering;

use Symfony\Component\HtmlSanitizer\{HtmlSanitizer, HtmlSanitizerConfig};
use Symfony\Component\HtmlSanitizer\Parser\{MastermindsParser, NativeParser};

final class SafeHtml
{
    public static function cleanBlock(string $html, ?string $namespace = null): string
    {
        if (!config('filaforge.embeds.scripts', false) || stripos($html, '<script') === false) {
            return self::clean($html, $namespace);
        }

        $parser = PHP_VERSION_ID < 80400 ? new MastermindsParser : new NativeParser;
        $root = $parser->parse($html);
        if ($root === null) return '';

        $scripts = [];
        $prefix = 'ff-script-'.bin2hex(random_bytes(16)).'-';
        self::markScripts($root, $prefix, $scripts);
        if ($scripts === []) return self::clean($html, $namespace);

        $document = $root->ownerDocument;
        $safe = self::clean($document->saveHTML($root));
        $rendered = strtr($safe, $scripts);

        // Provider scripts refer to the original IDs of their holders.
        return $rendered !== $safe ? $rendered : self::clean($safe, $namespace);
    }

    private static function markScripts(\DOMNode|\Dom\Node $root, string $prefix, array &$scripts): void
    {
        foreach (iterator_to_array($root->childNodes) as $node) {
            if (!$node instanceof \DOMElement && !$node instanceof \Dom\Element) continue;
            if ($node->namespaceURI !== 'http://www.w3.org/1999/xhtml') continue;
            if ($node->localName !== 'script') {
                self::markScripts($node, $prefix, $scripts);
                continue;
            }

            $script = EmbedCode::htmlBlockScript($node);
            if ($script === null) {
                $root->removeChild($node);
                continue;
            }

            $marker = $node->ownerDocument->createElement('span', $prefix.count($scripts));
            $scripts['<span>'.$marker->textContent.'</span>'] = $script;
            $root->replaceChild($marker, $node);
        }
    }

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
