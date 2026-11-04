<?php

namespace FilaForge\Studio\Rendering;

use FilaForge\Studio\Models\PageLocale;
use Illuminate\Http\Response;
use Illuminate\Support\HtmlString;

class HtmlRenderer
{
    public function __construct(private BladeRenderer $renderer) {}

    /** Render a validated document for embedding inside a host Blade layout. */
    public function fragment(array $document, ?PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): HtmlString
    {
        return new HtmlString($this->renderer->render($document, $page, $context));
    }

    /** Render a complete HTML document using the package or host-overridden view. */
    public function document(array $document, ?HtmlOptions $options = null, ?PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): HtmlString
    {
        $options ??= new HtmlOptions;
        $scripts=app(\FilaForge\Studio\Integrations\PageIntegrations::class)->markup($document,$options);
        $content = $this->fragment($document, $page, $context);

        return new HtmlString(view('filaforge::rendering.document', [
            'content' => $content,
            'scripts' => $scripts,
            'options' => $options ?? new HtmlOptions,
            'locale' => $document['locale'],
            'direction' => $document['direction'],
        ])->render());
    }

    public function response(array $document, ?HtmlOptions $options = null, ?PageLocale $page = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): Response
    {
        return new Response($this->document($document, $options, $page, $context)->toHtml(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
        ]);
    }

    /** Fetch only the publication belonging to this locale; never fall back to draft. */
    public function publishedDocument(PageLocale $page): array
    {
        abort_if($page->published_revision_id === null, 404);
        return $page->revisions()->whereKey($page->published_revision_id)->firstOrFail()->document;
    }

    public function publishedResponse(PageLocale $page, ?HtmlOptions $options = null, ?\FilaForge\Studio\DynamicData\DataContext $context = null): Response
    {
        return $this->response($this->publishedDocument($page), $options ?? new HtmlOptions(title:$page->title,includePageScripts:true), $page, $context);
    }
    /** Embed an authorized, host-resolved publication anywhere; each instance has isolated IDs. */
    public function publishedFragment(PageLocale $page, ?\FilaForge\Studio\DynamicData\DataContext $context = null, ?string $instance = null, ?RenderAssets $assets = null): HtmlString
    {
        $html = $this->renderer->render($this->publishedDocument($page), $page, $context, $instance ?? 'ff-'.bin2hex(random_bytes(8)));
        return new HtmlString($assets ? $assets->capture($html) : $html);
    }

}
