---
title: HTML Rendering API | FilaForge Studio
description: Render a validated FilaForge document as an HTML fragment, a complete SEO-ready document, or a Laravel HTTP response.
---

# HTML Rendering API

Laravel package discovery loads the helper functions. FilaForge renders versioned JSON documents through registered Blade widgets; raw HTML strings are not accepted as documents.

## Render a fragment in Blade

Use a fragment when the host website owns its page shell and `<head>`:

```blade
<main>{{ filaforge_html($document, $pageLocale) }}</main>
```

The helper returns an `HtmlString`. Pass only a validated FilaForge document. Do not compile stored content as Blade. The fragment includes rendering styles and required interactions, but not the editor runtime.

## Render a complete HTML document

```php
use FilaForge\Studio\Rendering\HtmlOptions;

$options = new HtmlOptions(
    title: 'Services',
    description: 'Explore our services and request a consultation.',
    canonical: 'https://example.test/en/services',
    stylesheets: ['/css/site.css'],
);

$html = filaforge_document($document, $options);
return filaforge_response($document, $options);
```

The wrapper includes doctype, language, direction, charset, viewport, title, optional description/canonical/stylesheets, and body. Metadata is escaped. Canonical and stylesheet URLs must be HTTP(S) or root-relative; the helper does not fetch them.

## Inject the renderer

```php
use FilaForge\Studio\Rendering\HtmlRenderer;

$renderer = app(HtmlRenderer::class);
$fragment = $renderer->fragment($document, $pageLocale);
$complete = $renderer->document($document, $options, $pageLocale);
$response = $renderer->response($document, $options, $pageLocale);
```

The `FilaForge\Studio\Facades\StudioHtml` facade exposes the fragment and document methods.

## Render a published page

```php
$revisionDocument = $renderer->publishedDocument($pageLocale);
return $renderer->publishedResponse($pageLocale);
```

The published revision must belong to the selected locale. If it does not exist, the renderer returns 404; it never falls back to the draft. The host application must resolve and authorize the page and apply tenant scoping before rendering.

`publishedResponse()` defaults the document title from the page and includes privileged page integrations when configured and authorized. If you pass `HtmlOptions` yourself, page scripts default to off; opt in only after checking the host's policy and CSP.

## Customize the wrapper

Override `resources/views/vendor/filaforge/rendering/document.blade.php`. It receives `$content`, `$options`, `$locale`, `$direction`, and `$scripts`. The host view can add its own layout, metadata, and assets. Do not compile page text as Blade.

## Current boundaries

The package does not automatically create public page routes, generate arbitrary Tailwind classes, construct JSON-LD, build sitemaps, or cache public pages. Inline styles/scripts can require host CSP changes. See [SEO guidance](seo.md), [architecture](architecture.md), and [security](security.md).
