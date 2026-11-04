<?php

namespace FilaForge\Studio\Rendering;

use Illuminate\Support\HtmlString;

/** Optional per-response collector; never register as a process-wide singleton. */
final class RenderAssets
{
    private array $tags = [];

    public function capture(string $html): string
    {
        return preg_replace_callback('~<style\b[^>]*>.*?</style>|<script\b[^>]*\bsrc="[^"]+"[^>]*>\s*</script>~si', function (array $match): string {
            $this->tags[hash('sha256', $match[0])] = $match[0];
            return '';
        }, $html);
    }

    public function html(): HtmlString
    {
        return new HtmlString(implode('', $this->tags));
    }
}
