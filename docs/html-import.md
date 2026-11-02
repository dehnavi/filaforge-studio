---
title: Import HTML into FilaForge Studio
description: Convert supported HTML fragments into editable FilaForge widgets while preserving existing page content and filtering unsafe or unsupported markup.
---

# Import HTML

The editor's HTML import dialog accepts an HTML fragment or complete document and converts supported markup into regular FilaForge document nodes. It appends the result to the selected container or its parent; existing page content is preserved. Review warnings and preview the result before saving.

## Supported content

The importer recognizes headings, paragraphs, sanitized rich text, links, images, video/source elements, div/section-style containers, dividers, a limited grid/flex class set, and selected inline dimensions, colors, and layout properties.

It does not reproduce arbitrary CSS frameworks or external stylesheets. Scripts, forms, iframe/embed, SVG, and executable content are not imported. Tables and complex hand-built layouts may need manual reconstruction.

## Security and bounds

Input is limited to 256 KiB, traversal depth 24, and 2,000 visited nodes, followed by the document validator's own limits. The importer does not fetch remote resources, perform entity substitution, execute Blade, or persist data by itself. The editor action applies the current user's page update authorization before save.

## Programmatic use

```php
$result = app(\FilaForge\Studio\Importing\HtmlImporter::class)
    ->import($html, locale: 'fa', direction: 'rtl');

$document = $result['document'];
$warningKeys = $result['warnings'];
```

Callers outside the provided editor must validate authorization, persistence, and output themselves. Warnings are translation-key suffixes from the package translation namespace.

## Import media by URL

To reference an existing remote or site-relative image/video URL, use the media library's URL option instead of expecting the importer to fetch the file. The host browser loads the URL directly; review licensing, privacy, CSP, CORS, and video codec support. See [media and fonts](media-and-fonts.md).
