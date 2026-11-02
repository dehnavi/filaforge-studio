---
title: Quick Start | Create and Render a FilaForge Page
description: Create an RTL or LTR page in the Filament 5 editor, publish a revision, and safely render it from a Laravel route with Blade.
---

# Quick Start

This guide assumes the package is installed and the Filament plugin is registered. Configure page permissions before creating content.

## 1. Create a page

In the selected Filament panel, open **Pages** and create a page. Set its title, slug, content locale, and direction. Persian content usually uses `fa` and `rtl`; English content usually uses `en` and `ltr`. Language and direction are separate values.

## 2. Compose the page

Open the visual editor. Add a section or container, then drag a heading, rich text, button, image, or preset into it. Select an element to edit its properties. Use the responsive preview to check desktop, tablet, and mobile layouts. The canvas is isolated in an iframe so website preview styling stays separate from the Filament admin theme.

## 3. Save and publish

Save the document as a draft, preview it, then publish it when ready. Saves use a document version to reject stale editor state. The published revision is stored separately from the working draft.

## 4. Render the published revision

The package intentionally does not claim your public routes. Resolve and authorize the requested page in your Laravel application, then render its published revision:

```php
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\HtmlRenderer;
use Illuminate\Support\Facades\Route;

Route::get('/{locale}/{slug}', function (string $locale, string $slug) {
    $page = PageLocale::query()
        ->where('locale', $locale)
        ->where('slug', $slug)
        ->firstOrFail();

    // Add the application's tenant, visibility, and access constraints above.
    return app(HtmlRenderer::class)->publishedResponse($page);
});
```

This simple route example is for a single-site public page. Do not copy it unchanged into a multi-tenant or private-content application. Scope lookup to the authorized tenant and implement any password, membership, preview, or access rules in the host application.

`publishedResponse()` reads the exact published revision for that locale. It returns 404 if no publication exists and never falls back to the draft. The response uses private/no-store cache headers. If you need a custom document wrapper or SEO metadata, see [HTML output](html-output.md) and [SEO metadata](seo.md).

## Render inside an existing Blade layout

If the host site already owns the document `<html>`, `<head>`, and `<body>`, render only the FilaForge fragment:

```blade
<main>
    {{ filaforge_html($publishedRevision->document, $pageLocale) }}
</main>
```

The helper emits validated FilaForge markup and scoped rendering styles. It does not execute stored text as Blade or include the editor runtime.

## Next steps

- [Widget and preset catalog](widgets-and-presets.md)
- [Media library and fonts](media-and-fonts.md)
- [Dynamic data sources](allowed-data-sources.md)
- [SEO metadata and page structure](seo.md)
- [Security requirements](security.md)
