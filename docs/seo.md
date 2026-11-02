---
title: SEO and Page Metadata | FilaForge Studio
description: Render search-friendly titles, descriptions, canonical URLs, semantic headings, localized pages, and clean server-rendered HTML with FilaForge Studio.
---

# SEO and Page Metadata

FilaForge Studio renders semantic HTML from the published page document. It helps page authors build structured content; search visibility still depends on the host website's routing, metadata, performance, accessibility, internal links, and content quality.

## Render complete document metadata

When FilaForge owns the full HTML document, pass SEO fields through HtmlOptions:

    use FilaForge\Studio\Rendering\HtmlOptions;
    use FilaForge\Studio\Rendering\HtmlRenderer;

    $options = new HtmlOptions(
        title: $page->seo_title ?: $page->title,
        description: $page->seo_description,
        canonical: route('pages.show', ['locale' => $page->locale, 'slug' => $page->slug]),
        stylesheets: ['/css/site.css'],
    );

    return app(HtmlRenderer::class)->publishedResponse($page, $options);

SEO fields and slug storage belong to the host application unless its schema adds them. HtmlOptions validates and escapes metadata and accepts only HTTP(S) or root-relative canonical and stylesheet URLs. The helper does not fetch URL content.

publishedResponse($page) supplies the page title and includes enabled page integrations by default. To customize metadata, pass HtmlOptions explicitly; set includePageScripts: true only when the authorized published page should include integrations.

## Render inside your existing layout

If the application owns the document head, render metadata in the host layout and add the FilaForge fragment:

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <main>{{ filaforge_html($revision->document, $pageLocale) }}</main>

Keep metadata values escaped. Add Open Graph tags, social cards, JSON-LD, and site-specific analytics through the host layout and trusted application services. FilaForge does not generate structured data or a sitemap automatically.

## Build readable page structure

- Use one descriptive page-level H1 and meaningful H2/H3 section headings.
- Write useful image alternative text; do not repeat keywords in alt text.
- Use descriptive link labels rather than “click here”.
- Write concise, unique page titles and summaries for each locale.
- Make content useful and specific to its intended visitor.
- Check layout, text wrapping, and accessibility at mobile widths.

## Multilingual and RTL pages

Use a distinct page locale and slug for each translated page. Ensure the HTML language and direction match the actual content. Add canonical and hreflang links in the host layout when you have a complete translation set. Do not claim two pages are translations merely because their language selectors differ.

The package currently supports page language and direction in rendering, but a complete localized slug, hreflang, sitemap, redirect, and SEO management workflow is not implemented. See [Localization](localization.md) and [Current Limitations](limitations.md).

## Search and marketing content

Use the package name and relevant phrases naturally in project pages and documentation: “Laravel visual page builder”, “Filament 5 page builder”, and “RTL page builder”. Keep titles specific, page descriptions accurate, and feature claims tied to tested capabilities. Avoid keyword repetition, fabricated rankings, and unverified compatibility badges.
