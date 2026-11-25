<?php
namespace FilaForge\Studio\Rendering;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

/**
 * Embed codes pasted by page editors: iframe and external-script snippets from any provider (Aparat, YouTube, Vimeo, ...).
 * The kept parts are rebuilt from an allowlist instead of being filtered out of the original markup, so styles, event
 * handlers and every other element are absent by construction. Scripts keep their position: providers attach them to
 * the holder that precedes them. Nothing here runs in the editor; the canvas draws a placeholder.
 */
final class EmbedCode
{
    public const MAX_LENGTH = 20000;

    /** Elements that keep their attributes and text. Any other element is dropped, and only iframes and scripts are looked for inside it. */
    private const HOLDERS = ['div', 'span'];
    /** Elements whose content is not page content or that load code of their own. They are skipped with everything inside them. */
    private const OPAQUE = ['style', 'title', 'noscript', 'textarea', 'xmp', 'plaintext', 'template', 'svg', 'math', 'object', 'embed', 'applet', 'form', 'select', 'option', 'link', 'meta', 'base', 'frame', 'frameset'];
    private const SCRIPT_TYPES = ['module', 'text/javascript', 'application/javascript'];
    /** Sizing only: no positioning, colors or functions, so a pasted style cannot cover or restyle the page. */
    private const STYLE_PROPERTIES = ['width', 'height', 'min-width', 'min-height', 'max-width', 'max-height', 'aspect-ratio', 'display', 'padding', 'padding-top', 'padding-bottom', 'padding-inline', 'padding-block', 'margin', 'margin-top', 'margin-bottom', 'margin-inline', 'margin-block', 'text-align', 'overflow'];

    /**
     * @param string $defaultTitle Title for iframes that do not declare one
     * @return array{code:string,kind:string,host:string,notices:list<string>} kind is "iframe" or "script"; notices name what changed
     * @throws InvalidArgumentException with a message of "too_large", "scripts_disabled", "iframes_disabled" or "nothing"
     */
    public static function sanitize(string $code, string $defaultTitle = ''): array
    {
        if (strlen($code) > self::MAX_LENGTH) throw new InvalidArgumentException('too_large');
        $code = trim($code);
        if (preg_match('~^(?:https?:)?//~i', $code) === 1) {
            $code = self::open('iframe', ['src' => $code]).'</iframe>';
        }
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$code.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $root = $dom->getElementsByTagName('div')->item(0);
        if ($root === null) throw new InvalidArgumentException('nothing');
        $options = [
            'iframes' => (bool) config('filaforge.embeds.iframes', true),
            'scripts' => (bool) config('filaforge.embeds.scripts', false),
            'inline' => (bool) config('filaforge.embeds.inline_scripts', false),
            'hosts' => array_map(fn ($host) => strtolower(trim((string) $host)), (array) config('filaforge.embeds.allowed_hosts', [])),
            'title' => $defaultTitle,
        ];
        $html = ''; $kind = ''; $host = ''; $notices = [];
        $walk = function (DOMNode $node, bool $content) use (&$walk, &$html, &$kind, &$host, &$notices, $options): void {
            if ($node->nodeType === XML_TEXT_NODE) { if ($content && trim($node->textContent) !== '') $html .= self::escape($node->textContent); return; }
            if (!$node instanceof DOMElement) return;
            $name = strtolower($node->tagName);
            if (in_array($name, self::HOLDERS, true)) {
                $html .= self::open($name, self::holder($node, $notices));
                foreach ($node->childNodes as $child) $walk($child, true);
                $html .= '</'.$name.'>';
            } elseif ($name === 'iframe') {
                if (!$options['iframes']) { $notices['iframes_disabled'] = true; return; }
                $frame = self::frame($node, $options, $host, $notices);
                if ($frame !== null) { $html .= $frame; if ($kind !== 'script') $kind = 'iframe'; }
            } elseif ($name === 'script') {
                if (!$options['scripts']) { $notices['scripts_disabled'] = true; return; }
                $script = self::script($node, $options, $host, $notices);
                if ($script !== null) { $html .= $script; $kind = 'script'; }
            } elseif (in_array($name, self::OPAQUE, true)) $notices['removed'] = true;
            else {
                $notices['removed'] = true;
                foreach ($node->childNodes as $child) $walk($child, false);
            }
        };
        foreach ($root->childNodes as $child) $walk($child, true);
        if ($kind === '') {
            // Say why nothing is left: a disabled embed type is something the editor can act on.
            foreach (['scripts_disabled', 'iframes_disabled'] as $reason) if (isset($notices[$reason])) throw new InvalidArgumentException($reason);
            throw new InvalidArgumentException('nothing');
        }
        if (strlen($html) > self::MAX_LENGTH) throw new InvalidArgumentException('too_large');
        return ['code' => $html, 'kind' => $kind, 'host' => $host, 'notices' => array_keys($notices)];
    }

    /** @return array{html:string,frame:bool}|null null when no part of the code may be shown */
    public static function render(string $code, string $title = ''): ?array
    {
        if (trim($code) === '') return null;
        try { $embed = self::sanitize($code, trim($title) !== '' ? trim($title) : __('filaforge::studio.editor_ui.embed_default_title')); }
        catch (InvalidArgumentException) { return null; }
        // A single iframe fills a frame with a fixed aspect ratio, so it scales with the section on every screen.
        return ['html' => $embed['code'], 'frame' => $embed['kind'] === 'iframe' && substr_count($embed['code'], '<iframe ') === 1];
    }

    private static function holder(DOMElement $element, array &$notices): array
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name); $value = $attribute->value;
            if ($name === 'style') {
                [$style, $dropped] = self::style($value);
                if ($style !== '') $attributes['style'] = $style;
                if ($dropped || $style === '') $notices['removed'] = true;
            } elseif (in_array($name, ['id', 'class', 'title', 'role'], true) && preg_match('/^[^<>&"\x00-\x1f\x7f]{1,200}$/u', $value) === 1) $attributes[$name] = $value;
            elseif (preg_match('/^(data|aria)-[a-z0-9_.-]{1,60}$/D', $name) === 1 && preg_match('/^[^<>&"\x00-\x08\x0b\x0c\x0e-\x1f\x7f]{0,500}$/u', $value) === 1) $attributes[$name] = $value;
            else $notices['removed'] = true;
        }
        return $attributes;
    }

    /** @return array{0:string,1:bool} the kept declarations and whether any was dropped */
    private static function style(string $value): array
    {
        $kept = []; $dropped = false;
        foreach (explode(';', $value) as $declaration) {
            if (trim($declaration) === '') continue;
            $parts = explode(':', $declaration, 2);
            $allowed = count($parts) === 2 && in_array(strtolower(trim($parts[0])), self::STYLE_PROPERTIES, true) && preg_match('/^[a-zA-Z0-9.%+\-*\/ ]{1,60}$/D', trim($parts[1])) === 1;
            if ($allowed) $kept[] = strtolower(trim($parts[0])).':'.trim($parts[1]);
            else $dropped = true;
        }
        return [implode(';', $kept), $dropped];
    }

    private static function frame(DOMElement $element, array $options, string &$host, array &$notices): ?string
    {
        $attributes = []; $addressed = false;
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name); $value = $attribute->value;
            if ($name === 'src') {
                $addressed = true;
                $url = self::url($value, $options['hosts'], $notices);
                if ($url !== null) { $attributes['src'] = $url[0]; if ($host === '') $host = $url[1]; }
            } elseif ($name === 'title' && preg_match('/^[^<>&"\x00-\x1f\x7f]{1,300}$/u', $value) === 1) $attributes['title'] = trim($value);
            elseif ($name === 'allow' && preg_match('/^[a-zA-Z0-9; _.-]{1,300}$/D', $value) === 1) $attributes['allow'] = $value;
            // Full-screen, loading and referrer settings below are fixed for every provider.
            elseif (!in_array($name, ['allowfullscreen', 'loading', 'referrerpolicy'], true)) $notices['removed'] = true;
        }
        // An iframe without a usable address would only show an empty box. A rejected address has already been reported.
        if (!isset($attributes['src'])) { if (!$addressed) $notices['removed'] = true; return null; }
        $title = $attributes['title'] ?? ($options['title'] !== '' ? $options['title'] : null);
        return self::open('iframe', ['src' => $attributes['src']]
            + ($title !== null ? ['title' => $title] : [])
            + (isset($attributes['allow']) ? ['allow' => $attributes['allow']] : [])
            + ['allowfullscreen' => true, 'loading' => 'lazy', 'referrerpolicy' => 'strict-origin-when-cross-origin']).'</iframe>';
    }

    private static function script(DOMElement $element, array $options, string &$host, array &$notices): ?string
    {
        $src = null; $addressed = false; $flags = []; $body = trim($element->textContent);
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name); $value = trim($attribute->value);
            if ($name === 'src') {
                $addressed = true;
                $src = self::url($attribute->value, $options['hosts'], $notices);
                if ($src !== null && $host === '') $host = $src[1];
            } elseif (in_array($name, ['async', 'defer'], true)) $flags[$name] = true;
            elseif ($name === 'type' && strtolower($value) === 'module') $flags['type'] = 'module';
            elseif ($name === 'data-ff-embed') continue;
            elseif ($name !== 'type') $notices['removed'] = true;
            // Types other than JavaScript and modules (JSON data, import maps, templates) are not embeds.
            if ($name === 'type' && !in_array(strtolower($value), self::SCRIPT_TYPES, true)) { $notices['removed'] = true; return null; }
        }
        if ($src === null) {
            if ($body === '') { if (!$addressed) $notices['removed'] = true; return null; }
            // Inline code is JavaScript written by the editor. It stays off until the host turns it on.
            if (!$options['inline'] || preg_match('~</script|<!--~i', $body) === 1) { $notices['inline'] = true; return null; }
            return '<script data-ff-embed="">'.$body.'</script>';
        }
        if ($body !== '') $notices['removed'] = true;
        return self::open('script', ['src' => $src[0]] + $flags + ['data-ff-embed' => '']).'</script>';
    }

    /**
     * @param list<string> $hosts
     * @return array{0:string,1:string}|null the HTTPS address and its host; a rejected address records one notice
     */
    private static function url(string $value, array $hosts, array &$notices): ?array
    {
        if ($value === '' || strlen($value) > 2000 || preg_match('/[\x00-\x20\x7f\\\\<>"\']/', $value) === 1) { $notices['removed'] = true; return null; }
        if (str_starts_with($value, '//')) { $value = 'https:'.$value; $notices['https'] = true; }
        elseif (preg_match('~^http://~i', $value) === 1) { $value = 'https://'.substr($value, 7); $notices['https'] = true; }
        // A host name with at least one dot and no user information; path and query may hold any other visible character.
        if (preg_match('~^https://([a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+)(?::\d{1,5})?(?:[/?#][^\x00-\x20\x7f\\\\<>"\']*)?$~iD', $value, $match) !== 1) { $notices['removed'] = true; return null; }
        $host = strtolower($match[1]);
        if ($hosts === []) return [$value, $host];
        foreach ($hosts as $allowed) if ($host === $allowed || (str_starts_with($allowed, '*.') && str_ends_with($host, substr($allowed, 1)))) return [$value, $host];
        $notices['host'] = true;
        return null;
    }

    private static function open(string $name, array $attributes): string
    {
        $html = '<'.$name;
        foreach ($attributes as $key => $value) $html .= ' '.$key.($value === true ? '' : '="'.self::escape($value).'"');
        return $html.'>';
    }

    private static function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
