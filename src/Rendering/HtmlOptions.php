<?php

namespace FilaForge\Studio\Rendering;

use InvalidArgumentException;

final readonly class HtmlOptions
{
    /** @param list<string> $stylesheets */
    public function __construct(
        public string $title = '',
        public ?string $description = null,
        public ?string $canonical = null,
        public array $stylesheets = [],
        public bool $includePageScripts = false,
        public ?string $scriptNonce = null,
    ) {
        if($scriptNonce!==null&&!preg_match('/^[A-Za-z0-9+\/_=-]{8,256}$/D',$scriptNonce))throw new InvalidArgumentException('Invalid CSP nonce.');
        if ($canonical !== null) self::assertUrl($canonical);
        if (!array_is_list($stylesheets)) throw new InvalidArgumentException('Stylesheets must be a list.');
        foreach ($stylesheets as $url) {
            if (!is_string($url)) throw new InvalidArgumentException('Invalid stylesheet URL.');
            self::assertUrl($url);
        }
    }

    private static function assertUrl(string $url): void
    {
        $relative = str_starts_with($url, '/') && !str_starts_with($url, '//');
        $absolute = filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
        if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url) || (!$relative && !$absolute)) {
            throw new InvalidArgumentException('Expected an HTTP(S) or root-relative URL.');
        }
    }
}
